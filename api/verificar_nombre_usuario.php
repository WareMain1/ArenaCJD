<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/_comun.php';

exigirMetodoApi('GET');
$contexto = contextoApi();

$nombreUsuario = strtolower(trim((string) ($_GET['nombre_usuario'] ?? '')));
$nombreUsuario = ltrim($nombreUsuario, '@');

if (!preg_match('/^[a-z0-9._-]{4,24}$/', $nombreUsuario)) {
    responderJson([
        'exito' => false,
        'disponible' => false,
        'mensaje' => 'Usa entre 4 y 24 caracteres: letras, números, punto, guion o guion bajo.'
    ], 400);
}

try {
    $modeloUsuario = new Usuario($contexto['conexion']);
    $idUsuario = (int) $contexto['usuario']['id_usuario'];
    $usuarioActual = $contexto['usuario'];

    if ($usuarioActual['nombre_usuario'] === $nombreUsuario) {
        responderJson([
            'exito' => true,
            'disponible' => true,
            'actual' => true,
            'nombre_usuario' => $nombreUsuario,
            'mensaje' => 'Este es tu nombre de usuario actual.'
        ]);
    }

    $disponible = $modeloUsuario->nombreUsuarioDisponible($nombreUsuario, $idUsuario);
    responderJson([
        'exito' => true,
        'disponible' => $disponible,
        'actual' => false,
        'nombre_usuario' => $nombreUsuario,
        'mensaje' => $disponible
            ? 'El nombre de usuario está disponible.'
            : 'Ese nombre de usuario ya está en uso.'
    ]);
} catch (Throwable $error) {
    responderJson([
        'exito' => false,
        'disponible' => false,
        'mensaje' => 'No se pudo verificar el nombre de usuario.'
    ], 500);
}
