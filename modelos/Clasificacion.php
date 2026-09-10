<?php

class Clasificacion
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtener(int $idTorneo): array
    {
        $q = $this->conexion->prepare(
            "SELECT t.id_torneo, t.nombre, t.modalidad, t.estado,
                    d.nombre AS disciplina, tt.nombre AS tipo_torneo
             FROM torneos t
             INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
             INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
             WHERE t.id_torneo = :id
             LIMIT 1"
        );
        $q->execute([':id' => $idTorneo]);
        $torneo = $q->fetch(PDO::FETCH_ASSOC);
        if (!$torneo) {
            throw new DomainException('El torneo no existe.');
        }
        if ((string) $torneo['estado'] === 'cancelado') {
            throw new DomainException('Los torneos cancelados no están disponibles en Clasificación.');
        }

        $participantes = $this->participantes($idTorneo, (string) $torneo['modalidad']);
        $enfrentamientos = $this->enfrentamientos($idTorneo);
        $esEliminacion = $this->esEliminacion((string) $torneo['tipo_torneo']);

        $tabla = [];
        foreach ($participantes as $participante) {
            $id = (int) $participante['id'];
            $tabla[$id] = [
                'id' => $id,
                'nombre' => $participante['nombre'],
                'usuario' => $participante['usuario'],
                'jugados' => 0,
                'ganados' => 0,
                'empatados' => 0,
                'perdidos' => 0,
                'favor' => 0,
                'contra' => 0,
                'diferencia' => 0,
                'puntos' => 0,
                'ronda' => 'Sin disputar',
                'numero_ronda' => 0,
                'estado' => 'clasificado',
                'ultimos' => []
            ];
        }

        $maxRonda = 0;
        $nombreRondaActual = 'Sin disputar';
        $finalizados = 0;
        $pendientes = 0;

        foreach ($enfrentamientos as $e) {
            if ((int) $e['numero_ronda'] >= $maxRonda) {
                $maxRonda = (int) $e['numero_ronda'];
                $nombreRondaActual = (string) $e['ronda'];
            }
            if ($e['estado'] === 'finalizado') {
                $finalizados++;
            } elseif ($e['estado'] !== 'cancelado') {
                $pendientes++;
            }

            $a = $e['id_participante_a'] === null ? null : (int) $e['id_participante_a'];
            $b = $e['id_participante_b'] === null ? null : (int) $e['id_participante_b'];

            foreach ([$a, $b] as $idParticipante) {
                if ($idParticipante !== null && isset($tabla[$idParticipante])) {
                    $tabla[$idParticipante]['numero_ronda'] = max(
                        $tabla[$idParticipante]['numero_ronda'],
                        (int) $e['numero_ronda']
                    );
                    $tabla[$idParticipante]['ronda'] = (string) $e['ronda'];
                }
            }

            if ($e['estado'] !== 'finalizado') {
                continue;
            }

            if ($a !== null && $b === null && isset($tabla[$a])) {
                $tabla[$a]['jugados']++;
                $tabla[$a]['ganados']++;
                $tabla[$a]['favor'] += (int) ($e['puntaje_a'] ?? 1);
                $tabla[$a]['contra'] += (int) ($e['puntaje_b'] ?? 0);
                $tabla[$a]['puntos'] += 3;
                $tabla[$a]['ultimos'][] = $this->resumenPartido($e, 'Pase automático');
                continue;
            }

            if ($a === null || $b === null || !isset($tabla[$a]) || !isset($tabla[$b])) {
                continue;
            }

            $pa = (int) $e['puntaje_a'];
            $pb = (int) $e['puntaje_b'];

            $tabla[$a]['jugados']++;
            $tabla[$b]['jugados']++;
            $tabla[$a]['favor'] += $pa;
            $tabla[$a]['contra'] += $pb;
            $tabla[$b]['favor'] += $pb;
            $tabla[$b]['contra'] += $pa;

            if ($pa > $pb) {
                $tabla[$a]['ganados']++;
                $tabla[$a]['puntos'] += 3;
                $tabla[$b]['perdidos']++;
                if ($esEliminacion) {
                    $tabla[$b]['estado'] = 'eliminado';
                }
            } elseif ($pb > $pa) {
                $tabla[$b]['ganados']++;
                $tabla[$b]['puntos'] += 3;
                $tabla[$a]['perdidos']++;
                if ($esEliminacion) {
                    $tabla[$a]['estado'] = 'eliminado';
                }
            } else {
                $tabla[$a]['empatados']++;
                $tabla[$b]['empatados']++;
                $tabla[$a]['puntos']++;
                $tabla[$b]['puntos']++;
            }

            $tabla[$a]['ultimos'][] = $this->resumenPartido($e, $e['participante_b']);
            $tabla[$b]['ultimos'][] = $this->resumenPartido($e, $e['participante_a']);
        }

        foreach ($tabla as &$fila) {
            $fila['diferencia'] = $fila['favor'] - $fila['contra'];
            $fila['ultimos'] = array_slice(array_reverse($fila['ultimos']), 0, 5);
        }
        unset($fila);

        $clasificacion = array_values($tabla);
        usort($clasificacion, static function (array $a, array $b): int {
            return ($b['puntos'] <=> $a['puntos'])
                ?: ($b['ganados'] <=> $a['ganados'])
                ?: ($b['diferencia'] <=> $a['diferencia'])
                ?: strcasecmp((string) $a['nombre'], (string) $b['nombre']);
        });

        foreach ($clasificacion as $indice => &$fila) {
            $fila['posicion'] = $indice + 1;
        }
        unset($fila);

        $campeon = null;
        $campeonId = null;
        if ($torneo['estado'] === 'finalizado' && $clasificacion) {
            if ($esEliminacion) {
                $ultimaRonda = 0;
                $ultimo = null;
                foreach ($enfrentamientos as $e) {
                    if ($e['estado'] === 'finalizado'
                        && $e['id_participante_a'] !== null
                        && $e['id_participante_b'] !== null
                        && (int) $e['numero_ronda'] >= $ultimaRonda) {
                        $ultimaRonda = (int) $e['numero_ronda'];
                        $ultimo = $e;
                    }
                }
                if ($ultimo) {
                    $ganadorId = (int) $ultimo['puntaje_a'] > (int) $ultimo['puntaje_b']
                        ? (int) $ultimo['id_participante_a']
                        : (int) $ultimo['id_participante_b'];
                    if (isset($tabla[$ganadorId])) {
                        $campeon = $tabla[$ganadorId]['nombre'];
                        $campeonId = $ganadorId;
                    }
                }
            } else {
                $campeon = $clasificacion[0]['nombre'];
                $campeonId = (int) $clasificacion[0]['id'];
            }
        }

        $totalEnfrentamientos = count($enfrentamientos);
        $progreso = $totalEnfrentamientos > 0
            ? (int) round(($finalizados / $totalEnfrentamientos) * 100)
            : 0;

        $rondas = [];
        foreach ($enfrentamientos as $e) {
            $numero = (int) $e['numero_ronda'];
            if (!isset($rondas[$numero])) {
                $rondas[$numero] = [
                    'numero' => $numero,
                    'nombre' => (string) $e['ronda'],
                    'total' => 0,
                    'finalizados' => 0
                ];
            }
            $rondas[$numero]['total']++;
            if ($e['estado'] === 'finalizado') {
                $rondas[$numero]['finalizados']++;
            }
        }

        return [
            'torneo' => $torneo,
            'clasificacion' => $clasificacion,
            'resumen' => [
                'participantes' => count($clasificacion),
                'clasificados' => count(array_filter($clasificacion, static fn(array $f): bool => $f['estado'] !== 'eliminado')),
                'eliminados' => count(array_filter($clasificacion, static fn(array $f): bool => $f['estado'] === 'eliminado')),
                'enfrentamientos' => $totalEnfrentamientos,
                'finalizados' => $finalizados,
                'pendientes' => $pendientes,
                'ronda_actual' => $maxRonda > 0 ? $nombreRondaActual : 'Sin disputar',
                'progreso' => $progreso,
                'campeon' => $campeon,
                'campeon_id' => $campeonId
            ],
            'rondas' => array_values($rondas)
        ];
    }

    private function participantes(int $idTorneo, string $modalidad): array
    {
        if ($modalidad === 'equipo') {
            $sql = "SELECT e.id_equipo AS id, e.nombre, NULL AS usuario
                    FROM inscripciones_equipos ie
                    INNER JOIN equipos e ON e.id_equipo = ie.id_equipo
                    WHERE ie.id_torneo = :id
                      AND ie.estado = 'aprobada'
                     
                    ORDER BY e.nombre";
        } else {
            $sql = "SELECT u.id_usuario AS id, u.nombre_completo AS nombre, u.nombre_usuario AS usuario
                    FROM inscripciones_individuales ii
                    INNER JOIN usuarios u ON u.id_usuario = ii.id_usuario
                    WHERE ii.id_torneo = :id
                      AND ii.estado = 'aprobada'
                     
                    ORDER BY u.nombre_completo";
        }
        $q = $this->conexion->prepare($sql);
        $q->execute([':id' => $idTorneo]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function enfrentamientos(int $idTorneo): array
    {
        $sql = "SELECT e.*,
                       CASE WHEN e.tipo_participante = 'equipo' THEN ea.nombre ELSE ua.nombre_completo END AS participante_a,
                       CASE WHEN e.tipo_participante = 'equipo' THEN eb.nombre ELSE ub.nombre_completo END AS participante_b,
                       CASE WHEN e.tipo_participante = 'equipo' THEN e.id_equipo_a ELSE e.id_usuario_a END AS id_participante_a,
                       CASE WHEN e.tipo_participante = 'equipo' THEN e.id_equipo_b ELSE e.id_usuario_b END AS id_participante_b
                FROM enfrentamientos e
                LEFT JOIN equipos ea ON ea.id_equipo = e.id_equipo_a
                LEFT JOIN equipos eb ON eb.id_equipo = e.id_equipo_b
                LEFT JOIN usuarios ua ON ua.id_usuario = e.id_usuario_a
                LEFT JOIN usuarios ub ON ub.id_usuario = e.id_usuario_b
                WHERE e.id_torneo = :id
                ORDER BY e.numero_ronda, e.orden_ronda";
        $q = $this->conexion->prepare($sql);
        $q->execute([':id' => $idTorneo]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function resumenPartido(array $e, ?string $rival): array
    {
        return [
            'id_enfrentamiento' => (int) $e['id_enfrentamiento'],
            'ronda' => (string) $e['ronda'],
            'rival' => $rival ?: 'Pase automático',
            'puntaje_a' => $e['puntaje_a'] === null ? null : (int) $e['puntaje_a'],
            'puntaje_b' => $e['puntaje_b'] === null ? null : (int) $e['puntaje_b'],
            'fecha_hora' => $e['fecha_hora']
        ];
    }

    private function esEliminacion(string $tipo): bool
    {
        $normalizado = mb_strtolower($tipo, 'UTF-8');
        $normalizado = strtr($normalizado, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        return str_contains($normalizado, 'elimin');
    }
}
