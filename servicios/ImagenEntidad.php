<?php

class ImagenEntidad
{
    private string $base;
    private array $tiposPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    public function __construct()
    {
        $this->base = dirname(__DIR__) . '/storage';
    }

    public function guardar(string $tipo, int $id, array $archivo): string
    {
        $directorio = $this->directorio($tipo);
        if (!isset($archivo['error'], $archivo['tmp_name'], $archivo['size'])) {
            throw new InvalidArgumentException('No se recibió una imagen válida.');
        }
        if ((int) $archivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->mensajeErrorSubida((int) $archivo['error']));
        }
        if ((int) $archivo['size'] <= 0 || (int) $archivo['size'] > 4 * 1024 * 1024) {
            throw new InvalidArgumentException('La imagen debe pesar como máximo 4 MB.');
        }
        if (!is_uploaded_file($archivo['tmp_name'])) {
            throw new RuntimeException('El archivo recibido no es una subida válida.');
        }
        $informacion = @getimagesize($archivo['tmp_name']);
        if ($informacion === false || empty($informacion['mime'])) {
            throw new InvalidArgumentException('El archivo seleccionado no es una imagen válida.');
        }
        $mime = strtolower((string) $informacion['mime']);
        if (!isset($this->tiposPermitidos[$mime])) {
            throw new InvalidArgumentException('Solo se permiten imágenes JPG, PNG o WebP.');
        }
        $ancho = (int) ($informacion[0] ?? 0);
        $alto = (int) ($informacion[1] ?? 0);
        if ($ancho < 120 || $alto < 120) {
            throw new InvalidArgumentException('La imagen debe tener al menos 120 × 120 píxeles.');
        }
        if ($ancho > 6000 || $alto > 6000) {
            throw new InvalidArgumentException('La imagen tiene dimensiones demasiado grandes.');
        }
        $this->asegurarDirectorio($directorio);
        $this->eliminar($tipo, $id);
        $extension = $this->tiposPermitidos[$mime];
        $destino = $directorio . '/' . $this->prefijo($tipo) . '_' . $id . '.' . $extension;
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }
        @chmod($destino, 0644);
        return $destino;
    }

    public function eliminar(string $tipo, int $id): bool
    {
        $directorio = $this->directorio($tipo);
        $eliminada = false;
        foreach ($this->extensionesPermitidas() as $extension) {
            $ruta = $directorio . '/' . $this->prefijo($tipo) . '_' . $id . '.' . $extension;
            if (is_file($ruta)) {
                if (!@unlink($ruta)) {
                    throw new RuntimeException('No se pudo eliminar la imagen.');
                }
                $eliminada = true;
            }
        }
        return $eliminada;
    }

    public function obtenerRuta(string $tipo, int $id): ?string
    {
        $directorio = $this->directorio($tipo);
        foreach ($this->extensionesPermitidas() as $extension) {
            $ruta = $directorio . '/' . $this->prefijo($tipo) . '_' . $id . '.' . $extension;
            if (is_file($ruta)) return $ruta;
        }
        return null;
    }

    public function obtenerMime(string $tipo, int $id): ?string
    {
        $ruta = $this->obtenerRuta($tipo, $id);
        if ($ruta === null) return null;
        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        return ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][$extension] ?? null;
    }

    public function obtenerVersion(string $tipo, int $id): int
    {
        $ruta = $this->obtenerRuta($tipo, $id);
        return $ruta !== null ? (int) filemtime($ruta) : 0;
    }

    public function existe(string $tipo, int $id): bool
    {
        return $this->obtenerRuta($tipo, $id) !== null;
    }

    private function directorio(string $tipo): string
    {
        return match ($tipo) {
            'torneo' => $this->base . '/torneos',
            'equipo' => $this->base . '/equipos',
            default => throw new InvalidArgumentException('Tipo de imagen no válido.')
        };
    }

    private function prefijo(string $tipo): string
    {
        return $tipo === 'torneo' ? 'torneo' : 'equipo';
    }

    private function asegurarDirectorio(string $directorio): void
    {
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo preparar el directorio de imágenes.');
        }
        $htaccess = $directorio . '/.htaccess';
        if (!is_file($htaccess)) @file_put_contents($htaccess, "Options -Indexes\nRequire all denied\n");
    }

    private function extensionesPermitidas(): array
    {
        return array_values(array_unique($this->tiposPermitidos));
    }

    private function mensajeErrorSubida(int $codigo): string
    {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño permitido por el servidor.',
            UPLOAD_ERR_PARTIAL => 'La imagen se subió de forma incompleta.',
            UPLOAD_ERR_NO_FILE => 'Selecciona una imagen.',
            default => 'No se pudo recibir la imagen seleccionada.'
        };
    }
}
