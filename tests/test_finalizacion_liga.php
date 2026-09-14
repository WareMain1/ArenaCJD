<?php

if (PHP_SAPI !== 'cli') {
    exit('Ejecutar solo por CLI.');
}

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';
require_once __DIR__ . '/../modelos/Torneo.php';

$conexion = (new Conexion())->conectar();
$conexion->beginTransaction();

try {
    $usuarios = $conexion->query("SELECT id_usuario FROM usuarios WHERE estado = 'activo' ORDER BY id_usuario LIMIT 2")
        ->fetchAll(PDO::FETCH_COLUMN);
    if (count($usuarios) < 2) {
        throw new RuntimeException('La prueba necesita dos usuarios activos existentes.');
    }

    $torneos = new Torneo($conexion);
    $idTorneo = $torneos->crear(
        'TEST_QA_FINALIZACION_LIGA',
        3,
        1,
        3,
        (int) $usuarios[0],
        'individual',
        date('Y-m-d', strtotime('+1 day')),
        '10:00:00',
        date('Y-m-d', strtotime('+2 days')),
        'en_curso',
        2,
        false,
        60
    );

    $insertar = $conexion->prepare(
        "INSERT INTO enfrentamientos
         (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante,
          id_usuario_a, id_usuario_b, puntaje_a, puntaje_b, estado, fecha_hora)
         VALUES (:torneo, 1, 1, 'Fecha 1', 'individual', :a, :b, 2, 1, 'finalizado', NOW())"
    );
    $insertar->execute([
        ':torneo' => $idTorneo,
        ':a' => (int) $usuarios[0],
        ':b' => (int) $usuarios[1]
    ]);

    set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): never {
        throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
    });
    try {
        (new Enfrentamiento($conexion))->crearSiguienteRondaSiCorresponde($idTorneo);
    } finally {
        restore_error_handler();
    }

    $estado = $conexion->query("SELECT estado FROM torneos WHERE id_torneo = {$idTorneo}")->fetchColumn();
    $detalle = $conexion->query(
        "SELECT detalle FROM auditoria WHERE entidad = 'torneo' AND id_entidad = '{$idTorneo}' ORDER BY id_auditoria DESC LIMIT 1"
    )->fetchColumn();

    if ($estado !== 'finalizado' || !str_contains((string) $detalle, 'TEST_QA_FINALIZACION_LIGA')) {
        throw new RuntimeException('La liga no se finalizó o su auditoría perdió el nombre del torneo.');
    }

    echo "1 caso de finalización automática de Liga correcto.\n";
} finally {
    $conexion->rollBack();
}
