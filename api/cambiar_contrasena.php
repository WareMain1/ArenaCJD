<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$actual = (string) ($datos['actual'] ?? '');
$nueva = (string) ($datos['nueva'] ?? '');
$confirmar = (string) ($datos['confirmar'] ?? '');
$idUsuario = (int) $contexto['usuario']['id_usuario'];

if ($actual === '' || $nueva === '' || $confirmar === '') responderJson(['exito' => false, 'mensaje' => 'Completa todos los campos.'], 400);
if ($nueva !== $confirmar) responderJson(['exito' => false, 'mensaje' => 'Las contraseñas nuevas no coinciden.'], 422);
if (strlen($nueva) < 8 || strlen($nueva) > 72 || !preg_match('/[0-9]/', $nueva) || !preg_match('/[A-ZÁÉÍÓÚÑ]/u', $nueva) || !preg_match('/[!@#$%&*]/', $nueva)) responderJson(['exito' => false, 'mensaje' => 'La nueva contraseña debe tener 8 a 72 caracteres, mayúscula, número y símbolo !@#$%&*.'], 422);

$modelo = new Usuario($contexto['conexion']);
$hash = $modelo->obtenerHashContrasena($idUsuario);
if (!$hash || !password_verify($actual, $hash)) {
    registrarAuditoriaApi($contexto['conexion'], $idUsuario, 'cambio_contrasena_denegado', 'usuario', $idUsuario, 'La contraseña actual no coincidió', 'denegado');
    responderJson(['exito' => false, 'mensaje' => 'La contraseña actual no es correcta.'], 403);
}

try {
    $contexto['conexion']->beginTransaction();
    $modelo->actualizarContrasena($idUsuario, password_hash($nueva, PASSWORD_DEFAULT));
    $versionSesion = $modelo->obtenerVersionSesion($idUsuario);
    registrarAuditoriaApi($contexto['conexion'], $idUsuario, 'cambio_contrasena', 'usuario', $idUsuario, 'Contraseña actualizada e invalidación de sesiones anteriores');
    $contexto['conexion']->commit();

    $_SESSION['version_sesion'] = $versionSesion;
    session_regenerate_id(true);
    responderJson(['exito' => true, 'mensaje' => 'Contraseña actualizada correctamente. Las demás sesiones fueron cerradas.']);
} catch (Throwable $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar la contraseña.'], 500);
}
