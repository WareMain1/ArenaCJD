<?php

require_once __DIR__ . '/_comun.php';

$idTorneo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idTorneo) {
    responderPublico(['exito' => false, 'mensaje' => 'Torneo no válido.'], 400);
}

try {
    $conexion = conexionPublica();
    $torneo = obtenerTorneoPublico($conexion, (int) $idTorneo);
    if (!$torneo) {
        responderPublico(['exito' => false, 'mensaje' => 'El torneo no está disponible públicamente.'], 404);
    }
    responderPublico(['exito' => true, 'torneo' => $torneo]);
} catch (Throwable $error) {
    responderPublico(['exito' => false, 'mensaje' => 'No se pudo cargar el torneo.'], 500);
}
