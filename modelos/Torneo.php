<?php

class Torneo
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTodos(?int $idOrganizador = null): array
    {
        $sql = "SELECT
                    t.id_torneo,
                    t.nombre,
                    t.id_disciplina,
                    t.id_categoria,
                    t.id_tipo_torneo,
                    t.id_organizador,
                    t.modalidad,
                    t.fecha_inicio,
                    t.hora_inicio,
                    t.fecha_fin,
                    t.estado,
                    t.publicado,
                    t.cupo_maximo,
                    t.periodo_gracia_resultado,
                    t.fecha_creacion,
                    d.nombre AS disciplina,
                    c.nombre AS categoria,
                    tt.nombre AS tipo_torneo,
                    u.nombre_completo AS organizador,
                    u.nombre_usuario AS organizador_usuario,
                    CASE
                        WHEN t.modalidad = 'equipo' THEN (
                            SELECT COUNT(*)
                            FROM inscripciones_equipos ie
                            WHERE ie.id_torneo = t.id_torneo
                              AND ie.estado = 'aprobada'
                        )
                        ELSE (
                            SELECT COUNT(*)
                            FROM inscripciones_individuales ii
                            WHERE ii.id_torneo = t.id_torneo
                              AND ii.estado = 'aprobada'
                        )
                    END AS cantidad_inscritos,
                    CASE
                        WHEN t.modalidad = 'equipo' THEN (
                            SELECT COUNT(*)
                            FROM inscripciones_equipos ie
                            WHERE ie.id_torneo = t.id_torneo
                              AND ie.estado <> 'rechazada'
                        )
                        ELSE (
                            SELECT COUNT(*)
                            FROM inscripciones_individuales ii
                            WHERE ii.id_torneo = t.id_torneo
                              AND ii.estado <> 'rechazada'
                        )
                    END AS cupo_ocupado,
                    (SELECT COUNT(*)
                     FROM enfrentamientos e
                     WHERE e.id_torneo = t.id_torneo) AS total_enfrentamientos,
                    (SELECT COUNT(*)
                     FROM enfrentamientos e
                     WHERE e.id_torneo = t.id_torneo
                       AND e.estado = 'finalizado') AS enfrentamientos_finalizados
                FROM torneos t
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN categorias c ON c.id_categoria = t.id_categoria
                INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
                INNER JOIN usuarios u ON u.id_usuario = t.id_organizador";

        $parametros = [];
        if ($idOrganizador !== null) {
            $sql .= " WHERE t.id_organizador = :id_organizador";
            $parametros[':id_organizador'] = $idOrganizador;
        }
        $sql .= " ORDER BY t.fecha_creacion DESC, t.id_torneo DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $idTorneo): array|false
    {
        $sql = "SELECT
                    t.id_torneo,
                    t.nombre,
                    t.id_disciplina,
                    t.id_categoria,
                    t.id_tipo_torneo,
                    t.id_organizador,
                    t.modalidad,
                    t.fecha_inicio,
                    t.hora_inicio,
                    t.fecha_fin,
                    t.estado,
                    t.publicado,
                    t.cupo_maximo,
                    t.periodo_gracia_resultado,
                    t.fecha_creacion,
                    d.nombre AS disciplina,
                    c.nombre AS categoria,
                    tt.nombre AS tipo_torneo,
                    u.nombre_completo AS organizador,
                    u.nombre_usuario AS organizador_usuario,
                    CASE
                        WHEN t.modalidad = 'equipo' THEN (
                            SELECT COUNT(*)
                            FROM inscripciones_equipos ie
                            WHERE ie.id_torneo = t.id_torneo
                              AND ie.estado = 'aprobada'
                        )
                        ELSE (
                            SELECT COUNT(*)
                            FROM inscripciones_individuales ii
                            WHERE ii.id_torneo = t.id_torneo
                              AND ii.estado = 'aprobada'
                        )
                    END AS cantidad_inscritos,
                    CASE
                        WHEN t.modalidad = 'equipo' THEN (
                            SELECT COUNT(*)
                            FROM inscripciones_equipos ie
                            WHERE ie.id_torneo = t.id_torneo
                              AND ie.estado <> 'rechazada'
                        )
                        ELSE (
                            SELECT COUNT(*)
                            FROM inscripciones_individuales ii
                            WHERE ii.id_torneo = t.id_torneo
                              AND ii.estado <> 'rechazada'
                        )
                    END AS cupo_ocupado,
                    (SELECT COUNT(*)
                     FROM enfrentamientos e
                     WHERE e.id_torneo = t.id_torneo) AS total_enfrentamientos,
                    (SELECT COUNT(*)
                     FROM enfrentamientos e
                     WHERE e.id_torneo = t.id_torneo
                       AND e.estado = 'finalizado') AS enfrentamientos_finalizados
                FROM torneos t
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN categorias c ON c.id_categoria = t.id_categoria
                INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
                INNER JOIN usuarios u ON u.id_usuario = t.id_organizador
                WHERE t.id_torneo = :id_torneo
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_torneo' => $idTorneo]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerCatalogos(): array
    {
        $consultas = [
            'disciplinas' => "SELECT id_disciplina AS id, nombre FROM disciplinas WHERE estado = 'activa' ORDER BY nombre ASC",
            'categorias' => "SELECT id_categoria AS id, nombre FROM categorias ORDER BY nombre ASC",
            'tipos' => "SELECT id_tipo_torneo AS id, nombre FROM tipos_torneo ORDER BY nombre ASC",
            'organizadores' => "SELECT DISTINCT
                                    u.id_usuario AS id,
                                    u.nombre_completo AS nombre,
                                    u.nombre_usuario AS usuario
                                FROM usuarios u
                                INNER JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                                INNER JOIN roles r ON r.id_rol = ur.id_rol
                                WHERE u.estado = 'activo'
                                  AND r.nombre IN ('administrador', 'organizador')
                                ORDER BY u.nombre_completo ASC"
        ];

        $resultado = [];

        foreach ($consultas as $clave => $sql) {
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute();
            $resultado[$clave] = $consulta->fetchAll(PDO::FETCH_ASSOC);
        }

        $qCategorias = $this->conexion->query("SELECT id_disciplina, id_categoria FROM disciplina_categoria ORDER BY id_disciplina, id_categoria");
        $qTipos = $this->conexion->query("SELECT id_disciplina, id_tipo_torneo FROM disciplina_tipo_torneo ORDER BY id_disciplina, id_tipo_torneo");
        $resultado['disciplina_categorias'] = $qCategorias->fetchAll(PDO::FETCH_ASSOC);
        $resultado['disciplina_tipos'] = $qTipos->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }

    public function combinacionPermitida(
        int $idDisciplina,
        int $idCategoria,
        int $idTipoTorneo
    ): bool {
        $consultaCategoria = $this->conexion->prepare(
            "SELECT
                (SELECT COUNT(*) FROM disciplina_categoria WHERE id_disciplina = :id_disciplina) AS configuradas,
                (SELECT COUNT(*) FROM disciplina_categoria WHERE id_disciplina = :id_disciplina_2 AND id_categoria = :id_categoria) AS coincide"
        );
        $consultaCategoria->execute([
            ':id_disciplina' => $idDisciplina,
            ':id_disciplina_2' => $idDisciplina,
            ':id_categoria' => $idCategoria
        ]);
        $categoria = $consultaCategoria->fetch(PDO::FETCH_ASSOC) ?: ['configuradas' => 0, 'coincide' => 0];

        if ((int) $categoria['configuradas'] > 0 && (int) $categoria['coincide'] === 0) {
            return false;
        }

        $consultaTipo = $this->conexion->prepare(
            "SELECT
                (SELECT COUNT(*) FROM disciplina_tipo_torneo WHERE id_disciplina = :id_disciplina) AS configurados,
                (SELECT COUNT(*) FROM disciplina_tipo_torneo WHERE id_disciplina = :id_disciplina_2 AND id_tipo_torneo = :id_tipo_torneo) AS coincide"
        );
        $consultaTipo->execute([
            ':id_disciplina' => $idDisciplina,
            ':id_disciplina_2' => $idDisciplina,
            ':id_tipo_torneo' => $idTipoTorneo
        ]);
        $tipo = $consultaTipo->fetch(PDO::FETCH_ASSOC) ?: ['configurados' => 0, 'coincide' => 0];

        return !((int) $tipo['configurados'] > 0 && (int) $tipo['coincide'] === 0);
    }

    public function organizadorValido(int $idUsuario): bool
    {
        $sql = "SELECT 1
                FROM usuarios u
                INNER JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                INNER JOIN roles r ON r.id_rol = ur.id_rol
                WHERE u.id_usuario = :id_usuario
                  AND u.estado = 'activo'
                  AND r.nombre IN ('administrador', 'organizador')
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_usuario' => $idUsuario]);

        return $consulta->fetchColumn() !== false;
    }

    public function crear(
        string $nombre,
        int $idDisciplina,
        int $idCategoria,
        int $idTipoTorneo,
        int $idOrganizador,
        string $modalidad,
        string $fechaInicio,
        string $horaInicio,
        string $fechaFin,
        string $estado,
        ?int $cupoMaximo,
        bool $publicado = false,
        int $periodoGracia = 60
    ): int {
        $sql = "INSERT INTO torneos
                (nombre, id_disciplina, id_categoria, id_tipo_torneo, id_organizador, modalidad, fecha_inicio, hora_inicio, fecha_fin, estado, publicado, cupo_maximo, periodo_gracia_resultado)
                VALUES
                (:nombre, :id_disciplina, :id_categoria, :id_tipo_torneo, :id_organizador, :modalidad, :fecha_inicio, :hora_inicio, :fecha_fin, :estado, :publicado, :cupo_maximo, :periodo_gracia)";

        $consulta = $this->conexion->prepare($sql);
        $consulta->bindValue(':nombre', $nombre);
        $consulta->bindValue(':id_disciplina', $idDisciplina, PDO::PARAM_INT);
        $consulta->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $consulta->bindValue(':id_tipo_torneo', $idTipoTorneo, PDO::PARAM_INT);
        $consulta->bindValue(':id_organizador', $idOrganizador, PDO::PARAM_INT);
        $consulta->bindValue(':modalidad', $modalidad);
        $consulta->bindValue(':fecha_inicio', $fechaInicio);
        $consulta->bindValue(':hora_inicio', $horaInicio);
        $consulta->bindValue(':fecha_fin', $fechaFin);
        $consulta->bindValue(':estado', $estado);
        $consulta->bindValue(':publicado', $publicado ? 1 : 0, PDO::PARAM_INT);
        if ($cupoMaximo === null) {
            $consulta->bindValue(':cupo_maximo', null, PDO::PARAM_NULL);
        } else {
            $consulta->bindValue(':cupo_maximo', $cupoMaximo, PDO::PARAM_INT);
        }
        $consulta->bindValue(':periodo_gracia', max(5, $periodoGracia), PDO::PARAM_INT);
        $consulta->execute();

        return (int) $this->conexion->lastInsertId();
    }

    public function actualizar(
        int $idTorneo,
        string $nombre,
        int $idDisciplina,
        int $idCategoria,
        int $idTipoTorneo,
        int $idOrganizador,
        string $modalidad,
        string $fechaInicio,
        string $horaInicio,
        string $fechaFin,
        string $estado,
        ?int $cupoMaximo,
        bool $publicado = false,
        int $periodoGracia = 60
    ): bool {
        $sql = "UPDATE torneos
                SET nombre = :nombre,
                    id_disciplina = :id_disciplina,
                    id_categoria = :id_categoria,
                    id_tipo_torneo = :id_tipo_torneo,
                    id_organizador = :id_organizador,
                    modalidad = :modalidad,
                    fecha_inicio = :fecha_inicio,
                    hora_inicio = :hora_inicio,
                    fecha_fin = :fecha_fin,
                    estado = :estado,
                    publicado = :publicado,
                    cupo_maximo = :cupo_maximo,
                    periodo_gracia_resultado = :periodo_gracia
                WHERE id_torneo = :id_torneo";

        $consulta = $this->conexion->prepare($sql);
        $consulta->bindValue(':nombre', $nombre);
        $consulta->bindValue(':id_disciplina', $idDisciplina, PDO::PARAM_INT);
        $consulta->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $consulta->bindValue(':id_tipo_torneo', $idTipoTorneo, PDO::PARAM_INT);
        $consulta->bindValue(':id_organizador', $idOrganizador, PDO::PARAM_INT);
        $consulta->bindValue(':modalidad', $modalidad);
        $consulta->bindValue(':fecha_inicio', $fechaInicio);
        $consulta->bindValue(':hora_inicio', $horaInicio);
        $consulta->bindValue(':fecha_fin', $fechaFin);
        $consulta->bindValue(':estado', $estado);
        $consulta->bindValue(':publicado', $publicado ? 1 : 0, PDO::PARAM_INT);
        if ($cupoMaximo === null) {
            $consulta->bindValue(':cupo_maximo', null, PDO::PARAM_NULL);
        } else {
            $consulta->bindValue(':cupo_maximo', $cupoMaximo, PDO::PARAM_INT);
        }
        $consulta->bindValue(':periodo_gracia', max(5, $periodoGracia), PDO::PARAM_INT);
        $consulta->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $consulta->execute();

        return true;
    }

    public function obtenerRestriccionesEdicion(int $idTorneo): array
    {
        $consulta = $this->conexion->prepare(
            "SELECT
                (SELECT COUNT(*) FROM inscripciones_individuales WHERE id_torneo = :id_i) AS inscripciones_individuales,
                (SELECT COUNT(*) FROM inscripciones_equipos WHERE id_torneo = :id_e) AS inscripciones_equipos,
                (SELECT COUNT(*) FROM inscripciones_individuales WHERE id_torneo = :id_io AND estado <> 'rechazada') AS cupo_individual,
                (SELECT COUNT(*) FROM inscripciones_equipos WHERE id_torneo = :id_eo AND estado <> 'rechazada') AS cupo_equipo,
                (SELECT COUNT(*) FROM enfrentamientos WHERE id_torneo = :id_enf) AS enfrentamientos,
                (SELECT COUNT(*) FROM enfrentamientos WHERE id_torneo = :id_pend AND estado NOT IN ('finalizado','cancelado')) AS enfrentamientos_pendientes,
                (SELECT COUNT(*) FROM invitaciones_torneo WHERE id_torneo = :id_inv) AS invitaciones"
        );
        $consulta->execute([
            ':id_i' => $idTorneo,
            ':id_e' => $idTorneo,
            ':id_io' => $idTorneo,
            ':id_eo' => $idTorneo,
            ':id_enf' => $idTorneo,
            ':id_pend' => $idTorneo,
            ':id_inv' => $idTorneo
        ]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC) ?: [];
        $inscripcionesIndividuales = (int) ($fila['inscripciones_individuales'] ?? 0);
        $inscripcionesEquipos = (int) ($fila['inscripciones_equipos'] ?? 0);
        $enfrentamientos = (int) ($fila['enfrentamientos'] ?? 0);

        return [
            'inscripciones' => $inscripcionesIndividuales + $inscripcionesEquipos,
            'cupo_individual' => (int) ($fila['cupo_individual'] ?? 0),
            'cupo_equipo' => (int) ($fila['cupo_equipo'] ?? 0),
            'enfrentamientos' => $enfrentamientos,
            'enfrentamientos_pendientes' => (int) ($fila['enfrentamientos_pendientes'] ?? 0),
            'invitaciones' => (int) ($fila['invitaciones'] ?? 0),
            'estructura_bloqueada' => ($inscripcionesIndividuales + $inscripcionesEquipos) > 0 || $enfrentamientos > 0,
            'organizador_bloqueado' => $enfrentamientos > 0
        ];
    }

    public function tieneActividad(int $idTorneo): bool
    {
        $consulta = $this->conexion->prepare(
            "SELECT (
                (SELECT COUNT(*) FROM inscripciones_individuales WHERE id_torneo = :id_torneo_1) +
                (SELECT COUNT(*) FROM inscripciones_equipos WHERE id_torneo = :id_torneo_2) +
                (SELECT COUNT(*) FROM enfrentamientos WHERE id_torneo = :id_torneo_3) +
                (SELECT COUNT(*) FROM invitaciones_torneo WHERE id_torneo = :id_torneo_4)
            ) AS total"
        );
        $consulta->execute([
            ':id_torneo_1' => $idTorneo,
            ':id_torneo_2' => $idTorneo,
            ':id_torneo_3' => $idTorneo,
            ':id_torneo_4' => $idTorneo
        ]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function cancelar(int $idTorneo): bool
    {
        $consulta = $this->conexion->prepare(
            "UPDATE torneos
             SET estado = 'cancelado', publicado = 0
             WHERE id_torneo = :id_torneo
               AND estado NOT IN ('finalizado', 'cancelado')"
        );
        $consulta->execute([':id_torneo' => $idTorneo]);
        return $consulta->rowCount() === 1;
    }

    public function sincronizarEstadosTemporales(): int
    {
        $buscar = $this->conexion->prepare(
            "SELECT id_torneo, nombre FROM torneos
             WHERE estado = 'inscripciones'
               AND TIMESTAMP(fecha_inicio, hora_inicio) <= NOW()
               AND fecha_fin >= CURRENT_DATE"
        );
        $buscar->execute();
        $torneos = $buscar->fetchAll(PDO::FETCH_ASSOC);

        if (!$torneos) {
            return 0;
        }

        $propietarioTransaccion = !$this->conexion->inTransaction();
        if ($propietarioTransaccion) {
            $this->conexion->beginTransaction();
        }

        try {
            $consulta = $this->conexion->prepare(
                "UPDATE torneos
                 SET estado = 'en_curso'
                 WHERE estado = 'inscripciones'
                   AND TIMESTAMP(fecha_inicio, hora_inicio) <= NOW()
                   AND fecha_fin >= CURRENT_DATE"
            );
            $consulta->execute();
            $filas = $consulta->rowCount();

            $insAud = $this->conexion->prepare(
                "INSERT INTO auditoria (id_usuario, accion, entidad, id_entidad, detalle, resultado)
                 VALUES (NULL, 'torneo_estado_sincronizado', 'torneo', :id, :detalle, 'exito')"
            );
            foreach ($torneos as $t) {
                $insAud->execute([
                    ':id' => (string) $t['id_torneo'],
                    ':detalle' => "Cambio de estado automático: inscripciones -> en_curso · Torneo: {$t['nombre']}"
                ]);
            }

            if ($propietarioTransaccion) {
                $this->conexion->commit();
            }

            return $filas;
        } catch (Throwable $error) {
            if ($propietarioTransaccion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    public function obtenerResumen(?int $idOrganizador = null): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'en_curso' THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN estado = 'finalizado' THEN 1 ELSE 0 END) AS finalizados,
                    SUM(CASE WHEN estado IN ('borrador', 'inscripciones') THEN 1 ELSE 0 END) AS proximos,
                    SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) AS cancelados,
                    SUM(CASE WHEN DATE(fecha_inicio) = CURRENT_DATE AND estado NOT IN ('finalizado', 'cancelado') THEN 1 ELSE 0 END) AS comienzan_hoy
                FROM torneos";
        $parametros = [];
        if ($idOrganizador !== null) {
            $sql .= " WHERE id_organizador = :id_organizador";
            $parametros[':id_organizador'] = $idOrganizador;
        }

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);
        $resumen = $consulta->fetch(PDO::FETCH_ASSOC) ?: [];

        $sqlParticipantes = "SELECT COUNT(DISTINCT participante.id_usuario)
             FROM (
                 SELECT ii.id_usuario
                 FROM inscripciones_individuales ii
                 INNER JOIN torneos ti ON ti.id_torneo = ii.id_torneo
                 WHERE ii.estado = 'aprobada'
                   AND ti.estado <> 'cancelado'";
        if ($idOrganizador !== null) {
            $sqlParticipantes .= " AND ti.id_organizador = :id_organizador_individual";
        }
        $sqlParticipantes .= " UNION
                 SELECT iie.id_usuario
                 FROM integrantes_inscripcion_equipo iie
                 INNER JOIN inscripciones_equipos ie ON ie.id_inscripcion = iie.id_inscripcion
                 INNER JOIN torneos te ON te.id_torneo = ie.id_torneo
                 WHERE ie.estado = 'aprobada'
                   AND te.estado <> 'cancelado'";
        if ($idOrganizador !== null) {
            $sqlParticipantes .= " AND te.id_organizador = :id_organizador_equipo";
        }
        $sqlParticipantes .= ") participante";

        $consultaParticipantes = $this->conexion->prepare($sqlParticipantes);
        $parametrosParticipantes = [];
        if ($idOrganizador !== null) {
            $parametrosParticipantes[':id_organizador_individual'] = $idOrganizador;
            $parametrosParticipantes[':id_organizador_equipo'] = $idOrganizador;
        }
        $consultaParticipantes->execute($parametrosParticipantes);

        $resumen['participantes'] = (int) $consultaParticipantes->fetchColumn();

        return [
            'total' => (int) ($resumen['total'] ?? 0),
            'activos' => (int) ($resumen['activos'] ?? 0),
            'finalizados' => (int) ($resumen['finalizados'] ?? 0),
            'proximos' => (int) ($resumen['proximos'] ?? 0),
            'cancelados' => (int) ($resumen['cancelados'] ?? 0),
            'comienzan_hoy' => (int) ($resumen['comienzan_hoy'] ?? 0),
            'participantes' => (int) ($resumen['participantes'] ?? 0)
        ];
    }

    public function obtenerProximo(?int $idOrganizador = null): array|false
    {
        $sql = "SELECT t.id_torneo, t.nombre, t.fecha_inicio, t.hora_inicio, d.nombre AS disciplina
                FROM torneos t
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                WHERE t.estado NOT IN ('finalizado', 'cancelado')
                  AND t.fecha_inicio >= CURRENT_DATE";
        $parametros = [];
        if ($idOrganizador !== null) {
            $sql .= " AND t.id_organizador = :id_organizador";
            $parametros[':id_organizador'] = $idOrganizador;
        }
        $sql .= " ORDER BY t.fecha_inicio ASC, t.hora_inicio ASC, t.id_torneo ASC LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerProximos(int $limite = 3, ?int $idOrganizador = null): array
    {
        $limite = max(1, min($limite, 20));

        $sql = "SELECT
                    t.id_torneo,
                    t.nombre,
                    t.fecha_inicio,
                    t.hora_inicio,
                    t.fecha_fin,
                    t.estado,
                    t.modalidad,
                    d.nombre AS disciplina,
                    c.nombre AS categoria,
                    tt.nombre AS tipo_torneo
                FROM torneos t
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN categorias c ON c.id_categoria = t.id_categoria
                INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
                WHERE t.estado NOT IN ('finalizado', 'cancelado')
                  AND t.fecha_inicio >= CURRENT_DATE";
        if ($idOrganizador !== null) {
            $sql .= " AND t.id_organizador = :id_organizador";
        }
        $sql .= " ORDER BY t.fecha_inicio ASC, t.hora_inicio ASC, t.id_torneo ASC LIMIT :limite";

        $consulta = $this->conexion->prepare($sql);
        if ($idOrganizador !== null) {
            $consulta->bindValue(':id_organizador', $idOrganizador, PDO::PARAM_INT);
        }
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerRecientes(int $limite = 4, ?int $idOrganizador = null): array
    {
        $limite = max(1, min($limite, 20));

        $sql = "SELECT
                    t.id_torneo,
                    t.nombre,
                    t.fecha_creacion,
                    t.estado,
                    d.nombre AS disciplina,
                    u.nombre_usuario AS organizador_usuario
                FROM torneos t
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN usuarios u ON u.id_usuario = t.id_organizador";
        if ($idOrganizador !== null) {
            $sql .= " WHERE t.id_organizador = :id_organizador";
        }
        $sql .= " ORDER BY t.fecha_creacion DESC, t.id_torneo DESC LIMIT :limite";

        $consulta = $this->conexion->prepare($sql);
        if ($idOrganizador !== null) {
            $consulta->bindValue(':id_organizador', $idOrganizador, PDO::PARAM_INT);
        }
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerParticipantesAprobados(int $idTorneo): array
    {
        $consultaTorneo = $this->conexion->prepare(
            "SELECT modalidad FROM torneos WHERE id_torneo = :id_torneo LIMIT 1"
        );
        $consultaTorneo->execute([':id_torneo' => $idTorneo]);
        $modalidad = $consultaTorneo->fetchColumn();

        if ($modalidad === false) {
            return [];
        }

        if ($modalidad === 'equipo') {
            $sql = "SELECT e.id_equipo AS id, e.nombre AS nombre, NULL AS usuario, 'equipo' AS tipo
                    FROM inscripciones_equipos ie
                    INNER JOIN equipos e ON e.id_equipo = ie.id_equipo
                    WHERE ie.id_torneo = :id_torneo
                      AND ie.estado = 'aprobada'
                    ORDER BY e.nombre ASC";
        } else {
            $sql = "SELECT u.id_usuario AS id, u.nombre_completo AS nombre, u.nombre_usuario AS usuario, 'individual' AS tipo
                    FROM inscripciones_individuales ii
                    INNER JOIN usuarios u ON u.id_usuario = ii.id_usuario
                    WHERE ii.id_torneo = :id_torneo
                      AND ii.estado = 'aprobada'
                      AND u.estado = 'activo'
                    ORDER BY u.nombre_completo ASC";
        }

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_torneo' => $idTorneo]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}
