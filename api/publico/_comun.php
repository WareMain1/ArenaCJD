<?php

require_once __DIR__ . '/../../config/Conexion.php';

function responderPublico(array $datos, int $estado = 200): never
{
    http_response_code($estado);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function conexionPublica(): PDO
{
    return (new Conexion())->conectar();
}

function obtenerTorneoPublico(PDO $conexion, int $idTorneo): array|false
{
    $consulta = $conexion->prepare(
        "SELECT t.id_torneo, t.nombre, t.modalidad, t.fecha_inicio, t.hora_inicio, t.fecha_fin,
                t.estado, t.cupo_maximo, t.publicado,
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
         WHERE t.id_torneo = :id_torneo
           AND t.publicado = 1
           AND t.estado IN ('inscripciones','en_curso','finalizado')
         LIMIT 1"
    );
    $consulta->execute([':id_torneo' => $idTorneo]);
    return $consulta->fetch(PDO::FETCH_ASSOC);
}

function idsTorneosPublicos(PDO $conexion): array
{
    $consulta = $conexion->query(
        "SELECT id_torneo FROM torneos
         WHERE publicado = 1
           AND estado IN ('inscripciones','en_curso','finalizado')"
    );
    return array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
}
