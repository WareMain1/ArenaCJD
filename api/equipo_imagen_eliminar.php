<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../servicios/ImagenEntidad.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$idEquipo = filter_var($datos['id_equipo'] ?? null, FILTER_VALIDATE_INT);
if (!$idEquipo || $idEquipo < 1) responderJson(['exito' => false, 'mensaje' => 'Equipo no válido.'], 400);
try {
    $q = $contexto['conexion']->prepare('SELECT nombre, id_creador FROM equipos WHERE id_equipo = :id LIMIT 1');
    $q->execute([':id' => (int) $idEquipo]);
    $equipo = $q->fetch(PDO::FETCH_ASSOC);
    if (!$equipo) responderJson(['exito' => false, 'mensaje' => 'El equipo no existe.'], 404);
    $esAdmin = in_array('administrador', $contexto['roles'], true);
    if (!$esAdmin && (int) $equipo['id_creador'] !== (int) $contexto['usuario']['id_usuario']) responderJson(['exito' => false, 'mensaje' => 'No tienes permiso para cambiar la imagen de este equipo.'], 403);
    (new ImagenEntidad())->eliminar('equipo', (int) $idEquipo);
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'imagen_equipo_eliminada', 'equipo', (int) $idEquipo, (string) $equipo['nombre']);
    responderJson(['exito' => true, 'mensaje' => 'Imagen del equipo eliminada.']);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo eliminar la imagen del equipo.'], 500);
}
