<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso no permitido.');
}

require_once __DIR__ . '/../config/Conexion.php';

try {
    $conexion = (new Conexion())->conectar();
    $duplicados = (int) $conexion->query(
        "SELECT COUNT(*) FROM (
            SELECT id_torneo, tipo, CASE WHEN tipo = 'equipo' THEN id_equipo ELSE id_invitado END AS objetivo
            FROM invitaciones_torneo
            GROUP BY id_torneo, tipo, objetivo
            HAVING COUNT(*) > 1
        ) duplicados"
    )->fetchColumn();
    if ($duplicados > 0) {
        throw new RuntimeException('Hay invitaciones duplicadas. Revísalas antes de aplicar la migración.');
    }

    $columnas = $conexion->query("SHOW COLUMNS FROM invitaciones_torneo")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('objetivo_individual', $columnas, true)) {
        $conexion->exec("ALTER TABLE invitaciones_torneo ADD COLUMN objetivo_individual INT UNSIGNED GENERATED ALWAYS AS (CASE WHEN tipo = 'individual' THEN id_invitado ELSE NULL END) STORED AFTER id_equipo");
    }
    if (!in_array('objetivo_equipo', $columnas, true)) {
        $conexion->exec("ALTER TABLE invitaciones_torneo ADD COLUMN objetivo_equipo INT UNSIGNED GENERATED ALWAYS AS (CASE WHEN tipo = 'equipo' THEN id_equipo ELSE NULL END) STORED AFTER objetivo_individual");
    }

    $indices = $conexion->query("SHOW INDEX FROM invitaciones_torneo")->fetchAll(PDO::FETCH_ASSOC);
    $nombres = array_column($indices, 'Key_name');
    if (!in_array('uq_invitaciones_objetivo_individual', $nombres, true)) {
        $conexion->exec('ALTER TABLE invitaciones_torneo ADD UNIQUE KEY uq_invitaciones_objetivo_individual (id_torneo, objetivo_individual)');
    }
    if (!in_array('uq_invitaciones_objetivo_equipo', $nombres, true)) {
        $conexion->exec('ALTER TABLE invitaciones_torneo ADD UNIQUE KEY uq_invitaciones_objetivo_equipo (id_torneo, objetivo_equipo)');
    }

    echo "Migración de unicidad de invitaciones aplicada correctamente.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'No se pudo aplicar la migración: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
