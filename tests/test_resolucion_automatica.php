<?php
// Test automatizado para Período de Gracia + Resolución Automática + Duración Fija de 90 minutos
if (PHP_SAPI !== 'cli') {
    exit('Ejecutar solo por CLI.');
}

if (!getenv('ARENA_DB_PASS')) {
    putenv('ARENA_DB_PASS=Maracaibo24158$');
}

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';
require_once __DIR__ . '/../modelos/Torneo.php';

$conexion = (new Conexion())->conectar();
$modelo = new Enfrentamiento($conexion);

echo "========================================================\n";
echo "EJECUTANDO TESTS DE RESOLUCION AUTOMATICA Y PERIODO DE GRACIA\n";
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

// 1. Verificar constante central
afirmar(
    Enfrentamiento::DURACION_ESTIMADA_PARTIDO === 90 && Enfrentamiento::DURACION_ESTIMADA_PARTIDO_MINUTOS === 90,
    "La constante central Enfrentamiento::DURACION_ESTIMADA_PARTIDO es exactamente 90 minutos."
);

// 2. Probar cálculos en transacción aislada
$conexion->beginTransaction();

try {
    // Obtener un organizador y disciplina existentes
    $org = $conexion->query("SELECT id_usuario FROM usuarios WHERE estado = 'activo' LIMIT 1")->fetchColumn();
    $disc = $conexion->query("SELECT id_disciplina FROM disciplinas LIMIT 1")->fetchColumn();
    $cat = $conexion->query("SELECT id_categoria FROM categorias LIMIT 1")->fetchColumn();
    
    // Obtener tipo liga y tipo eliminacion
    $qLiga = $conexion->query("SELECT id_tipo_torneo FROM tipos_torneo WHERE nombre LIKE '%liga%' OR nombre LIKE '%todos%' LIMIT 1");
    $idTipoLiga = $qLiga->fetchColumn() ?: 1;

    $qElim = $conexion->query("SELECT id_tipo_torneo FROM tipos_torneo WHERE nombre LIKE '%elimin%' LIMIT 1");
    $idTipoElim = $qElim->fetchColumn() ?: 2;

    // Crear 2 torneos de prueba: uno liga y uno eliminación
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
        60 // 60 min de gracia
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
        60 // 60 min de gracia
    );

    // Obtener dos usuarios para los partidos
    $users = $conexion->query("SELECT id_usuario FROM usuarios LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
    $u1 = (int) ($users[0] ?? 1);
    $u2 = (int) ($users[1] ?? 2);

    $ahora = time();

    // TEST 3: Partido recién comenzado hace 30 minutos (Dentro de los 90 min de juego)
    $inicio30m = date('Y-m-d H:i:s', $ahora - (30 * 60));
    $ins = $conexion->prepare("INSERT INTO enfrentamientos (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante, id_usuario_a, id_usuario_b, estado, fecha_hora) VALUES (:t, 1, :orden, 'Ronda 1', 'individual', :u1, :u2, 'en_curso', :fh)");
    $ins->execute([':t' => $idTorneoLiga, ':orden' => 1, ':u1' => $u1, ':u2' => $u2, ':fh' => $inicio30m]);
    $idPartidoJugando = (int) $conexion->lastInsertId();

    $partidoJugando = $modelo->obtenerPorId($idPartidoJugando);
    afirmar($partidoJugando['en_gracia'] === false, "Partido a los 30 min de juego: NO está en gracia aún.");
    afirmar($partidoJugando['gracia_vencida'] === false, "Partido a los 30 min de juego: NO está vencido.");
    afirmar($partidoJugando['nivel_alerta'] === 'amarillo', "Partido a los 30 min de juego: nivel_alerta es amarillo (en juego).");

    // TEST 4: Partido cuyo tiempo de juego concluyó hace poco (110 min desde inicio -> dentro de la gracia)
    $inicio110m = date('Y-m-d H:i:s', $ahora - (110 * 60));
    $ins->execute([':t' => $idTorneoLiga, ':orden' => 2, ':u1' => $u1, ':u2' => $u2, ':fh' => $inicio110m]);
    $idPartidoEnGracia = (int) $conexion->lastInsertId();

    $partidoEnGracia = $modelo->obtenerPorId($idPartidoEnGracia);
    afirmar($partidoEnGracia['en_gracia'] === true, "Partido a los 110 min (concluyó 90 min de juego): SÍ está en gracia.");
    afirmar($partidoEnGracia['gracia_vencida'] === false, "Partido a los 110 min: gracia aún NO venció.");
    afirmar($partidoEnGracia['nivel_alerta'] === 'naranja', "Partido a los 110 min: nivel_alerta es naranja.");

    // Ejecutar procesarResolucionAutomatica() para verificar transición a 'en_periodo_gracia'
    $rep1 = $modelo->procesarResolucionAutomatica($idTorneoLiga);
    $partidoEnGraciaActualizado = $modelo->obtenerPorId($idPartidoEnGracia);
    afirmar($partidoEnGraciaActualizado['estado'] === 'en_periodo_gracia', "El cron pasa automáticamente el enfrentamiento a estado 'en_periodo_gracia'.");

    // TEST 5: Partido con gracia vencida (hace 180 min -> 90 min juego + 60 min gracia = 150 min vencimiento)
    // Caso con resultado claro (3 a 1)
    $inicio180m = date('Y-m-d H:i:s', $ahora - (180 * 60));
    $insScore = $conexion->prepare("INSERT INTO enfrentamientos (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante, id_usuario_a, id_usuario_b, puntaje_a, puntaje_b, estado, fecha_hora) VALUES (:t, 1, :orden, 'Ronda 1', 'individual', :u1, :u2, :pa, :pb, 'en_periodo_gracia', :fh)");
    $insScore->execute([':t' => $idTorneoLiga, ':orden' => 3, ':u1' => $u1, ':u2' => $u2, ':pa' => 3, ':pb' => 1, ':fh' => $inicio180m]);
    $idGanadorClaro = (int) $conexion->lastInsertId();

    $rep2 = $modelo->procesarResolucionAutomatica($idTorneoLiga);
    $partidoResuelto = $modelo->obtenerPorId($idGanadorClaro);
    afirmar($partidoResuelto['estado'] === 'finalizado', "Partido con gracia vencida y 3-1: finaliza automáticamente.");
    afirmar($partidoResuelto['ganador'] === $partidoResuelto['participante_a'], "Declara ganador objetivo al participante A (3 > 1).");

    // TEST 6: Partido con gracia vencida y empate en LIGA (1 a 1) -> Se finaliza como empate
    $insScore->execute([':t' => $idTorneoLiga, ':orden' => 4, ':u1' => $u1, ':u2' => $u2, ':pa' => 1, ':pb' => 1, ':fh' => $inicio180m]);
    $idEmpateLiga = (int) $conexion->lastInsertId();

    $modelo->procesarResolucionAutomatica($idTorneoLiga);
    $partidoEmpateLiga = $modelo->obtenerPorId($idEmpateLiga);
    afirmar($partidoEmpateLiga['estado'] === 'finalizado', "Empate 1-1 en torneo tipo Liga: se finaliza automáticamente.");
    afirmar($partidoEmpateLiga['ganador'] === 'Empate', "El resultado queda asentado como Empate.");

    // TEST 7: Partido con gracia vencida y empate en ELIMINACION DIRECTA (1 a 1) -> Requiere revisión
    $insScoreElim = $conexion->prepare("INSERT INTO enfrentamientos (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante, id_usuario_a, id_usuario_b, puntaje_a, puntaje_b, estado, fecha_hora) VALUES (:t, 1, :orden, 'Ronda 1', 'individual', :u1, :u2, 1, 1, 'en_periodo_gracia', :fh)");
    $insScoreElim->execute([':t' => $idTorneoElim, ':orden' => 1, ':u1' => $u1, ':u2' => $u2, ':fh' => $inicio180m]);
    $idEmpateElim = (int) $conexion->lastInsertId();

    $modelo->procesarResolucionAutomatica($idTorneoElim);
    $partidoEmpateElim = $modelo->obtenerPorId($idEmpateElim);
    afirmar($partidoEmpateElim['estado'] === 'pendiente_revision', "Empate 1-1 en Eliminación Directa: NO inventa ganador, pasa a 'pendiente_revision'.");

    // TEST 8: Partido con gracia vencida SIN resultado cargado (null - null) -> Requiere revisión
    $insNull = $conexion->prepare("INSERT INTO enfrentamientos (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante, id_usuario_a, id_usuario_b, estado, fecha_hora) VALUES (:t, 1, :orden, 'Ronda 1', 'individual', :u1, :u2, 'en_periodo_gracia', :fh)");
    $insNull->execute([':t' => $idTorneoElim, ':orden' => 2, ':u1' => $u1, ':u2' => $u2, ':fh' => $inicio180m]);
    $idSinResultado = (int) $conexion->lastInsertId();

    $modelo->procesarResolucionAutomatica($idTorneoElim);
    $partidoSinRes = $modelo->obtenerPorId($idSinResultado);
    afirmar($partidoSinRes['estado'] === 'pendiente_revision', "Sin resultado al vencer gracia: pasa a 'pendiente_revision'.");

    // TEST 9: Auditoría registrada correctamente con id_usuario = NULL para acciones automáticas
    $qAud = $conexion->prepare("SELECT accion, detalle FROM auditoria WHERE entidad = 'enfrentamiento' AND id_entidad = :id AND id_usuario IS NULL ORDER BY id_auditoria DESC LIMIT 1");
    $qAud->execute([':id' => (string) $idGanadorClaro]);
    $aud = $qAud->fetch(PDO::FETCH_ASSOC);
    afirmar(!empty($aud), "La auditoría registra la resolución automática con id_usuario = NULL.");

} finally {
    // Revertir todo para no dejar registros de prueba en la base de datos
    $conexion->rollBack();
}

echo "\n========================================================\n";
echo "RESULTADO FINAL: {$aciertos} pruebas superadas, {$errores} fallos.\n";
echo "========================================================\n";

exit($errores === 0 ? 0 : 1);
