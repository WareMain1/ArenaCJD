<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Equipo.php';
require_once __DIR__ . '/../servicios/ImagenEntidad.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$idEquipo = filter_var($datos['id_equipo'] ?? null, FILTER_VALIDATE_INT);

if (!$idEquipo || $idEquipo < 1) {
    responderJson(['exito' => false, 'mensaje' => 'Equipo no válido.'], 400);
}

try {
    $modelo = new Equipo($contexto['conexion']);
    $equipo = $modelo->obtenerPorId((int) $idEquipo);
    if (!$equipo) {
        responderJson(['exito' => false, 'mensaje' => 'El equipo no existe.'], 404);
    }

    $puedeGestionar = in_array('administrador', $contexto['roles'], true)
        || (int) $equipo['id_creador'] === (int) $contexto['usuario']['id_usuario'];
    if (!$puedeGestionar) {
        registrarAuditoriaApi(
            $contexto['conexion'],
            (int) $contexto['usuario']['id_usuario'],
            'equipo_eliminado',
            'equipo',
            (int) $idEquipo,
            'Intento no autorizado de eliminación de equipo',
            'denegado'
        );
        responderJson(['exito' => false, 'mensaje' => 'No tienes permiso para eliminar este equipo.'], 403);
    }

    $contexto['conexion']->beginTransaction();

    if ($modelo->tieneHistorial((int) $idEquipo)) {
        $modelo->archivar((int) $idEquipo);
        registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'equipo_archivado', 'equipo', (int) $idEquipo, (string) $equipo['nombre'] . ' · historial competitivo preservado');
        $contexto['conexion']->commit();
        responderJson(['exito' => true, 'accion' => 'archivado', 'mensaje' => 'El equipo tiene historial competitivo y fue archivado sin borrar sus torneos anteriores.']);
    }

    if (!$modelo->eliminar((int) $idEquipo)) {
        $contexto['conexion']->rollBack();
        responderJson(['exito' => false, 'mensaje' => 'El equipo ya no existe.'], 404);
    }

    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'equipo_eliminado', 'equipo', (int) $idEquipo, (string) $equipo['nombre']);
    $contexto['conexion']->commit();

    try {
        (new ImagenEntidad())->eliminar('equipo', (int) $idEquipo);
    } catch (Throwable $imagenError) {
         
    }

    responderJson(['exito' => true, 'accion' => 'eliminado', 'mensaje' => 'Equipo eliminado correctamente.']);
} catch (Throwable $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => 'No se pudo eliminar el equipo.'], 500);
}
