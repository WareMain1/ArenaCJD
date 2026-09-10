<?php

class Calendario
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtener(?int $idOrganizador = null): array
    {
        $sqlTorneos = "SELECT t.id_torneo, t.nombre, t.fecha_inicio, t.hora_inicio, t.fecha_fin, t.estado, t.modalidad,
                    d.nombre AS disciplina, tt.nombre AS tipo_torneo,
                    u.nombre_completo AS organizador, u.nombre_usuario AS organizador_usuario
             FROM torneos t
             INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
             INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
             INNER JOIN usuarios u ON u.id_usuario = t.id_organizador
             WHERE t.estado <> 'cancelado'";
        $parametrosTorneos = [];
        if ($idOrganizador !== null) {
            $sqlTorneos .= " AND t.id_organizador = :id_organizador";
            $parametrosTorneos[':id_organizador'] = $idOrganizador;
        }
        $sqlTorneos .= " ORDER BY t.fecha_inicio, t.id_torneo";
        $torneosQ = $this->conexion->prepare($sqlTorneos);
        $torneosQ->execute($parametrosTorneos);
        $torneos = $torneosQ->fetchAll(PDO::FETCH_ASSOC);

        $sqlEnfrentamientos = "SELECT e.id_enfrentamiento, e.id_torneo, e.ronda, e.numero_ronda, e.estado,
                    e.fecha_hora, e.puntaje_a, e.puntaje_b, e.tipo_participante,
                    t.nombre AS torneo, t.modalidad, d.nombre AS disciplina,
                    CASE WHEN e.tipo_participante='equipo' THEN ea.nombre ELSE ua.nombre_completo END AS participante_a,
                    CASE WHEN e.tipo_participante='equipo' THEN eb.nombre ELSE ub.nombre_completo END AS participante_b
             FROM enfrentamientos e
             INNER JOIN torneos t ON t.id_torneo = e.id_torneo
             INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
             LEFT JOIN equipos ea ON ea.id_equipo = e.id_equipo_a
             LEFT JOIN equipos eb ON eb.id_equipo = e.id_equipo_b
             LEFT JOIN usuarios ua ON ua.id_usuario = e.id_usuario_a
             LEFT JOIN usuarios ub ON ub.id_usuario = e.id_usuario_b
             WHERE e.fecha_hora IS NOT NULL
               AND t.estado <> 'cancelado'";
        $parametrosEnfrentamientos = [];
        if ($idOrganizador !== null) {
            $sqlEnfrentamientos .= " AND t.id_organizador = :id_organizador";
            $parametrosEnfrentamientos[':id_organizador'] = $idOrganizador;
        }
        $sqlEnfrentamientos .= " ORDER BY e.fecha_hora, e.id_enfrentamiento";
        $enfrentamientosQ = $this->conexion->prepare($sqlEnfrentamientos);
        $enfrentamientosQ->execute($parametrosEnfrentamientos);
        $enfrentamientos = $enfrentamientosQ->fetchAll(PDO::FETCH_ASSOC);

        $eventos = [];
        foreach ($torneos as $torneo) {
            if (!empty($torneo['fecha_inicio'])) {
                $eventos[] = [
                    'id' => 'torneo-inicio-' . $torneo['id_torneo'],
                    'tipo' => 'inicio_torneo',
                    'id_torneo' => (int) $torneo['id_torneo'],
                    'torneo' => $torneo['nombre'],
                    'disciplina' => $torneo['disciplina'],
                    'estado' => $torneo['estado'],
                    'fecha_hora' => $torneo['fecha_inicio'] . ' ' . ($torneo['hora_inicio'] ?: '09:00:00'),
                    'titulo' => 'Inicio del torneo',
                    'detalle' => $torneo['tipo_torneo']
                ];
            }
            if (!empty($torneo['fecha_fin'])) {
                $eventos[] = [
                    'id' => 'torneo-fin-' . $torneo['id_torneo'],
                    'tipo' => 'fin_torneo',
                    'id_torneo' => (int) $torneo['id_torneo'],
                    'torneo' => $torneo['nombre'],
                    'disciplina' => $torneo['disciplina'],
                    'estado' => $torneo['estado'],
                    'fecha_hora' => $torneo['fecha_fin'] . ' 23:59:00',
                    'titulo' => 'Finalización del torneo',
                    'detalle' => $torneo['tipo_torneo']
                ];
            }
        }

        foreach ($enfrentamientos as $e) {
            $eventos[] = [
                'id' => 'enfrentamiento-' . $e['id_enfrentamiento'],
                'tipo' => 'enfrentamiento',
                'id_torneo' => (int) $e['id_torneo'],
                'id_enfrentamiento' => (int) $e['id_enfrentamiento'],
                'torneo' => $e['torneo'],
                'disciplina' => $e['disciplina'],
                'estado' => $e['estado'],
                'fecha_hora' => $e['fecha_hora'],
                'titulo' => $e['ronda'],
                'detalle' => trim(($e['participante_a'] ?: 'Pase automático') . ' vs ' . ($e['participante_b'] ?: 'Pase automático')),
                'puntaje_a' => $e['puntaje_a'] === null ? null : (int) $e['puntaje_a'],
                'puntaje_b' => $e['puntaje_b'] === null ? null : (int) $e['puntaje_b']
            ];
        }

        usort($eventos, static fn(array $a, array $b): int => strcmp($a['fecha_hora'], $b['fecha_hora']));

        return [
            'torneos' => $torneos,
            'eventos' => $eventos
        ];
    }
}
