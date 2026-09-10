<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador', 'organizador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$ids = is_array($datos['ids'] ?? null) ? array_values(array_unique(array_filter(array_map('intval', $datos['ids'])))) : [];
$estado = (string) ($datos['estado'] ?? '');

if (!$ids || count($ids) > 50) {
    responderJson(['exito' => false, 'mensaje' => 'Selecciona entre 1 y 50 inscripciones.'], 400);
}
if (!in_array($estado, ['aprobada', 'rechazada'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'La acción masiva seleccionada no es válida.'], 400);
}

try {
    $conexion = $contexto['conexion'];
    $idUsuario = (int) $contexto['usuario']['id_usuario'];
    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $conexion->beginTransaction();

    $consulta = $conexion->prepare(
        "SELECT ii.id_inscripcion, ii.id_torneo, ii.estado, t.nombre AS torneo, t.estado AS estado_torneo,
                t.modalidad, t.cupo_maximo, t.id_organizador,
                EXISTS(SELECT 1 FROM enfrentamientos e WHERE e.id_torneo = t.id_torneo LIMIT 1) AS competencia_iniciada
         FROM inscripciones_individuales ii
         INNER JOIN torneos t ON t.id_torneo = ii.id_torneo
         WHERE ii.id_inscripcion = :id
         FOR UPDATE"
    );
    $contarCupo = $conexion->prepare("SELECT COUNT(*) FROM inscripciones_individuales WHERE id_torneo = :id_torneo AND estado = 'aprobada'");
    $actualizar = $conexion->prepare("UPDATE inscripciones_individuales SET estado = :estado WHERE id_inscripcion = :id");

    $actualizadas = 0;
    foreach ($ids as $id) {
        $consulta->execute([':id' => $id]);
        $inscripcion = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!$inscripcion) throw new RuntimeException('Una de las inscripciones seleccionadas ya no existe.');
        if (!$esAdministrador && (int) $inscripcion['id_organizador'] !== $idUsuario) throw new RuntimeException('Una inscripción seleccionada pertenece a un torneo que no administras.');
        if ($inscripcion['modalidad'] !== 'individual') throw new RuntimeException('La selección contiene una inscripción que no es individual.');
        if ($inscripcion['estado'] !== 'pendiente') throw new RuntimeException('Solo se pueden gestionar en lote las inscripciones pendientes.');
        if ($inscripcion['estado_torneo'] !== 'inscripciones' || !empty($inscripcion['competencia_iniciada'])) throw new RuntimeException('Una de las inscripciones pertenece a una competencia que ya comenzó o cerró inscripciones.');

        if ($estado === 'aprobada' && $inscripcion['cupo_maximo'] !== null) {
            $contarCupo->execute([':id_torneo' => (int) $inscripcion['id_torneo']]);
            if ((int) $contarCupo->fetchColumn() >= (int) $inscripcion['cupo_maximo']) {
                throw new RuntimeException('El torneo “' . $inscripcion['torneo'] . '” alcanzó su cupo máximo.');
            }
        }

        $actualizar->execute([':estado' => $estado, ':id' => $id]);
        registrarAuditoriaApi($conexion, $idUsuario, 'inscripcion_actualizada', 'inscripcion_individual', $id, $estado . ' · acción masiva');
        $actualizadas++;
    }

    $conexion->commit();
    responderJson([
        'exito' => true,
        'mensaje' => $actualizadas . ($actualizadas === 1 ? ' inscripción actualizada.' : ' inscripciones actualizadas.'),
        'actualizadas' => $actualizadas
    ]);
} catch (RuntimeException $error) {
    if (isset($conexion) && $conexion->inTransaction()) $conexion->rollBack();
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 409);
} catch (Throwable $error) {
    if (isset($conexion) && $conexion->inTransaction()) $conexion->rollBack();
    responderJson(['exito' => false, 'mensaje' => 'No se pudieron actualizar las inscripciones seleccionadas.'], 500);
}
