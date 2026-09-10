<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../servicios/ImagenEntidad.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador', 'organizador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$idTorneo = filter_var($datos['id_torneo'] ?? null, FILTER_VALIDATE_INT);
if (!$idTorneo || $idTorneo < 1) responderJson(['exito' => false, 'mensaje' => 'Torneo no válido.'], 400);
try {
    $q = $contexto['conexion']->prepare('SELECT nombre, id_organizador FROM torneos WHERE id_torneo = :id LIMIT 1');
    $q->execute([':id' => (int) $idTorneo]);
    $torneo = $q->fetch(PDO::FETCH_ASSOC);
    if (!$torneo) responderJson(['exito' => false, 'mensaje' => 'El torneo no existe.'], 404);
    $esAdmin = in_array('administrador', $contexto['roles'], true);
    if (!$esAdmin && (int) $torneo['id_organizador'] !== (int) $contexto['usuario']['id_usuario']) responderJson(['exito' => false, 'mensaje' => 'No tienes permiso para cambiar la imagen de este torneo.'], 403);
    (new ImagenEntidad())->eliminar('torneo', (int) $idTorneo);
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'imagen_torneo_eliminada', 'torneo', (int) $idTorneo, (string) $torneo['nombre']);
    responderJson(['exito' => true, 'mensaje' => 'Imagen del torneo eliminada.']);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo eliminar la imagen del torneo.'], 500);
}
