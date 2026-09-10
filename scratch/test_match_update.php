<?php
putenv('ARENA_DB_PASS=Maracaibo24158$');
require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';
$db = (new Conexion())->conectar();
$m = new Enfrentamiento($db);
$match = $m->obtenerPorId(51);
print_r($match);

try {
    $m->actualizarEnfrentamiento(51, '2026-12-01 10:00:00', 'finalizado', 2, 2);
    echo "Actualizado con exito!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
