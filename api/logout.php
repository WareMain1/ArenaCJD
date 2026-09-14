<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);
$idUsuario = (int) $contexto['usuario']['id_usuario'];
registrarAuditoriaApi($contexto['conexion'], $idUsuario, 'logout', 'sesion', null, 'Cierre de sesión solicitado por el usuario');
registrarEventoSeguridadApi('LOGOUT', (string) $contexto['usuario']['nombre_usuario']);

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametros['path'],
        $parametros['domain'],
        $parametros['secure'],
        $parametros['httponly']
    );
}

session_destroy();
responderJson(['exito' => true, 'mensaje' => 'Sesión cerrada correctamente.']);
