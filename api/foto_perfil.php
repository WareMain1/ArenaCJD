<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../servicios/FotoPerfil.php';

exigirMetodoApi('GET');
$contexto = contextoApi();
$idSesion = (int) $contexto['usuario']['id_usuario'];
$idSolicitado = filter_input(INPUT_GET, 'id_usuario', FILTER_VALIDATE_INT);
$idUsuario = ($idSolicitado !== false && $idSolicitado !== null && $idSolicitado > 0) ? (int) $idSolicitado : $idSesion;

$consulta = $contexto['conexion']->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id LIMIT 1");
$consulta->execute([':id' => $idUsuario]);
if ($consulta->fetchColumn() === false) {
    http_response_code(404);
    exit;
}

$fotoPerfil = new FotoPerfil();
$ruta = $fotoPerfil->obtenerRuta($idUsuario);
$mime = $fotoPerfil->obtenerMime($idUsuario);

if ($ruta === null || $mime === null) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');

readfile($ruta);
