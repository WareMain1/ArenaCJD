<?php
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../../servicios/ImagenEntidad.php';
$id = filter_input(INPUT_GET, 'id_torneo', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(404); exit; }
try {
    $conexion = conexionPublica();
    if (!obtenerTorneoPublico($conexion, (int) $id)) { http_response_code(404); exit; }
    $imagen = new ImagenEntidad();
    $ruta = $imagen->obtenerRuta('torneo', (int) $id);
    $mime = $imagen->obtenerMime('torneo', (int) $id);
    if (!$ruta || !$mime) { http_response_code(404); exit; }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($ruta));
    header('Cache-Control: public, no-cache, max-age=0');
    header('X-Content-Type-Options: nosniff');
    readfile($ruta);
} catch (Throwable $error) { http_response_code(404); }
