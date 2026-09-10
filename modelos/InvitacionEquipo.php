<?php

class InvitacionEquipo
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function listarRecibidas(int $idUsuario): array
    {
        $consulta = $this->conexion->prepare(
            "SELECT
                ie.id_invitacion_equipo,
                ie.id_equipo,
                ie.id_invitador,
                ie.id_invitado,
                ie.estado,
                ie.fecha_creacion,
                ie.fecha_respuesta,
                e.nombre AS equipo,
                e.estado AS estado_equipo,
                u.nombre_completo AS invitador,
                u.nombre_usuario AS invitador_usuario
             FROM invitaciones_equipo ie
             INNER JOIN equipos e ON e.id_equipo = ie.id_equipo
             INNER JOIN usuarios u ON u.id_usuario = ie.id_invitador
             WHERE ie.id_invitado = :id_usuario
             ORDER BY
                CASE ie.estado WHEN 'pendiente' THEN 0 ELSE 1 END,
                ie.fecha_creacion DESC,
                ie.id_invitacion_equipo DESC"
        );
        $consulta->execute([':id_usuario' => $idUsuario]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPendientesDeEquipo(int $idEquipo): array
    {
        $consulta = $this->conexion->prepare(
            "SELECT
                ie.id_invitacion_equipo,
                ie.id_invitado,
                u.nombre_completo AS nombre,
                u.nombre_usuario AS usuario,
                ie.fecha_creacion
             FROM invitaciones_equipo ie
             INNER JOIN usuarios u ON u.id_usuario = ie.id_invitado
             WHERE ie.id_equipo = :id_equipo
               AND ie.estado = 'pendiente'
             ORDER BY ie.fecha_creacion ASC, ie.id_invitacion_equipo ASC"
        );
        $consulta->execute([':id_equipo' => $idEquipo]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPendientes(int $idUsuario): int
    {
        $consulta = $this->conexion->prepare(
            "SELECT COUNT(*)
             FROM invitaciones_equipo
             WHERE id_invitado = :id_usuario
               AND estado = 'pendiente'"
        );
        $consulta->execute([':id_usuario' => $idUsuario]);
        return (int) $consulta->fetchColumn();
    }

    public function crearOReenviar(int $idEquipo, int $idInvitador, int $idInvitado): int
    {
        if ($idInvitador === $idInvitado) {
            throw new DomainException('No necesitas invitarte a tu propio equipo.');
        }

        $consultaEquipo = $this->conexion->prepare(
            "SELECT id_equipo, nombre, id_creador, estado
             FROM equipos
             WHERE id_equipo = :id_equipo
             LIMIT 1"
        );
        $consultaEquipo->execute([':id_equipo' => $idEquipo]);
        $equipo = $consultaEquipo->fetch(PDO::FETCH_ASSOC);
        if (!$equipo || $equipo['estado'] !== 'activo') {
            throw new DomainException('El equipo no existe o está inactivo.');
        }

        $consultaUsuario = $this->conexion->prepare(
            "SELECT u.id_usuario, u.nombre_usuario
             FROM usuarios u
             WHERE u.id_usuario = :id_usuario
               AND u.estado = 'activo'
               AND NOT EXISTS (
                    SELECT 1
                    FROM usuario_rol ur
                    INNER JOIN roles r ON r.id_rol = ur.id_rol
                    WHERE ur.id_usuario = u.id_usuario
                      AND r.nombre = 'administrador'
               )
             LIMIT 1"
        );
        $consultaUsuario->execute([':id_usuario' => $idInvitado]);
        if ($consultaUsuario->fetch(PDO::FETCH_ASSOC) === false) {
            throw new DomainException('La cuenta indicada no está disponible para participar en equipos.');
        }

        $consultaIntegrante = $this->conexion->prepare(
            "SELECT 1
             FROM integrantes_equipo
             WHERE id_equipo = :id_equipo
               AND id_usuario = :id_usuario
             LIMIT 1"
        );
        $consultaIntegrante->execute([
            ':id_equipo' => $idEquipo,
            ':id_usuario' => $idInvitado
        ]);
        if ($consultaIntegrante->fetchColumn() !== false) {
            throw new DomainException('Ese usuario ya forma parte del equipo.');
        }

        $consultaExistente = $this->conexion->prepare(
            "SELECT id_invitacion_equipo, estado
             FROM invitaciones_equipo
             WHERE id_equipo = :id_equipo
               AND id_invitado = :id_invitado
             LIMIT 1"
        );
        $consultaExistente->execute([
            ':id_equipo' => $idEquipo,
            ':id_invitado' => $idInvitado
        ]);
        $existente = $consultaExistente->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            if ($existente['estado'] === 'pendiente') {
                return (int) $existente['id_invitacion_equipo'];
            }

            $actualizar = $this->conexion->prepare(
                "UPDATE invitaciones_equipo
                 SET id_invitador = :id_invitador,
                     estado = 'pendiente',
                     fecha_creacion = CURRENT_TIMESTAMP,
                     fecha_respuesta = NULL
                 WHERE id_invitacion_equipo = :id_invitacion_equipo"
            );
            $actualizar->execute([
                ':id_invitador' => $idInvitador,
                ':id_invitacion_equipo' => (int) $existente['id_invitacion_equipo']
            ]);
            return (int) $existente['id_invitacion_equipo'];
        }

        $insertar = $this->conexion->prepare(
            "INSERT INTO invitaciones_equipo
                (id_equipo, id_invitador, id_invitado, estado)
             VALUES
                (:id_equipo, :id_invitador, :id_invitado, 'pendiente')"
        );
        $insertar->execute([
            ':id_equipo' => $idEquipo,
            ':id_invitador' => $idInvitador,
            ':id_invitado' => $idInvitado
        ]);
        return (int) $this->conexion->lastInsertId();
    }

    public function cancelarPendientesNoIncluidas(int $idEquipo, array $idsInvitadosConservar): void
    {
        $ids = array_values(array_unique(array_map('intval', $idsInvitadosConservar)));
        if (!$ids) {
            $consulta = $this->conexion->prepare(
                "UPDATE invitaciones_equipo
                 SET estado = 'cancelada', fecha_respuesta = NOW()
                 WHERE id_equipo = :id_equipo
                   AND estado = 'pendiente'"
            );
            $consulta->execute([':id_equipo' => $idEquipo]);
            return;
        }

        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $consulta = $this->conexion->prepare(
            "UPDATE invitaciones_equipo
             SET estado = 'cancelada', fecha_respuesta = NOW()
             WHERE id_equipo = ?
               AND estado = 'pendiente'
               AND id_invitado NOT IN ($marcadores)"
        );
        $consulta->execute(array_merge([$idEquipo], $ids));
    }

    public function responder(int $idInvitacion, int $idUsuario, string $respuesta): array
    {
        if (!in_array($respuesta, ['aceptada', 'rechazada'], true)) {
            throw new InvalidArgumentException('Respuesta de invitación no válida.');
        }

        try {
            $this->conexion->beginTransaction();

            $consulta = $this->conexion->prepare(
                "SELECT
                    ie.id_invitacion_equipo,
                    ie.id_equipo,
                    ie.id_invitador,
                    ie.id_invitado,
                    ie.estado,
                    e.nombre AS equipo,
                    e.estado AS estado_equipo,
                    u.estado AS estado_usuario,
                    EXISTS(
                        SELECT 1
                        FROM usuario_rol ur
                        INNER JOIN roles r ON r.id_rol = ur.id_rol
                        WHERE ur.id_usuario = ie.id_invitado
                          AND r.nombre = 'administrador'
                    ) AS es_administrador
                 FROM invitaciones_equipo ie
                 INNER JOIN equipos e ON e.id_equipo = ie.id_equipo
                 INNER JOIN usuarios u ON u.id_usuario = ie.id_invitado
                 WHERE ie.id_invitacion_equipo = :id_invitacion
                 FOR UPDATE"
            );
            $consulta->execute([':id_invitacion' => $idInvitacion]);
            $invitacion = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$invitacion || (int) $invitacion['id_invitado'] !== $idUsuario) {
                throw new DomainException('La invitación no existe o no te pertenece.');
            }
            if ($invitacion['estado'] !== 'pendiente') {
                throw new DomainException('La invitación ya fue respondida.');
            }

            if ($respuesta === 'aceptada') {
                if ($invitacion['estado_equipo'] !== 'activo') {
                    throw new DomainException('El equipo ya no está activo.');
                }
                if ($invitacion['estado_usuario'] !== 'activo' || (int) $invitacion['es_administrador'] === 1) {
                    throw new DomainException('Tu cuenta no está disponible para participar en equipos.');
                }

                $insertar = $this->conexion->prepare(
                    "INSERT IGNORE INTO integrantes_equipo (id_equipo, id_usuario)
                     VALUES (:id_equipo, :id_usuario)"
                );
                $insertar->execute([
                    ':id_equipo' => (int) $invitacion['id_equipo'],
                    ':id_usuario' => $idUsuario
                ]);
            }

            $actualizar = $this->conexion->prepare(
                "UPDATE invitaciones_equipo
                 SET estado = :estado,
                     fecha_respuesta = NOW()
                 WHERE id_invitacion_equipo = :id_invitacion"
            );
            $actualizar->execute([
                ':estado' => $respuesta,
                ':id_invitacion' => $idInvitacion
            ]);

            $this->conexion->commit();
            return [
                'id_equipo' => (int) $invitacion['id_equipo'],
                'equipo' => (string) $invitacion['equipo'],
                'estado' => $respuesta
            ];
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }
}
