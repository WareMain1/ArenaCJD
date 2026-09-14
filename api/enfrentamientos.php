<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';

$contexto = contextoApi();
try {
    $modelo = new Enfrentamiento($contexto['conexion']);
    $idTorneo = isset($_GET['id_torneo']) ? filter_var($_GET['id_torneo'], FILTER_VALIDATE_INT) : null;
    if ($idTorneo === false) {
        responderJson(['exito' => false, 'mensaje' => 'Torneo no válido.'], 400);
    }
    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $idOrganizador = (!$esAdministrador && in_array('organizador', $contexto['roles'], true))
        ? (int) $contexto['usuario']['id_usuario']
        : null;

     
    try {
        $modelo->procesarPeriodosGracia($idTorneo ? (int) $idTorneo : null);
    } catch (Throwable) {
    }

    responderJson([
        'exito' => true,
        'enfrentamientos' => $modelo->obtenerTodos($idTorneo ? (int) $idTorneo : null, $idOrganizador),
        'csrf_token' => $contexto['csrf_token'],
        'usuario_id' => (int) $contexto['usuario']['id_usuario'],
        'es_administrador' => in_array('administrador', $contexto['roles'], true),
        'es_organizador' => in_array('organizador', $contexto['roles'], true)
    ]);
} catch (Throwable $error) {
    responderJson([
        'exito' => false,
        'mensaje' => 'No se pudieron cargar los enfrentamientos. Verifica la conexión y la estructura de la base de datos.'
    ], 500);
}
