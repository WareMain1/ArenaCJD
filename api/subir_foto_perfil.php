<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../servicios/FotoPerfil.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);

if (!isset($_FILES['foto'])) {
    responderJson([
        'exito' => false,
        'mensaje' => 'Selecciona una imagen para tu perfil.'
    ], 400);
}

try {
    $idUsuario = (int) $contexto['usuario']['id_usuario'];
    $fotoPerfil = new FotoPerfil();
    $fotoPerfil->guardar($idUsuario, $_FILES['foto']);
    $version = $fotoPerfil->obtenerVersion($idUsuario);
    registrarAuditoriaApi($contexto['conexion'], $idUsuario, 'foto_perfil_actualizada', 'usuario', $idUsuario);

    responderJson([
        'exito' => true,
        'mensaje' => 'Foto de perfil actualizada.',
        'tiene_foto_perfil' => true,
        'foto_perfil_url' => 'api/foto_perfil.php?v=' . $version
    ]);
} catch (InvalidArgumentException $error) {
    responderJson([
        'exito' => false,
        'mensaje' => $error->getMessage()
    ], 400);
} catch (Throwable $error) {
    responderJson([
        'exito' => false,
        'mensaje' => 'No se pudo actualizar la foto de perfil.'
    ], 500);
}
