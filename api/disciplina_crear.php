<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Disciplina.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$nombre = trim((string) ($datos['nombre'] ?? ''));
$estado = (string) ($datos['estado'] ?? 'activa');
$categorias = is_array($datos['categorias'] ?? null) ? $datos['categorias'] : [];
$tipos = is_array($datos['tipos'] ?? null) ? $datos['tipos'] : [];

if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 80) {
    responderJson(['exito' => false, 'mensaje' => 'El nombre de la disciplina debe tener entre 2 y 80 caracteres.'], 400);
}
if (!in_array($estado, ['activa', 'inactiva'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'El estado seleccionado no es válido.'], 400);
}

try {
    $modelo = new Disciplina($contexto['conexion']);
    if ($modelo->existeNombre($nombre)) {
        responderJson(['exito' => false, 'mensaje' => 'Ya existe una disciplina con ese nombre.'], 409);
    }
    $id = $modelo->crear($nombre, $estado, $categorias, $tipos);
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'disciplina_creada', 'disciplina', $id, $nombre . ' · ' . $estado);
    responderJson([
        'exito' => true,
        'mensaje' => 'Disciplina creada correctamente.',
        'disciplina' => $modelo->obtenerPorId($id)
    ], 201);
} catch (InvalidArgumentException $error) {
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 422);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo crear la disciplina.'], 500);
}
