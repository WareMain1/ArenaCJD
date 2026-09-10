<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Torneo.php';

$contexto = contextoApi();
$idTorneo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idTorneo || $idTorneo < 1) {
    responderJson(['exito' => false, 'mensaje' => 'Torneo no válido.'], 400);
}

try {
    $modelo = new Torneo($contexto['conexion']);
    $torneo = $modelo->obtenerPorId((int) $idTorneo);

    if (!$torneo) {
        responderJson(['exito' => false, 'mensaje' => 'El torneo no existe.'], 404);
    }

    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $esOrganizador = in_array('organizador', $contexto['roles'], true);
    $esOrganizadorPropietario = $esOrganizador
        && (int) $torneo['id_organizador'] === (int) $contexto['usuario']['id_usuario'];

    if (!$esAdministrador && $esOrganizador && !$esOrganizadorPropietario) {
        responderJson(['exito' => false, 'mensaje' => 'Solo puedes abrir los torneos que tienes asignados.'], 403);
    }

    responderJson([
        'exito' => true,
        'torneo' => array_merge($torneo, [
            'cupo_disponible' => $torneo['cupo_maximo'] === null
                ? null
                : max(0, (int) $torneo['cupo_maximo'] - (int) ($torneo['cupo_ocupado'] ?? 0))
        ]),
        'restricciones_edicion' => $modelo->obtenerRestriccionesEdicion((int) $idTorneo),
        'catalogos' => $modelo->obtenerCatalogos(),
        'puede_gestionar' => $esAdministrador || $esOrganizadorPropietario,
        'es_administrador' => $esAdministrador,
        'csrf_token' => $contexto['csrf_token']
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo cargar el torneo.'], 500);
}
