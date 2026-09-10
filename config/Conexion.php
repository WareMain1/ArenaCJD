<?php

class Conexion
{
    private string $host;
    private string $baseDatos;
    private string $usuario;
    private string $contrasena;
    private string $zonaHoraria;

    public function __construct()
    {
        $this->host = getenv('ARENA_DB_HOST') ?: 'localhost';
        $this->baseDatos = getenv('ARENA_DB_NAME') ?: 'arenacjd';
        $this->usuario = getenv('ARENA_DB_USER') ?: 'arenacjd_app';
        $this->contrasena = (string) (getenv('ARENA_DB_PASS') ?: '');

        if ($this->contrasena === '') {
            throw new RuntimeException('Falta configurar la variable de entorno ARENA_DB_PASS.');
        }
        $this->zonaHoraria = getenv('ARENA_TIMEZONE') ?: 'America/Montevideo';
        date_default_timezone_set($this->zonaHoraria);
    }

    public function conectar(): PDO
    {
        try {
            $conexion = new PDO(
                "mysql:host={$this->host};dbname={$this->baseDatos};charset=utf8mb4",
                $this->usuario,
                $this->contrasena
            );

            $conexion->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $zona = new DateTimeZone($this->zonaHoraria);
            $offset = (new DateTimeImmutable('now', $zona))->format('P');
            $conexion->exec('SET time_zone = ' . $conexion->quote($offset));

            return $conexion;
        } catch (PDOException $error) {
            throw new RuntimeException('No se pudo establecer la conexión con la base de datos.', 0, $error);
        }
    }
}