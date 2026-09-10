<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Disciplina.php';

$contexto = contextoApi(['administrador']);

try {
    $modelo = new Disciplina($contexto['conexion']);
    responderJson([
        'exito' => true,
        'disciplinas' => $modelo->obtenerTodas(),
        'catalogos' => $modelo->obtenerCatalogosAsociacion(),
        'puede_gestionar' => true,
        'csrf_token' => $contexto['csrf_token'],
        'actualizado_en' => date(DATE_ATOM)
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudieron cargar las disciplinas.'], 500);
}
