<?php

class Conexion
{
    private string $host;
    private string $puerto;
    private string $baseDatos;
    private string $usuario;
    private string $contrasena;
    private string $zonaHoraria;

    public function __construct()
    {
        if (file_exists(__DIR__ . '/entorno.local.php')) {
            require_once __DIR__ . '/entorno.local.php';
        }

        $this->host = $this->leerEntorno('ARENA_DB_HOST', 'localhost');
        $this->puerto = $this->leerEntorno('ARENA_DB_PORT', '3306');
        $this->baseDatos = $this->leerEntorno('ARENA_DB_NAME', 'arenacjd');
        $this->usuario = $this->leerEntorno('ARENA_DB_USER', 'arenacjd_app');
        $this->contrasena = $this->leerEntorno('ARENA_DB_PASS');

        if ($this->contrasena === '') {
            throw new RuntimeException('Falta configurar la variable de entorno ARENA_DB_PASS.');
        }

        $this->zonaHoraria = $this->leerEntorno('ARENA_TIMEZONE', 'America/Montevideo');
        date_default_timezone_set($this->zonaHoraria);
    }

    public function conectar(): PDO
    {
        try {
            $conexion = new PDO(
                "mysql:host={$this->host};port={$this->puerto};dbname={$this->baseDatos};charset=utf8mb4",
                $this->usuario,
                $this->contrasena
            );

            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $zona = new DateTimeZone($this->zonaHoraria);
            $offset = (new DateTimeImmutable('now', $zona))->format('P');
            $conexion->exec('SET time_zone = ' . $conexion->quote($offset));

            return $conexion;
        } catch (PDOException $error) {
            throw new RuntimeException('No se pudo establecer la conexión con la base de datos.', 0, $error);
        }
    }

    private function leerEntorno(string $nombre, string $predeterminado = ''): string
    {
        $valor = $_ENV[$nombre] ?? $_SERVER[$nombre] ?? getenv($nombre);

        if ($valor === false || $valor === null || trim((string) $valor) === '') {
            return $predeterminado;
        }

        return (string) $valor;
    }
}
