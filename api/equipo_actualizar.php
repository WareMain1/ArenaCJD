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

$idEquipo = filter_var($datos['id_equipo'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim((string) ($datos['nombre'] ?? ''));
$responsableUsuario = strtolower(ltrim(trim((string) ($datos['responsable_usuario'] ?? '')), '@'));
$estado = (string) ($datos['estado'] ?? '');
$integrantesRecibidos = $datos['integrantes'] ?? null;
$invitadosRecibidos = $datos['invitados'] ?? [];

if (!$idEquipo || $idEquipo < 1) {
    responderJson(['exito' => false, 'mensaje' => 'Equipo no válido.'], 400);
}
if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
    responderJson(['exito' => false, 'mensaje' => 'El nombre debe tener entre 2 y 100 caracteres.'], 400);
}
if (!preg_match('/^[a-z0-9._-]{4,24}$/', $responsableUsuario)) {
    responderJson(['exito' => false, 'mensaje' => 'El @usuario responsable no es válido.'], 400);
}
if (!in_array($estado, ['activo', 'inactivo'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'Estado de equipo no válido.'], 400);
}
if ($integrantesRecibidos !== null && !is_array($integrantesRecibidos)) {
    responderJson(['exito' => false, 'mensaje' => 'La lista de integrantes actuales no es válida.'], 400);
}
if (!is_array($invitadosRecibidos)) {
    responderJson(['exito' => false, 'mensaje' => 'La lista de invitaciones no es válida.'], 400);
}

try {
    $modelo = new Equipo($contexto['conexion']);
    $equipo = $modelo->obtenerDetalle((int) $idEquipo);
    if (!$equipo) {
        responderJson(['exito' => false, 'mensaje' => 'El equipo no existe.'], 404);
    }

    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $esResponsable = (int) $equipo['id_creador'] === (int) $contexto['usuario']['id_usuario'];
    if (!$esAdministrador && !$esResponsable) {
        registrarAuditoriaApi(
            $contexto['conexion'],
            (int) $contexto['usuario']['id_usuario'],
            'equipo_actualizado',
            'equipo',
            (int) $idEquipo,
            'Intento no autorizado de modificación de equipo',
            'denegado'
        );
        responderJson(['exito' => false, 'mensaje' => 'No tienes permiso para editar este equipo.'], 403);
    }

    if (!$modelo->nombreDisponible($nombre, (int) $idEquipo)) {
        responderJson(['exito' => false, 'mensaje' => 'Ya existe otro equipo con ese nombre.'], 409);
    }

    $responsable = $modelo->responsablePorUsuario($responsableUsuario);
    if (!$responsable) {
        responderJson(['exito' => false, 'mensaje' => 'El @usuario responsable no existe o no está activo.'], 404);
    }
    if (!$esAdministrador && (int) $responsable['id_usuario'] !== (int) $equipo['id_creador']) {
        responderJson(['exito' => false, 'mensaje' => 'Solo el administrador puede transferir la responsabilidad de un equipo.'], 403);
    }

    $actualesPorId = [];
    foreach (($equipo['integrantes_actuales'] ?? []) as $actual) {
        $actualesPorId[(int) $actual['id_usuario']] = true;
    }

    $idsIntegrantes = [];
    if (is_array($integrantesRecibidos)) {
        foreach (array_values(array_unique($integrantesRecibidos)) as $aliasBruto) {
            $alias = strtolower(ltrim(trim((string) $aliasBruto), '@'));
            if (!preg_match('/^[a-z0-9._-]{4,24}$/', $alias)) {
                responderJson(['exito' => false, 'mensaje' => 'Uno de los integrantes actuales tiene un @usuario no válido.'], 400);
            }
            $integrante = $modelo->participantePorUsuario($alias);
            if (!$integrante) {
                responderJson(['exito' => false, 'mensaje' => 'Uno de los integrantes actuales ya no está disponible para participar.'], 409);
            }
            $idIntegrante = (int) $integrante['id_usuario'];
            if (!isset($actualesPorId[$idIntegrante])) {
                responderJson(['exito' => false, 'mensaje' => '@' . $alias . ' todavía no pertenece al equipo. Debes invitarlo y esperar a que acepte.'], 409);
            }
            $idsIntegrantes[$idIntegrante] = $idIntegrante;
        }
    }

    $invitadosValidos = [];
    foreach (array_values(array_unique($invitadosRecibidos)) as $aliasBruto) {
        $alias = strtolower(ltrim(trim((string) $aliasBruto), '@'));
        if (!preg_match('/^[a-z0-9._-]{4,24}$/', $alias)) {
            responderJson(['exito' => false, 'mensaje' => 'Uno de los usuarios invitados tiene un @usuario no válido.'], 400);
        }
        $invitado = $modelo->participantePorUsuario($alias);
        if (!$invitado) {
            responderJson(['exito' => false, 'mensaje' => 'Uno de los usuarios no existe, no está activo o no está disponible para participar.'], 404);
        }
        $idInvitado = (int) $invitado['id_usuario'];
        if (isset($idsIntegrantes[$idInvitado])) {
            continue;
        }
        $invitadosValidos[$idInvitado] = $invitado;
    }

    if (count($idsIntegrantes) + count($invitadosValidos) < 2) {
        responderJson(['exito' => false, 'mensaje' => 'El equipo debe conservar al menos dos lugares entre integrantes actuales e invitaciones pendientes.'], 400);
    }

    $contexto['conexion']->beginTransaction();

    $qPrev = $contexto['conexion']->prepare("SELECT id_usuario FROM integrantes_equipo WHERE id_equipo = :id");
    $qPrev->execute([':id' => (int) $idEquipo]);
    $prevIntegrantes = array_map('intval', $qPrev->fetchAll(PDO::FETCH_COLUMN));

    $modelo->actualizar((int) $idEquipo, $nombre, (int) $responsable['id_usuario'], $estado);
    $modelo->reemplazarIntegrantes((int) $idEquipo, array_values($idsIntegrantes));

    $modeloInvitacion = new InvitacionEquipo($contexto['conexion']);
    $idsInvitados = array_keys($invitadosValidos);
    $modeloInvitacion->cancelarPendientesNoIncluidas((int) $idEquipo, $idsInvitados);
    foreach ($invitadosValidos as $idInvitado => $invitado) {
        $modeloInvitacion->crearOReenviar(
            (int) $idEquipo,
            (int) $contexto['usuario']['id_usuario'],
            (int) $idInvitado
        );
    }

    $nuevosIntegrantes = array_values(array_map('intval', $idsIntegrantes));
    $agregados = array_diff($nuevosIntegrantes, $prevIntegrantes);
    $eliminados = array_diff($prevIntegrantes, $nuevosIntegrantes);

    foreach ($agregados as $idAgregado) {
        registrarAuditoriaApi(
            $contexto['conexion'],
            (int) $contexto['usuario']['id_usuario'],
            'equipo_integrante_agregado',
            'equipo',
            (int) $idEquipo,
            "Integrante #{$idAgregado} incorporado al equipo {$nombre}"
        );
    }
    foreach ($eliminados as $idEliminado) {
        registrarAuditoriaApi(
            $contexto['conexion'],
            (int) $contexto['usuario']['id_usuario'],
            'equipo_integrante_eliminado',
            'equipo',
            (int) $idEquipo,
            "Integrante #{$idEliminado} removido del equipo {$nombre}"
        );
    }

    registrarAuditoriaApi(
        $contexto['conexion'],
        (int) $contexto['usuario']['id_usuario'],
        'equipo_actualizado',
        'equipo',
        (int) $idEquipo,
        $nombre . ' · ' . $estado . ' · ' . count($invitadosValidos) . ' invitaciones pendientes'
    );

    $contexto['conexion']->commit();

    responderJson([
        'exito' => true,
        'mensaje' => count($invitadosValidos) > 0
            ? 'Equipo actualizado. Los usuarios nuevos quedaron invitados y solo serán integrantes si aceptan.'
            : 'Equipo actualizado correctamente.'
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
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar el equipo. Verifica que la base de datos esté instalada correctamente.'], 500);
}
