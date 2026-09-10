<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Inscripcion.php';
require_once __DIR__ . '/../modelos/Torneo.php';

$contexto = contextoApi();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) responderJson(['exito' => false, 'mensaje' => 'Inscripción no válida.'], 400);

try {
    $modelo = new Inscripcion($contexto['conexion']);
    $inscripcion = $modelo->obtenerIndividualPorId((int) $id);
    if (!$inscripcion) responderJson(['exito' => false, 'mensaje' => 'La inscripción no existe.'], 404);

    $idActual = (int) $contexto['usuario']['id_usuario'];
    $esAdmin = in_array('administrador', $contexto['roles'], true);
    $esOrganizador = in_array('organizador', $contexto['roles'], true);
    $gestor = $esAdmin || ($esOrganizador && (int) $inscripcion['id_organizador'] === $idActual);
    $propiaPendiente = (int) $inscripcion['id_usuario'] === $idActual && $inscripcion['estado'] === 'pendiente';

    $torneos = array_values(array_filter((new Torneo($contexto['conexion']))->obtenerTodos(), function ($torneo) use ($esAdmin, $esOrganizador, $idActual, $propiaPendiente) {
        if ($torneo['modalidad'] !== 'individual' || $torneo['estado'] !== 'inscripciones') return false;
        if ($esAdmin || $propiaPendiente) return true;
        return $esOrganizador && (int) $torneo['id_organizador'] === $idActual;
    }));

    responderJson([
        'exito' => true,
        'inscripcion' => $inscripcion,
        'torneos' => $torneos,
        'puede_editar' => $gestor || $propiaPendiente,
        'puede_gestionar_estado' => $gestor,
        'csrf_token' => $contexto['csrf_token']
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo cargar la inscripción.'], 500);
}
