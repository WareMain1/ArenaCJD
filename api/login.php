<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/_comun.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    http_response_code(400);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'Los datos enviados no son válidos.'
    ]);

    exit;
}

$nombreUsuario = strtolower(trim($datos['usuario'] ?? ''));
$contrasena = $datos['contrasena'] ?? '';
$recordar = (bool) ($datos['recordar'] ?? false);

$nombreUsuario = ltrim($nombreUsuario, '@');

if ($nombreUsuario === '' || $contrasena === '') {
    http_response_code(400);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'Debes completar usuario y contraseña.'
    ]);

    exit;
}

try {
    $conexionBD = new Conexion();
    $conexion = $conexionBD->conectar();

    $modeloUsuario = new Usuario($conexion);

    $usuario = $modeloUsuario->buscarParaLogin($nombreUsuario);

    if ($usuario && !empty($usuario['login_bloqueado_hasta'])) {
        $bloqueadoHasta = new DateTimeImmutable($usuario['login_bloqueado_hasta']);
        $ahora = new DateTimeImmutable();

        if ($bloqueadoHasta > $ahora) {
            $segundosRestantes = max(1, $bloqueadoHasta->getTimestamp() - $ahora->getTimestamp());
            registrarAuditoriaApi($conexion, (int) $usuario['id_usuario'], 'login_bloqueado_temporal', 'sesion', null, 'Intento durante bloqueo temporal', 'denegado');
            registrarEventoSeguridadApi('LOGIN_BLOCKED', $nombreUsuario);
            http_response_code(429);
            header('Retry-After: ' . $segundosRestantes);

            echo json_encode([
                'exito' => false,
                'mensaje' => 'Demasiados intentos fallidos. Intenta nuevamente más tarde.',
                'reintentar_en' => $segundosRestantes
            ]);

            exit;
        }
    }

    if (!$usuario || !password_verify($contrasena, $usuario['contrasena'])) {
        $estadoIntento = null;

        if ($usuario) {
            $estadoIntento = $modeloUsuario->registrarIntentoLoginFallido((int) $usuario['id_usuario'], 5, 15);
        }

        registrarAuditoriaApi($conexion, $usuario ? (int) $usuario['id_usuario'] : null, 'login_fallido', 'sesion', null, 'Intento de acceso para @' . $nombreUsuario, 'denegado');
        registrarEventoSeguridadApi('LOGIN_FAILED', $nombreUsuario);

        if ($estadoIntento && ($estadoIntento['bloqueado'] ?? false)) {
            registrarAuditoriaApi($conexion, (int) $usuario['id_usuario'], 'login_bloqueado_temporal', 'sesion', null, 'Bloqueo automático por 5 intentos fallidos', 'denegado');
            registrarEventoSeguridadApi('LOGIN_BLOCKED', $nombreUsuario);
            http_response_code(429);

            echo json_encode([
                'exito' => false,
                'mensaje' => 'Demasiados intentos fallidos. La cuenta quedó bloqueada temporalmente durante 15 minutos.',
                'reintentar_en' => 900
            ]);

            exit;
        }

        http_response_code(401);

        echo json_encode([
            'exito' => false,
            'mensaje' => 'Usuario o contraseña incorrectos.'
        ]);

        exit;
    }

    $modeloUsuario->limpiarIntentosLogin((int) $usuario['id_usuario']);

    if ($usuario['estado'] === 'pendiente') {
        registrarAuditoriaApi($conexion, (int) $usuario['id_usuario'], 'login_denegado', 'sesion', null, 'Cuenta pendiente de aprobación', 'denegado');
        http_response_code(403);

        echo json_encode([
            'exito' => false,
            'mensaje' => 'Tu cuenta todavía está pendiente de aprobación.'
        ]);

        exit;
    }

    if ($usuario['estado'] === 'bloqueado') {
        registrarAuditoriaApi($conexion, (int) $usuario['id_usuario'], 'login_denegado', 'sesion', null, 'Cuenta bloqueada', 'denegado');
        http_response_code(403);

        echo json_encode([
            'exito' => false,
            'mensaje' => 'Tu cuenta está bloqueada.'
        ]);

        exit;
    }

    if ($usuario['estado'] !== 'activo') {
        registrarAuditoriaApi($conexion, (int) $usuario['id_usuario'], 'login_denegado', 'sesion', null, 'Cuenta no activa: ' . $usuario['estado'], 'denegado');
        http_response_code(403);

        echo json_encode([
            'exito' => false,
            'mensaje' => 'Tu cuenta no está activa.'
        ]);

        exit;
    }

    session_set_cookie_params([
        'lifetime' => $recordar ? 60 * 60 * 24 * 30 : 0,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);

    session_start();

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = (int) $usuario['id_usuario'];
    $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
    $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
    $_SESSION['roles'] = $usuario['roles']
        ? explode(',', $usuario['roles'])
        : [];
    $_SESSION['version_sesion'] = (int) ($usuario['version_sesion'] ?? 1);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    registrarAuditoriaApi($conexion, (int) $usuario['id_usuario'], 'login_exitoso', 'sesion', null, 'Inicio de sesión correcto');
    registrarEventoSeguridadApi('LOGIN_SUCCESS', $nombreUsuario);

    echo json_encode([
        'exito' => true,
        'mensaje' => 'Inicio de sesión correcto.',
        'usuario' => [
            'id' => (int) $usuario['id_usuario'],
            'nombre' => $usuario['nombre_completo'],
            'nombre_usuario' => $usuario['nombre_usuario'],
            'roles' => $_SESSION['roles']
        ]
    ]);

} catch (Throwable $error) {
    http_response_code(500);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'No se pudo iniciar sesión.'
    ]);
}