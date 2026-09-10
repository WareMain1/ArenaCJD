<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Inscripcion.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$idInscripcion = filter_var($datos['id_inscripcion'] ?? null, FILTER_VALIDATE_INT);
$idTorneo = filter_var($datos['id_torneo'] ?? null, FILTER_VALIDATE_INT);
$nombreUsuario = strtolower(ltrim(trim((string) ($datos['nombre_usuario'] ?? '')), '@'));
$estado = (string) ($datos['estado'] ?? 'pendiente');

if (!$idInscripcion || !$idTorneo || !preg_match('/^[a-z0-9._-]{4,24}$/', $nombreUsuario)) {
    responderJson(['exito' => false, 'mensaje' => 'Los datos de la inscripción no son válidos.'], 400);
}

try {
    $modelo = new Inscripcion($contexto['conexion']);
    $actual = $modelo->obtenerIndividualPorId((int) $idInscripcion);
    if (!$actual) responderJson(['exito' => false, 'mensaje' => 'La inscripción no existe.'], 404);

    $idActual = (int) $contexto['usuario']['id_usuario'];
    $esAdmin = in_array('administrador', $contexto['roles'], true);
    $esOrganizador = in_array('organizador', $contexto['roles'], true);
    $gestor = $esAdmin || ($esOrganizador && (int) $actual['id_organizador'] === $idActual);
    $propiaPendiente = (int) $actual['id_usuario'] === $idActual && $actual['estado'] === 'pendiente';
    if (!$gestor && !$propiaPendiente) responderJson(['exito' => false, 'mensaje' => 'No tienes permiso para editar esta inscripción.'], 403);

    $torneo = $modelo->torneoPorId((int) $idTorneo);
    if (!$torneo || $torneo['modalidad'] !== 'individual') responderJson(['exito' => false, 'mensaje' => 'El torneo seleccionado no admite inscripciones individuales.'], 400);
    if ($gestor && !$esAdmin && (int) $torneo['id_organizador'] !== $idActual) {
        responderJson(['exito' => false, 'mensaje' => 'Solo puedes mover inscripciones entre torneos que administras.'], 403);
    }
    if (($actual['competencia_iniciada'] ?? false) || $torneo['estado'] !== 'inscripciones') {
        responderJson(['exito' => false, 'mensaje' => 'La inscripción ya forma parte de un torneo cerrado o con enfrentamientos generados y no puede modificarse.'], 409);
    }

    if ($gestor) {
        $usuario = $modelo->usuarioActivoPorNombre($nombreUsuario);
        if (!$usuario) responderJson(['exito' => false, 'mensaje' => 'El @usuario no existe o no está activo.'], 404);
        $idUsuario = (int) $usuario['id_usuario'];
        if (!in_array($estado, ['pendiente', 'aprobada', 'rechazada'], true)) responderJson(['exito' => false, 'mensaje' => 'Estado no válido.'], 400);
    } else {
        $idUsuario = $idActual;
        $estado = 'pendiente';
    }

    if ($modelo->existeIndividual((int) $idTorneo, $idUsuario, (int) $idInscripcion)) {
        responderJson(['exito' => false, 'mensaje' => 'Ese usuario ya está inscripto en el torneo seleccionado.'], 409);
    }

    if ($estado === 'aprobada' && $torneo['cupo_maximo'] !== null && ($actual['estado'] !== 'aprobada' || (int) $actual['id_torneo'] !== (int) $idTorneo)) {
        $consultaCupo = $contexto['conexion']->prepare(
            "SELECT COUNT(*) FROM inscripciones_individuales WHERE id_torneo = :id_torneo AND estado = 'aprobada'"
        );
        $consultaCupo->execute([':id_torneo' => (int) $idTorneo]);
        if ((int) $consultaCupo->fetchColumn() >= (int) $torneo['cupo_maximo']) {
            responderJson(['exito' => false, 'mensaje' => 'El torneo alcanzó su cupo máximo.'], 409);
        }
    }

    $modelo->actualizarIndividual((int) $idInscripcion, (int) $idTorneo, $idUsuario, $estado);
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'inscripcion_actualizada', 'inscripcion_individual', (int) $idInscripcion, $estado);
    responderJson(['exito' => true, 'mensaje' => 'Inscripción actualizada correctamente.']);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar la inscripción.'], 500);
}
