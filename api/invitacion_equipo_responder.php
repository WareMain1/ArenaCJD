<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/InvitacionEquipo.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$idInvitacion = filter_var($datos['id_invitacion_equipo'] ?? null, FILTER_VALIDATE_INT);
$respuesta = strtolower(trim((string) ($datos['respuesta'] ?? '')));

if (!$idInvitacion || !in_array($respuesta, ['aceptada', 'rechazada'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'Invitación o respuesta no válida.'], 400);
}

try {
    $modelo = new InvitacionEquipo($contexto['conexion']);
    $contexto['conexion']->beginTransaction();
    $resultado = $modelo->responder((int) $idInvitacion, (int) $contexto['usuario']['id_usuario'], $respuesta);

    registrarAuditoriaApi(
        $contexto['conexion'],
        (int) $contexto['usuario']['id_usuario'],
        'invitacion_equipo_' . $respuesta,
        'invitacion_equipo',
        (int) $idInvitacion,
        $resultado['equipo']
    );

    if ($respuesta === 'aceptada') {
        registrarAuditoriaApi(
            $contexto['conexion'],
            (int) $contexto['usuario']['id_usuario'],
            'equipo_integrante_agregado',
            'equipo',
            (int) ($resultado['id_equipo'] ?? 0),
            "Integrante incorporado al equipo {$resultado['equipo']} tras aceptar invitación"
        );
    }
    $contexto['conexion']->commit();

    responderJson([
        'exito' => true,
        'mensaje' => $respuesta === 'aceptada'
            ? 'Aceptaste la invitación. Ahora formas parte de ' . $resultado['equipo'] . '.'
            : 'Rechazaste la invitación a ' . $resultado['equipo'] . '.',
        'resultado' => $resultado
    ]);
} catch (DomainException|InvalidArgumentException $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 409);
} catch (Throwable $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => 'No se pudo responder la invitación al equipo.'], 500);
}
