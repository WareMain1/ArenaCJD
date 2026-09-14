<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Torneo.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador', 'organizador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$idTorneo = filter_var($datos['id_torneo'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim((string) ($datos['nombre'] ?? ''));
$idDisciplina = filter_var($datos['id_disciplina'] ?? null, FILTER_VALIDATE_INT);
$idCategoria = filter_var($datos['id_categoria'] ?? null, FILTER_VALIDATE_INT);
$idTipo = filter_var($datos['id_tipo_torneo'] ?? null, FILTER_VALIDATE_INT);
$idOrganizador = filter_var($datos['id_organizador'] ?? null, FILTER_VALIDATE_INT);
$modalidad = (string) ($datos['modalidad'] ?? '');
$fechaInicio = (string) ($datos['fecha_inicio'] ?? '');
$horaInicio = (string) ($datos['hora_inicio'] ?? '09:00');
$fechaFin = (string) ($datos['fecha_fin'] ?? '');
$estado = (string) ($datos['estado'] ?? '');
$publicado = filter_var($datos['publicado'] ?? false, FILTER_VALIDATE_BOOLEAN);
$cupoBruto = $datos['cupo_maximo'] ?? null;
$cupoMaximo = ($cupoBruto === '' || $cupoBruto === null) ? null : filter_var($cupoBruto, FILTER_VALIDATE_INT);
$periodoGraciaRaw = $datos['periodo_gracia_resultado'] ?? 60;
$periodoGracia = filter_var($periodoGraciaRaw, FILTER_VALIDATE_INT);
if ($periodoGracia === false || $periodoGracia < 5 || $periodoGracia > 10080) {
    $periodoGracia = 60;
}

if (!$idTorneo || !$idDisciplina || !$idCategoria || !$idTipo || !$idOrganizador) {
    responderJson(['exito' => false, 'mensaje' => 'Faltan datos obligatorios del torneo.'], 400);
}
if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 120) {
    responderJson(['exito' => false, 'mensaje' => 'El nombre debe tener entre 3 y 120 caracteres.'], 400);
}
if (!in_array($modalidad, ['individual', 'equipo'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'Modalidad de torneo no válida.'], 400);
}
if (!in_array($estado, ['borrador', 'inscripciones', 'en_curso', 'finalizado', 'cancelado'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'Estado de torneo no válido.'], 400);
}
$fechaInicioObj = fechaYmdValidaApi($fechaInicio);
$horaInicioSql = horaHmValidaApi($horaInicio);
$fechaFinObj = fechaYmdValidaApi($fechaFin);
if (!$fechaInicioObj || !$horaInicioSql || !$fechaFinObj) {
    responderJson(['exito' => false, 'mensaje' => 'La fecha u hora de inicio no es válida.'], 400);
}
if ($fechaFinObj < $fechaInicioObj) {
    responderJson(['exito' => false, 'mensaje' => 'La fecha de finalización no puede ser anterior al inicio.'], 400);
}
if ($cupoMaximo !== null && ($cupoMaximo === false || $cupoMaximo < 1 || $cupoMaximo > 65535)) {
    responderJson(['exito' => false, 'mensaje' => 'El cupo máximo no es válido.'], 400);
}

try {
    $modelo = new Torneo($contexto['conexion']);
    $torneo = $modelo->obtenerPorId((int) $idTorneo);
    if (!$torneo) {
        responderJson(['exito' => false, 'mensaje' => 'El torneo no existe.'], 404);
    }

    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $esPropietario = in_array('organizador', $contexto['roles'], true)
        && (int) $torneo['id_organizador'] === (int) $contexto['usuario']['id_usuario'];
    if (!$esAdministrador && !$esPropietario) {
        registrarAuditoriaApi(
            $contexto['conexion'],
            (int) $contexto['usuario']['id_usuario'],
            'torneo_actualizado',
            'torneo',
            (int) $idTorneo,
            'Intento no autorizado de modificación',
            'denegado'
        );
        responderJson(['exito' => false, 'mensaje' => 'No tienes permiso para editar este torneo.'], 403);
    }

    if (!$esAdministrador) {
        $idOrganizador = (int) $torneo['id_organizador'];
    }

    $restricciones = $modelo->obtenerRestriccionesEdicion((int) $idTorneo);

    if (in_array((string) $torneo['estado'], ['finalizado', 'cancelado'], true)) {
        responderJson([
            'exito' => false,
            'mensaje' => 'Un torneo finalizado o cancelado conserva su historial y ya no puede editarse.'
        ], 409);
    }

    if ($restricciones['estructura_bloqueada']) {
        $cambioEstructural = (int) $torneo['id_disciplina'] !== (int) $idDisciplina
            || (int) $torneo['id_categoria'] !== (int) $idCategoria
            || (int) $torneo['id_tipo_torneo'] !== (int) $idTipo
            || (string) $torneo['modalidad'] !== $modalidad;
        if ($cambioEstructural) {
            responderJson([
                'exito' => false,
                'mensaje' => 'Disciplina, categoría, tipo y modalidad quedan bloqueados cuando el torneo ya tiene inscripciones o enfrentamientos.'
            ], 409);
        }
    }

    if ($restricciones['organizador_bloqueado'] && (int) $torneo['id_organizador'] !== (int) $idOrganizador) {
        responderJson([
            'exito' => false,
            'mensaje' => 'El organizador no puede cambiarse después de generar enfrentamientos.'
        ], 409);
    }

    $ocupados = $modalidad === 'equipo'
        ? (int) $restricciones['cupo_equipo']
        : (int) $restricciones['cupo_individual'];
    if ($cupoMaximo !== null && (int) $cupoMaximo < $ocupados) {
        responderJson([
            'exito' => false,
            'mensaje' => 'El cupo máximo no puede ser menor que las ' . $ocupados . ' plazas actualmente ocupadas.'
        ], 409);
    }

    if ($restricciones['inscripciones'] > 0 && $estado === 'borrador') {
        responderJson(['exito' => false, 'mensaje' => 'Un torneo con inscripciones no puede volver a Borrador.'], 409);
    }
    if ($restricciones['enfrentamientos'] > 0 && in_array($estado, ['borrador', 'inscripciones'], true)) {
        responderJson(['exito' => false, 'mensaje' => 'Un torneo con enfrentamientos no puede volver a una fase de inscripción.'], 409);
    }
    if ($estado === 'finalizado') {
        responderJson([
            'exito' => false,
            'mensaje' => 'El estado Finalizado se asigna automáticamente al completar la competencia.'
        ], 409);
    }
    if ($publicado && !in_array($estado, ['inscripciones', 'en_curso', 'finalizado'], true)) {
        responderJson([
            'exito' => false,
            'mensaje' => 'Solo se pueden publicar torneos con inscripciones abiertas, en curso o finalizados.'
        ], 409);
    }
    if ($estado === 'cancelado') {
        responderJson([
            'exito' => false,
            'mensaje' => 'Para cancelar el torneo utiliza la opción “Cancelar torneo” e indica el motivo.'
        ], 409);
    }

    $inicioOriginal = (string) $torneo['fecha_inicio'] . ' ' . substr((string) ($torneo['hora_inicio'] ?? '09:00:00'), 0, 8);
    $inicioNuevo = $fechaInicio . ' ' . $horaInicioSql;
    if ($inicioNuevo !== $inicioOriginal && in_array($estado, ['borrador', 'inscripciones'], true)) {
        $inicioCompleto = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $inicioNuevo);
        if (!$inicioCompleto || $inicioCompleto <= new DateTimeImmutable()) {
            responderJson(['exito' => false, 'mensaje' => 'La nueva fecha y hora de inicio debe estar en el futuro mientras el torneo aún no comenzó.'], 409);
        }
    }

    if (!$modelo->organizadorValido((int) $idOrganizador)) {
        responderJson(['exito' => false, 'mensaje' => 'El organizador seleccionado no es válido.'], 400);
    }
    if (!$modelo->combinacionPermitida((int) $idDisciplina, (int) $idCategoria, (int) $idTipo)) {
        responderJson(['exito' => false, 'mensaje' => 'La categoría o el tipo de torneo no son compatibles con la disciplina seleccionada.'], 400);
    }

    $contexto['conexion']->beginTransaction();
    $modelo->actualizar(
        (int) $idTorneo,
        $nombre,
        (int) $idDisciplina,
        (int) $idCategoria,
        (int) $idTipo,
        (int) $idOrganizador,
        $modalidad,
        $fechaInicio,
        $horaInicioSql,
        $fechaFin,
        $estado,
        $cupoMaximo === null ? null : (int) $cupoMaximo,
        $publicado,
        (int) $periodoGracia
    );

    $cambios = [];
    if ($nombre !== $torneo['nombre']) {
        $cambios[] = "Nombre: {$torneo['nombre']} -> {$nombre}";
    }
    if ($estado !== $torneo['estado']) {
        $cambios[] = "Estado: {$torneo['estado']} -> {$estado}";
    }
    $pubAnterior = (int) $torneo['publicado'];
    $pubNuevo = $publicado ? 1 : 0;
    if ($pubAnterior !== $pubNuevo) {
        $cambios[] = "Publicado: {$pubAnterior} -> {$pubNuevo}";
    }
    $detalleAuditoria = $nombre . ($cambios ? ' · ' . implode(' · ', $cambios) : '');

    registrarAuditoriaApi(
        $contexto['conexion'],
        (int) $contexto['usuario']['id_usuario'],
        'torneo_actualizado',
        'torneo',
        (int) $idTorneo,
        $detalleAuditoria
    );

    $contexto['conexion']->commit();

    responderJson([
        'exito' => true,
        'mensaje' => 'Torneo actualizado correctamente.',
        'torneo' => $modelo->obtenerPorId((int) $idTorneo)
    ]);
} catch (Throwable $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar el torneo.'], 500);
}
