<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Equipo.php';

$contexto = contextoApi();
$idEquipo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idEquipo || $idEquipo < 1) {
    responderJson(['exito' => false, 'mensaje' => 'Equipo no válido.'], 400);
}

try {
    $modelo = new Equipo($contexto['conexion']);
    $equipo = $modelo->obtenerDetalle((int) $idEquipo);
    if (!$equipo) {
        responderJson(['exito' => false, 'mensaje' => 'El equipo no existe.'], 404);
    }

    $equipo['puede_gestionar'] = in_array('administrador', $contexto['roles'], true)
        || (int) $equipo['id_creador'] === (int) $contexto['usuario']['id_usuario'];
    if (!$equipo['puede_gestionar']) {
        unset($equipo['invitaciones_pendientes']);
    }

    responderJson(['exito' => true, 'equipo' => $equipo]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo cargar el equipo.'], 500);
}
