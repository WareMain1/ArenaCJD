<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
session_start();
require_once __DIR__ . '/_comun.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
}

if (empty($_SESSION['recuperacion_usuario_id']) || ($_SESSION['recuperacion_expira'] ?? 0) < time()) {
    unset($_SESSION['recuperacion_usuario_id'], $_SESSION['recuperacion_expira'], $_SESSION['recuperacion_intentos'], $_SESSION['recuperacion_token']);
    responderJson(['exito' => false, 'mensaje' => 'La recuperación venció. Vuelve a identificar tu cuenta.'], 410);
}

if (($_SESSION['recuperacion_intentos'] ?? 0) >= 5) {
    responderJson(['exito' => false, 'mensaje' => 'Se alcanzó el límite de intentos. Reinicia la recuperación.'], 429);
}

$datos = json_decode(file_get_contents('php://input'), true) ?: [];
$tokenProceso = (string) ($datos['token_proceso'] ?? '');
if ($tokenProceso === '' || empty($_SESSION['recuperacion_token']) || !hash_equals((string) $_SESSION['recuperacion_token'], $tokenProceso)) {
    responderJson(['exito' => false, 'mensaje' => 'El proceso de recuperación no es válido. Vuelve a identificar tu cuenta.'], 403);
}

$respuesta = trim((string) ($datos['respuesta'] ?? ''));
$nueva = (string) ($datos['nueva'] ?? '');
$confirmar = (string) ($datos['confirmar'] ?? '');
$idUsuario = (int) $_SESSION['recuperacion_usuario_id'];

if ($respuesta === '' || $nueva !== $confirmar || strlen($nueva) < 8 || strlen($nueva) > 72 || !preg_match('/[0-9]/', $nueva) || !preg_match('/[A-ZÁÉÍÓÚÑ]/u', $nueva) || !preg_match('/[!@#$%&*]/', $nueva)) {
    responderJson(['exito' => false, 'mensaje' => 'Revisa la respuesta y los requisitos de la nueva contraseña.'], 422);
}

try {
    $conexion = (new Conexion())->conectar();
    $modelo = new Usuario($conexion);
    $consulta = $conexion->prepare('SELECT respuesta_recuperacion FROM usuarios WHERE id_usuario = :id LIMIT 1');
    $consulta->execute([':id' => $idUsuario]);
    $hashRespuesta = $consulta->fetchColumn();

    if (!$hashRespuesta || !password_verify(mb_strtolower($respuesta, 'UTF-8'), $hashRespuesta)) {
        $_SESSION['recuperacion_intentos'] = ($_SESSION['recuperacion_intentos'] ?? 0) + 1;
        $intentos = (int) $_SESSION['recuperacion_intentos'];
        $bloquear = $intentos >= 5;
        $consultaIntentos = $conexion->prepare(
            "UPDATE usuarios SET recuperacion_intentos = :intentos, recuperacion_bloqueada_hasta = :bloqueada WHERE id_usuario = :id"
        );
        $consultaIntentos->execute([
            ':intentos' => $bloquear ? 0 : $intentos,
            ':bloqueada' => $bloquear ? date('Y-m-d H:i:s', time() + 900) : null,
            ':id' => $idUsuario
        ]);
        registrarAuditoriaApi($conexion, $idUsuario, 'recuperacion_fallida', 'usuario', $idUsuario, $bloquear ? 'Bloqueo temporal por cinco respuestas incorrectas' : 'Respuesta de recuperación incorrecta', 'denegado');
        if ($bloquear) responderJson(['exito' => false, 'mensaje' => 'Se alcanzó el límite de intentos. La recuperación quedó bloqueada durante 15 minutos.'], 429);
        responderJson(['exito' => false, 'mensaje' => 'La respuesta no coincide. Te quedan ' . (5 - $intentos) . ' intentos.'], 403);
    }

    $modelo->actualizarContrasena($idUsuario, password_hash($nueva, PASSWORD_DEFAULT));
    $conexion->prepare("UPDATE usuarios SET recuperacion_intentos = 0, recuperacion_bloqueada_hasta = NULL WHERE id_usuario = :id")
        ->execute([':id' => $idUsuario]);
    registrarAuditoriaApi($conexion, $idUsuario, 'recuperacion_contrasena', 'usuario', $idUsuario, 'Contraseña restablecida e invalidación de sesiones anteriores');
    unset($_SESSION['recuperacion_usuario_id'], $_SESSION['recuperacion_expira'], $_SESSION['recuperacion_intentos'], $_SESSION['recuperacion_token']);
    session_regenerate_id(true);
    responderJson(['exito' => true, 'mensaje' => 'Contraseña restablecida. Ya puedes iniciar sesión.']);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo restablecer la contraseña.'], 500);
}
