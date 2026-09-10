<?php
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../../servicios/ImagenEntidad.php';
$id = filter_input(INPUT_GET, 'id_equipo', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(404); exit; }
try {
    $conexion = conexionPublica();
    $q = $conexion->prepare("SELECT 1 FROM inscripciones_equipos ie INNER JOIN torneos t ON t.id_torneo = ie.id_torneo WHERE ie.id_equipo = :id AND ie.estado = 'aprobada' AND t.publicado = 1 AND t.estado IN ('inscripciones','en_curso','finalizado') LIMIT 1");
    $q->execute([':id' => (int) $id]);
    if ($q->fetchColumn() === false) { http_response_code(404); exit; }
    $imagen = new ImagenEntidad();
    $ruta = $imagen->obtenerRuta('equipo', (int) $id);
    $mime = $imagen->obtenerMime('equipo', (int) $id);
    if (!$ruta || !$mime) { http_response_code(404); exit; }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($ruta));
    header('Cache-Control: public, no-cache, max-age=0');
    header('X-Content-Type-Options: nosniff');
    readfile($ruta);
} catch (Throwable $error) { http_response_code(404); }
