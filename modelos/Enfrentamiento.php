<?php

class Enfrentamiento
{
    






    public const DURACION_ESTIMADA_PARTIDO = 90;
    public const DURACION_ESTIMADA_PARTIDO_MINUTOS = self::DURACION_ESTIMADA_PARTIDO;

    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function tablaDisponible(): bool
    {
        try {
            $consulta = $this->conexion->prepare("SELECT 1 FROM enfrentamientos LIMIT 1");
            $consulta->execute();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function obtenerTodos(?int $idTorneo = null, ?int $idOrganizador = null): array
    {
        $sql = "SELECT e.*, t.nombre AS torneo, t.modalidad, t.id_organizador, t.estado AS torneo_estado,
                       t.periodo_gracia_resultado,
                       d.nombre AS disciplina, tt.nombre AS tipo_torneo,
                       ua.nombre_completo AS usuario_a_nombre, ua.nombre_usuario AS usuario_a_usuario,
                       ub.nombre_completo AS usuario_b_nombre, ub.nombre_usuario AS usuario_b_usuario,
                       ea.nombre AS equipo_a_nombre, eb.nombre AS equipo_b_nombre
                FROM enfrentamientos e
                INNER JOIN torneos t ON t.id_torneo = e.id_torneo
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
                LEFT JOIN usuarios ua ON ua.id_usuario = e.id_usuario_a
                LEFT JOIN usuarios ub ON ub.id_usuario = e.id_usuario_b
                LEFT JOIN equipos ea ON ea.id_equipo = e.id_equipo_a
                LEFT JOIN equipos eb ON eb.id_equipo = e.id_equipo_b";
        $parametros = [];
        $sql .= " WHERE t.estado <> 'cancelado'";
        if ($idTorneo !== null) {
            $sql .= " AND e.id_torneo = :id_torneo";
            $parametros[':id_torneo'] = $idTorneo;
        }
        if ($idOrganizador !== null) {
            $sql .= " AND t.id_organizador = :id_organizador";
            $parametros[':id_organizador'] = $idOrganizador;
        }
        $sql .= " ORDER BY t.fecha_inicio DESC, e.numero_ronda ASC, e.orden_ronda ASC, e.id_enfrentamiento ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$fila) {
            $fila = $this->normalizarFila($fila);
        }
        unset($fila);
        return $filas;
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = "SELECT e.*, t.nombre AS torneo, t.modalidad, t.id_organizador, t.estado AS torneo_estado,
                       t.periodo_gracia_resultado,
                       d.nombre AS disciplina, tt.nombre AS tipo_torneo,
                       ua.nombre_completo AS usuario_a_nombre, ua.nombre_usuario AS usuario_a_usuario,
                       ub.nombre_completo AS usuario_b_nombre, ub.nombre_usuario AS usuario_b_usuario,
                       ea.nombre AS equipo_a_nombre, eb.nombre AS equipo_b_nombre
                FROM enfrentamientos e
                INNER JOIN torneos t ON t.id_torneo = e.id_torneo
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
                LEFT JOIN usuarios ua ON ua.id_usuario = e.id_usuario_a
                LEFT JOIN usuarios ub ON ub.id_usuario = e.id_usuario_b
                LEFT JOIN equipos ea ON ea.id_equipo = e.id_equipo_a
                LEFT JOIN equipos eb ON eb.id_equipo = e.id_equipo_b
                WHERE e.id_enfrentamiento = :id
                LIMIT 1";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id' => $id]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        return $fila ? $this->normalizarFila($fila) : false;
    }

    private function normalizarFila(array $fila): array
    {
        $esEquipo = ($fila['tipo_participante'] ?? '') === 'equipo';
        $fila['participante_a'] = $esEquipo
            ? ($fila['equipo_a_nombre'] ?? 'Pase automático')
            : ($fila['usuario_a_nombre'] ?? 'Pase automático');
        $fila['participante_b'] = $esEquipo
            ? ($fila['equipo_b_nombre'] ?? 'Pase automático')
            : ($fila['usuario_b_nombre'] ?? 'Pase automático');
        $fila['id_participante_a'] = $esEquipo ? ($fila['id_equipo_a'] ?? null) : ($fila['id_usuario_a'] ?? null);
        $fila['id_participante_b'] = $esEquipo ? ($fila['id_equipo_b'] ?? null) : ($fila['id_usuario_b'] ?? null);
        $fila['es_eliminacion'] = $this->esEliminacionDirecta((string) ($fila['tipo_torneo'] ?? ''));
        $fila['es_liga'] = $this->esLiga((string) ($fila['tipo_torneo'] ?? ''));

        $duracionMinutos = self::DURACION_ESTIMADA_PARTIDO_MINUTOS;
        $fila['duracion_estimada_minutos'] = $duracionMinutos;
        $minutosGracia = (int) ($fila['periodo_gracia_resultado'] ?? 60);
        $fila['periodo_gracia_minutos'] = $minutosGracia;

        $fechaHoraStr = (string) ($fila['fecha_hora'] ?? '');
        $fila['fecha_fin_estimada'] = null;
        $fila['fecha_limite_gracia'] = null;
        $fila['segundos_restantes_gracia'] = 0;
        $fila['minutos_restantes_gracia'] = 0;
        $fila['en_gracia'] = false;
        $fila['gracia_vencida'] = false;
        $fila['nivel_alerta'] = 'normal';
        $fila['alerta_texto'] = '';
        $fila['tiempo_restante_formateado'] = '';

        if ($fechaHoraStr !== '') {
            $tsInicio = strtotime($fechaHoraStr);
            if ($tsInicio !== false) {
                $tsFinEstimado = $tsInicio + ($duracionMinutos * 60);
                $tsFinGracia = $tsFinEstimado + ($minutosGracia * 60);
                $ahora = time();

                $fila['fecha_fin_estimada'] = date('Y-m-d H:i:s', $tsFinEstimado);
                $fila['fecha_limite_gracia'] = date('Y-m-d H:i:s', $tsFinGracia);

                $juegoConcluido = ($ahora >= $tsFinEstimado);
                $fila['en_gracia'] = ($juegoConcluido && $ahora < $tsFinGracia);
                $fila['gracia_vencida'] = ($ahora >= $tsFinGracia);

                $restante = $tsFinGracia - $ahora;
                $fila['segundos_restantes_gracia'] = max(0, $restante);
                $fila['minutos_restantes_gracia'] = max(0, (int) ceil($restante / 60));
                $fila['tiempo_restante_formateado'] = $this->formatearTiempoRestante($restante);

                if ($fila['estado'] === 'finalizado') {
                    $fila['nivel_alerta'] = 'completado';
                } elseif ($fila['estado'] === 'cancelado') {
                    $fila['nivel_alerta'] = 'cancelado';
                } elseif ($fila['estado'] === 'pendiente_revision' || ($fila['gracia_vencida'] && !in_array($fila['estado'], ['finalizado', 'cancelado'], true))) {
                    $fila['nivel_alerta'] = 'rojo';
                    $fila['alerta_texto'] = 'Período de gracia vencido. El resultado requiere revisión manual.';
                } elseif ($fila['en_gracia'] || $fila['estado'] === 'en_periodo_gracia') {
                    $fila['nivel_alerta'] = 'naranja';
                    $minutos = $fila['minutos_restantes_gracia'];
                    if ($minutos <= 10) {
                        $fila['alerta_texto'] = 'El período de gracia está por vencer. Quedan ' . $minutos . ' min para registrar o confirmar este resultado.';
                    } else {
                        $fila['alerta_texto'] = 'Período de gracia: tienes ' . $minutos . ' minutos para registrar o confirmar el resultado.';
                    }
                } elseif ($ahora >= $tsInicio) {
                    $fila['nivel_alerta'] = 'amarillo';
                    $fila['alerta_texto'] = 'En juego / Esperando conclusión del tiempo estimado de juego';
                } else {
                    $fila['nivel_alerta'] = 'programado';
                }
            }
        }

        $fila['ganador'] = null;
        if ($fila['estado'] === 'finalizado') {
            if ($fila['puntaje_a'] !== null && $fila['puntaje_b'] !== null) {
                $pa = (int) $fila['puntaje_a'];
                $pb = (int) $fila['puntaje_b'];
                if ($pa > $pb) {
                    $fila['ganador'] = $fila['participante_a'];
                } elseif ($pb > $pa) {
                    $fila['ganador'] = $fila['participante_b'];
                } else {
                    $fila['ganador'] = 'Empate';
                }
            }
        }
        return $fila;
    }

    public function obtenerParticipantesAprobados(int $idTorneo, string $modalidad): array
    {
        if ($modalidad === 'equipo') {
            $sql = "SELECT e.id_equipo AS id, e.nombre
                    FROM inscripciones_equipos ie
                    INNER JOIN equipos e ON e.id_equipo = ie.id_equipo
                    WHERE ie.id_torneo = :id_torneo AND ie.estado = 'aprobada'
                    ORDER BY e.nombre";
        } else {
            $sql = "SELECT u.id_usuario AS id, u.nombre_completo AS nombre
                    FROM inscripciones_individuales ii
                    INNER JOIN usuarios u ON u.id_usuario = ii.id_usuario
                    WHERE ii.id_torneo = :id_torneo AND ii.estado = 'aprobada'
                    ORDER BY u.nombre_completo";
        }
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_torneo' => $idTorneo]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generarPrimeraRonda(int $idTorneo, array $ordenIds): array
    {
        $consultaTorneo = $this->conexion->prepare(
            "SELECT t.id_torneo, t.modalidad, t.estado, tt.nombre AS tipo_torneo
             FROM torneos t
             INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
             WHERE t.id_torneo = :id LIMIT 1"
        );
        $consultaTorneo->execute([':id' => $idTorneo]);
        $torneo = $consultaTorneo->fetch(PDO::FETCH_ASSOC);
        if (!$torneo) {
            throw new DomainException('El torneo no existe.');
        }
        if (!in_array($torneo['estado'], ['inscripciones', 'en_curso'], true)) {
            throw new DomainException('El torneo no está disponible para realizar el sorteo.');
        }

        $participantes = $this->obtenerParticipantesAprobados($idTorneo, $torneo['modalidad']);
        $permitidos = array_map(static fn(array $p): int => (int) $p['id'], $participantes);
        $ordenIds = array_values(array_unique(array_map('intval', $ordenIds)));
        if (count($ordenIds) < 2 || count($ordenIds) !== count($permitidos)) {
            throw new DomainException('El sorteo debe incluir todas las inscripciones aprobadas.');
        }
        sort($permitidos);
        $comprobacion = $ordenIds;
        sort($comprobacion);
        if ($permitidos !== $comprobacion) {
            throw new DomainException('El listado del sorteo no coincide con las inscripciones actuales.');
        }

        $existentes = $this->obtenerTodos($idTorneo);
        foreach ($existentes as $existente) {
            $tieneDosParticipantes = $existente['id_participante_a'] !== null && $existente['id_participante_b'] !== null;
            $yaDisputado = (int) $existente['numero_ronda'] > 1
                || $existente['estado'] === 'en_curso'
                || ($existente['estado'] === 'finalizado' && $tieneDosParticipantes)
                || $existente['fecha_hora'] !== null;
            if ($yaDisputado) {
                throw new DomainException('El torneo ya tiene enfrentamientos disputados o programados. No se puede volver a sortear sin perder resultados.');
            }
        }

        $propietarioTransaccion = !$this->conexion->inTransaction();
        if ($propietarioTransaccion) {
            $this->conexion->beginTransaction();
        }

        try {
            $borrar = $this->conexion->prepare("DELETE FROM enfrentamientos WHERE id_torneo = :id_torneo");
            $borrar->execute([':id_torneo' => $idTorneo]);
            if ($this->esLiga((string) $torneo['tipo_torneo'])) {
                $this->insertarCalendarioLiga($idTorneo, $ordenIds, (string) $torneo['modalidad']);
            } else {
                $this->insertarRonda($idTorneo, 1, $ordenIds, $torneo['modalidad']);
            }
            $this->conexion->prepare("UPDATE torneos SET estado = 'en_curso' WHERE id_torneo = :id AND estado = 'inscripciones'")
                ->execute([':id' => $idTorneo]);
            if ($propietarioTransaccion) {
                $this->conexion->commit();
            }
            return $this->obtenerTodos($idTorneo);
        } catch (Throwable $e) {
            if ($propietarioTransaccion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

    private function insertarRonda(int $idTorneo, int $numeroRonda, array $ids, string $modalidad): void
    {
        $insertar = $this->conexion->prepare(
            "INSERT INTO enfrentamientos
             (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante,
              id_usuario_a, id_usuario_b, id_equipo_a, id_equipo_b,
              puntaje_a, puntaje_b, estado)
             VALUES
             (:id_torneo, :numero_ronda, :orden, :ronda, :tipo,
              :ua, :ub, :ea, :eb, :pa, :pb, :estado)"
        );
        $esEquipo = $modalidad === 'equipo';
        $orden = 1;
        for ($i = 0, $total = count($ids); $i < $total; $i += 2) {
            $a = (int) $ids[$i];
            $b = isset($ids[$i + 1]) ? (int) $ids[$i + 1] : null;
            $insertar->execute([
                ':id_torneo' => $idTorneo,
                ':numero_ronda' => $numeroRonda,
                ':orden' => $orden++,
                ':ronda' => 'Ronda ' . $numeroRonda,
                ':tipo' => $esEquipo ? 'equipo' : 'individual',
                ':ua' => $esEquipo ? null : $a,
                ':ub' => $esEquipo ? null : $b,
                ':ea' => $esEquipo ? $a : null,
                ':eb' => $esEquipo ? $b : null,
                ':pa' => $b === null ? 1 : null,
                ':pb' => $b === null ? 0 : null,
                ':estado' => $b === null ? 'finalizado' : 'pendiente'
            ]);
        }
    }

    private function insertarCalendarioLiga(int $idTorneo, array $ids, string $modalidad): void
    {
        $jugadores = array_values($ids);
        if (count($jugadores) % 2 !== 0) $jugadores[] = null;
        $n = count($jugadores);
        if ($n < 2) return;

        $insertar = $this->conexion->prepare(
            "INSERT INTO enfrentamientos
             (id_torneo, numero_ronda, orden_ronda, ronda, tipo_participante,
              id_usuario_a, id_usuario_b, id_equipo_a, id_equipo_b,
              puntaje_a, puntaje_b, estado)
             VALUES
             (:id_torneo, :numero_ronda, :orden, :ronda, :tipo,
              :ua, :ub, :ea, :eb, NULL, NULL, 'pendiente')"
        );
        $esEquipo = $modalidad === 'equipo';

        for ($ronda = 1; $ronda <= $n - 1; $ronda++) {
            $orden = 1;
            for ($i = 0; $i < $n / 2; $i++) {
                $a = $jugadores[$i];
                $b = $jugadores[$n - 1 - $i];
                if ($a === null || $b === null) continue;  
                $insertar->execute([
                    ':id_torneo' => $idTorneo,
                    ':numero_ronda' => $ronda,
                    ':orden' => $orden++,
                    ':ronda' => 'Fecha ' . $ronda,
                    ':tipo' => $esEquipo ? 'equipo' : 'individual',
                    ':ua' => $esEquipo ? null : (int) $a,
                    ':ub' => $esEquipo ? null : (int) $b,
                    ':ea' => $esEquipo ? (int) $a : null,
                    ':eb' => $esEquipo ? (int) $b : null,
                ]);
            }
             
            $fijo = array_shift($jugadores);
            $ultimo = array_pop($jugadores);
            array_unshift($jugadores, $fijo);
            array_splice($jugadores, 1, 0, [$ultimo]);
        }
    }

    public function actualizarEnfrentamiento(
        int $id,
        ?string $fechaHora,
        string $estado,
        ?int $puntajeA,
        ?int $puntajeB
    ): bool {
        if (!in_array($estado, ['pendiente', 'programado', 'en_curso', 'en_periodo_gracia', 'pendiente_revision', 'finalizado', 'cancelado'], true)) {
            throw new InvalidArgumentException('Estado no válido.');
        }
        if ($estado === 'finalizado' && ($puntajeA === null || $puntajeB === null)) {
            throw new InvalidArgumentException('Debes indicar ambos resultados para finalizar el enfrentamiento.');
        }
        if (in_array($estado, ['programado', 'en_curso', 'en_periodo_gracia', 'pendiente_revision', 'finalizado'], true) && !$fechaHora) {
            throw new InvalidArgumentException('Debes asignar fecha y hora antes de programar, iniciar o finalizar el enfrentamiento.');
        }
        if (($puntajeA !== null && $puntajeA < 0) || ($puntajeB !== null && $puntajeB < 0)) {
            throw new InvalidArgumentException('El resultado no puede ser negativo.');
        }
        if (in_array($estado, ['pendiente', 'cancelado'], true)) {
            $puntajeA = null;
            $puntajeB = null;
        }

        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            throw new DomainException('El enfrentamiento no existe.');
        }

        $esEliminacion = $this->esEliminacionDirecta((string) $actual['tipo_torneo']);
        if ($esEliminacion && $estado === 'finalizado'
            && $actual['id_participante_a'] !== null && $actual['id_participante_b'] !== null
            && $puntajeA === $puntajeB) {
            throw new InvalidArgumentException('En eliminación directa el resultado final no puede terminar empatado.');
        }

        $esLiga = $this->esLiga((string) ($actual['tipo_torneo'] ?? ''));
        if (!$esLiga) {
            $consultaPosteriores = $this->conexion->prepare(
                "SELECT COUNT(*) FROM enfrentamientos
                 WHERE id_torneo = :id_torneo AND numero_ronda > :numero_ronda"
            );
            $consultaPosteriores->execute([
                ':id_torneo' => (int) $actual['id_torneo'],
                ':numero_ronda' => (int) $actual['numero_ronda']
            ]);
            $hayRondaPosterior = (int) $consultaPosteriores->fetchColumn() > 0;
            if ($hayRondaPosterior) {
                $cambiaResultado = $estado !== $actual['estado']
                    || $puntajeA !== ($actual['puntaje_a'] === null ? null : (int) $actual['puntaje_a'])
                    || $puntajeB !== ($actual['puntaje_b'] === null ? null : (int) $actual['puntaje_b']);
                if ($cambiaResultado) {
                    throw new DomainException('Este resultado ya generó una ronda posterior y no puede modificarse.');
                }
            }
        }

        $consulta = $this->conexion->prepare(
            "UPDATE enfrentamientos
             SET fecha_hora = :fecha_hora,
                 estado = :estado,
                 puntaje_a = :pa,
                 puntaje_b = :pb
             WHERE id_enfrentamiento = :id"
        );
        $consulta->bindValue(':fecha_hora', $fechaHora ?: null, $fechaHora ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $consulta->bindValue(':estado', $estado);
        $consulta->bindValue(':pa', $puntajeA, $puntajeA === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $consulta->bindValue(':pb', $puntajeB, $puntajeB === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        return true;
    }

    public function crearSiguienteRondaSiCorresponde(int $idTorneo): int
    {
        $torneoQ = $this->conexion->prepare(
            "SELECT t.nombre, t.modalidad, tt.nombre AS tipo_torneo
             FROM torneos t
             INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
             WHERE t.id_torneo = :id LIMIT 1"
        );
        $torneoQ->execute([':id' => $idTorneo]);
        $torneo = $torneoQ->fetch(PDO::FETCH_ASSOC);
        if (!$torneo) {
            return 0;
        }

        if ($this->esLiga((string) $torneo['tipo_torneo'])) {
            $qLiga = $this->conexion->prepare("SELECT COUNT(*) AS total, SUM(estado = 'finalizado') AS finalizados FROM enfrentamientos WHERE id_torneo = :id");
            $qLiga->execute([':id' => $idTorneo]);
            $estadoLiga = $qLiga->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'finalizados' => 0];
            if ((int) $estadoLiga['total'] > 0 && (int) $estadoLiga['total'] === (int) $estadoLiga['finalizados']) {
                $this->conexion->prepare("UPDATE torneos SET estado = 'finalizado' WHERE id_torneo = :id")
                    ->execute([':id' => $idTorneo]);
                $this->registrarAuditoria(
                    null,
                    'torneo_finalizado',
                    'torneo',
                    $idTorneo,
                    "Torneo {$torneo['nombre']} finalizado automáticamente al concluir todos los enfrentamientos"
                );
            }
            return 0;
        }

        $q = $this->conexion->prepare("SELECT MAX(numero_ronda) FROM enfrentamientos WHERE id_torneo = :id");
        $q->execute([':id' => $idTorneo]);
        $ronda = (int) $q->fetchColumn();
        if ($ronda < 1) {
            return 0;
        }

        $q = $this->conexion->prepare(
            "SELECT * FROM enfrentamientos
             WHERE id_torneo = :id AND numero_ronda = :r
             ORDER BY orden_ronda"
        );
        $q->execute([':id' => $idTorneo, ':r' => $ronda]);
        $actual = $q->fetchAll(PDO::FETCH_ASSOC);
        if (!$actual) {
            return 0;
        }
        foreach ($actual as $partido) {
            if ($partido['estado'] !== 'finalizado') {
                return 0;
            }
        }

        $q = $this->conexion->prepare(
            "SELECT COUNT(*) FROM enfrentamientos WHERE id_torneo = :id AND numero_ronda = :r"
        );
        $q->execute([':id' => $idTorneo, ':r' => $ronda + 1]);
        if ((int) $q->fetchColumn() > 0) {
            return 0;
        }

        if ($this->esEliminacionDirecta((string) $torneo['tipo_torneo'])) {
            return $this->crearSiguienteRondaEliminacion($idTorneo, $ronda, $actual, (string) $torneo['modalidad']);
        }

        return $this->crearSiguienteRondaSuiza($idTorneo, $ronda, (string) $torneo['modalidad']);
    }

    private function crearSiguienteRondaEliminacion(int $idTorneo, int $ronda, array $actual, string $modalidad): int
    {
        $ganadores = [];
        $esEquipo = $modalidad === 'equipo';
        foreach ($actual as $partido) {
            $lado = null;
            if ((int) $partido['puntaje_a'] > (int) $partido['puntaje_b']) {
                $lado = 'a';
            } elseif ((int) $partido['puntaje_b'] > (int) $partido['puntaje_a']) {
                $lado = 'b';
            }

            if ($lado === null) {
                return 0;
            }

            $id = $partido[$esEquipo ? ('id_equipo_' . $lado) : ('id_usuario_' . $lado)];
            if ($id !== null) {
                $ganadores[] = (int) $id;
            }
        }

        if (count($ganadores) <= 1) {
            $this->conexion->prepare("UPDATE torneos SET estado = 'finalizado' WHERE id_torneo = :id")
                ->execute([':id' => $idTorneo]);
            $this->registrarAuditoria(
                null,
                'torneo_finalizado',
                'torneo',
                $idTorneo,
                "Torneo finalizado automáticamente al concluir la final de eliminación directa"
            );
            return 0;
        }

        $nueva = $ronda + 1;
        $this->insertarRonda($idTorneo, $nueva, $ganadores, $modalidad);
        $this->registrarAuditoria(
            null,
            'ronda_generada',
            'torneo',
            $idTorneo,
            "Ronda {$nueva} generada automáticamente (Eliminación directa)"
        );
        return $nueva;
    }

    private function crearSiguienteRondaSuiza(int $idTorneo, int $ronda, string $modalidad): int
    {
        $participantes = $this->obtenerParticipantesAprobados($idTorneo, $modalidad);
        $ids = array_map(static fn(array $p): int => (int) $p['id'], $participantes);
        $total = count($ids);
        if ($total < 2) {
            return 0;
        }

        $maxRondas = max(1, (int) ceil(log($total, 2)));
        if ($ronda >= $maxRondas) {
            $this->conexion->prepare("UPDATE torneos SET estado = 'finalizado' WHERE id_torneo = :id")
                ->execute([':id' => $idTorneo]);
            $this->registrarAuditoria(
                null,
                'torneo_finalizado',
                'torneo',
                $idTorneo,
                "Torneo finalizado automáticamente tras completar las {$maxRondas} rondas del Sistema Suizo"
            );
            return 0;
        }

        $esEquipo = $modalidad === 'equipo';
        $colA = $esEquipo ? 'id_equipo_a' : 'id_usuario_a';
        $colB = $esEquipo ? 'id_equipo_b' : 'id_usuario_b';
        $q = $this->conexion->prepare(
            "SELECT {$colA} AS a, {$colB} AS b, puntaje_a, puntaje_b
             FROM enfrentamientos
             WHERE id_torneo = :id AND estado = 'finalizado'"
        );
        $q->execute([':id' => $idTorneo]);
        $jugados = $q->fetchAll(PDO::FETCH_ASSOC);

        $puntos = array_fill_keys($ids, 0);
        $rivales = [];
        foreach ($ids as $id) {
            $rivales[$id] = [];
        }
        foreach ($jugados as $partido) {
            $a = $partido['a'] === null ? null : (int) $partido['a'];
            $b = $partido['b'] === null ? null : (int) $partido['b'];
            if ($a !== null && $b !== null) {
                $rivales[$a][$b] = true;
                $rivales[$b][$a] = true;
            }
            if ($a !== null && $b === null) {
                $puntos[$a] = ($puntos[$a] ?? 0) + 3;
                continue;
            }
            if ($a === null || $b === null) {
                continue;
            }
            $pa = (int) $partido['puntaje_a'];
            $pb = (int) $partido['puntaje_b'];
            if ($pa > $pb) {
                $puntos[$a] = ($puntos[$a] ?? 0) + 3;
            } elseif ($pb > $pa) {
                $puntos[$b] = ($puntos[$b] ?? 0) + 3;
            } else {
                $puntos[$a] = ($puntos[$a] ?? 0) + 1;
                $puntos[$b] = ($puntos[$b] ?? 0) + 1;
            }
        }

        usort($ids, static function (int $a, int $b) use ($puntos): int {
            $comparacion = ($puntos[$b] ?? 0) <=> ($puntos[$a] ?? 0);
            return $comparacion !== 0 ? $comparacion : ($a <=> $b);
        });

        $orden = [];
        while ($ids) {
            $a = array_shift($ids);
            $orden[] = $a;
            if (!$ids) {
                break;
            }
            $indiceRival = null;
            foreach ($ids as $indice => $candidato) {
                if (empty($rivales[$a][$candidato])) {
                    $indiceRival = $indice;
                    break;
                }
            }
            if ($indiceRival === null) {
                $indiceRival = 0;
            }
            $b = $ids[$indiceRival];
            array_splice($ids, $indiceRival, 1);
            $orden[] = $b;
        }

        $nueva = $ronda + 1;
        $this->insertarRonda($idTorneo, $nueva, $orden, $modalidad);
        $this->registrarAuditoria(
            null,
            'ronda_generada',
            'torneo',
            $idTorneo,
            "Ronda {$nueva} generada automáticamente (Sistema Suizo)"
        );
        return $nueva;
    }

    private function esLiga(string $tipo): bool
    {
        $normalizado = mb_strtolower($tipo, 'UTF-8');
        $normalizado = strtr($normalizado, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        return $normalizado === 'liga' || str_contains($normalizado, 'todos contra todos');
    }

    private function esEliminacionDirecta(string $tipo): bool
    {
        $normalizado = mb_strtolower($tipo, 'UTF-8');
        $normalizado = strtr($normalizado, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        return str_contains($normalizado, 'elimin');
    }

    public function resumenPorTorneo(?int $idOrganizador = null): array
    {
        $sql = "SELECT t.id_torneo, t.nombre, t.modalidad, t.estado, d.nombre AS disciplina,
                       CASE WHEN t.modalidad = 'equipo'
                            THEN (SELECT COUNT(*) FROM inscripciones_equipos ie WHERE ie.id_torneo = t.id_torneo AND ie.estado = 'aprobada')
                            ELSE (SELECT COUNT(*) FROM inscripciones_individuales ii WHERE ii.id_torneo = t.id_torneo AND ii.estado = 'aprobada') END AS aprobados,
                       (SELECT COUNT(*) FROM enfrentamientos e WHERE e.id_torneo = t.id_torneo) AS enfrentamientos,
                       (SELECT COUNT(*) FROM enfrentamientos e WHERE e.id_torneo = t.id_torneo AND e.estado = 'finalizado') AS finalizados
                FROM torneos t
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                WHERE t.estado <> 'cancelado'";
        $parametros = [];
        if ($idOrganizador !== null) {
            $sql .= " AND t.id_organizador = :id_organizador";
            $parametros[':id_organizador'] = $idOrganizador;
        }
        $sql .= " ORDER BY t.fecha_inicio DESC, t.id_torneo DESC";
        $q = $this->conexion->prepare($sql);
        $q->execute($parametros);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerResultadosPendientes(?int $idOrganizador = null): array
    {
        $todos = $this->obtenerTodos(null, $idOrganizador);
        $pendientes = [];

        foreach ($todos as $e) {
            if (in_array($e['estado'], ['programado', 'en_curso', 'en_periodo_gracia', 'pendiente_revision'], true)) {
                $pendientes[] = $e;
            } elseif ($e['estado'] === 'pendiente' && !empty($e['fecha_hora'])) {
                $pendientes[] = $e;
            }
        }

         
         
         
         
        usort($pendientes, static function (array $a, array $b): int {
            $prioridadA = ($a['estado'] === 'pendiente_revision' || $a['gracia_vencida']) ? 0 : ($a['en_gracia'] ? 1 : 2);
            $prioridadB = ($b['estado'] === 'pendiente_revision' || $b['gracia_vencida']) ? 0 : ($b['en_gracia'] ? 1 : 2);

            if ($prioridadA !== $prioridadB) {
                return $prioridadA <=> $prioridadB;
            }

            if ($prioridadA === 1) {
                return ($a['segundos_restantes_gracia'] <=> $b['segundos_restantes_gracia']);
            }

            return strcmp((string) ($a['fecha_hora'] ?? ''), (string) ($b['fecha_hora'] ?? ''));
        });

        return $pendientes;
    }

    public function obtenerConteoPendientes(?int $idOrganizador = null): array
    {
        $pendientes = $this->obtenerResultadosPendientes($idOrganizador);
        $requierenIntervencion = 0;
        $enGracia = 0;

        foreach ($pendientes as $p) {
            if ($p['estado'] === 'pendiente_revision' || $p['gracia_vencida']) {
                $requierenIntervencion++;
            } elseif ($p['en_gracia'] || $p['estado'] === 'en_periodo_gracia') {
                $enGracia++;
            }
        }

        return [
            'total_pendientes' => count($pendientes),
            'requieren_intervencion' => $requierenIntervencion,
            'en_gracia' => $enGracia
        ];
    }

    public function procesarPeriodosGracia(?int $idTorneo = null, ?int $idUsuarioOperador = null): array
    {
        $sql = "SELECT e.*, t.nombre AS torneo, t.modalidad, t.id_organizador, t.estado AS torneo_estado,
                       t.periodo_gracia_resultado,
                       d.nombre AS disciplina, tt.nombre AS tipo_torneo,
                       ua.nombre_completo AS usuario_a_nombre, ub.nombre_completo AS usuario_b_nombre,
                       ea.nombre AS equipo_a_nombre, eb.nombre AS equipo_b_nombre
                FROM enfrentamientos e
                INNER JOIN torneos t ON t.id_torneo = e.id_torneo
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
                LEFT JOIN usuarios ua ON ua.id_usuario = e.id_usuario_a
                LEFT JOIN usuarios ub ON ub.id_usuario = e.id_usuario_b
                LEFT JOIN equipos ea ON ea.id_equipo = e.id_equipo_a
                LEFT JOIN equipos eb ON eb.id_equipo = e.id_equipo_b
                WHERE t.estado = 'en_curso'
                  AND e.fecha_hora IS NOT NULL
                  AND e.estado NOT IN ('finalizado', 'cancelado')";

        $params = [];
        if ($idTorneo !== null) {
            $sql .= " AND e.id_torneo = :id_torneo";
            $params[':id_torneo'] = $idTorneo;
        }

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($params);
        $candidatos = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $reporte = [
            'evaluados' => count($candidatos),
            'entraron_en_gracia' => 0,
            'enviados_a_revision' => 0,
            'detalles' => []
        ];

        $ahora = time();

        foreach ($candidatos as $cand) {
            $cand = $this->normalizarFila($cand);
            $id = (int) $cand['id_enfrentamiento'];
            $tsInicio = strtotime((string) $cand['fecha_hora']);
            if ($tsInicio === false) continue;

            $duracionMinutos = self::DURACION_ESTIMADA_PARTIDO_MINUTOS;
            $tsFinEstimado = $tsInicio + ($duracionMinutos * 60);
            $minutosGracia = (int) ($cand['periodo_gracia_minutos'] ?? 60);
            $tsFinGracia = $tsFinEstimado + ($minutosGracia * 60);

            if ($ahora >= $tsFinEstimado && $ahora < $tsFinGracia) {
                if (in_array($cand['estado'], ['programado', 'en_curso', 'pendiente'], true)) {
                    $qGracia = $this->conexion->prepare(
                        "UPDATE enfrentamientos
                         SET estado = 'en_periodo_gracia'
                         WHERE id_enfrentamiento = :id
                           AND estado NOT IN ('finalizado', 'cancelado', 'pendiente_revision')"
                    );
                    $qGracia->execute([':id' => $id]);
                    if ($qGracia->rowCount() > 0) {
                        $reporte['entraron_en_gracia']++;
                        $reporte['detalles'][] = [
                            'id_enfrentamiento' => $id,
                            'accion' => 'ENTRO_EN_PERIODO_GRACIA',
                            'motivo' => "Concluyó el tiempo estimado de juego (90 min). Quedan {$minutosGracia} min para registrar o confirmar el resultado"
                        ];
                        $this->registrarAuditoria(
                            $idUsuarioOperador,
                            'PERIODO_GRACIA_INICIADO',
                            'enfrentamiento',
                            $id,
                            "Torneo: {$cand['torneo']} · {$cand['ronda']} · Fin estimado cumplido · Gracia: {$minutosGracia} min · Límite para registrar o confirmar resultado: " . date('Y-m-d H:i:s', $tsFinGracia)
                        );
                    }
                }
                continue;
            }

            if ($ahora >= $tsFinGracia) {
                if ($cand['estado'] === 'pendiente_revision') {
                    continue;
                }

                $transaccionPropia = false;
                if (!$this->conexion->inTransaction()) {
                    $this->conexion->beginTransaction();
                    $transaccionPropia = true;
                }
                try {
                    $lockQ = $this->conexion->prepare("SELECT * FROM enfrentamientos WHERE id_enfrentamiento = :id FOR UPDATE");
                    $lockQ->execute([':id' => $id]);
                    $filaBloqueada = $lockQ->fetch(PDO::FETCH_ASSOC);

                    if (!$filaBloqueada || in_array($filaBloqueada['estado'], ['finalizado', 'cancelado', 'pendiente_revision'], true)) {
                        if ($transaccionPropia && $this->conexion->inTransaction()) {
                            $this->conexion->commit();
                        }
                        continue;
                    }

                    $pa = $filaBloqueada['puntaje_a'] !== null ? (int) $filaBloqueada['puntaje_a'] : null;
                    $pb = $filaBloqueada['puntaje_b'] !== null ? (int) $filaBloqueada['puntaje_b'] : null;
                    $tieneMarcador = $pa !== null && $pb !== null;
                    $detalleResultado = $tieneMarcador
                        ? "Marcador pendiente de confirmación manual: {$pa}-{$pb}"
                        : 'Sin resultado confirmado';

                    $this->marcarPendienteRevision($id);
                    $this->registrarAuditoria(
                        $idUsuarioOperador,
                        'PERIODO_GRACIA_VENCIDO',
                        'enfrentamiento',
                        $id,
                        "Torneo: {$cand['torneo']} · {$cand['ronda']} · Período de gracia vencido · {$detalleResultado} · Requiere revisión manual"
                    );
                    if ($transaccionPropia && $this->conexion->inTransaction()) {
                        $this->conexion->commit();
                    }
                    $reporte['enviados_a_revision']++;
                    $reporte['detalles'][] = [
                        'id_enfrentamiento' => $id,
                        'accion' => 'ENVIADO_A_REVISION',
                        'motivo' => $tieneMarcador ? 'VENCIDO_CON_MARCADOR_PENDIENTE' : 'VENCIDO_SIN_RESULTADO'
                    ];
                } catch (Throwable $error) {
                    if ($transaccionPropia && $this->conexion->inTransaction()) {
                        $this->conexion->rollBack();
                    }
                    throw $error;
                }
            }
        }

        return $reporte;
    }

    private function marcarPendienteRevision(int $idEnfrentamiento): void
    {
        $q = $this->conexion->prepare(
            "UPDATE enfrentamientos
             SET estado = 'pendiente_revision'
             WHERE id_enfrentamiento = :id"
        );
        $q->execute([
            ':id' => $idEnfrentamiento
        ]);
    }

    private function registrarAuditoria(?int $idUsuario, string $accion, string $entidad, string|int|null $idEntidad, ?string $detalle, string $resultado = 'exito'): void
    {
        try {
            $consulta = $this->conexion->prepare(
                "INSERT INTO auditoria (id_usuario, accion, entidad, id_entidad, detalle, resultado)
                 VALUES (:id_usuario, :accion, :entidad, :id_entidad, :detalle, :resultado)"
            );
            $consulta->execute([
                ':id_usuario' => $idUsuario,
                ':accion' => mb_substr($accion, 0, 80),
                ':entidad' => mb_substr($entidad, 0, 60),
                ':id_entidad' => $idEntidad === null ? null : mb_substr((string) $idEntidad, 0, 80),
                ':detalle' => $detalle === null ? null : mb_substr($detalle, 0, 500),
                ':resultado' => in_array($resultado, ['exito', 'error', 'denegado'], true) ? $resultado : 'exito'
            ]);
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                throw $error;
            }
        }
    }

    private function formatearTiempoRestante(int $segundos): string
    {
        if ($segundos <= 0) {
            return '00:00:00';
        }
        $horas = (int) floor($segundos / 3600);
        $minutos = (int) floor(($segundos % 3600) / 60);
        $segs = $segundos % 60;
        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segs);
    }
}
