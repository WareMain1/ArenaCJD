<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/PreferenciaUsuario.php';

$contexto = contextoApi();
$modelo = new PreferenciaUsuario($contexto['conexion']);
$idUsuario = (int) $contexto['usuario']['id_usuario'];

$predeterminadas = [
    'elementosPagina' => '10',
    'torneoPredeterminado' => '',
    'tema' => 'claro'
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $guardadas = $modelo->obtener($idUsuario);
        responderJson([
            'exito' => true,
            'preferencias' => array_replace($predeterminadas, array_intersect_key($guardadas, $predeterminadas)),
            'csrf_token' => $contexto['csrf_token']
        ]);
    }

    exigirMetodoApi('POST');
    exigirCsrfApi($contexto['csrf_token']);
    $datos = leerJsonApi();

    $permitidas = array_keys($predeterminadas);
    $limpias = array_replace($predeterminadas, array_intersect_key($modelo->obtener($idUsuario), $predeterminadas));
    foreach ($permitidas as $clave) {
        if (!array_key_exists($clave, $datos)) {
            continue;
        }
        $valor = $datos[$clave];
        if (is_bool($predeterminadas[$clave])) {
            $limpias[$clave] = (bool) $valor;
        } else {
            $limpias[$clave] = trim((string) $valor);
        }
    }

    if (!in_array($limpias['elementosPagina'], ['10', '20', '50'], true)) {
        responderJson(['exito' => false, 'mensaje' => 'La cantidad de elementos por página no es válida.'], 422);
    }
    if (!in_array($limpias['tema'], ['claro', 'oscuro', 'sistema'], true)) {
        $limpias['tema'] = 'claro';
    }

    if ($limpias['torneoPredeterminado'] !== '') {
        $idTorneo = filter_var($limpias['torneoPredeterminado'], FILTER_VALIDATE_INT);
        if (!$idTorneo) {
            responderJson(['exito' => false, 'mensaje' => 'El torneo predeterminado no es válido.'], 422);
        }
        $q = $contexto['conexion']->prepare('SELECT 1 FROM torneos WHERE id_torneo = :id LIMIT 1');
        $q->execute([':id' => (int) $idTorneo]);
        if (!$q->fetchColumn()) {
            responderJson(['exito' => false, 'mensaje' => 'El torneo predeterminado ya no existe.'], 422);
        }
        $limpias['torneoPredeterminado'] = (string) $idTorneo;
    }

    $modelo->guardar($idUsuario, $limpias);
    responderJson([
        'exito' => true,
        'mensaje' => 'Preferencias guardadas.',
        'preferencias' => $limpias
    ]);
} catch (PDOException $error) {
    responderJson([
        'exito' => false,
        'mensaje' => 'La tabla de preferencias no está disponible. Ejecuta database/segunda_entrega_actualizacion.sql.'
    ], 500);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudieron gestionar las preferencias.'], 500);
}
