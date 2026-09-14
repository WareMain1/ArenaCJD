<?php

class Equipo
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTodos(): array
    {
        $sql = "SELECT
                    e.id_equipo,
                    e.nombre,
                    e.id_creador,
                    e.fecha_creacion,
                    e.estado AS estado_equipo,
                    u.nombre_completo AS responsable,
                    u.nombre_usuario AS responsable_usuario,
                    ie.id_inscripcion AS ultima_inscripcion_id,
                    ie.estado AS estado_inscripcion,
                    ie.fecha_inscripcion,
                    t.id_torneo,
                    t.id_organizador AS torneo_id_organizador,
                    t.nombre AS torneo,
                    t.estado AS torneo_estado,
                    CASE WHEN t.id_torneo IS NULL THEN 0 ELSE EXISTS(SELECT 1 FROM enfrentamientos enc WHERE enc.id_torneo = t.id_torneo LIMIT 1) END AS competencia_iniciada,
                    d.nombre AS disciplina,
                    COALESCE((
                        SELECT COUNT(*)
                        FROM integrantes_equipo ieq
                        WHERE ieq.id_equipo = e.id_equipo
                    ), 0) AS cantidad_integrantes
                FROM equipos e
                INNER JOIN usuarios u
                    ON u.id_usuario = e.id_creador
                LEFT JOIN inscripciones_equipos ie
                    ON ie.id_inscripcion = (
                        SELECT ie2.id_inscripcion
                        FROM inscripciones_equipos ie2
                        INNER JOIN torneos t2 ON t2.id_torneo = ie2.id_torneo
                        WHERE ie2.id_equipo = e.id_equipo
                          AND t2.estado <> 'cancelado'
                        ORDER BY ie2.fecha_inscripcion DESC, ie2.id_inscripcion DESC
                        LIMIT 1
                    )
                LEFT JOIN torneos t
                    ON t.id_torneo = ie.id_torneo
                LEFT JOIN disciplinas d
                    ON d.id_disciplina = t.id_disciplina
                ORDER BY e.fecha_creacion DESC, e.id_equipo DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerResumen(): array
    {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM equipos) AS equipos,
                    (
                        SELECT COUNT(DISTINCT ii.id_usuario)
                        FROM inscripciones_individuales ii
                        INNER JOIN torneos ti ON ti.id_torneo = ii.id_torneo
                        WHERE ti.estado <> 'cancelado'
                    ) AS individuales,
                    (
                        (
                            SELECT COUNT(*)
                            FROM inscripciones_equipos ie
                            INNER JOIN torneos te ON te.id_torneo = ie.id_torneo
                            WHERE te.estado <> 'cancelado'
                        ) +
                        (
                            SELECT COUNT(*)
                            FROM inscripciones_individuales ii
                            INNER JOIN torneos ti ON ti.id_torneo = ii.id_torneo
                            WHERE ti.estado <> 'cancelado'
                        )
                    ) AS total_registros,
                    (
                        (
                            SELECT COUNT(*)
                            FROM inscripciones_equipos ie
                            INNER JOIN torneos te ON te.id_torneo = ie.id_torneo
                            WHERE te.estado <> 'cancelado' AND ie.estado = 'aprobada'
                        ) +
                        (
                            SELECT COUNT(*)
                            FROM inscripciones_individuales ii
                            INNER JOIN torneos ti ON ti.id_torneo = ii.id_torneo
                            WHERE ti.estado <> 'cancelado' AND ii.estado = 'aprobada'
                        )
                    ) AS aprobados,
                    (
                        (
                            SELECT COUNT(*)
                            FROM inscripciones_equipos ie
                            INNER JOIN torneos te ON te.id_torneo = ie.id_torneo
                            WHERE te.estado <> 'cancelado' AND ie.estado = 'pendiente'
                        ) +
                        (
                            SELECT COUNT(*)
                            FROM inscripciones_individuales ii
                            INNER JOIN torneos ti ON ti.id_torneo = ii.id_torneo
                            WHERE ti.estado <> 'cancelado' AND ii.estado = 'pendiente'
                        )
                    ) AS pendientes";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        $fila = $consulta->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'equipos' => (int) ($fila['equipos'] ?? 0),
            'individuales' => (int) ($fila['individuales'] ?? 0),
            'total_registros' => (int) ($fila['total_registros'] ?? 0),
            'aprobados' => (int) ($fila['aprobados'] ?? 0),
            'pendientes' => (int) ($fila['pendientes'] ?? 0)
        ];
    }

    public function obtenerPorId(int $idEquipo): array|false
    {
        $sql = "SELECT
                    e.id_equipo,
                    e.nombre,
                    e.id_creador,
                    e.estado,
                    e.fecha_creacion,
                    u.nombre_completo AS responsable,
                    u.nombre_usuario AS responsable_usuario
                FROM equipos e
                INNER JOIN usuarios u
                    ON u.id_usuario = e.id_creador
                WHERE e.id_equipo = :id_equipo
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':id_equipo' => $idEquipo
        ]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerDetalle(int $idEquipo): array|false
    {
        $equipo = $this->obtenerPorId($idEquipo);

        if (!$equipo) {
            return false;
        }

        $sql = "SELECT
                    ie.id_inscripcion,
                    ie.estado,
                    ie.fecha_inscripcion,
                    t.id_torneo,
                    t.nombre AS torneo,
                    d.nombre AS disciplina,
                    u.id_usuario,
                    u.nombre_completo AS integrante,
                    u.nombre_usuario AS integrante_usuario
                FROM inscripciones_equipos ie
                INNER JOIN torneos t
                    ON t.id_torneo = ie.id_torneo
                INNER JOIN disciplinas d
                    ON d.id_disciplina = t.id_disciplina
                LEFT JOIN integrantes_inscripcion_equipo iie
                    ON iie.id_inscripcion = ie.id_inscripcion
                LEFT JOIN usuarios u
                    ON u.id_usuario = iie.id_usuario
                WHERE ie.id_equipo = :id_equipo
                  AND t.estado <> 'cancelado'
                ORDER BY ie.fecha_inscripcion DESC,
                         ie.id_inscripcion DESC,
                         u.nombre_completo ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':id_equipo' => $idEquipo
        ]);

        $inscripciones = [];

        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $idInscripcion = (int) $fila['id_inscripcion'];

            if (!isset($inscripciones[$idInscripcion])) {
                $inscripciones[$idInscripcion] = [
                    'id_inscripcion' => $idInscripcion,
                    'estado' => $fila['estado'],
                    'fecha_inscripcion' => $fila['fecha_inscripcion'],
                    'id_torneo' => (int) $fila['id_torneo'],
                    'torneo' => $fila['torneo'],
                    'disciplina' => $fila['disciplina'],
                    'integrantes' => []
                ];
            }

            if ($fila['id_usuario'] !== null) {
                $inscripciones[$idInscripcion]['integrantes'][] = [
                    'id_usuario' => (int) $fila['id_usuario'],
                    'nombre' => $fila['integrante'],
                    'usuario' => $fila['integrante_usuario']
                ];
            }
        }

        $consultaActuales = $this->conexion->prepare(
            "SELECT u.id_usuario, u.nombre_completo AS integrante, u.nombre_usuario AS integrante_usuario
             FROM integrantes_equipo ieq
             INNER JOIN usuarios u ON u.id_usuario = ieq.id_usuario
             WHERE ieq.id_equipo = :id_equipo
             ORDER BY u.nombre_completo ASC"
        );
        $consultaActuales->execute([':id_equipo' => $idEquipo]);
        $equipo['integrantes_actuales'] = array_map(static function (array $fila): array {
            return [
                'id_usuario' => (int) $fila['id_usuario'],
                'nombre' => $fila['integrante'],
                'usuario' => $fila['integrante_usuario']
            ];
        }, $consultaActuales->fetchAll(PDO::FETCH_ASSOC));

        $consultaInvitaciones = $this->conexion->prepare(
            "SELECT ie.id_invitacion_equipo, u.id_usuario, u.nombre_completo AS nombre, u.nombre_usuario AS usuario, ie.fecha_creacion
             FROM invitaciones_equipo ie
             INNER JOIN usuarios u ON u.id_usuario = ie.id_invitado
             WHERE ie.id_equipo = :id_equipo
               AND ie.estado = 'pendiente'
             ORDER BY ie.fecha_creacion ASC, ie.id_invitacion_equipo ASC"
        );
        $consultaInvitaciones->execute([':id_equipo' => $idEquipo]);
        $equipo['invitaciones_pendientes'] = array_map(static function (array $fila): array {
            return [
                'id_invitacion_equipo' => (int) $fila['id_invitacion_equipo'],
                'id_usuario' => (int) $fila['id_usuario'],
                'nombre' => $fila['nombre'],
                'usuario' => $fila['usuario'],
                'fecha_creacion' => $fila['fecha_creacion']
            ];
        }, $consultaInvitaciones->fetchAll(PDO::FETCH_ASSOC));

        $equipo['inscripciones'] = array_values($inscripciones);

        return $equipo;
    }

    public function responsablePorUsuario(string $nombreUsuario): array|false
    {
        $sql = "SELECT id_usuario, nombre_completo, nombre_usuario
                FROM usuarios
                WHERE nombre_usuario = :nombre_usuario
                  AND estado = 'activo'
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':nombre_usuario' => $nombreUsuario
        ]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public function participantePorUsuario(string $nombreUsuario): array|false
    {
        $sql = "SELECT u.id_usuario, u.nombre_completo, u.nombre_usuario
                FROM usuarios u
                WHERE u.nombre_usuario = :nombre_usuario
                  AND u.estado = 'activo'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM usuario_rol ur
                      INNER JOIN roles r ON r.id_rol = ur.id_rol
                      WHERE ur.id_usuario = u.id_usuario
                        AND r.nombre = 'administrador'
                  )
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':nombre_usuario' => $nombreUsuario]);
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public function nombreDisponible(string $nombre, int $idEquipoExcluir = 0): bool
    {
        $sql = "SELECT id_equipo
                FROM equipos
                WHERE nombre = :nombre";

        $parametros = [
            ':nombre' => $nombre
        ];

        if ($idEquipoExcluir > 0) {
            $sql .= " AND id_equipo <> :id_equipo";
            $parametros[':id_equipo'] = $idEquipoExcluir;
        }

        $sql .= " LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetch() === false;
    }


    public function crear(string $nombre, int $idCreador): int
    {
        $sql = "INSERT INTO equipos (nombre, id_creador, estado)
                VALUES (:nombre, :id_creador, 'activo')";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':nombre' => $nombre,
            ':id_creador' => $idCreador
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function actualizar(
        int $idEquipo,
        string $nombre,
        int $idCreador,
        string $estado
    ): bool {
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            throw new InvalidArgumentException('Estado de equipo no válido.');
        }

        $sql = "UPDATE equipos
                SET nombre = :nombre,
                    id_creador = :id_creador,
                    estado = :estado
                WHERE id_equipo = :id_equipo";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':nombre' => $nombre,
            ':id_creador' => $idCreador,
            ':estado' => $estado,
            ':id_equipo' => $idEquipo
        ]);

        return $consulta->rowCount() > 0;
    }

    public function reemplazarIntegrantes(int $idEquipo, array $idsUsuarios): void
    {
        $idsUsuarios = array_values(array_unique(array_map('intval', $idsUsuarios)));

        if ($idsUsuarios) {
            $marcadores = implode(',', array_fill(0, count($idsUsuarios), '?'));
            $consultaElegibles = $this->conexion->prepare(
                "SELECT COUNT(DISTINCT u.id_usuario)
                 FROM usuarios u
                 WHERE u.id_usuario IN ($marcadores)
                   AND u.estado = 'activo'
                   AND NOT EXISTS (
                       SELECT 1
                       FROM usuario_rol ur
                       INNER JOIN roles r ON r.id_rol = ur.id_rol
                       WHERE ur.id_usuario = u.id_usuario
                         AND r.nombre = 'administrador'
                   )"
            );
            $consultaElegibles->execute($idsUsuarios);
            if ((int) $consultaElegibles->fetchColumn() !== count($idsUsuarios)) {
                throw new DomainException('Uno o más integrantes no están disponibles para participar.');
            }
        }

        $eliminar = $this->conexion->prepare(
            "DELETE FROM integrantes_equipo WHERE id_equipo = :id_equipo"
        );
        $eliminar->execute([':id_equipo' => $idEquipo]);

        $insertar = $this->conexion->prepare(
            "INSERT INTO integrantes_equipo (id_equipo, id_usuario) VALUES (:id_equipo, :id_usuario)"
        );

        foreach ($idsUsuarios as $idUsuario) {
            $insertar->execute([
                ':id_equipo' => $idEquipo,
                ':id_usuario' => $idUsuario
            ]);
        }
    }

    public function tieneHistorial(int $idEquipo): bool
    {
        $consulta = $this->conexion->prepare(
            "SELECT COUNT(*) FROM inscripciones_equipos WHERE id_equipo = :id_equipo"
        );
        $consulta->execute([':id_equipo' => $idEquipo]);
        return (int) $consulta->fetchColumn() > 0;
    }

    public function archivar(int $idEquipo): bool
    {
        $consulta = $this->conexion->prepare(
            "UPDATE equipos SET estado = 'inactivo' WHERE id_equipo = :id_equipo"
        );
        $consulta->execute([':id_equipo' => $idEquipo]);
        return $consulta->rowCount() > 0;
    }

    public function eliminar(int $idEquipo): bool
    {
        if ($this->tieneHistorial($idEquipo)) {
            return false;
        }

        $propietarioTransaccion = !$this->conexion->inTransaction();
        if ($propietarioTransaccion) {
            $this->conexion->beginTransaction();
        }

        try {
            $consultaIntegrantes = $this->conexion->prepare(
                "DELETE iie
                 FROM integrantes_inscripcion_equipo iie
                 INNER JOIN inscripciones_equipos ie
                     ON ie.id_inscripcion = iie.id_inscripcion
                 WHERE ie.id_equipo = :id_equipo"
            );
            $consultaIntegrantes->execute([
                ':id_equipo' => $idEquipo
            ]);

            $consultaInscripciones = $this->conexion->prepare(
                "DELETE FROM inscripciones_equipos
                 WHERE id_equipo = :id_equipo"
            );
            $consultaInscripciones->execute([
                ':id_equipo' => $idEquipo
            ]);

            $consultaEquipo = $this->conexion->prepare(
                "DELETE FROM equipos
                 WHERE id_equipo = :id_equipo"
            );
            $consultaEquipo->execute([
                ':id_equipo' => $idEquipo
            ]);

            $eliminado = $consultaEquipo->rowCount() === 1;

            if (!$eliminado) {
                if ($propietarioTransaccion && $this->conexion->inTransaction()) {
                    $this->conexion->rollBack();
                }
                return false;
            }

            if ($propietarioTransaccion) {
                $this->conexion->commit();
            }
            return true;
        } catch (Throwable $error) {
            if ($propietarioTransaccion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }
}
