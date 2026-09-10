<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Torneo.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$nombre = trim((string) ($datos['nombre'] ?? ''));
$idDisciplina = filter_var($datos['id_disciplina'] ?? null, FILTER_VALIDATE_INT);
$idCategoria = filter_var($datos['id_categoria'] ?? null, FILTER_VALIDATE_INT);
$idTipo = filter_var($datos['id_tipo_torneo'] ?? null, FILTER_VALIDATE_INT);
$idOrganizador = filter_var($datos['id_organizador'] ?? null, FILTER_VALIDATE_INT);
$modalidad = (string) ($datos['modalidad'] ?? '');
$fechaInicio = (string) ($datos['fecha_inicio'] ?? '');
$horaInicio = (string) ($datos['hora_inicio'] ?? '09:00');
$fechaFin = (string) ($datos['fecha_fin'] ?? '');
$estado = 'borrador'; // Estado inicial obligatorio: no se acepta desde el cliente.
$cupoBruto = $datos['cupo_maximo'] ?? null;
$cupoMaximo = ($cupoBruto === '' || $cupoBruto === null) ? null : filter_var($cupoBruto, FILTER_VALIDATE_INT);

$periodoGraciaRaw = $datos['periodo_gracia_resultado'] ?? 60;
$periodoGracia = filter_var($periodoGraciaRaw, FILTER_VALIDATE_INT);
if ($periodoGracia === false || $periodoGracia < 5 || $periodoGracia > 10080) {
    $periodoGracia = 60;
}

if (!$idDisciplina || !$idCategoria || !$idTipo || !$idOrganizador) {
    responderJson(['exito' => false, 'mensaje' => 'Completa todos los datos obligatorios del torneo.'], 400);
}
if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 120) {
    responderJson(['exito' => false, 'mensaje' => 'El nombre debe tener entre 3 y 120 caracteres.'], 400);
}
if (!in_array($modalidad, ['individual', 'equipo'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'Modalidad de torneo no válida.'], 400);
}
$fechaInicioObj = fechaYmdValidaApi($fechaInicio);
$horaInicioSql = horaHmValidaApi($horaInicio);
$fechaFinObj = fechaYmdValidaApi($fechaFin);
if (!$fechaInicioObj || !$horaInicioSql || !$fechaFinObj) {
    responderJson(['exito' => false, 'mensaje' => 'La fecha u hora de inicio no es válida.'], 400);
}
$inicioCompleto = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $fechaInicio . ' ' . $horaInicioSql);
$ahora = new DateTimeImmutable();
if (!$inicioCompleto || $inicioCompleto <= $ahora) {
    responderJson(['exito' => false, 'mensaje' => 'Un torneo nuevo debe comenzar en una fecha y hora futuras.'], 400);
}
if ($fechaFinObj < $fechaInicioObj) {
    responderJson(['exito' => false, 'mensaje' => 'La fecha de finalización no puede ser anterior al inicio.'], 400);
}
if ($cupoMaximo !== null && ($cupoMaximo === false || $cupoMaximo < 1 || $cupoMaximo > 65535)) {
    responderJson(['exito' => false, 'mensaje' => 'El cupo máximo no es válido.'], 400);
}

try {
    $modelo = new Torneo($contexto['conexion']);
    if (!$modelo->organizadorValido((int) $idOrganizador)) {
        responderJson(['exito' => false, 'mensaje' => 'El organizador seleccionado no es válido.'], 400);
    }
    if (!$modelo->combinacionPermitida((int) $idDisciplina, (int) $idCategoria, (int) $idTipo)) {
        responderJson(['exito' => false, 'mensaje' => 'La categoría o el tipo de torneo no son compatibles con la disciplina seleccionada.'], 400);
    }

    $idTorneo = $modelo->crear(
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
        false,
        (int) $periodoGracia
    );

    registrarAuditoriaApi(
        $contexto['conexion'],
        (int) $contexto['usuario']['id_usuario'],
        'torneo_creado',
        'torneo',
        $idTorneo,
        $nombre
    );

    responderJson([
        'exito' => true,
        'mensaje' => 'Torneo creado correctamente.',
        'torneo' => $modelo->obtenerPorId($idTorneo)
    ], 201);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo crear el torneo.'], 500);
}
