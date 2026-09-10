<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Torneo.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador', 'organizador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$idTorneo = filter_var($datos['id_torneo'] ?? null, FILTER_VALIDATE_INT);
$motivo = trim((string) ($datos['motivo'] ?? ''));

if (!$idTorneo || $idTorneo < 1) {
    responderJson(['exito' => false, 'mensaje' => 'Torneo no válido.'], 400);
}
if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 300) {
    responderJson([
        'exito' => false,
        'mensaje' => 'Indica un motivo de cancelación de entre 5 y 300 caracteres.'
    ], 400);
}

try {
    $modelo = new Torneo($contexto['conexion']);
    $torneo = $modelo->obtenerPorId((int) $idTorneo);
    if (!$torneo) {
        responderJson(['exito' => false, 'mensaje' => 'El torneo no existe.'], 404);
    }

    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $esPropietario = in_array('organizador', $contexto['roles'], true)
        && (int) $torneo['id_organizador'] === (int) $contexto['usuario']['id_usuario'];
    if (!$esAdministrador && !$esPropietario) {
        responderJson(['exito' => false, 'mensaje' => 'No tienes permiso para cancelar este torneo.'], 403);
    }

    $estadoActual = (string) $torneo['estado'];
    if ($estadoActual === 'finalizado') {
        responderJson([
            'exito' => false,
            'mensaje' => 'Un torneo finalizado conserva su historial y no puede cancelarse.'
        ], 409);
    }
    if ($estadoActual === 'cancelado') {
        responderJson([
            'exito' => true,
            'mensaje' => 'El torneo ya estaba cancelado.',
            'accion' => 'cancelado'
        ]);
    }

    if (!$modelo->cancelar((int) $idTorneo)) {
        responderJson(['exito' => false, 'mensaje' => 'No se pudo cancelar el torneo.'], 409);
    }

    registrarAuditoriaApi(
        $contexto['conexion'],
        (int) $contexto['usuario']['id_usuario'],
        'torneo_cancelado',
        'torneo',
        (int) $idTorneo,
        (string) $torneo['nombre'] . ' · Motivo: ' . $motivo
    );

    responderJson([
        'exito' => true,
        'mensaje' => 'Torneo cancelado. Se conservaron sus datos, inscripciones, enfrentamientos y resultados.',
        'accion' => 'cancelado'
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo cancelar el torneo.'], 500);
}
