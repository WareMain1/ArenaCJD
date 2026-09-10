<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';

$contexto = contextoApi(['administrador']);
try {
    $modelo = new Usuario($contexto['conexion']);
    $usuarios = $modelo->listarActivosConRoles();
    foreach ($usuarios as &$usuario) {
        $usuario['id_usuario'] = (int) $usuario['id_usuario'];
        $usuario['roles_lista'] = $usuario['roles'] ? explode(',', $usuario['roles']) : [];
        $usuario['es_sesion_actual'] = (int) $usuario['id_usuario'] === (int) $contexto['usuario']['id_usuario'];
    }
    unset($usuario);
    responderJson([
        'exito' => true,
        'usuarios' => $usuarios,
        'roles' => $modelo->listarRoles(),
        'csrf_token' => $contexto['csrf_token'],
        'actualizado_en' => date(DATE_ATOM)
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudieron cargar los usuarios activos.'], 500);
}
