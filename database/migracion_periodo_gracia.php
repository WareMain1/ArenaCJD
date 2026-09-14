<?php
require_once __DIR__ . '/../config/Conexion.php';

function arenaValorEntorno(string $nombre, string $predeterminado = ''): string
{
    $valor = $_ENV[$nombre] ?? $_SERVER[$nombre] ?? getenv($nombre);
    if ($valor === false || $valor === null || trim((string) $valor) === '') {
        return $predeterminado;
    }
    return (string) $valor;
}

try {
    $adminUsuario = arenaValorEntorno('ARENA_DB_ADMIN_USER');

    if ($adminUsuario !== '') {
        $host = arenaValorEntorno('ARENA_DB_HOST', 'localhost');
        $puerto = arenaValorEntorno('ARENA_DB_PORT', '3306');
        $baseDatos = arenaValorEntorno('ARENA_DB_NAME', 'arenacjd');
        $adminPass = arenaValorEntorno('ARENA_DB_ADMIN_PASS');

        $conexion = new PDO(
            "mysql:host={$host};port={$puerto};dbname={$baseDatos};charset=utf8mb4",
            $adminUsuario,
            $adminPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } else {
        $conexion = (new Conexion())->conectar();
    }

    echo "Conexión exitosa a la base de datos.\n";

    $colsTorneo = $conexion->query('SHOW COLUMNS FROM torneos')->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('periodo_gracia_resultado', $colsTorneo, true)) {
        $conexion->exec("ALTER TABLE torneos ADD COLUMN `periodo_gracia_resultado` INT(10) UNSIGNED NOT NULL DEFAULT 60 AFTER `cupo_maximo`");
        echo "Columna periodo_gracia_resultado agregada a torneos.\n";
    } else {
        echo "Columna periodo_gracia_resultado ya existe en torneos.\n";
    }

    $conexion->exec("ALTER TABLE enfrentamientos MODIFY COLUMN `estado` ENUM('pendiente','programado','en_curso','en_periodo_gracia','pendiente_revision','finalizado','cancelado') NOT NULL DEFAULT 'pendiente'");
    echo "Enum estado actualizado en enfrentamientos.\n";

    echo "Migración simplificada completada con éxito.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error durante la migración: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Si la base es nueva, importe database/arenacjd.sql. Si está actualizando una base antigua, ejecute esta migración con un usuario MySQL que tenga permiso ALTER (ARENA_DB_ADMIN_USER / ARENA_DB_ADMIN_PASS).\n");
    exit(1);
}
