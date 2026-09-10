<?php
require_once __DIR__ . '/_comun.php';

try {
    $conexion = conexionPublica();

    $qProximos = $conexion->query(
        "SELECT e.id_enfrentamiento, e.id_torneo, e.ronda, e.fecha_hora, t.nombre AS torneo, d.nombre AS disciplina,
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
           AND t.estado IN ('inscripciones','en_curso')
           AND e.estado IN ('pendiente','programado','en_curso')
           AND e.fecha_hora IS NOT NULL
           AND e.fecha_hora >= NOW()
         ORDER BY e.fecha_hora ASC, e.id_enfrentamiento ASC
         LIMIT 5"
    );

    $qResultados = $conexion->query(
        "SELECT e.id_enfrentamiento, e.id_torneo, e.ronda, e.fecha_hora, e.puntaje_a, e.puntaje_b,
                t.nombre AS torneo, d.nombre AS disciplina,
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
           AND e.estado = 'finalizado'
         ORDER BY COALESCE(e.fecha_hora, e.fecha_actualizacion) DESC, e.id_enfrentamiento DESC
         LIMIT 5"
    );

    $qTorneos = $conexion->query(
        "SELECT t.id_torneo, t.nombre, t.estado, t.fecha_inicio, t.hora_inicio, d.nombre AS disciplina, c.nombre AS categoria,
                CASE WHEN t.modalidad = 'equipo' THEN
                    (SELECT COUNT(*) FROM inscripciones_equipos ie WHERE ie.id_torneo = t.id_torneo AND ie.estado = 'aprobada')
                ELSE
                    (SELECT COUNT(*) FROM inscripciones_individuales ii WHERE ii.id_torneo = t.id_torneo AND ii.estado = 'aprobada')
                END AS cantidad_inscritos
         FROM torneos t
         INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
         INNER JOIN categorias c ON c.id_categoria = t.id_categoria
         WHERE t.publicado = 1
           AND t.estado IN ('inscripciones','en_curso')
         ORDER BY FIELD(t.estado,'en_curso','inscripciones'), t.fecha_inicio ASC, t.hora_inicio ASC
         LIMIT 5"
    );

    responderPublico([
        'exito' => true,
        'proximos' => $qProximos->fetchAll(PDO::FETCH_ASSOC),
        'resultados' => $qResultados->fetchAll(PDO::FETCH_ASSOC),
        'torneos' => $qTorneos->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $error) {
    responderPublico(['exito' => false, 'mensaje' => 'No se pudo cargar el resumen público.'], 500);
}
