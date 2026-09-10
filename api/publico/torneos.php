<?php

require_once __DIR__ . '/_comun.php';

try {
    $conexion = conexionPublica();
    $sql = "SELECT t.id_torneo, t.nombre, t.modalidad, t.fecha_inicio, t.hora_inicio, t.fecha_fin,
                   t.estado, t.cupo_maximo,
                   d.nombre AS disciplina, c.nombre AS categoria, tt.nombre AS tipo_torneo,
                   u.nombre_completo AS organizador,
                   CASE
                       WHEN t.modalidad = 'equipo' THEN (
                           SELECT COUNT(*) FROM inscripciones_equipos ie
                           WHERE ie.id_torneo = t.id_torneo AND ie.estado = 'aprobada'
                       )
                       ELSE (
                           SELECT COUNT(*) FROM inscripciones_individuales ii
                           WHERE ii.id_torneo = t.id_torneo AND ii.estado = 'aprobada'
                       )
                   END AS cantidad_inscritos
            FROM torneos t
            INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
            INNER JOIN categorias c ON c.id_categoria = t.id_categoria
            INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
            INNER JOIN usuarios u ON u.id_usuario = t.id_organizador
            WHERE t.publicado = 1
              AND t.estado IN ('inscripciones','en_curso','finalizado')
            ORDER BY FIELD(t.estado,'en_curso','inscripciones','finalizado'), t.fecha_inicio DESC, t.hora_inicio DESC";
    $consulta = $conexion->query($sql);
    responderPublico(['exito' => true, 'torneos' => $consulta->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $error) {
    responderPublico(['exito' => false, 'mensaje' => 'No se pudieron cargar los torneos públicos.'], 500);
}
