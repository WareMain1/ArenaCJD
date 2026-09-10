<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Torneo.php';

$contexto = contextoApi(['administrador', 'organizador']);
try {
    $modelo = new Torneo($contexto['conexion']);
    responderJson([
        'exito' => true,
        'catalogos' => $modelo->obtenerCatalogos(),
        'es_administrador' => in_array('administrador', $contexto['roles'], true),
        'usuario_actual' => [
            'id' => (int) $contexto['usuario']['id_usuario'],
            'nombre' => $contexto['usuario']['nombre_completo'],
            'usuario' => $contexto['usuario']['nombre_usuario']
        ],
        'csrf_token' => $contexto['csrf_token']
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudieron cargar las opciones del torneo.'], 500);
}
