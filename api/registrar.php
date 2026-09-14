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

exigirLimiteSolicitudesApi('registro', 8, 3600);

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    http_response_code(400);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'Los datos enviados no son válidos.'
    ]);

    exit;
}

$nombreCompleto = trim($datos['nombre_completo'] ?? '');
$nombreUsuario = trim($datos['nombre_usuario'] ?? '');
$correo = strtolower(trim($datos['correo'] ?? ''));
$contrasena = $datos['contrasena'] ?? '';
$confirmarContrasena = $datos['confirmar_contrasena'] ?? '';
$terminos = (bool) ($datos['terminos'] ?? false);
$preguntaRecuperacion = trim((string) ($datos['pregunta_recuperacion'] ?? ''));
$respuestaRecuperacion = trim((string) ($datos['respuesta_recuperacion'] ?? ''));

$nombreUsuario = ltrim($nombreUsuario, '@');
$nombreUsuario = strtolower($nombreUsuario);

if (
    $nombreCompleto === '' ||
    $nombreUsuario === '' ||
    $correo === '' ||
    $contrasena === '' ||
    $confirmarContrasena === '' ||
    $preguntaRecuperacion === '' ||
    $respuestaRecuperacion === ''
) {
    http_response_code(400);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'Debes completar todos los campos.'
    ]);

    exit;
}

if (strlen($nombreCompleto) < 3 || strlen($nombreCompleto) > 120) {
    http_response_code(400);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'El nombre completo no es válido.'
    ]);

    exit;
}

if (!preg_match('/^[a-z0-9._-]{4,24}$/', $nombreUsuario)) {
    http_response_code(400);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'El nombre de usuario debe tener entre 4 y 24 caracteres.'
    ]);

    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'El correo electrónico no es válido.'
    ]);

    exit;
}

$contrasenaValida =
    strlen($contrasena) >= 8 &&
    strlen($contrasena) <= 72 &&
    preg_match('/[0-9]/', $contrasena) &&
    preg_match('/[A-ZÁÉÍÓÚÑ]/u', $contrasena) &&
    preg_match('/[!@#$%&*]/', $contrasena);

if (!$contrasenaValida) {
    http_response_code(400);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'La contraseña no cumple los requisitos.'
    ]);

    exit;
}

if ($contrasena !== $confirmarContrasena) {
    http_response_code(400);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'Las contraseñas no coinciden.'
    ]);

    exit;
}

if (strlen($preguntaRecuperacion) < 5 || strlen($preguntaRecuperacion) > 150 || strlen($respuestaRecuperacion) < 2 || strlen($respuestaRecuperacion) > 100) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'mensaje' => 'Configura correctamente la pregunta y respuesta de recuperación.']);
    exit;
}

if (!$terminos) {
    http_response_code(400);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'Debes aceptar los términos y condiciones.'
    ]);

    exit;
}

try {
    $conexionBD = new Conexion();
    $conexion = $conexionBD->conectar();

    $usuario = new Usuario($conexion);

    if ($usuario->existeNombreUsuario($nombreUsuario)) {
        http_response_code(409);

        echo json_encode([
            'exito' => false,
            'mensaje' => 'El nombre de usuario ya está registrado.'
        ]);

        exit;
    }

    if ($usuario->existeCorreo($correo)) {
        http_response_code(409);

        echo json_encode([
            'exito' => false,
            'mensaje' => 'El correo electrónico ya está registrado.'
        ]);

        exit;
    }

    $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);
    $respuestaRecuperacionHash = password_hash(mb_strtolower($respuestaRecuperacion, 'UTF-8'), PASSWORD_DEFAULT);

    $conexion->beginTransaction();
    $idUsuario = $usuario->registrar(
        $nombreCompleto,
        $nombreUsuario,
        $correo,
        $contrasenaHash,
        $preguntaRecuperacion,
        $respuestaRecuperacionHash
    );
    registrarAuditoriaApi($conexion, (int) $idUsuario, 'cuenta_registrada', 'usuario', (int) $idUsuario, '@' . $nombreUsuario);
    $conexion->commit();

    http_response_code(201);

    echo json_encode([
        'exito' => true,
        'mensaje' => 'Cuenta creada correctamente.',
        'id_usuario' => $idUsuario
    ]);
} catch (Throwable $error) {
    if (isset($conexion) && $conexion instanceof PDO && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    http_response_code(500);

    echo json_encode([
        'exito' => false,
        'mensaje' => 'No se pudo crear la cuenta.'
    ]);
}
