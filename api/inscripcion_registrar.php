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

$modalidad = (string) ($datos['modalidad'] ?? '');
$accion = strtolower(trim((string) ($datos['accion'] ?? 'auto')));
$idTorneo = filter_var($datos['id_torneo'] ?? null, FILTER_VALIDATE_INT);
if (!in_array($modalidad, ['individual', 'equipo'], true) || !$idTorneo) {
    responderJson(['exito' => false, 'mensaje' => 'Modalidad o torneo no válidos.'], 400);
}
if (!in_array($accion, ['auto', 'inscribir', 'invitar'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'La acción solicitada no es válida.'], 400);
}

try {
    $modelo = new Inscripcion($contexto['conexion']);
    $torneo = $modelo->torneoPorId((int) $idTorneo);
    if (!$torneo) responderJson(['exito' => false, 'mensaje' => 'El torneo seleccionado no existe.'], 404);
    if ($torneo['modalidad'] !== $modalidad) responderJson(['exito' => false, 'mensaje' => 'La modalidad no coincide con la configuración del torneo.'], 409);
    if ($torneo['estado'] !== 'inscripciones') responderJson(['exito' => false, 'mensaje' => 'Este torneo no tiene inscripciones abiertas.'], 409);

    if ($torneo['cupo_maximo'] !== null) {
        $ocupados = $modelo->contarInscritosEnTorneo((int) $idTorneo, $modalidad);
        if ($ocupados >= (int) $torneo['cupo_maximo']) responderJson(['exito' => false, 'mensaje' => 'El torneo alcanzó su cupo máximo.'], 409);
    }

    $idActual = (int) $contexto['usuario']['id_usuario'];
    $esAdmin = in_array('administrador', $contexto['roles'], true);
    $esOrganizadorPropietario = in_array('organizador', $contexto['roles'], true)
        && (int) $torneo['id_organizador'] === $idActual;
    $gestorTorneo = $esAdmin || $esOrganizadorPropietario;

    if ($modalidad === 'individual') {
        $nombreUsuario = strtolower(ltrim(trim((string) ($datos['nombre_usuario'] ?? '')), '@'));
        if ($nombreUsuario === '') {
            $nombreUsuario = strtolower((string) ($contexto['usuario']['nombre_usuario'] ?? ''));
        }
        if (!preg_match('/^[a-z0-9._-]{4,24}$/', $nombreUsuario)) responderJson(['exito' => false, 'mensaje' => 'El @usuario no es válido.'], 400);
        $usuario = $modelo->usuarioActivoPorNombre($nombreUsuario);
        if (!$usuario) responderJson(['exito' => false, 'mensaje' => 'El usuario no existe o no está activo.'], 404);

        $idObjetivo = (int) $usuario['id_usuario'];
        $esOtraPersona = $idObjetivo !== $idActual;
        $debeInvitar = $accion === 'invitar' || ($accion === 'auto' && $esOtraPersona);

        if ($debeInvitar) {
            if (!$esOtraPersona) {
                responderJson(['exito' => false, 'mensaje' => 'No puedes enviarte una invitación a ti mismo. Para participar usa tu propia inscripción.'], 409);
            }
            if (!$gestorTorneo) {
                responderJson(['exito' => false, 'mensaje' => 'Solo el administrador o el organizador responsable puede invitar a otra persona a este torneo.'], 403);
            }

            $modeloInvitacion = new Invitacion($contexto['conexion']);
            $contexto['conexion']->beginTransaction();
            $idInvitacion = $modeloInvitacion->crearOReenviar((int) $idTorneo, $idActual, $idObjetivo);
            registrarAuditoriaApi($contexto['conexion'], $idActual, 'invitacion_enviada', 'invitacion', $idInvitacion, '@' . $usuario['nombre_usuario'] . ' · ' . $torneo['nombre']);
            $contexto['conexion']->commit();
            responderJson([
                'exito' => true,
                'tipo' => 'invitacion',
                'mensaje' => 'Invitación enviada a @' . $usuario['nombre_usuario'] . '. No se creó ninguna inscripción: debe aceptarla desde la campana o Participantes > Mis invitaciones.',
                'id_invitacion' => $idInvitacion,
                'id_invitado' => $idObjetivo
            ]);
        }

        if ($esOtraPersona) {
            responderJson(['exito' => false, 'mensaje' => 'No se puede inscribir directamente a otra persona. Debes enviarle una invitación.'], 409);
        }

        if ($modelo->existeIndividual((int) $idTorneo, $idObjetivo)) responderJson(['exito' => false, 'mensaje' => 'Ya tienes una inscripción en este torneo.'], 409);
        $contexto['conexion']->beginTransaction();
        $idInscripcion = $modelo->crearIndividual((int) $idTorneo, $idObjetivo);
        registrarAuditoriaApi($contexto['conexion'], $idActual, 'inscripcion_registrada', 'inscripcion_individual', $idInscripcion, $torneo['nombre']);
        $contexto['conexion']->commit();
        responderJson(['exito' => true, 'tipo' => 'inscripcion', 'mensaje' => 'Inscripción individual registrada y pendiente de aprobación.', 'id_inscripcion' => $idInscripcion]);
    }

    $idEquipoExistente = filter_var($datos['id_equipo'] ?? null, FILTER_VALIDATE_INT);
    if ($idEquipoExistente) {
        $equipoExistente = $modelo->equipoPorId((int) $idEquipoExistente);
        if (!$equipoExistente || $equipoExistente['estado'] !== 'activo') {
            responderJson(['exito' => false, 'mensaje' => 'El equipo seleccionado no existe o está inactivo.'], 404);
        }
        $esResponsableEquipo = (int) $equipoExistente['id_creador'] === $idActual;
        if (!$esResponsableEquipo) {
            if (!$gestorTorneo) {
                responderJson(['exito' => false, 'mensaje' => 'Solo el responsable del equipo puede inscribirlo. El organizador responsable del torneo puede enviarle una invitación.'], 403);
            }

            $modeloInvitacion = new Invitacion($contexto['conexion']);
            $contexto['conexion']->beginTransaction();
            $idInvitacion = $modeloInvitacion->crearOReenviarEquipo((int) $idTorneo, $idActual, (int) $idEquipoExistente);
            registrarAuditoriaApi($contexto['conexion'], $idActual, 'invitacion_enviada', 'invitacion', $idInvitacion, (string) $equipoExistente['nombre'] . ' · ' . $torneo['nombre']);
            $contexto['conexion']->commit();
            responderJson([
                'exito' => true,
                'tipo' => 'invitacion',
                'mensaje' => 'Invitación enviada al responsable del equipo ' . $equipoExistente['nombre'] . '. Podrá aceptarla desde la campana o Mis invitaciones.',
                'id_invitacion' => $idInvitacion
            ]);
        }

        $contexto['conexion']->beginTransaction();
        $idInscripcion = $modelo->inscribirEquipoExistente((int) $idEquipoExistente, (int) $idTorneo);
        registrarAuditoriaApi($contexto['conexion'], $idActual, 'inscripcion_registrada', 'inscripcion_equipo', $idInscripcion, (string) $equipoExistente['nombre'] . ' · ' . $torneo['nombre']);
        $contexto['conexion']->commit();
        responderJson([
            'exito' => true,
            'tipo' => 'inscripcion_equipo',
            'mensaje' => 'Equipo reutilizado correctamente. Sus integrantes actuales quedaron congelados para esta inscripción, que permanece pendiente de aprobación.',
            'id_equipo' => (int) $idEquipoExistente,
            'id_inscripcion' => $idInscripcion
        ]);
    }

    responderJson([
        'exito' => false,
        'mensaje' => 'Selecciona un equipo permanente existente. Los equipos nuevos se crean por separado antes de inscribirlos.'
    ], 400);
} catch (DomainException $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 409);
} catch (Throwable $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => 'No se pudo registrar la inscripción.'], 500);
}
