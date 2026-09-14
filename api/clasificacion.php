<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Clasificacion.php';

$contexto = contextoApi();
$id = filter_input(INPUT_GET, 'id_torneo', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    responderJson(['exito' => false, 'mensaje' => 'Selecciona un torneo válido.'], 400);
}

try {
    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    if (!$esAdministrador && in_array('organizador', $contexto['roles'], true)) {
        $qPropietario = $contexto['conexion']->prepare('SELECT id_organizador FROM torneos WHERE id_torneo = :id_torneo LIMIT 1');
        $qPropietario->execute([':id_torneo' => (int) $id]);
        $idPropietario = $qPropietario->fetchColumn();
        if ($idPropietario === false || (int) $idPropietario !== (int) $contexto['usuario']['id_usuario']) {
            responderJson(['exito' => false, 'mensaje' => 'Solo puedes consultar la clasificación de los torneos que tienes asignados.'], 403);
        }
    }

    $modelo = new Clasificacion($contexto['conexion']);
    responderJson([
        'exito' => true,
        'datos' => $modelo->obtener((int) $id),
        'actualizado_en' => date(DATE_ATOM)
    ]);
} catch (DomainException $error) {
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 404);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo calcular la clasificación. Verifica la instalación de la base de datos.'], 500);
}
