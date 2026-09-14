<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Invitacion.php';
require_once __DIR__ . '/../modelos/InvitacionEquipo.php';

$contexto = contextoApi();

try {
    $modelo = new Invitacion($contexto['conexion']);
    $modeloEquipo = new InvitacionEquipo($contexto['conexion']);
    $idUsuario = (int) $contexto['usuario']['id_usuario'];
    $recibidas = $modelo->listarRecibidas($idUsuario);
    $enviadas = $modelo->listarEnviadas($idUsuario);
    $recibidasEquipo = $modeloEquipo->listarRecibidas($idUsuario);

    responderJson([
        'exito' => true,
        'recibidas' => $recibidas,
        'enviadas' => $enviadas,
        'recibidas_equipo' => $recibidasEquipo,
        'cantidad_pendientes' => $modelo->contarPendientes($idUsuario) + $modeloEquipo->contarPendientes($idUsuario),
        'cantidad_pendientes_torneo' => $modelo->contarPendientes($idUsuario),
        'cantidad_pendientes_equipo' => $modeloEquipo->contarPendientes($idUsuario),
        'csrf_token' => $contexto['csrf_token'],
        'actualizado_en' => date(DATE_ATOM)
    ]);
} catch (Throwable $error) {
    responderJson([
        'exito' => false,
        'mensaje' => 'No se pudieron cargar las invitaciones. Verifica la instalación de la base de datos.'
    ], 500);
}
