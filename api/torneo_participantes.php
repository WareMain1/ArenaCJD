<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Torneo.php';

exigirMetodoApi('GET');
$contexto = contextoApi();
$idTorneo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idTorneo || $idTorneo < 1) {
    responderJson([
        'exito' => false,
        'mensaje' => 'El torneo indicado no es válido.'
    ], 400);
}

try {
    $modelo = new Torneo($contexto['conexion']);
    $torneo = $modelo->obtenerPorId((int) $idTorneo);
    if (!$torneo) {
        responderJson(['exito' => false, 'mensaje' => 'El torneo no existe.'], 404);
    }
    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    if (!$esAdministrador && in_array('organizador', $contexto['roles'], true)
        && (int) $torneo['id_organizador'] !== (int) $contexto['usuario']['id_usuario']) {
        responderJson(['exito' => false, 'mensaje' => 'Solo puedes consultar participantes de los torneos que tienes asignados.'], 403);
    }
    responderJson([
        'exito' => true,
        'participantes' => $modelo->obtenerParticipantesAprobados($idTorneo)
    ]);
} catch (Throwable $error) {
    responderJson([
        'exito' => false,
        'mensaje' => 'No se pudieron cargar los participantes del torneo.'
    ], 500);
}
