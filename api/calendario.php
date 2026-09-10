<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Calendario.php';

$contexto = contextoApi();
try {
    $modelo = new Calendario($contexto['conexion']);
    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $idOrganizador = (!$esAdministrador && in_array('organizador', $contexto['roles'], true))
        ? (int) $contexto['usuario']['id_usuario']
        : null;
    responderJson([
        'exito' => true,
        'datos' => $modelo->obtener($idOrganizador),
        'actualizado_en' => date(DATE_ATOM)
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo cargar el calendario.'], 500);
}
