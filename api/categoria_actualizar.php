<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Disciplina.php';
exigirMetodoApi('POST');
$contexto = contextoApi(['administrador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$nombre = $datos['nombre'] ?? null;
$id = filter_var($datos['id_categoria'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) responderJson(['exito' => false, 'mensaje' => 'Categoría no válida.'], 400);
if (!is_string($nombre)) responderJson(['exito' => false, 'mensaje' => 'Nombre no válido.'], 400);
try {
    $modelo = new Disciplina($contexto['conexion']);
    $categoria = $modelo->actualizarCategoria((int) $id, $nombre);
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'],
        'categoria_actualizada', 'categoria', $categoria['id_categoria'],
        $categoria['nombre_anterior'] . ' → ' . $categoria['nombre']);
    responderJson(['exito' => true, 'mensaje' => 'Categoría actualizada correctamente.', 'categoria' => $categoria], 200);
} catch (InvalidArgumentException $error) {
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 422);
} catch (DomainException $error) {
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 409);
} catch (PDOException $error) {
    if ((int) ($error->errorInfo[1] ?? 0) === 1062) responderJson(['exito' => false, 'mensaje' => 'Ya existe una categoría con ese nombre.'], 409);
    responderJson(['exito' => false, 'mensaje' => 'No se pudo guardar la categoría.'], 500);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo guardar la categoría.'], 500);
}
