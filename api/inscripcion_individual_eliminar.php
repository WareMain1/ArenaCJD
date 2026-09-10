<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Inscripcion.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$id = filter_var($datos['id_inscripcion'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) responderJson(['exito' => false, 'mensaje' => 'Inscripción no válida.'], 400);

try {
    $modelo = new Inscripcion($contexto['conexion']);
    $actual = $modelo->obtenerIndividualPorId((int) $id);
    if (!$actual) responderJson(['exito' => false, 'mensaje' => 'La inscripción ya no existe.'], 404);

    $idActual = (int) $contexto['usuario']['id_usuario'];
    $esAdmin = in_array('administrador', $contexto['roles'], true);
    $esOrganizador = in_array('organizador', $contexto['roles'], true);
    $gestor = $esAdmin || ($esOrganizador && (int) $actual['id_organizador'] === $idActual);
    $propia = (int) $actual['id_usuario'] === $idActual;
    if (!$gestor && !$propia) responderJson(['exito' => false, 'mensaje' => 'No tienes permiso para eliminar esta inscripción.'], 403);
    if (!in_array($actual['estado'], ['pendiente', 'rechazada'], true)) {
        responderJson(['exito' => false, 'mensaje' => 'Una inscripción aprobada se conserva como parte del historial competitivo.'], 409);
    }
    if (($actual['competencia_iniciada'] ?? false)) {
        responderJson(['exito' => false, 'mensaje' => 'La inscripción ya está vinculada a enfrentamientos y no puede eliminarse.'], 409);
    }

    if (!$modelo->eliminarIndividual((int) $id)) responderJson(['exito' => false, 'mensaje' => 'La inscripción ya no existe.'], 404);
    registrarAuditoriaApi($contexto['conexion'], $idActual, 'inscripcion_eliminada', 'inscripcion_individual', (int) $id, (string) $actual['torneo']);
    responderJson(['exito' => true, 'mensaje' => 'Inscripción eliminada correctamente.']);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo eliminar la inscripción.'], 500);
}
