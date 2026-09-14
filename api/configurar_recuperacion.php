<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$actual = (string) ($datos['contrasena_actual'] ?? '');
$pregunta = trim((string) ($datos['pregunta'] ?? ''));
$respuesta = trim((string) ($datos['respuesta'] ?? ''));
$idUsuario = (int) $contexto['usuario']['id_usuario'];

if ($actual === '' || mb_strlen($pregunta) < 5 || mb_strlen($pregunta) > 150 || mb_strlen($respuesta) < 2 || mb_strlen($respuesta) > 100) responderJson(['exito' => false, 'mensaje' => 'Completa correctamente la contraseña, pregunta y respuesta.'], 422);
$modelo = new Usuario($contexto['conexion']);
$hash = $modelo->obtenerHashContrasena($idUsuario);
if (!$hash || !password_verify($actual, $hash)) {
    registrarAuditoriaApi($contexto['conexion'], $idUsuario, 'recuperacion_config_denegada', 'usuario', $idUsuario, 'Contraseña actual incorrecta', 'denegado');
    responderJson(['exito' => false, 'mensaje' => 'La contraseña actual no es correcta.'], 403);
}
try {
    $contexto['conexion']->beginTransaction();
    $modelo->configurarRecuperacion($idUsuario, $pregunta, password_hash(mb_strtolower($respuesta, 'UTF-8'), PASSWORD_DEFAULT));
    registrarAuditoriaApi($contexto['conexion'], $idUsuario, 'recuperacion_configurada', 'usuario', $idUsuario, 'Pregunta de recuperación actualizada');
    $contexto['conexion']->commit();
    responderJson(['exito' => true, 'mensaje' => 'Pregunta de recuperación actualizada.']);
} catch (Throwable $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar la recuperación.'], 500);
}
