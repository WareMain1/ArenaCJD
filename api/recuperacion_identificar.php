<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
session_start();
require_once __DIR__ . '/_comun.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderJson(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
$datos = json_decode(file_get_contents('php://input'), true) ?: [];
$identificador = trim((string) ($datos['identificador'] ?? ''));
if (mb_strlen($identificador) < 3 || mb_strlen($identificador) > 150) responderJson(['exito' => false, 'mensaje' => 'Ingresa tu nombre, @usuario o correo.'], 422);

try {
    $conexion = (new Conexion())->conectar();
    $modelo = new Usuario($conexion);
    $usuario = $modelo->buscarRecuperacion($identificador);
    if (!$usuario || $usuario['estado'] !== 'activo' || empty($usuario['pregunta_recuperacion']) || empty($usuario['respuesta_recuperacion'])) {
        responderJson(['exito' => false, 'mensaje' => 'No se encontró una cuenta única y activa con recuperación configurada.'], 404);
    }

    if (!empty($usuario['recuperacion_bloqueada_hasta']) && strtotime((string) $usuario['recuperacion_bloqueada_hasta']) > time()) {
        registrarAuditoriaApi($conexion, (int) $usuario['id_usuario'], 'recuperacion_bloqueada', 'usuario', (int) $usuario['id_usuario'], 'Intento durante bloqueo temporal', 'denegado');
        responderJson(['exito' => false, 'mensaje' => 'La recuperación está bloqueada temporalmente por demasiados intentos. Intenta más tarde.'], 429);
    }

    $_SESSION['recuperacion_usuario_id'] = (int) $usuario['id_usuario'];
    $_SESSION['recuperacion_expira'] = time() + 600;
    $_SESSION['recuperacion_token'] = bin2hex(random_bytes(32));
    $_SESSION['recuperacion_intentos'] = (int) ($usuario['recuperacion_intentos'] ?? 0);
    responderJson(['exito' => true, 'pregunta' => $usuario['pregunta_recuperacion'], 'token_proceso' => $_SESSION['recuperacion_token'], 'expira_en' => 600, 'mensaje' => 'Responde la pregunta para continuar.']);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo iniciar la recuperación.'], 500);
}
