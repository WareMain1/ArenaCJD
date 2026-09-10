<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once dirname(__DIR__) . '/servicios/FotoPerfil.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$idUsuario = filter_var($datos['id_usuario'] ?? null, FILTER_VALIDATE_INT);
if (!$idUsuario) responderJson(['exito' => false, 'mensaje' => 'Usuario no válido.'], 400);
if ((int) $idUsuario === (int) $contexto['usuario']['id_usuario']) responderJson(['exito' => false, 'mensaje' => 'No puedes eliminar la cuenta con la que estás administrando el sistema.'], 409);

try {
    $modelo = new Usuario($contexto['conexion']);
    $usuario = $modelo->buscarPorId((int) $idUsuario);
    if (!$usuario) responderJson(['exito' => false, 'mensaje' => 'El usuario ya no existe.'], 404);
    $dependencias = $modelo->contarDependencias((int) $idUsuario);
    if (array_sum($dependencias) > 0) {
        responderJson([
            'exito' => false,
            'mensaje' => 'No se puede eliminar porque el usuario tiene datos relacionados. Puedes cambiar su estado a inactivo o bloqueado.',
            'dependencias' => $dependencias
        ], 409);
    }
    if (!$modelo->eliminarSinDependencias((int) $idUsuario)) responderJson(['exito' => false, 'mensaje' => 'No se pudo eliminar el usuario.'], 409);
    try { (new FotoPerfil())->eliminar((int) $idUsuario); } catch (Throwable $fotoError) {}
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'usuario_eliminado', 'usuario', (int) $idUsuario, '@' . (string) $usuario['nombre_usuario']);
    responderJson(['exito' => true, 'mensaje' => 'Usuario eliminado correctamente.']);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo eliminar el usuario.'], 500);
}
