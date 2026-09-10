<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Disciplina.php';
exigirMetodoApi('GET');
$contexto = contextoApi(['administrador', 'organizador']);
try {
    $catalogos = (new Disciplina($contexto['conexion']))->obtenerCatalogosAsociacion();
    responderJson(['exito' => true, 'categorias' => $catalogos['categorias'],
        'puede_gestionar' => in_array('administrador', $contexto['roles'], true), 'csrf_token' => $contexto['csrf_token']]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudieron cargar las categorías.'], 500);
}
