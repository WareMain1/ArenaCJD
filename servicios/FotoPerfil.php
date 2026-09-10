<?php

class FotoPerfil
{
    private string $directorio;

    private array $tiposPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    public function __construct()
    {
        $this->directorio = dirname(__DIR__) . '/storage/perfiles';
    }

    public function guardar(int $idUsuario, array $archivo): string
    {
        if (!isset($archivo['error'], $archivo['tmp_name'], $archivo['size'])) {
            throw new InvalidArgumentException('No se recibió una imagen válida.');
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->mensajeErrorSubida((int) $archivo['error']));
        }

        if ((int) $archivo['size'] <= 0 || (int) $archivo['size'] > 3 * 1024 * 1024) {
            throw new InvalidArgumentException('La imagen debe pesar como máximo 3 MB.');
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

        if ($ancho < 80 || $alto < 80) {
            throw new InvalidArgumentException('La imagen debe tener al menos 80 × 80 píxeles.');
        }

        if ($ancho > 6000 || $alto > 6000) {
            throw new InvalidArgumentException('La imagen tiene dimensiones demasiado grandes.');
        }

        $this->asegurarDirectorio();
        $this->eliminar($idUsuario);

        $extension = $this->tiposPermitidos[$mime];
        $destino = $this->directorio . '/usuario_' . $idUsuario . '.' . $extension;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            throw new RuntimeException('No se pudo guardar la foto de perfil.');
        }

        @chmod($destino, 0644);

        return $destino;
    }

    public function eliminar(int $idUsuario): bool
    {
        $eliminada = false;

        foreach ($this->extensionesPermitidas() as $extension) {
            $ruta = $this->directorio . '/usuario_' . $idUsuario . '.' . $extension;

            if (is_file($ruta)) {
                if (!@unlink($ruta)) {
                    throw new RuntimeException('No se pudo eliminar la foto de perfil.');
                }

                $eliminada = true;
            }
        }

        return $eliminada;
    }

    public function obtenerRuta(int $idUsuario): ?string
    {
        foreach ($this->extensionesPermitidas() as $extension) {
            $ruta = $this->directorio . '/usuario_' . $idUsuario . '.' . $extension;

            if (is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    public function existe(int $idUsuario): bool
    {
        return $this->obtenerRuta($idUsuario) !== null;
    }

    public function obtenerMime(int $idUsuario): ?string
    {
        $ruta = $this->obtenerRuta($idUsuario);

        if ($ruta === null) {
            return null;
        }

        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mimes = [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp'
        ];

        return $mimes[$extension] ?? null;
    }

    public function obtenerVersion(int $idUsuario): int
    {
        $ruta = $this->obtenerRuta($idUsuario);

        return $ruta !== null ? (int) filemtime($ruta) : 0;
    }

    private function asegurarDirectorio(): void
    {
        if (!is_dir($this->directorio) && !mkdir($this->directorio, 0755, true) && !is_dir($this->directorio)) {
            throw new RuntimeException('No se pudo preparar el directorio de fotos de perfil.');
        }
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
            UPLOAD_ERR_NO_FILE => 'Selecciona una imagen para tu perfil.',
            default => 'No se pudo recibir la imagen seleccionada.'
        };
    }
}
