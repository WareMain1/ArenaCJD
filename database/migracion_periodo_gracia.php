<?php
if (!getenv('ARENA_DB_PASS')) {
    putenv('ARENA_DB_PASS=Maracaibo24158$');
}
require_once __DIR__ . '/../config/Conexion.php';

try {
    // Intentar conectar con usuario root para permisos de ALTER TABLE en XAMPP, o usar conexión estándar
    try {
        $conexion = new PDO("mysql:host=localhost;dbname=arenacjd;charset=utf8mb4", "root", "");
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Throwable) {
        $conexion = (new Conexion())->conectar();
    }
    echo "Conexión exitosa a la base de datos.\n";

    // 1. Columna periodo_gracia_resultado en tabla torneos
    $colsTorneo = $conexion->query("SHOW COLUMNS FROM torneos")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('periodo_gracia_resultado', $colsTorneo, true)) {
        $conexion->exec("ALTER TABLE torneos ADD COLUMN `periodo_gracia_resultado` INT(10) UNSIGNED NOT NULL DEFAULT 60 AFTER `cupo_maximo`");
        echo "Columna periodo_gracia_resultado agregada a torneos.\n";
    } else {
        echo "Columna periodo_gracia_resultado ya existe en torneos.\n";
    }

    // 2. Modificar enum de estado en enfrentamientos
    $conexion->exec("ALTER TABLE enfrentamientos MODIFY COLUMN `estado` ENUM('pendiente','programado','en_curso','en_periodo_gracia','pendiente_revision','finalizado','cancelado') NOT NULL DEFAULT 'pendiente'");
    echo "Enum estado actualizado en enfrentamientos.\n";

    echo "Migración simplificada completada con éxito.\n";
} catch (Throwable $e) {
    echo "Error durante la migración: " . $e->getMessage() . "\n";
    exit(1);
}
