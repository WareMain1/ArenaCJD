<?php

final class LimiteSolicitudes
{
    public static function consumir(string $accion, string $identificador, int $maximo, int $ventanaSegundos): array
    {
        $ahora = time();
        $directorio = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'limites';
        if (!is_dir($directorio) && !@mkdir($directorio, 0700, true) && !is_dir($directorio)) {
            return ['permitido' => true, 'reintentar_en' => 0];
        }

        $clave = hash('sha256', $accion . '|' . $identificador);
        $ruta = $directorio . DIRECTORY_SEPARATOR . $clave . '.json';
        $archivo = @fopen($ruta, 'c+');
        if ($archivo === false || !flock($archivo, LOCK_EX)) {
            if (is_resource($archivo)) fclose($archivo);
            return ['permitido' => true, 'reintentar_en' => 0];
        }

        try {
            rewind($archivo);
            $contenido = stream_get_contents($archivo);
            $intentos = json_decode($contenido ?: '[]', true);
            if (!is_array($intentos)) $intentos = [];
            $desde = $ahora - $ventanaSegundos;
            $intentos = array_values(array_filter($intentos, static fn ($marca): bool => is_int($marca) && $marca > $desde));

            if (count($intentos) >= $maximo) {
                return [
                    'permitido' => false,
                    'reintentar_en' => max(1, $ventanaSegundos - ($ahora - (int) $intentos[0]))
                ];
            }

            $intentos[] = $ahora;
            ftruncate($archivo, 0);
            rewind($archivo);
            fwrite($archivo, json_encode($intentos));
            fflush($archivo);
            return ['permitido' => true, 'reintentar_en' => 0];
        } finally {
            flock($archivo, LOCK_UN);
            fclose($archivo);
        }
    }
}
