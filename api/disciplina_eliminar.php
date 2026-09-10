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
if (!$idDisciplina) {
    responderJson(['exito' => false, 'mensaje' => 'Disciplina no válida.'], 400);
}

try {
    $modelo = new Disciplina($contexto['conexion']);
    $disciplina = $modelo->eliminarDisciplina((int) $idDisciplina);
    registrarAuditoriaApi(
        $contexto['conexion'],
        (int) $contexto['usuario']['id_usuario'],
        'disciplina_eliminada',
        'disciplina',
        (int) $idDisciplina,
        (string) $disciplina['nombre']
    );
    responderJson([
        'exito' => true,
        'mensaje' => 'Disciplina eliminada correctamente. Dejó de estar disponible en ArenaCJD.',
        'disciplina' => $disciplina
    ]);
} catch (InvalidArgumentException|DomainException $error) {
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 409);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo eliminar la disciplina.'], 500);
}
