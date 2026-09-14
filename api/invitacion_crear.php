<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Inscripcion.php';
require_once __DIR__ . '/../modelos/Invitacion.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$tipo = strtolower(trim((string) ($datos['tipo'] ?? 'individual')));
$idTorneo = filter_var($datos['id_torneo'] ?? null, FILTER_VALIDATE_INT);

if (!in_array($tipo, ['individual', 'equipo'], true) || !$idTorneo) {
    responderJson(['exito' => false, 'mensaje' => 'Tipo de invitación o torneo no válido.'], 400);
}

try {
    $modeloInscripcion = new Inscripcion($contexto['conexion']);
    $torneo = $modeloInscripcion->torneoPorId((int) $idTorneo);
    if (!$torneo) {
        responderJson(['exito' => false, 'mensaje' => 'El torneo seleccionado no existe.'], 404);
    }
    if ($torneo['estado'] !== 'inscripciones') {
        responderJson(['exito' => false, 'mensaje' => 'Este torneo no tiene las inscripciones abiertas.'], 409);
    }

    $idActual = (int) $contexto['usuario']['id_usuario'];
    $esAdmin = in_array('administrador', $contexto['roles'], true);
    $esOrganizadorPropietario = in_array('organizador', $contexto['roles'], true)
        && (int) $torneo['id_organizador'] === $idActual;

    if (!$esAdmin && !$esOrganizadorPropietario) {
        responderJson(['exito' => false, 'mensaje' => 'Solo el administrador o el organizador propietario puede enviar invitaciones para este torneo.'], 403);
    }

    if ($tipo === 'individual') {
        if ($torneo['modalidad'] !== 'individual') {
            responderJson(['exito' => false, 'mensaje' => 'Este torneo no admite participantes individuales.'], 409);
        }

        $nombreUsuario = strtolower(ltrim(trim((string) ($datos['nombre_usuario'] ?? '')), '@'));
        if (!preg_match('/^[a-z0-9._-]{4,24}$/', $nombreUsuario)) {
            responderJson(['exito' => false, 'mensaje' => 'El @usuario no es válido.'], 400);
        }

        $usuario = $modeloInscripcion->usuarioActivoPorNombre($nombreUsuario);
        if (!$usuario) {
            responderJson(['exito' => false, 'mensaje' => 'El usuario no existe o no está activo.'], 404);
        }

        $idInvitado = (int) $usuario['id_usuario'];
        if ($idInvitado === $idActual) {
            responderJson(['exito' => false, 'mensaje' => 'No puedes invitarte a ti mismo. Usa Inscribirme para participar.'], 409);
        }

        $modeloInvitacion = new Invitacion($contexto['conexion']);
        $contexto['conexion']->beginTransaction();
        $idInvitacion = $modeloInvitacion->crearOReenviar((int) $idTorneo, $idActual, $idInvitado);

        registrarAuditoriaApi(
            $contexto['conexion'],
            $idActual,
            'invitacion_enviada',
            'invitacion',
            $idInvitacion,
            '@' . $usuario['nombre_usuario'] . ' · ' . $torneo['nombre']
        );
        $contexto['conexion']->commit();

        responderJson([
            'exito' => true,
            'tipo' => 'invitacion',
            'estado' => 'pendiente',
            'mensaje' => 'Invitación enviada a @' . $usuario['nombre_usuario'] . '. La persona todavía NO está inscrita; debe aceptar o rechazar la invitación.',
            'id_invitacion' => $idInvitacion,
            'id_invitado' => $idInvitado
        ]);
    }

    if ($torneo['modalidad'] !== 'equipo') {
        responderJson(['exito' => false, 'mensaje' => 'Este torneo no admite equipos.'], 409);
    }

    $idEquipo = filter_var($datos['id_equipo'] ?? null, FILTER_VALIDATE_INT);
    if (!$idEquipo) {
        responderJson(['exito' => false, 'mensaje' => 'Selecciona el equipo que quieres invitar.'], 400);
    }

    $modeloInvitacion = new Invitacion($contexto['conexion']);
    $contexto['conexion']->beginTransaction();
    $idInvitacion = $modeloInvitacion->crearOReenviarEquipo((int) $idTorneo, $idActual, (int) $idEquipo);

    registrarAuditoriaApi(
        $contexto['conexion'],
        $idActual,
        'invitacion_enviada',
        'invitacion',
        $idInvitacion,
        'Equipo #' . (int) $idEquipo . ' · ' . $torneo['nombre']
    );
    $contexto['conexion']->commit();

    responderJson([
        'exito' => true,
        'tipo' => 'invitacion',
        'estado' => 'pendiente',
        'mensaje' => 'Invitación enviada al responsable del equipo. El equipo todavía NO está inscrito; su responsable debe aceptar o rechazar la invitación.',
        'id_invitacion' => $idInvitacion
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
    responderJson(['exito' => false, 'mensaje' => 'No se pudo enviar la invitación.'], 500);
}
