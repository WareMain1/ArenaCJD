<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../servicios/FotoPerfil.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);

try {
    $idUsuario = (int) $contexto['usuario']['id_usuario'];
    $fotoPerfil = new FotoPerfil();
    $fotoPerfil->eliminar($idUsuario);
    registrarAuditoriaApi($contexto['conexion'], $idUsuario, 'foto_perfil_eliminada', 'usuario', $idUsuario);

    responderJson([
        'exito' => true,
        'mensaje' => 'Foto de perfil eliminada.',
        'tiene_foto_perfil' => false
    ]);
} catch (Throwable $error) {
    responderJson([
        'exito' => false,
        'mensaje' => 'No se pudo eliminar la foto de perfil.'
    ], 500);
}
