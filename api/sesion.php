<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../modelos/Usuario.php';
require_once __DIR__ . '/../servicios/FotoPerfil.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'No hay una sesión activa.'
    ]);

    exit;
}

try {
    $conexionBD = new Conexion();
    $conexion = $conexionBD->conectar();

    $modeloUsuario = new Usuario($conexion);

    $usuario = $modeloUsuario->buscarPorId(
        (int) $_SESSION['usuario_id']
    );

    if (!$usuario) {
        $_SESSION = [];
        session_destroy();

        http_response_code(401);

        echo json_encode([
            'exito' => false,
            'mensaje' => 'El usuario de la sesión ya no existe.'
        ]);

        exit;
    }

    if ($usuario['estado'] !== 'activo') {
        $_SESSION = [];
        session_destroy();

        http_response_code(401);

        echo json_encode([
            'exito' => false,
            'mensaje' => 'La cuenta ya no está activa.'
        ]);

        exit;
    }

    if (!isset($_SESSION['version_sesion']) || (int) $_SESSION['version_sesion'] !== (int) ($usuario['version_sesion'] ?? 0)) {
        $_SESSION = [];
        session_destroy();
        http_response_code(401);
        echo json_encode([
            'exito' => false,
            'mensaje' => 'La sesión fue invalidada por un cambio de seguridad.'
        ]);
        exit;
    }

    $rolesActuales = $usuario['roles']
        ? explode(',', $usuario['roles'])
        : [];

    $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
    $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
    $_SESSION['roles'] = $rolesActuales;

    $fotoPerfil = new FotoPerfil();
    $idUsuario = (int) $usuario['id_usuario'];
    $tieneFotoPerfil = $fotoPerfil->existe($idUsuario);
    $versionFotoPerfil = $tieneFotoPerfil
        ? $fotoPerfil->obtenerVersion($idUsuario)
        : 0;

    echo json_encode([
        'exito' => true,
        'usuario' => [
            'id' => (int) $usuario['id_usuario'],
            'nombre' => $usuario['nombre_completo'],
            'nombre_usuario' => $usuario['nombre_usuario'],
            'correo' => $usuario['correo'],
            'estado' => $usuario['estado'],
            'roles' => $rolesActuales,
            'tiene_foto_perfil' => $tieneFotoPerfil,
            'foto_perfil_url' => $tieneFotoPerfil
                ? 'api/foto_perfil.php?v=' . $versionFotoPerfil
                : null
        ],
        'csrf_token' => $_SESSION['csrf_token']
    ]);

} catch (Throwable $error) {
    http_response_code(500);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'No se pudo obtener la sesión.'
    ]);
}