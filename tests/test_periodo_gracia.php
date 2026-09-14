<?php
if (PHP_SAPI !== 'cli') {
    exit('Ejecutar solo por CLI.');
}

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';
require_once __DIR__ . '/../modelos/Torneo.php';

$conexion = (new Conexion())->conectar();
$modelo = new Enfrentamiento($conexion);

echo "========================================================\n";
echo "EJECUTANDO TESTS DE PERIODO DE GRACIA\n";
echo "========================================================\n\n";

$errores = 0;
$aciertos = 0;

function afirmar(bool $condicion, string $descripcion) {
    global $aciertos, $errores;
    if ($condicion) {
        echo " [OK] " . $descripcion . "\n";
        $aciertos++;
    } else {
        echo " [FAIL] " . $descripcion . "\n";
        $errores++;
    }
}

afirmar(
    Enfrentamiento::DURACION_ESTIMADA_PARTIDO === 90 && Enfrentamiento::DURACION_ESTIMADA_PARTIDO_MINUTOS === 90,
    "La duración estimada central del enfrentamiento es de 90 minutos."
);

$conexion->beginTransaction();

try {
    $org = $conexion->query("SELECT id_usuario FROM usuarios WHERE estado = 'activo' LIMIT 1")->fetchColumn();
    $disc = $conexion->query("SELECT id_disciplina FROM disciplinas LIMIT 1")->fetchColumn();
    $cat = $conexion->query("SELECT id_categoria FROM categorias LIMIT 1")->fetchColumn();
    $qLiga = $conexion->query("SELECT id_tipo_torneo FROM tipos_torneo WHERE nombre LIKE '%liga%' OR nombre LIKE '%todos%' LIMIT 1");
    $idTipoLiga = $qLiga->fetchColumn() ?: 1;
    $qElim = $conexion->query("SELECT id_tipo_torneo FROM tipos_torneo WHERE nombre LIKE '%elimin%' LIMIT 1");
    $idTipoElim = $qElim->fetchColumn() ?: 2;

    $modeloTorneo = new Torneo($conexion);
    $idTorneoLiga = $modeloTorneo->crear(
        'Torneo Test Liga Gracia',
        (int) $disc,
        (int) $cat,
        (int) $idTipoLiga,
        (int) $org,
        'individual',
        date('Y-m-d'),
        '08:00:00',
        date('Y-m-d', strtotime('+7 days')),
        'en_curso',
        16,
        true,
        60
    );

    $idTorneoElim = $modeloTorneo->crear(
        'Torneo Test Elim Gracia',
        (int) $disc,
        (int) $cat,
        (int) $idTipoElim,
        (int) $org,
        'individual',
        date('Y-m-d'),
        '08:00:00',
        date('Y-m-d', strtotime('+7 days')),
        'en_curso',
        16,
        true,
        60
    );

    $users = $conexion->query("SELECT id_usuario FROM usuarios LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
    $u1 = (int) ($users[0] ?? 1);
    $u2 = (int) ($users[1] ?? 2);
    $ahora = time();

    $inicio30m = date('Y-m-d H:i:s', $ahora - (30 * 60));
    $ins = $conexion->prepare("INSERT INTO enfrentamientos (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante, id_usuario_a, id_usuario_b, estado, fecha_hora) VALUES (:t, 1, :orden, 'Ronda 1', 'individual', :u1, :u2, 'en_curso', :fh)");
    $ins->execute([':t' => $idTorneoLiga, ':orden' => 1, ':u1' => $u1, ':u2' => $u2, ':fh' => $inicio30m]);
    $idPartidoJugando = (int) $conexion->lastInsertId();

    $partidoJugando = $modelo->obtenerPorId($idPartidoJugando);
    afirmar($partidoJugando['en_gracia'] === false, "A los 30 minutos el enfrentamiento todavía no está en gracia.");
    afirmar($partidoJugando['gracia_vencida'] === false, "A los 30 minutos el plazo de gracia no está vencido.");
    afirmar($partidoJugando['nivel_alerta'] === 'amarillo', "A los 30 minutos el nivel de alerta corresponde a un encuentro en juego.");

    $inicio110m = date('Y-m-d H:i:s', $ahora - (110 * 60));
    $ins->execute([':t' => $idTorneoLiga, ':orden' => 2, ':u1' => $u1, ':u2' => $u2, ':fh' => $inicio110m]);
    $idPartidoEnGracia = (int) $conexion->lastInsertId();

    $partidoEnGracia = $modelo->obtenerPorId($idPartidoEnGracia);
    afirmar($partidoEnGracia['en_gracia'] === true, "A los 110 minutos el enfrentamiento está dentro del período de gracia.");
    afirmar($partidoEnGracia['gracia_vencida'] === false, "A los 110 minutos el período de gracia todavía no venció.");
    afirmar($partidoEnGracia['nivel_alerta'] === 'naranja', "Durante el período de gracia el nivel de alerta es naranja.");

    $modelo->procesarPeriodosGracia($idTorneoLiga);
    $partidoEnGraciaActualizado = $modelo->obtenerPorId($idPartidoEnGracia);
    afirmar($partidoEnGraciaActualizado['estado'] === 'en_periodo_gracia', "El proceso cambia el estado a en_periodo_gracia cuando corresponde.");

    $inicio180m = date('Y-m-d H:i:s', $ahora - (180 * 60));
    $insScore = $conexion->prepare("INSERT INTO enfrentamientos (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante, id_usuario_a, id_usuario_b, puntaje_a, puntaje_b, estado, fecha_hora) VALUES (:t, 1, :orden, 'Ronda 1', 'individual', :u1, :u2, :pa, :pb, 'en_periodo_gracia', :fh)");
    $insScore->execute([':t' => $idTorneoLiga, ':orden' => 3, ':u1' => $u1, ':u2' => $u2, ':pa' => 3, ':pb' => 1, ':fh' => $inicio180m]);
    $idMarcadorPendiente = (int) $conexion->lastInsertId();

    $modelo->procesarPeriodosGracia($idTorneoLiga);
    $partidoMarcadorPendiente = $modelo->obtenerPorId($idMarcadorPendiente);
    afirmar($partidoMarcadorPendiente['estado'] === 'pendiente_revision', "Con gracia vencida y marcador 3-1, el sistema no decide el resultado y lo envía a revisión.");
    afirmar((int) $partidoMarcadorPendiente['puntaje_a'] === 3 && (int) $partidoMarcadorPendiente['puntaje_b'] === 1, "El marcador pendiente se conserva para la revisión manual.");
    afirmar($partidoMarcadorPendiente['ganador'] === null, "No se declara ganador mientras el resultado no haya sido confirmado manualmente.");

    $insScore->execute([':t' => $idTorneoLiga, ':orden' => 4, ':u1' => $u1, ':u2' => $u2, ':pa' => 1, ':pb' => 1, ':fh' => $inicio180m]);
    $idEmpateLiga = (int) $conexion->lastInsertId();
    $modelo->procesarPeriodosGracia($idTorneoLiga);
    $partidoEmpateLiga = $modelo->obtenerPorId($idEmpateLiga);
    afirmar($partidoEmpateLiga['estado'] === 'pendiente_revision', "Un empate de Liga no se confirma automáticamente al vencer la gracia.");

    $insScoreElim = $conexion->prepare("INSERT INTO enfrentamientos (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante, id_usuario_a, id_usuario_b, puntaje_a, puntaje_b, estado, fecha_hora) VALUES (:t, 1, :orden, 'Ronda 1', 'individual', :u1, :u2, 1, 1, 'en_periodo_gracia', :fh)");
    $insScoreElim->execute([':t' => $idTorneoElim, ':orden' => 1, ':u1' => $u1, ':u2' => $u2, ':fh' => $inicio180m]);
    $idEmpateElim = (int) $conexion->lastInsertId();
    $modelo->procesarPeriodosGracia($idTorneoElim);
    $partidoEmpateElim = $modelo->obtenerPorId($idEmpateElim);
    afirmar($partidoEmpateElim['estado'] === 'pendiente_revision', "Un empate de Eliminación Directa se envía a revisión manual.");

    $insNull = $conexion->prepare("INSERT INTO enfrentamientos (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante, id_usuario_a, id_usuario_b, estado, fecha_hora) VALUES (:t, 1, :orden, 'Ronda 1', 'individual', :u1, :u2, 'en_periodo_gracia', :fh)");
    $insNull->execute([':t' => $idTorneoElim, ':orden' => 2, ':u1' => $u1, ':u2' => $u2, ':fh' => $inicio180m]);
    $idSinResultado = (int) $conexion->lastInsertId();
    $modelo->procesarPeriodosGracia($idTorneoElim);
    $partidoSinResultado = $modelo->obtenerPorId($idSinResultado);
    afirmar($partidoSinResultado['estado'] === 'pendiente_revision', "Sin resultado al vencer la gracia, el enfrentamiento pasa a revisión manual.");

    $qAud = $conexion->prepare("SELECT accion, detalle FROM auditoria WHERE entidad = 'enfrentamiento' AND id_entidad = :id AND id_usuario IS NULL ORDER BY id_auditoria DESC LIMIT 1");
    $qAud->execute([':id' => (string) $idMarcadorPendiente]);
    $aud = $qAud->fetch(PDO::FETCH_ASSOC);
    afirmar(!empty($aud) && ($aud['accion'] ?? '') === 'PERIODO_GRACIA_VENCIDO', "La auditoría registra el vencimiento del período de gracia con id_usuario = NULL.");
} finally {
    $conexion->rollBack();
}

echo "\n========================================================\n";
echo "RESULTADO FINAL: {$aciertos} pruebas superadas, {$errores} fallos.\n";
echo "========================================================\n";

exit($errores === 0 ? 0 : 1);
