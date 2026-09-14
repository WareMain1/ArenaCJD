<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Invitacion.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$idInvitacion = filter_var($datos['id_invitacion'] ?? null, FILTER_VALIDATE_INT);
$respuesta = (string) ($datos['respuesta'] ?? '');

if (!$idInvitacion || !in_array($respuesta, ['aceptada', 'rechazada'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'Invitación o respuesta no válidas.'], 400);
}

try {
    $modelo = new Invitacion($contexto['conexion']);
    $contexto['conexion']->beginTransaction();
    $resultado = $modelo->responder(
        (int) $idInvitacion,
        (int) $contexto['usuario']['id_usuario'],
        $respuesta
    );

    registrarAuditoriaApi(
        $contexto['conexion'],
        (int) $contexto['usuario']['id_usuario'],
        $respuesta === 'aceptada' ? 'invitacion_aceptada' : 'invitacion_rechazada',
        'invitacion',
        (int) $idInvitacion
    );
    $contexto['conexion']->commit();

    responderJson([
        'exito' => true,
        'mensaje' => $respuesta === 'aceptada'
            ? 'Invitación aceptada. Ya formas parte del torneo.'
            : 'Invitación rechazada.',
        'invitacion' => $resultado
    ]);
} catch (DomainException $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 409);
} catch (Throwable $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => 'No se pudo responder la invitación.'], 500);
}
