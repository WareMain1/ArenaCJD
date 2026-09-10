<?php

class Invitacion
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function listarRecibidas(int $idUsuario): array
    {
        $sql = "SELECT
                    i.id_invitacion,
                    i.id_torneo,
                    i.id_invitador,
                    i.id_invitado,
                    i.tipo,
                    i.id_equipo,
                    i.estado,
                    i.fecha_creacion,
                    i.fecha_respuesta,
                    t.nombre AS torneo,
                    t.estado AS estado_torneo,
                    t.modalidad,
                    t.fecha_inicio,
                    t.hora_inicio,
                    d.nombre AS disciplina,
                    c.nombre AS categoria,
                    u.nombre_completo AS invitador,
                    u.nombre_usuario AS invitador_usuario,
                    e.nombre AS equipo
                FROM invitaciones_torneo i
                INNER JOIN torneos t ON t.id_torneo = i.id_torneo
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN categorias c ON c.id_categoria = t.id_categoria
                INNER JOIN usuarios u ON u.id_usuario = i.id_invitador
                LEFT JOIN equipos e ON e.id_equipo = i.id_equipo
                WHERE i.id_invitado = :id_usuario
                  AND t.estado <> 'cancelado'
                ORDER BY
                    CASE i.estado WHEN 'pendiente' THEN 0 ELSE 1 END,
                    i.fecha_creacion DESC,
                    i.id_invitacion DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_usuario' => $idUsuario]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarEnviadas(int $idUsuario): array
    {
        $sql = "SELECT
                    i.id_invitacion,
                    i.id_torneo,
                    i.id_invitador,
                    i.id_invitado,
                    i.tipo,
                    i.id_equipo,
                    i.estado,
                    i.fecha_creacion,
                    i.fecha_respuesta,
                    t.nombre AS torneo,
                    d.nombre AS disciplina,
                    u.nombre_completo AS invitado,
                    u.nombre_usuario AS invitado_usuario,
                    e.nombre AS equipo
                FROM invitaciones_torneo i
                INNER JOIN torneos t ON t.id_torneo = i.id_torneo
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN usuarios u ON u.id_usuario = i.id_invitado
                LEFT JOIN equipos e ON e.id_equipo = i.id_equipo
                WHERE i.id_invitador = :id_usuario
                  AND t.estado <> 'cancelado'
                ORDER BY i.fecha_creacion DESC, i.id_invitacion DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_usuario' => $idUsuario]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPendientes(int $idUsuario): int
    {
        $consulta = $this->conexion->prepare(
            "SELECT COUNT(*)
             FROM invitaciones_torneo i
             INNER JOIN torneos t ON t.id_torneo = i.id_torneo
             WHERE i.id_invitado = :id_usuario
               AND i.estado = 'pendiente'
               AND t.estado <> 'cancelado'"
        );
        $consulta->execute([':id_usuario' => $idUsuario]);
        return (int) $consulta->fetchColumn();
    }

    public function crearOReenviar(int $idTorneo, int $idInvitador, int $idInvitado): int
    {
        if ($idInvitador === $idInvitado) {
            throw new DomainException('No puedes enviarte una invitación a ti mismo.');
        }

        $consultaElegible = $this->conexion->prepare(
            "SELECT 1
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
        $consultaElegible->execute([':id_usuario' => $idInvitado]);
        if ($consultaElegible->fetchColumn() === false) {
            throw new DomainException('La cuenta indicada no está disponible para participar en torneos.');
        }

        $consultaInscripcion = $this->conexion->prepare(
            "SELECT id_inscripcion
             FROM inscripciones_individuales
             WHERE id_torneo = :id_torneo AND id_usuario = :id_usuario
             LIMIT 1"
        );
        $consultaInscripcion->execute([
            ':id_torneo' => $idTorneo,
            ':id_usuario' => $idInvitado
        ]);
        if ($consultaInscripcion->fetch() !== false) {
            throw new DomainException('Ese usuario ya está inscripto en el torneo.');
        }

        return $this->guardarInvitacion($idTorneo, $idInvitador, $idInvitado, 'individual', null);
    }

    public function crearOReenviarEquipo(int $idTorneo, int $idInvitador, int $idEquipo): int
    {
        $consultaEquipo = $this->conexion->prepare(
            "SELECT e.id_equipo, e.nombre, e.id_creador, e.estado, u.nombre_usuario
             FROM equipos e
             INNER JOIN usuarios u ON u.id_usuario = e.id_creador
             WHERE e.id_equipo = :id_equipo
             LIMIT 1"
        );
        $consultaEquipo->execute([':id_equipo' => $idEquipo]);
        $equipo = $consultaEquipo->fetch(PDO::FETCH_ASSOC);
        if (!$equipo || $equipo['estado'] !== 'activo') {
            throw new DomainException('El equipo no existe o está inactivo.');
        }
        if ((int) $equipo['id_creador'] === $idInvitador) {
            throw new DomainException('No necesitas invitar a tu propio equipo. Puedes inscribirlo directamente.');
        }

        $consultaInscripcion = $this->conexion->prepare(
            "SELECT id_inscripcion
             FROM inscripciones_equipos
             WHERE id_torneo = :id_torneo AND id_equipo = :id_equipo
             LIMIT 1"
        );
        $consultaInscripcion->execute([
            ':id_torneo' => $idTorneo,
            ':id_equipo' => $idEquipo
        ]);
        if ($consultaInscripcion->fetch() !== false) {
            throw new DomainException('Ese equipo ya está inscripto en el torneo.');
        }

        return $this->guardarInvitacion(
            $idTorneo,
            $idInvitador,
            (int) $equipo['id_creador'],
            'equipo',
            $idEquipo
        );
    }

    private function guardarInvitacion(
        int $idTorneo,
        int $idInvitador,
        int $idInvitado,
        string $tipo,
        ?int $idEquipo
    ): int {
        if ($tipo === 'equipo' && $idEquipo !== null) {
            $consultaExistente = $this->conexion->prepare(
                "SELECT id_invitacion, estado
                 FROM invitaciones_torneo
                 WHERE id_torneo = :id_torneo
                   AND tipo = 'equipo'
                   AND id_equipo = :id_equipo
                 LIMIT 1"
            );
            $consultaExistente->execute([
                ':id_torneo' => $idTorneo,
                ':id_equipo' => $idEquipo
            ]);
        } else {
            $consultaExistente = $this->conexion->prepare(
                "SELECT id_invitacion, estado
                 FROM invitaciones_torneo
                 WHERE id_torneo = :id_torneo
                   AND tipo = 'individual'
                   AND id_invitado = :id_invitado
                 LIMIT 1"
            );
            $consultaExistente->execute([
                ':id_torneo' => $idTorneo,
                ':id_invitado' => $idInvitado
            ]);
        }
        $existente = $consultaExistente->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            if ($existente['estado'] === 'pendiente') {
                throw new DomainException($tipo === 'equipo' ? 'Ese equipo ya tiene una invitación pendiente para este torneo.' : 'Ese usuario ya tiene una invitación pendiente para este torneo.');
            }
            if ($existente['estado'] === 'aceptada') {
                throw new DomainException('Esa invitación ya fue aceptada.');
            }

            $consulta = $this->conexion->prepare(
                "UPDATE invitaciones_torneo
                 SET id_invitador = :id_invitador,
                     tipo = :tipo,
                     id_equipo = :id_equipo,
                     estado = 'pendiente',
                     fecha_creacion = CURRENT_TIMESTAMP,
                     fecha_respuesta = NULL
                 WHERE id_invitacion = :id_invitacion"
            );
            $consulta->bindValue(':id_invitador', $idInvitador, PDO::PARAM_INT);
            $consulta->bindValue(':tipo', $tipo);
            if ($idEquipo === null) {
                $consulta->bindValue(':id_equipo', null, PDO::PARAM_NULL);
            } else {
                $consulta->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
            }
            $consulta->bindValue(':id_invitacion', (int) $existente['id_invitacion'], PDO::PARAM_INT);
            $consulta->execute();
            return (int) $existente['id_invitacion'];
        }

        $consulta = $this->conexion->prepare(
            "INSERT INTO invitaciones_torneo (id_torneo, id_invitador, id_invitado, tipo, id_equipo, estado)
             VALUES (:id_torneo, :id_invitador, :id_invitado, :tipo, :id_equipo, 'pendiente')"
        );
        $consulta->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $consulta->bindValue(':id_invitador', $idInvitador, PDO::PARAM_INT);
        $consulta->bindValue(':id_invitado', $idInvitado, PDO::PARAM_INT);
        $consulta->bindValue(':tipo', $tipo);
        if ($idEquipo === null) {
            $consulta->bindValue(':id_equipo', null, PDO::PARAM_NULL);
        } else {
            $consulta->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        }
        $consulta->execute();

        return (int) $this->conexion->lastInsertId();
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
                    i.id_invitacion,
                    i.id_torneo,
                    i.id_invitado,
                    i.tipo,
                    i.id_equipo,
                    i.estado,
                    t.nombre AS torneo,
                    t.modalidad,
                    t.estado AS estado_torneo,
                    t.cupo_maximo,
                    e.nombre AS equipo,
                    e.id_creador AS equipo_responsable,
                    e.estado AS estado_equipo
                 FROM invitaciones_torneo i
                 INNER JOIN torneos t ON t.id_torneo = i.id_torneo
                 LEFT JOIN equipos e ON e.id_equipo = i.id_equipo
                 WHERE i.id_invitacion = :id_invitacion
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
                if ($invitacion['estado_torneo'] !== 'inscripciones') {
                    throw new DomainException('El torneo ya no tiene inscripciones abiertas.');
                }

                if ($invitacion['tipo'] === 'equipo') {
                    $this->aceptarEquipo($invitacion, $idUsuario);
                } else {
                    $this->aceptarIndividual($invitacion, $idUsuario);
                }
            }

            $consultaActualizar = $this->conexion->prepare(
                "UPDATE invitaciones_torneo
                 SET estado = :estado, fecha_respuesta = NOW()
                 WHERE id_invitacion = :id_invitacion"
            );
            $consultaActualizar->execute([
                ':estado' => $respuesta,
                ':id_invitacion' => $idInvitacion
            ]);

            $this->conexion->commit();

            return [
                'id_torneo' => (int) $invitacion['id_torneo'],
                'torneo' => (string) $invitacion['torneo'],
                'tipo' => (string) $invitacion['tipo'],
                'equipo' => $invitacion['equipo'],
                'estado' => $respuesta
            ];
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    private function aceptarIndividual(array $invitacion, int $idUsuario): void
    {
        if ($invitacion['modalidad'] !== 'individual') {
            throw new DomainException('Esta invitación no corresponde a una modalidad individual.');
        }

        $consultaElegible = $this->conexion->prepare(
            "SELECT 1
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
        $consultaElegible->execute([':id_usuario' => $idUsuario]);
        if ($consultaElegible->fetchColumn() === false) {
            throw new DomainException('Tu cuenta no está disponible para participar en torneos.');
        }

        $consultaExiste = $this->conexion->prepare(
            "SELECT id_inscripcion
             FROM inscripciones_individuales
             WHERE id_torneo = :id_torneo AND id_usuario = :id_usuario
             LIMIT 1"
        );
        $consultaExiste->execute([
            ':id_torneo' => (int) $invitacion['id_torneo'],
            ':id_usuario' => $idUsuario
        ]);

        if ($consultaExiste->fetch() !== false) {
            return;
        }

        $this->comprobarCupo((int) $invitacion['id_torneo'], 'individual', $invitacion['cupo_maximo']);

        $consultaInscribir = $this->conexion->prepare(
            "INSERT INTO inscripciones_individuales (id_torneo, id_usuario, estado)
             VALUES (:id_torneo, :id_usuario, 'aprobada')"
        );
        $consultaInscribir->execute([
            ':id_torneo' => (int) $invitacion['id_torneo'],
            ':id_usuario' => $idUsuario
        ]);
    }

    private function aceptarEquipo(array $invitacion, int $idUsuario): void
    {
        if ($invitacion['modalidad'] !== 'equipo') {
            throw new DomainException('Esta invitación no corresponde a un torneo por equipos.');
        }
        if (!$invitacion['id_equipo'] || (int) $invitacion['equipo_responsable'] !== $idUsuario || $invitacion['estado_equipo'] !== 'activo') {
            throw new DomainException('El equipo invitado ya no está disponible o no te pertenece.');
        }

        $consultaExiste = $this->conexion->prepare(
            "SELECT id_inscripcion
             FROM inscripciones_equipos
             WHERE id_torneo = :id_torneo AND id_equipo = :id_equipo
             LIMIT 1"
        );
        $consultaExiste->execute([
            ':id_torneo' => (int) $invitacion['id_torneo'],
            ':id_equipo' => (int) $invitacion['id_equipo']
        ]);
        if ($consultaExiste->fetch() !== false) {
            return;
        }

        $this->comprobarCupo((int) $invitacion['id_torneo'], 'equipo', $invitacion['cupo_maximo']);

        $consultaPlantel = $this->conexion->prepare(
            "SELECT
                ieq.id_usuario,
                u.estado,
                EXISTS(
                    SELECT 1
                    FROM usuario_rol ur
                    INNER JOIN roles r ON r.id_rol = ur.id_rol
                    WHERE ur.id_usuario = ieq.id_usuario
                      AND r.nombre = 'administrador'
                ) AS es_administrador
             FROM integrantes_equipo ieq
             INNER JOIN usuarios u ON u.id_usuario = ieq.id_usuario
             WHERE ieq.id_equipo = :id_equipo
             ORDER BY ieq.fecha_alta, ieq.id_usuario"
        );
        $consultaPlantel->execute([':id_equipo' => (int) $invitacion['id_equipo']]);
        $plantel = $consultaPlantel->fetchAll(PDO::FETCH_ASSOC);
        if (count($plantel) < 2) {
            throw new DomainException('El equipo invitado no tiene un plantel válido de al menos dos integrantes.');
        }
        if (array_filter($plantel, static fn(array $fila): bool => (string) $fila['estado'] !== 'activo')) {
            throw new DomainException('El equipo invitado tiene integrantes con cuentas inactivas. Su responsable debe actualizar el plantel antes de aceptar.');
        }
        if (array_filter($plantel, static fn(array $fila): bool => (int) ($fila['es_administrador'] ?? 0) === 1)) {
            throw new DomainException('El equipo invitado contiene una cuenta administradora, que no puede participar en torneos. Actualiza el plantel antes de aceptar.');
        }
        $integrantes = array_map(static fn(array $fila): int => (int) $fila['id_usuario'], $plantel);

        $consultaInscribir = $this->conexion->prepare(
            "INSERT INTO inscripciones_equipos (id_torneo, id_equipo, estado)
             VALUES (:id_torneo, :id_equipo, 'aprobada')"
        );
        $consultaInscribir->execute([
            ':id_torneo' => (int) $invitacion['id_torneo'],
            ':id_equipo' => (int) $invitacion['id_equipo']
        ]);
        $idInscripcion = (int) $this->conexion->lastInsertId();

        $consultaIntegrante = $this->conexion->prepare(
            "INSERT INTO integrantes_inscripcion_equipo (id_inscripcion, id_usuario)
             VALUES (:id_inscripcion, :id_usuario)"
        );
        foreach ($integrantes as $idIntegrante) {
            $consultaIntegrante->execute([
                ':id_inscripcion' => $idInscripcion,
                ':id_usuario' => $idIntegrante
            ]);
        }
    }

    private function comprobarCupo(int $idTorneo, string $modalidad, mixed $cupoMaximo): void
    {
        if ($cupoMaximo === null) {
            return;
        }

        $tabla = $modalidad === 'equipo' ? 'inscripciones_equipos' : 'inscripciones_individuales';
        $consulta = $this->conexion->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE id_torneo = :id_torneo AND estado <> 'rechazada'"
        );
        $consulta->execute([':id_torneo' => $idTorneo]);
        if ((int) $consulta->fetchColumn() >= (int) $cupoMaximo) {
            throw new DomainException('El torneo alcanzó su cupo máximo.');
        }
    }
}
