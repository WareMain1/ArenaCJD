<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Disciplina.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();

$idDisciplina = filter_var($datos['id_disciplina'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim((string) ($datos['nombre'] ?? ''));
$estado = (string) ($datos['estado'] ?? 'activa');
$categorias = is_array($datos['categorias'] ?? null) ? $datos['categorias'] : [];
$tipos = is_array($datos['tipos'] ?? null) ? $datos['tipos'] : [];

if (!$idDisciplina) {
    responderJson(['exito' => false, 'mensaje' => 'Disciplina no válida.'], 400);
}
if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 80) {
    responderJson(['exito' => false, 'mensaje' => 'El nombre de la disciplina debe tener entre 2 y 80 caracteres.'], 400);
}
if (!in_array($estado, ['activa', 'inactiva'], true)) {
    responderJson(['exito' => false, 'mensaje' => 'El estado seleccionado no es válido.'], 400);
}

try {
    $modelo = new Disciplina($contexto['conexion']);
    if (!$modelo->obtenerPorId((int) $idDisciplina)) {
        responderJson(['exito' => false, 'mensaje' => 'La disciplina no existe.'], 404);
    }
    if ($modelo->existeNombre($nombre, (int) $idDisciplina)) {
        responderJson(['exito' => false, 'mensaje' => 'Ya existe otra disciplina con ese nombre.'], 409);
    }
    $contexto['conexion']->beginTransaction();
    $modelo->actualizar((int) $idDisciplina, $nombre, $estado, $categorias, $tipos);
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'disciplina_actualizada', 'disciplina', (int) $idDisciplina, $nombre . ' · ' . $estado);
    $contexto['conexion']->commit();
    responderJson([
        'exito' => true,
        'mensaje' => 'Disciplina actualizada correctamente.',
        'disciplina' => $modelo->obtenerPorId((int) $idDisciplina)
    ]);
} catch (InvalidArgumentException $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 422);
} catch (Throwable $error) {
    if ($contexto['conexion']->inTransaction()) {
        $contexto['conexion']->rollBack();
    }
    responderJson(['exito' => false, 'mensaje' => 'No se pudo actualizar la disciplina.'], 500);
}
