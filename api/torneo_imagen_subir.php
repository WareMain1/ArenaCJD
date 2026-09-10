<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../servicios/ImagenEntidad.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador', 'organizador']);
exigirCsrfApi($contexto['csrf_token']);
$idTorneo = filter_input(INPUT_POST, 'id_torneo', FILTER_VALIDATE_INT);
if (!$idTorneo || $idTorneo < 1) responderJson(['exito' => false, 'mensaje' => 'Torneo no válido.'], 400);
if (!isset($_FILES['imagen'])) responderJson(['exito' => false, 'mensaje' => 'Selecciona una imagen para el torneo.'], 400);

try {
    $q = $contexto['conexion']->prepare('SELECT id_torneo, nombre, id_organizador FROM torneos WHERE id_torneo = :id LIMIT 1');
    $q->execute([':id' => (int) $idTorneo]);
    $torneo = $q->fetch(PDO::FETCH_ASSOC);
    if (!$torneo) responderJson(['exito' => false, 'mensaje' => 'El torneo no existe.'], 404);
    $esAdmin = in_array('administrador', $contexto['roles'], true);
    if (!$esAdmin && (int) $torneo['id_organizador'] !== (int) $contexto['usuario']['id_usuario']) {
        responderJson(['exito' => false, 'mensaje' => 'Solo puedes cambiar la imagen de los torneos que organizas.'], 403);
    }
    $imagen = new ImagenEntidad();
    $imagen->guardar('torneo', (int) $idTorneo, $_FILES['imagen']);
    $version = $imagen->obtenerVersion('torneo', (int) $idTorneo);
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'imagen_torneo_actualizada', 'torneo', (int) $idTorneo, (string) $torneo['nombre']);
    responderJson([
        'exito' => true,
        'mensaje' => 'Imagen del torneo actualizada.',
        'imagen_url' => 'api/imagen_torneo.php?id_torneo=' . (int) $idTorneo . '&v=' . $version
    ]);
} catch (InvalidArgumentException|RuntimeException $error) {
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 400);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar la imagen del torneo.'], 500);
}
