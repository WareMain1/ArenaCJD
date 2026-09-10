<?php

require_once __DIR__ . '/_comun.php';

$idTorneo = filter_input(INPUT_GET, 'id_torneo', FILTER_VALIDATE_INT);

try {
    $conexion = conexionPublica();
    $sql = "SELECT e.id_enfrentamiento, e.id_torneo, e.ronda, e.numero_ronda, e.tipo_participante, e.id_usuario_a, e.id_usuario_b, e.id_equipo_a, e.id_equipo_b, e.fecha_hora,
                   e.puntaje_a, e.puntaje_b, t.nombre AS torneo, d.nombre AS disciplina,
                   CASE WHEN e.tipo_participante = 'equipo' THEN ea.nombre ELSE ua.nombre_completo END AS participante_a,
                   CASE WHEN e.tipo_participante = 'equipo' THEN eb.nombre ELSE ub.nombre_completo END AS participante_b
            FROM enfrentamientos e
            INNER JOIN torneos t ON t.id_torneo = e.id_torneo
            INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
            LEFT JOIN equipos ea ON ea.id_equipo = e.id_equipo_a
            LEFT JOIN equipos eb ON eb.id_equipo = e.id_equipo_b
            LEFT JOIN usuarios ua ON ua.id_usuario = e.id_usuario_a
            LEFT JOIN usuarios ub ON ub.id_usuario = e.id_usuario_b
            WHERE t.publicado = 1
              AND t.estado IN ('en_curso','finalizado')
              AND e.estado = 'finalizado'";
    $parametros = [];
    if ($idTorneo) {
        $sql .= " AND e.id_torneo = :id_torneo";
        $parametros[':id_torneo'] = (int) $idTorneo;
    }
    $sql .= " ORDER BY COALESCE(e.fecha_hora, '1970-01-01 00:00:00') DESC, e.id_enfrentamiento DESC";
    $consulta = $conexion->prepare($sql);
    $consulta->execute($parametros);
    responderPublico(['exito' => true, 'resultados' => $consulta->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $error) {
    responderPublico(['exito' => false, 'mensaje' => 'No se pudieron cargar los resultados públicos.'], 500);
}
