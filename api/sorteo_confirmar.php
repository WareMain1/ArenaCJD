<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';

exigirMetodoApi('POST');
$contexto = contextoApi(['administrador', 'organizador']);
exigirCsrfApi($contexto['csrf_token']);
$datos = leerJsonApi();
$id = (int) ($datos['id_torneo'] ?? 0);
$orden = $datos['orden'] ?? [];

if ($id < 1 || !is_array($orden)) {
    responderJson(['exito' => false, 'mensaje' => 'Datos de sorteo no válidos.'], 400);
}

try {
    $consulta = $contexto['conexion']->prepare('SELECT id_organizador, nombre, estado FROM torneos WHERE id_torneo=:id LIMIT 1');
    $consulta->execute([':id' => $id]);
    $torneo = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$torneo) {
        responderJson(['exito' => false, 'mensaje' => 'El torneo no existe.'], 404);
    }

    if (!in_array($torneo['estado'], ['inscripciones', 'en_curso'], true)) {
        $mensajeEstado = $torneo['estado'] === 'cancelado'
            ? 'No se puede generar ni modificar el sorteo de un torneo cancelado.'
            : ($torneo['estado'] === 'finalizado'
                ? 'No se puede generar ni modificar el sorteo de un torneo finalizado.'
                : 'El torneo debe estar en inscripciones para generar el sorteo.');
        responderJson(['exito' => false, 'mensaje' => $mensajeEstado], 422);
    }

    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    if (!$esAdministrador && (int) $torneo['id_organizador'] !== (int) $contexto['usuario']['id_usuario']) {
        responderJson(['exito' => false, 'mensaje' => 'No puedes gestionar este torneo.'], 403);
    }

    $modelo = new Enfrentamiento($contexto['conexion']);
    $lista = $modelo->generarPrimeraRonda($id, $orden);
    registrarAuditoriaApi($contexto['conexion'], (int) $contexto['usuario']['id_usuario'], 'sorteo_confirmado', 'torneo', $id, (string) $torneo['nombre']);
    responderJson(['exito' => true, 'mensaje' => 'Sorteo guardado correctamente.', 'enfrentamientos' => $lista]);
} catch (DomainException | InvalidArgumentException $error) {
    responderJson(['exito' => false, 'mensaje' => $error->getMessage()], 422);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo guardar el sorteo. Verifica la conexión y la estructura de la base de datos.'], 500);
}
