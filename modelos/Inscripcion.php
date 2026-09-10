<?php

class Inscripcion
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerIndividuales(): array
    {
        $sql = "SELECT
                    ii.id_inscripcion,
                    ii.id_torneo,
                    ii.id_usuario,
                    ii.fecha_inscripcion,
                    ii.estado,
                    u.nombre_completo,
                    u.nombre_usuario,
                    t.nombre AS torneo,
                    t.id_organizador,
                    t.modalidad,
                    t.estado AS torneo_estado,
                    EXISTS(SELECT 1 FROM enfrentamientos enc WHERE enc.id_torneo = t.id_torneo LIMIT 1) AS competencia_iniciada,
                    d.nombre AS disciplina,
                    c.nombre AS categoria,
                    tt.nombre AS tipo_torneo
                FROM inscripciones_individuales ii
                INNER JOIN usuarios u ON u.id_usuario = ii.id_usuario
                INNER JOIN torneos t ON t.id_torneo = ii.id_torneo
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN categorias c ON c.id_categoria = t.id_categoria
                INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
                WHERE t.estado <> 'cancelado'
                ORDER BY ii.fecha_inscripcion DESC, ii.id_inscripcion DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerIndividualPorId(int $idInscripcion): array|false
    {
        $sql = "SELECT
                    ii.id_inscripcion,
                    ii.id_torneo,
                    ii.id_usuario,
                    ii.fecha_inscripcion,
                    ii.estado,
                    u.nombre_completo,
                    u.nombre_usuario,
                    t.nombre AS torneo,
                    t.id_organizador,
                    t.modalidad,
                    t.estado AS torneo_estado,
                    EXISTS(SELECT 1 FROM enfrentamientos enc WHERE enc.id_torneo = t.id_torneo LIMIT 1) AS competencia_iniciada,
                    d.nombre AS disciplina,
                    c.nombre AS categoria,
                    tt.nombre AS tipo_torneo
                FROM inscripciones_individuales ii
                INNER JOIN usuarios u ON u.id_usuario = ii.id_usuario
                INNER JOIN torneos t ON t.id_torneo = ii.id_torneo
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                INNER JOIN categorias c ON c.id_categoria = t.id_categoria
                INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
                WHERE ii.id_inscripcion = :id_inscripcion
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_inscripcion' => $idInscripcion]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public function usuarioActivoPorNombre(string $nombreUsuario): array|false
    {
        $sql = "SELECT u.id_usuario, u.nombre_completo, u.nombre_usuario, u.correo
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

    public function torneoPorId(int $idTorneo): array|false
    {
        $sql = "SELECT
                    t.id_torneo,
                    t.nombre,
                    t.modalidad,
                    t.estado,
                    t.cupo_maximo,
                    t.id_organizador,
                    c.nombre AS categoria,
                    d.nombre AS disciplina
                FROM torneos t
                INNER JOIN categorias c ON c.id_categoria = t.id_categoria
                INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                WHERE t.id_torneo = :id_torneo
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_torneo' => $idTorneo]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public function existeIndividual(int $idTorneo, int $idUsuario, int $idExcluir = 0): bool
    {
        $sql = "SELECT id_inscripcion
                FROM inscripciones_individuales
                WHERE id_torneo = :id_torneo
                  AND id_usuario = :id_usuario";

        $parametros = [
            ':id_torneo' => $idTorneo,
            ':id_usuario' => $idUsuario
        ];

        if ($idExcluir > 0) {
            $sql .= " AND id_inscripcion <> :id_excluir";
            $parametros[':id_excluir'] = $idExcluir;
        }

        $sql .= " LIMIT 1";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetch() !== false;
    }

    public function crearIndividual(int $idTorneo, int $idUsuario): int
    {
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
            throw new DomainException('La cuenta indicada no está disponible para participar en torneos.');
        }

        $sql = "INSERT INTO inscripciones_individuales
                    (id_torneo, id_usuario, estado)
                VALUES
                    (:id_torneo, :id_usuario, 'pendiente')";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':id_torneo' => $idTorneo,
            ':id_usuario' => $idUsuario
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function actualizarIndividual(
        int $idInscripcion,
        int $idTorneo,
        int $idUsuario,
        string $estado
    ): bool {
        if (!in_array($estado, ['pendiente', 'aprobada', 'rechazada'], true)) {
            throw new InvalidArgumentException('Estado de inscripción no válido.');
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
            throw new DomainException('La cuenta indicada no está disponible para participar en torneos.');
        }

        $sql = "UPDATE inscripciones_individuales
                SET id_torneo = :id_torneo,
                    id_usuario = :id_usuario,
                    estado = :estado
                WHERE id_inscripcion = :id_inscripcion";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':id_torneo' => $idTorneo,
            ':id_usuario' => $idUsuario,
            ':estado' => $estado,
            ':id_inscripcion' => $idInscripcion
        ]);

        return true;
    }

    public function eliminarIndividual(int $idInscripcion): bool
    {
        $consulta = $this->conexion->prepare(
            "DELETE FROM inscripciones_individuales WHERE id_inscripcion = :id_inscripcion"
        );
        $consulta->execute([':id_inscripcion' => $idInscripcion]);

        return $consulta->rowCount() === 1;
    }

    public function equipoPorId(int $idEquipo): array|false
    {
        $consulta = $this->conexion->prepare(
            "SELECT id_equipo, nombre, id_creador, estado
             FROM equipos
             WHERE id_equipo = :id_equipo
             LIMIT 1"
        );
        $consulta->execute([':id_equipo' => $idEquipo]);
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public function inscribirEquipoExistente(int $idEquipo, int $idTorneo): int
    {
        try {
            $this->conexion->beginTransaction();

            $consultaExiste = $this->conexion->prepare(
                "SELECT id_inscripcion
                 FROM inscripciones_equipos
                 WHERE id_torneo = :id_torneo AND id_equipo = :id_equipo
                 LIMIT 1"
            );
            $consultaExiste->execute([':id_torneo' => $idTorneo, ':id_equipo' => $idEquipo]);
            if ($consultaExiste->fetch() !== false) {
                throw new DomainException('Ese equipo ya tiene una inscripción en este torneo.');
            }

            $consultaIntegrantes = $this->conexion->prepare(
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
                 ORDER BY ieq.id_usuario ASC"
            );
            $consultaIntegrantes->execute([':id_equipo' => $idEquipo]);
            $plantel = $consultaIntegrantes->fetchAll(PDO::FETCH_ASSOC);
            if (count($plantel) < 2) {
                throw new DomainException('El equipo necesita al menos dos integrantes actuales para volver a inscribirse.');
            }
            $inactivos = array_filter($plantel, static fn(array $fila): bool => (string) $fila['estado'] !== 'activo');
            if ($inactivos) {
                throw new DomainException('El equipo tiene integrantes que ya no poseen una cuenta activa. Actualiza el plantel antes de inscribirlo.');
            }
            $administradores = array_filter($plantel, static fn(array $fila): bool => (int) ($fila['es_administrador'] ?? 0) === 1);
            if ($administradores) {
                throw new DomainException('Los administradores no pueden participar como integrantes de un torneo. Actualiza el plantel antes de inscribirlo.');
            }
            $ids = array_map(static fn(array $fila): int => (int) $fila['id_usuario'], $plantel);

            $consultaInscripcion = $this->conexion->prepare(
                "INSERT INTO inscripciones_equipos (id_torneo, id_equipo, estado)
                 VALUES (:id_torneo, :id_equipo, 'pendiente')"
            );
            $consultaInscripcion->execute([':id_torneo' => $idTorneo, ':id_equipo' => $idEquipo]);
            $idInscripcion = (int) $this->conexion->lastInsertId();

            $consultaSnapshot = $this->conexion->prepare(
                "INSERT INTO integrantes_inscripcion_equipo (id_inscripcion, id_usuario)
                 VALUES (:id_inscripcion, :id_usuario)"
            );
            foreach ($ids as $idUsuario) {
                $consultaSnapshot->execute([':id_inscripcion' => $idInscripcion, ':id_usuario' => $idUsuario]);
            }

            $this->conexion->commit();
            return $idInscripcion;
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    public function contarInscritosEnTorneo(int $idTorneo, string $modalidad): int
    {
        $tabla = $modalidad === 'equipo' ? 'inscripciones_equipos' : 'inscripciones_individuales';
        $consulta = $this->conexion->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE id_torneo = :id_torneo AND estado <> 'rechazada'"
        );
        $consulta->execute([':id_torneo' => $idTorneo]);

        return (int) $consulta->fetchColumn();
    }
}
