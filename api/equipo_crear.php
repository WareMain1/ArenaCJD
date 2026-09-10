<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Equipo.php';
require_once __DIR__ . '/../modelos/InvitacionEquipo.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$nombre = trim((string) ($datos['nombre'] ?? ''));
$invitados = is_array($datos['invitados'] ?? null)
    ? $datos['invitados']
    : (is_array($datos['integrantes'] ?? null) ? $datos['integrantes'] : []);
$incluirResponsable = !empty($datos['incluir_responsable']);
$esAdministrador = in_array('administrador', $contexto['roles'], true);
$idActual = (int) $contexto['usuario']['id_usuario'];

if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
    responderJson(['exito' => false, 'mensaje' => 'El nombre del equipo debe tener entre 2 y 100 caracteres.'], 400);
}

if ($esAdministrador && $incluirResponsable) {
    responderJson(['exito' => false, 'mensaje' => 'Las cuentas administradoras no pueden participar como integrantes de equipos.'], 403);
}

try {
    $modelo = new Equipo($contexto['conexion']);
    if (!$modelo->nombreDisponible($nombre)) {
        responderJson(['exito' => false, 'mensaje' => 'Ya existe un equipo con ese nombre.'], 409);
    }

    $invitadosValidos = [];
    foreach (array_values(array_unique($invitados)) as $aliasBruto) {
        $alias = strtolower(ltrim(trim((string) $aliasBruto), '@'));
        if (!preg_match('/^[a-z0-9._-]{4,24}$/', $alias)) {
            responderJson(['exito' => false, 'mensaje' => 'Uno de los usuarios invitados tiene un @usuario no válido.'], 400);
        }
        $usuario = $modelo->participantePorUsuario($alias);
        if (!$usuario) {
            responderJson(['exito' => false, 'mensaje' => 'Uno de los usuarios no existe, no está activo o no está disponible para participar.'], 404);
        }
        if ((int) $usuario['id_usuario'] === $idActual) {
            responderJson(['exito' => false, 'mensaje' => 'Tu propia cuenta se controla con la opción “Participar también como integrante”.'], 409);
        }
        $invitadosValidos[(int) $usuario['id_usuario']] = $usuario;
    }

    $cantidadPlanificada = count($invitadosValidos) + ($incluirResponsable ? 1 : 0);
    if ($cantidadPlanificada < 2) {
        responderJson(['exito' => false, 'mensaje' => 'El equipo debe quedar planificado con al menos dos integrantes entre miembros actuales e invitaciones.'], 400);
    }

    $contexto['conexion']->beginTransaction();
    $idEquipo = $modelo->crear($nombre, $idActual);
    $modelo->reemplazarIntegrantes($idEquipo, $incluirResponsable ? [$idActual] : []);

    $modeloInvitacion = new InvitacionEquipo($contexto['conexion']);
    $idsInvitaciones = [];
    foreach ($invitadosValidos as $idInvitado => $usuario) {
        $idsInvitaciones[] = $modeloInvitacion->crearOReenviar($idEquipo, $idActual, (int) $idInvitado);
    }
    $contexto['conexion']->commit();

    registrarAuditoriaApi(
        $contexto['conexion'],
        $idActual,
        'equipo_creado',
        'equipo',
        $idEquipo,
        $nombre . ' · ' . count($idsInvitaciones) . ' invitaciones pendientes'
    );

    responderJson([
        'exito' => true,
        'mensaje' => count($idsInvitaciones) > 0
            ? 'Equipo creado. Se enviaron ' . count($idsInvitaciones) . ' invitación(es); esas personas NO forman parte del equipo hasta que acepten.'
            : 'Equipo creado correctamente.',
        'id_equipo' => $idEquipo,
        'invitaciones_enviadas' => count($idsInvitaciones)
    ], 201);
} catch (DomainException|InvalidArgumentException $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 409);
} catch (Throwable $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => 'No se pudo crear el equipo. Verifica que la migración de invitaciones a equipos esté aplicada.'], 500);
}
