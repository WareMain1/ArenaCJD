<?php
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../servicios/ImagenEntidad.php';
$contexto = contextoApi();
$id = filter_input(INPUT_GET, 'id_equipo', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(404); exit; }
$q = $contexto['conexion']->prepare('SELECT 1 FROM equipos WHERE id_equipo = :id LIMIT 1');
$q->execute([':id' => (int) $id]);
if ($q->fetchColumn() === false) { http_response_code(404); exit; }
$imagen = new ImagenEntidad();
$ruta = $imagen->obtenerRuta('equipo', (int) $id);
$mime = $imagen->obtenerMime('equipo', (int) $id);
if (!$ruta || !$mime) { http_response_code(404); exit; }
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: private, no-cache, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($ruta);
