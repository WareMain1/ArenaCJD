<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../servicios/ImagenEntidad.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$idEquipo = filter_input(INPUT_POST, 'id_equipo', FILTER_VALIDATE_INT);
if (!$idEquipo || $idEquipo < 1) responderJson(['exito' => false, 'mensaje' => 'Equipo no válido.'], 400);
if (!isset($_FILES['imagen'])) responderJson(['exito' => false, 'mensaje' => 'Selecciona una imagen para el equipo.'], 400);
try {
    $q = $contexto['conexion']->prepare('SELECT id_equipo, nombre, id_creador FROM equipos WHERE id_equipo = :id LIMIT 1');
    $q->execute([':id' => (int) $idEquipo]);
    $equipo = $q->fetch(PDO::FETCH_ASSOC);
    if (!$equipo) responderJson(['exito' => false, 'mensaje' => 'El equipo no existe.'], 404);
    $esAdmin = in_array('administrador', $contexto['roles'], true);
    if (!$esAdmin && (int) $equipo['id_creador'] !== (int) $contexto['usuario']['id_usuario']) responderJson(['exito' => false, 'mensaje' => 'Solo el responsable del equipo puede cambiar su imagen.'], 403);
    $imagen = new ImagenEntidad();
    $imagen->guardar('equipo', (int) $idEquipo, $_FILES['imagen']);
    $version = $imagen->obtenerVersion('equipo', (int) $idEquipo);
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'imagen_equipo_actualizada', 'equipo', (int) $idEquipo, (string) $equipo['nombre']);
    responderJson(['exito' => true, 'mensaje' => 'Imagen del equipo actualizada.', 'imagen_url' => 'api/imagen_equipo.php?id_equipo=' . (int) $idEquipo . '&v=' . $version]);
} catch (InvalidArgumentException|RuntimeException $error) {
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 400);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar la imagen del equipo.'], 500);
}
