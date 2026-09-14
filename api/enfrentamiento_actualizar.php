<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador', 'organizador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$id = filter_var($datos['id_enfrentamiento'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    responderJson(['exito' => false, 'mensaje' => 'Enfrentamiento no válido.'], 400);
}

try {
    $modelo = new Enfrentamiento($contexto['conexion']);
    $actual = $modelo->obtenerPorId((int) $id);
    if (!$actual) {
        responderJson(['exito' => false, 'mensaje' => 'El enfrentamiento no existe.'], 404);
    }
    if (in_array((string) ($actual['torneo_estado'] ?? ''), ['cancelado', 'finalizado'], true)) {
        responderJson(['exito' => false, 'mensaje' => 'No se pueden modificar enfrentamientos de un torneo cancelado o finalizado.'], 422);
    }
    if ($actual['id_participante_a'] === null || $actual['id_participante_b'] === null) {
        responderJson(['exito' => false, 'mensaje' => 'Los pases automáticos son resueltos por el sistema y no se editan manualmente.'], 422);
    }

    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    if (!$esAdministrador && (int) $actual['id_organizador'] !== (int) $contexto['usuario']['id_usuario']) {
        registrarAuditoriaApi(
            $contexto['conexion'],
            (int) $contexto['usuario']['id_usuario'],
            'enfrentamiento_actualizado',
            'enfrentamiento',
            (int) $id,
            'Intento no autorizado de gestión de enfrentamiento',
            'denegado'
        );
        responderJson(['exito' => false, 'mensaje' => 'No puedes gestionar este torneo.'], 403);
    }

    $fechaRecibida = trim((string) ($datos['fecha_hora'] ?? ''));
    $fechaSql = null;
    if ($fechaRecibida !== '') {
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $fechaRecibida);
        $errores = DateTimeImmutable::getLastErrors();
        if (!$fecha || (is_array($errores) && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
            responderJson(['exito' => false, 'mensaje' => 'Fecha y hora no válidas.'], 422);
        }
        $fechaSql = $fecha->format('Y-m-d H:i:s');
    }

    $estado = (string) ($datos['estado'] ?? 'programado');
    $puntajeA = ($datos['puntaje_a'] ?? '') === '' ? null : filter_var($datos['puntaje_a'], FILTER_VALIDATE_INT);
    $puntajeB = ($datos['puntaje_b'] ?? '') === '' ? null : filter_var($datos['puntaje_b'], FILTER_VALIDATE_INT);
    if ($puntajeA === false || $puntajeB === false) {
        responderJson(['exito' => false, 'mensaje' => 'Los puntajes deben ser números enteros.'], 422);
    }

    if (in_array($estado, ['pendiente', 'cancelado'], true)) {
        $puntajeA = null;
        $puntajeB = null;
    }

    $conexion = $contexto['conexion'];
    $conexion->beginTransaction();
    try {
        $modelo->actualizarEnfrentamiento(
            (int) $id,
            $fechaSql,
            $estado,
            $puntajeA === null ? null : (int) $puntajeA,
            $puntajeB === null ? null : (int) $puntajeB
        );
        $nuevaRonda = $modelo->crearSiguienteRondaSiCorresponde((int) $actual['id_torneo']);

        $accionAuditoria = match ($estado) {
            'finalizado' => 'resultado_actualizado',
            'pendiente_revision' => 'enfrentamiento_enviado_a_revision',
            default => 'enfrentamiento_actualizado'
        };

        $detalleAuditoria = (string) $actual['torneo'] . ' · ' . (string) $actual['ronda'];
        if ($estado === 'finalizado') {
            $detalleAuditoria .= " · Marcador: {$puntajeA}-{$puntajeB}";
        }

        registrarAuditoriaApi(
            $conexion,
            (int) $contexto['usuario']['id_usuario'],
            $accionAuditoria,
            'enfrentamiento',
            (int) $id,
            $detalleAuditoria
        );
        $conexion->commit();
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }

    responderJson([
        'exito' => true,
        'mensaje' => $nuevaRonda
            ? 'Resultado guardado y siguiente ronda generada automáticamente.'
            : 'Enfrentamiento actualizado correctamente.',
        'nueva_ronda' => $nuevaRonda
    ]);
} catch (DomainException | InvalidArgumentException $error) {
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 422);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar el enfrentamiento.'], 500);
}
