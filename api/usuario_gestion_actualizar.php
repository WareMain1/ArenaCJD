<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$idUsuario = filter_var($datos['id_usuario'] ?? null, FILTER_VALIDATE_INT);
$estado = (string) ($datos['estado'] ?? 'activo');
$roles = $datos['roles'] ?? [];

if (!$idUsuario || !is_array($roles)) {
    responderJson(['exito' => false, 'mensaje' => 'Los datos del usuario no son válidos.'], 400);
}

if ((int) $idUsuario === (int) $contexto['usuario']['id_usuario']) {
    if ($estado !== 'activo' || !in_array('administrador', $roles, true)) {
        responderJson(['exito' => false, 'mensaje' => 'No puedes quitarte tu propio acceso de administrador ni desactivar tu cuenta actual.'], 409);
    }
}

try {
    $modelo = new Usuario($contexto['conexion']);
    if (!$modelo->buscarPorId((int) $idUsuario)) {
        responderJson(['exito' => false, 'mensaje' => 'El usuario no existe.'], 404);
    }
    $modelo->actualizarGestion((int) $idUsuario, $estado, $roles);
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'usuario_actualizado', 'usuario', (int) $idUsuario, $estado . ' · ' . implode(', ', $roles));
    responderJson(['exito' => true, 'mensaje' => 'Usuario actualizado correctamente.']);
} catch (InvalidArgumentException $error) {
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 400);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar el usuario.'], 500);
}
