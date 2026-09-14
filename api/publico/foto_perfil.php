<?php

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../../servicios/FotoPerfil.php';

$id = filter_input(INPUT_GET, 'id_usuario', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit;
}

try {
    $conexion = conexionPublica();
    $consulta = $conexion->prepare(
        "SELECT 1
         FROM usuarios u
         WHERE u.id_usuario = :id
           AND (
               EXISTS (
                   SELECT 1
                   FROM inscripciones_individuales ii
                   INNER JOIN torneos t ON t.id_torneo = ii.id_torneo
                   WHERE ii.id_usuario = u.id_usuario
                     AND ii.estado = 'aprobada'
                     AND t.publicado = 1
                     AND t.estado IN ('inscripciones','en_curso','finalizado')
               )
               OR EXISTS (
                   SELECT 1
                   FROM enfrentamientos e
                   INNER JOIN torneos t ON t.id_torneo = e.id_torneo
                   WHERE e.tipo_participante = 'individual'
                     AND (e.id_usuario_a = u.id_usuario OR e.id_usuario_b = u.id_usuario)
                     AND e.estado <> 'cancelado'
                     AND t.publicado = 1
                     AND t.estado IN ('inscripciones','en_curso','finalizado')
               )
           )
         LIMIT 1"
    );
    $consulta->execute([':id' => (int) $id]);
    if ($consulta->fetchColumn() === false) {
        http_response_code(404);
        exit;
    }

    $foto = new FotoPerfil();
    $ruta = $foto->obtenerRuta((int) $id);
    $mime = $foto->obtenerMime((int) $id);
    if (!$ruta || !$mime) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($ruta));
    header('Cache-Control: public, no-cache, max-age=0');
    header('X-Content-Type-Options: nosniff');
    readfile($ruta);
} catch (Throwable $error) {
    http_response_code(404);
}
