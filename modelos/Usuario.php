<?php

class Usuario
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function existeNombreUsuario(string $nombreUsuario): bool
    {
        $sql = "SELECT id_usuario
                FROM usuarios
                WHERE nombre_usuario = :nombre_usuario
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':nombre_usuario' => $nombreUsuario
        ]);

        return $consulta->fetch() !== false;
    }

    public function existeCorreo(string $correo): bool
    {
        $sql = "SELECT id_usuario
                FROM usuarios
                WHERE correo = :correo
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':correo' => $correo
        ]);

        return $consulta->fetch() !== false;
    }

    public function registrar(
        string $nombreCompleto,
        string $nombreUsuario,
        string $correo,
        string $contrasena,
        string $preguntaRecuperacion,
        string $respuestaRecuperacion
    ): int {
        $propietarioTransaccion = !$this->conexion->inTransaction();
        if ($propietarioTransaccion) {
            $this->conexion->beginTransaction();
        }

        try {
            $sql = "INSERT INTO usuarios
                    (
                        nombre_completo,
                        nombre_usuario,
                        correo,
                        contrasena,
                        pregunta_recuperacion,
                        respuesta_recuperacion,
                        estado
                    )
                    VALUES
                    (
                        :nombre_completo,
                        :nombre_usuario,
                        :correo,
                        :contrasena,
                        :pregunta_recuperacion,
                        :respuesta_recuperacion,
                        'pendiente'
                    )";

            $consulta = $this->conexion->prepare($sql);

            $consulta->execute([
                ':nombre_completo' => $nombreCompleto,
                ':nombre_usuario' => $nombreUsuario,
                ':correo' => $correo,
                ':contrasena' => $contrasena,
                ':pregunta_recuperacion' => $preguntaRecuperacion,
                ':respuesta_recuperacion' => $respuestaRecuperacion
            ]);

            $idUsuario = (int) $this->conexion->lastInsertId();

            $sqlRol = "INSERT INTO usuario_rol
                       (id_usuario, id_rol)
                       SELECT :id_usuario, id_rol
                       FROM roles
                       WHERE nombre = 'participante'";

            $consultaRol = $this->conexion->prepare($sqlRol);

            $consultaRol->execute([
                ':id_usuario' => $idUsuario
            ]);

            if ($consultaRol->rowCount() !== 1) {
                throw new RuntimeException(
                    'No se encontró el rol participante.'
                );
            }

            if ($propietarioTransaccion) {
                $this->conexion->commit();
            }

            return $idUsuario;

        } catch (Throwable $error) {

            if ($propietarioTransaccion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }

    public function buscarParaLogin(string $nombreUsuario): array|false
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.correo,
                    u.contrasena,
                    u.estado,
                    u.version_sesion,
                    u.login_intentos,
                    u.login_bloqueado_hasta,
                    GROUP_CONCAT(
                        r.nombre
                        ORDER BY r.nombre
                        SEPARATOR ','
                    ) AS roles
                FROM usuarios u

                LEFT JOIN usuario_rol ur
                    ON u.id_usuario = ur.id_usuario

                LEFT JOIN roles r
                    ON ur.id_rol = r.id_rol

                WHERE u.nombre_usuario = :nombre_usuario

                GROUP BY
                    u.id_usuario,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.correo,
                    u.contrasena,
                    u.estado,
                    u.version_sesion,
                    u.login_intentos,
                    u.login_bloqueado_hasta

                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':nombre_usuario' => $nombreUsuario
        ]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public function registrarIntentoLoginFallido(int $idUsuario, int $maxIntentos = 5, int $minutosBloqueo = 15): array
    {
        if ($maxIntentos < 1 || $minutosBloqueo < 1) {
            throw new InvalidArgumentException('La política de bloqueo no es válida.');
        }

        $propietarioTransaccion = !$this->conexion->inTransaction();
        if ($propietarioTransaccion) {
            $this->conexion->beginTransaction();
        }

        try {
            $consulta = $this->conexion->prepare(
                "SELECT login_intentos, login_bloqueado_hasta
                 FROM usuarios
                 WHERE id_usuario = :id_usuario
                 FOR UPDATE"
            );
            $consulta->execute([':id_usuario' => $idUsuario]);
            $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                if ($propietarioTransaccion && $this->conexion->inTransaction()) {
                    $this->conexion->rollBack();
                }
                return ['bloqueado' => false, 'intentos_restantes' => $maxIntentos];
            }

            $ahora = new DateTimeImmutable();
            $bloqueadoHastaActual = !empty($usuario['login_bloqueado_hasta'])
                ? new DateTimeImmutable($usuario['login_bloqueado_hasta'])
                : null;

            if ($bloqueadoHastaActual && $bloqueadoHastaActual > $ahora) {
                if ($propietarioTransaccion) {
                    $this->conexion->commit();
                }
                return [
                    'bloqueado' => true,
                    'bloqueado_hasta' => $bloqueadoHastaActual->format('Y-m-d H:i:s'),
                    'intentos_restantes' => 0
                ];
            }

            $intentos = $bloqueadoHastaActual ? 0 : (int) $usuario['login_intentos'];
            $intentos++;
            $bloqueado = $intentos >= $maxIntentos;
            $bloqueadoHasta = null;

            if ($bloqueado) {
                $bloqueadoHasta = $ahora->modify('+' . $minutosBloqueo . ' minutes');
                $intentos = 0;
            }

            $actualizar = $this->conexion->prepare(
                "UPDATE usuarios
                 SET login_intentos = :login_intentos,
                     login_bloqueado_hasta = :login_bloqueado_hasta
                 WHERE id_usuario = :id_usuario"
            );
            $actualizar->execute([
                ':login_intentos' => $intentos,
                ':login_bloqueado_hasta' => $bloqueadoHasta?->format('Y-m-d H:i:s'),
                ':id_usuario' => $idUsuario
            ]);

            if ($propietarioTransaccion) {
                $this->conexion->commit();
            }

            return [
                'bloqueado' => $bloqueado,
                'bloqueado_hasta' => $bloqueadoHasta?->format('Y-m-d H:i:s'),
                'intentos_restantes' => $bloqueado ? 0 : max(0, $maxIntentos - $intentos)
            ];
        } catch (Throwable $error) {
            if ($propietarioTransaccion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    public function limpiarIntentosLogin(int $idUsuario): void
    {
        $consulta = $this->conexion->prepare(
            "UPDATE usuarios
             SET login_intentos = 0,
                 login_bloqueado_hasta = NULL
             WHERE id_usuario = :id_usuario"
        );
        $consulta->execute([':id_usuario' => $idUsuario]);
    }

    public function buscarPorId(int $idUsuario): array|false
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.correo,
                    u.estado,
                    u.version_sesion,
                    GROUP_CONCAT(
                        r.nombre
                        ORDER BY r.nombre
                        SEPARATOR ','
                    ) AS roles
                FROM usuarios u

                LEFT JOIN usuario_rol ur
                    ON u.id_usuario = ur.id_usuario

                LEFT JOIN roles r
                    ON ur.id_rol = r.id_rol

                WHERE u.id_usuario = :id_usuario

                GROUP BY
                    u.id_usuario,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.correo,
                    u.estado,
                    u.version_sesion

                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':id_usuario' => $idUsuario
        ]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }


    public function listarPendientes(): array
    {
        $sql = "SELECT
                    id_usuario,
                    nombre_completo,
                    nombre_usuario,
                    correo,
                    estado,
                    fecha_registro
                FROM usuarios
                WHERE estado = 'pendiente'
                ORDER BY fecha_registro ASC";

        $consulta = $this->conexion->query($sql);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorEstado(string $estado): int
    {
        $estadosPermitidos = ['pendiente', 'activo', 'inactivo', 'bloqueado'];

        if (!in_array($estado, $estadosPermitidos, true)) {
            throw new InvalidArgumentException('Estado de usuario no válido.');
        }

        $sql = "SELECT COUNT(*)
                FROM usuarios
                WHERE estado = :estado";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':estado' => $estado
        ]);

        return (int) $consulta->fetchColumn();
    }

    public function cambiarEstado(int $idUsuario, string $estado): bool
    {
        $estadosPermitidos = ['activo', 'inactivo', 'bloqueado'];

        if (!in_array($estado, $estadosPermitidos, true)) {
            throw new InvalidArgumentException('Estado de usuario no válido.');
        }

        $sql = "UPDATE usuarios
                SET estado = :estado
                WHERE id_usuario = :id_usuario
                  AND estado = 'pendiente'";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':estado' => $estado,
            ':id_usuario' => $idUsuario
        ]);

        return $consulta->rowCount() === 1;
    }


    public function nombreUsuarioDisponible(string $nombreUsuario, int $idUsuarioExcluir = 0): bool
    {
        $sql = "SELECT id_usuario
                FROM usuarios
                WHERE nombre_usuario = :nombre_usuario";

        $parametros = [
            ':nombre_usuario' => $nombreUsuario
        ];

        if ($idUsuarioExcluir > 0) {
            $sql .= " AND id_usuario <> :id_usuario_excluir";
            $parametros[':id_usuario_excluir'] = $idUsuarioExcluir;
        }

        $sql .= " LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetch() === false;
    }

    public function actualizarNombreUsuario(int $idUsuario, string $nombreUsuario): bool
    {
        $sql = "UPDATE usuarios
                SET nombre_usuario = :nombre_usuario
                WHERE id_usuario = :id_usuario";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':nombre_usuario' => $nombreUsuario,
            ':id_usuario' => $idUsuario
        ]);

        return $consulta->rowCount() === 1;
    }


    public function listarActivosConRoles(): array
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.correo,
                    u.estado,
                    u.fecha_registro,
                    GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR ',') AS roles
                FROM usuarios u
                LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                LEFT JOIN roles r ON r.id_rol = ur.id_rol
                WHERE u.estado IN ('activo', 'inactivo', 'bloqueado')
                GROUP BY u.id_usuario, u.nombre_completo, u.nombre_usuario, u.correo, u.estado, u.fecha_registro
                ORDER BY u.nombre_completo ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarRoles(): array
    {
        $consulta = $this->conexion->prepare("SELECT id_rol, nombre FROM roles ORDER BY id_rol ASC");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarGestion(int $idUsuario, string $estado, array $roles): bool
    {
        if (!in_array($estado, ['activo', 'inactivo', 'bloqueado'], true)) {
            throw new InvalidArgumentException('Estado de usuario no válido.');
        }

        $roles = array_values(array_unique(array_filter(array_map('strval', $roles))));
        if (!$roles) {
            throw new InvalidArgumentException('El usuario debe conservar al menos un rol.');
        }

        $propietarioTransaccion = !$this->conexion->inTransaction();
        if ($propietarioTransaccion) {
            $this->conexion->beginTransaction();
        }

        try {
            $marcadores = implode(',', array_fill(0, count($roles), '?'));
            $consultaRoles = $this->conexion->prepare(
                "SELECT id_rol, nombre FROM roles WHERE nombre IN ($marcadores)"
            );
            $consultaRoles->execute($roles);
            $rolesValidos = $consultaRoles->fetchAll(PDO::FETCH_ASSOC);

            if (count($rolesValidos) !== count($roles)) {
                throw new InvalidArgumentException('Uno de los roles seleccionados no es válido.');
            }

            $consultaUsuario = $this->conexion->prepare(
                "UPDATE usuarios SET estado = :estado WHERE id_usuario = :id_usuario"
            );
            $consultaUsuario->execute([
                ':estado' => $estado,
                ':id_usuario' => $idUsuario
            ]);

            $this->conexion->prepare("DELETE FROM usuario_rol WHERE id_usuario = :id_usuario")
                ->execute([':id_usuario' => $idUsuario]);

            $insertar = $this->conexion->prepare(
                "INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (:id_usuario, :id_rol)"
            );
            foreach ($rolesValidos as $rol) {
                $insertar->execute([
                    ':id_usuario' => $idUsuario,
                    ':id_rol' => (int) $rol['id_rol']
                ]);
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

    public function contarDependencias(int $idUsuario): array
    {
        $consultas = [
            'torneos' => "SELECT COUNT(*) FROM torneos WHERE id_organizador = :id_usuario",
            'equipos' => "SELECT COUNT(*) FROM equipos WHERE id_creador = :id_usuario",
            'inscripciones' => "SELECT COUNT(*) FROM inscripciones_individuales WHERE id_usuario = :id_usuario",
            'integrantes_historicos' => "SELECT COUNT(*) FROM integrantes_inscripcion_equipo WHERE id_usuario = :id_usuario",
            'integrantes_actuales' => "SELECT COUNT(*) FROM integrantes_equipo WHERE id_usuario = :id_usuario",
            'invitaciones' => "SELECT COUNT(*) FROM invitaciones_torneo WHERE :id_usuario IN (id_invitado, id_invitador)"
        ];
        $resultado = [];
        foreach ($consultas as $clave => $sql) {
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([':id_usuario' => $idUsuario]);
            $resultado[$clave] = (int) $consulta->fetchColumn();
        }
        return $resultado;
    }

    public function eliminarSinDependencias(int $idUsuario): bool
    {
        $dependencias = $this->contarDependencias($idUsuario);
        if (array_sum($dependencias) > 0) {
            return false;
        }

        $propietarioTransaccion = !$this->conexion->inTransaction();
        if ($propietarioTransaccion) {
            $this->conexion->beginTransaction();
        }

        try {
            $this->conexion->prepare("DELETE FROM usuario_rol WHERE id_usuario = :id_usuario")
                ->execute([':id_usuario' => $idUsuario]);
            $consulta = $this->conexion->prepare("DELETE FROM usuarios WHERE id_usuario = :id_usuario");
            $consulta->execute([':id_usuario' => $idUsuario]);
            if ($consulta->rowCount() !== 1) {
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


    public function buscarRecuperacion(string $identificador): array|false
    {
        $identificador = trim($identificador);
        $usuario = strtolower(ltrim($identificador, '@'));
        $sql = "SELECT id_usuario,nombre_completo,nombre_usuario,correo,pregunta_recuperacion,respuesta_recuperacion,estado,recuperacion_intentos,recuperacion_bloqueada_hasta
                FROM usuarios
                WHERE LOWER(nombre_completo)=LOWER(:identificador)
                   OR LOWER(nombre_usuario)=:usuario
                   OR LOWER(correo)=LOWER(:identificador)
                ORDER BY id_usuario ASC LIMIT 2";
        $q=$this->conexion->prepare($sql);$q->execute([':identificador'=>$identificador,':usuario'=>$usuario]);
        $filas=$q->fetchAll(PDO::FETCH_ASSOC);
        return count($filas)===1 ? $filas[0] : false;
    }

    public function configurarRecuperacion(int $idUsuario, string $pregunta, string $respuestaHash): bool
    {
        $q=$this->conexion->prepare("UPDATE usuarios SET pregunta_recuperacion=:p,respuesta_recuperacion=:r WHERE id_usuario=:id");
        return $q->execute([':p'=>$pregunta,':r'=>$respuestaHash,':id'=>$idUsuario]);
    }

    public function actualizarContrasena(int $idUsuario, string $hash): bool
    {
        $q=$this->conexion->prepare("UPDATE usuarios SET contrasena=:c, version_sesion=version_sesion+1 WHERE id_usuario=:id");
        return $q->execute([':c'=>$hash,':id'=>$idUsuario]);
    }

    public function obtenerHashContrasena(int $idUsuario): string|false
    {
        $q=$this->conexion->prepare("SELECT contrasena FROM usuarios WHERE id_usuario=:id LIMIT 1");
        $q->execute([':id'=>$idUsuario]);
        $v=$q->fetchColumn(); return $v===false?false:(string)$v;
    }

    public function obtenerVersionSesion(int $idUsuario): int
    {
        $q=$this->conexion->prepare("SELECT version_sesion FROM usuarios WHERE id_usuario=:id LIMIT 1");
        $q->execute([':id'=>$idUsuario]);
        return (int) ($q->fetchColumn() ?: 0);
    }

}