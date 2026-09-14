<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador', 'organizador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$idInscripcion = filter_var($datos['id_inscripcion'] ?? null, FILTER_VALIDATE_INT);
$estado = (string) ($datos['estado'] ?? '');
if (!$idInscripcion || !in_array($estado, ['pendiente', 'aprobada', 'rechazada'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'Inscripción o estado no válidos.'], 400);
}

try {
    $consulta = $contexto['conexion']->prepare(
        "SELECT ie.id_inscripcion, ie.id_torneo, ie.id_equipo, ie.estado,
                t.nombre AS torneo, t.id_organizador, t.cupo_maximo, t.estado AS torneo_estado,
                EXISTS(SELECT 1 FROM enfrentamientos enc WHERE enc.id_torneo = t.id_torneo LIMIT 1) AS competencia_iniciada,
                e.nombre AS equipo
         FROM inscripciones_equipos ie
         INNER JOIN torneos t ON t.id_torneo = ie.id_torneo
         INNER JOIN equipos e ON e.id_equipo = ie.id_equipo
         WHERE ie.id_inscripcion = :id
         LIMIT 1"
    );
    $consulta->execute([':id' => (int) $idInscripcion]);
    $actual = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$actual) {
        responderJson(['exito' => false, 'mensaje' => 'La inscripción no existe.'], 404);
    }

    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $esPropietario = in_array('organizador', $contexto['roles'], true)
        && (int) $actual['id_organizador'] === (int) $contexto['usuario']['id_usuario'];
    if (!$esAdministrador && !$esPropietario) {
        registrarAuditoriaApi(
            $contexto['conexion'],
            (int) $contexto['usuario']['id_usuario'],
            'inscripcion_actualizada',
            'inscripcion_equipo',
            (int) $idInscripcion,
            'Intento no autorizado de gestión de inscripción de equipo',
            'denegado'
        );
        responderJson(['exito' => false, 'mensaje' => 'No tienes permiso para gestionar esta inscripción.'], 403);
    }
    if ((bool) ($actual['competencia_iniciada'] ?? false) || $actual['torneo_estado'] !== 'inscripciones') {
        responderJson(['exito' => false, 'mensaje' => 'La inscripción ya pertenece a una competencia iniciada o cerrada y no puede modificarse.'], 409);
    }

    if ($estado === 'aprobada' && $actual['cupo_maximo'] !== null && $actual['estado'] !== 'aprobada') {
        $cupo = $contexto['conexion']->prepare(
            "SELECT COUNT(*) FROM inscripciones_equipos WHERE id_torneo = :id_torneo AND estado = 'aprobada'"
        );
        $cupo->execute([':id_torneo' => (int) $actual['id_torneo']]);
        if ((int) $cupo->fetchColumn() >= (int) $actual['cupo_maximo']) {
            responderJson(['exito' => false, 'mensaje' => 'El torneo alcanzó su cupo máximo.'], 409);
        }
    }

    $contexto['conexion']->beginTransaction();
    $actualizar = $contexto['conexion']->prepare(
        "UPDATE inscripciones_equipos SET estado = :estado WHERE id_inscripcion = :id"
    );
    $actualizar->execute([':estado' => $estado, ':id' => (int) $idInscripcion]);

    $accionAuditoria = match ($estado) {
        'aprobada' => 'inscripcion_aprobada',
        'rechazada' => 'inscripcion_rechazada',
        default => 'inscripcion_actualizada'
    };
    $detalleAuditoria = $actual['equipo'] . ' · ' . $actual['torneo'] . " · Estado: {$actual['estado']} -> {$estado}";

    registrarAuditoriaApi(
        $contexto['conexion'],
        (int) $contexto['usuario']['id_usuario'],
        $accionAuditoria,
        'inscripcion_equipo',
        (int) $idInscripcion,
        $detalleAuditoria
    );

    $contexto['conexion']->commit();
    responderJson(['exito' => true, 'mensaje' => 'Inscripción del equipo actualizada correctamente.']);
} catch (Throwable $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar la inscripción del equipo.'], 500);
}
