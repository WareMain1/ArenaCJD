<?php

class PreferenciaUsuario
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtener(int $idUsuario): array
    {
        $consulta = $this->conexion->prepare(
            "SELECT configuracion_json
             FROM preferencias_usuario
             WHERE id_usuario = :id_usuario
             LIMIT 1"
        );
        $consulta->execute([':id_usuario' => $idUsuario]);
        $json = $consulta->fetchColumn();

        if ($json === false || $json === null || $json === '') {
            return [];
        }

        $datos = json_decode((string) $json, true);
        return is_array($datos) ? $datos : [];
    }

    public function guardar(int $idUsuario, array $preferencias): bool
    {
        $json = json_encode($preferencias, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('No se pudieron serializar las preferencias.');
        }

        $consulta = $this->conexion->prepare(
            "INSERT INTO preferencias_usuario (id_usuario, configuracion_json)
             VALUES (:id_usuario, :configuracion_json)
             ON DUPLICATE KEY UPDATE configuracion_json = VALUES(configuracion_json)"
        );

        return $consulta->execute([
            ':id_usuario' => $idUsuario,
            ':configuracion_json' => $json
        ]);
    }
}
