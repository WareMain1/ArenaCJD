<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /ArenaCJD/index.php');
    exit;
}

require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../modelos/Usuario.php';
require_once __DIR__ . '/../servicios/FotoPerfil.php';

try {
    $conexionProteccion = (new Conexion())->conectar();
    $modeloProteccion = new Usuario($conexionProteccion);
    $usuarioProtegido = $modeloProteccion->buscarPorId((int) $_SESSION['usuario_id']);

    if (!$usuarioProtegido || $usuarioProtegido['estado'] !== 'activo') {
        $_SESSION = [];
        session_destroy();
        header('Location: /ArenaCJD/index.php');
        exit;
    }

    if (!isset($_SESSION['version_sesion']) || (int) $_SESSION['version_sesion'] !== (int) ($usuarioProtegido['version_sesion'] ?? 0)) {
        $_SESSION = [];
        session_destroy();
        header('Location: /ArenaCJD/index.php?sesion=invalidada');
        exit;
    }

    $rolesProtegidos = $usuarioProtegido['roles']
        ? explode(',', $usuarioProtegido['roles'])
        : [];

    $_SESSION['nombre_completo'] = $usuarioProtegido['nombre_completo'];
    $_SESSION['nombre_usuario'] = $usuarioProtegido['nombre_usuario'];
    $_SESSION['roles'] = $rolesProtegidos;

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $fotoPerfilProtegida = new FotoPerfil();
    $idUsuarioProtegido = (int) $usuarioProtegido['id_usuario'];
    $tieneFotoPerfilProtegida = $fotoPerfilProtegida->existe($idUsuarioProtegido);
    $versionFotoPerfilProtegida = $tieneFotoPerfilProtegida
        ? $fotoPerfilProtegida->obtenerVersion($idUsuarioProtegido)
        : 0;

    $datosSesionArenaCJD = [
        'usuario' => [
            'id' => $idUsuarioProtegido,
            'nombre' => (string) $usuarioProtegido['nombre_completo'],
            'nombre_usuario' => (string) $usuarioProtegido['nombre_usuario'],
            'correo' => (string) $usuarioProtegido['correo'],
            'estado' => (string) $usuarioProtegido['estado'],
            'roles' => $rolesProtegidos,
            'tiene_foto_perfil' => $tieneFotoPerfilProtegida,
            'foto_perfil_url' => $tieneFotoPerfilProtegida
                ? 'api/foto_perfil.php?v=' . $versionFotoPerfilProtegida
                : null
        ],
        'csrf_token' => (string) $_SESSION['csrf_token']
    ];
} catch (Throwable $error) {
    header('Location: /ArenaCJD/index.php');
    exit;
}

function atributosSesionArenaCJD(): string
{
    global $datosSesionArenaCJD;

    $json = json_encode(
        $datosSesionArenaCJD ?? [],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    return 'data-sesion-arena-cjd="' . htmlspecialchars(
        $json !== false ? $json : '{}',
        ENT_QUOTES,
        'UTF-8'
    ) . '"';
}

function esAdministradorSesionArenaCJD(): bool
{
    return in_array('administrador', $_SESSION['roles'] ?? [], true);
}

function puedeGestionarSesionArenaCJD(): bool
{
    $roles = $_SESSION['roles'] ?? [];

    return in_array('administrador', $roles, true)
        || in_array('organizador', $roles, true);
}
