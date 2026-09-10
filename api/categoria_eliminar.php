<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Disciplina.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$idCategoria = filter_var($datos['id_categoria'] ?? null, FILTER_VALIDATE_INT);
if (!$idCategoria || $idCategoria < 1) {
    responderJson(['exito' => false, 'mensaje' => 'Categoría no válida.'], 400);
}

try {
    $modelo = new Disciplina($contexto['conexion']);
    $categoria = $modelo->eliminarCategoria((int) $idCategoria);
    registrarAuditoriaApi(
        $contexto['conexion'],
        (int) $contexto['usuario']['id_usuario'],
        'categoria_eliminada',
        'categoria',
        (int) $idCategoria,
        (string) $categoria['nombre']
    );

    responderJson([
        'exito' => true,
        'mensaje' => 'Categoría eliminada correctamente. Dejó de estar disponible en todo ArenaCJD.',
        'categoria' => $categoria
    ]);
} catch (InvalidArgumentException|DomainException $error) {
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'],
        'categoria_eliminada', 'categoria', (int) $idCategoria, $error->getMessage(), 'denegado');
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 409);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo eliminar la categoría.'], 500);
}
