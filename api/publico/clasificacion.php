<?php

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../../modelos/Clasificacion.php';

$idTorneo = filter_input(INPUT_GET, 'id_torneo', FILTER_VALIDATE_INT);
if (!$idTorneo) {
    responderPublico(['exito' => false, 'mensaje' => 'Selecciona un torneo público.'], 400);
}

try {
    $conexion = conexionPublica();
    $torneo = obtenerTorneoPublico($conexion, (int) $idTorneo);
    if (!$torneo) {
        responderPublico(['exito' => false, 'mensaje' => 'El torneo no está disponible públicamente.'], 404);
    }
    $datos = (new Clasificacion($conexion))->obtener((int) $idTorneo);
    $filas = array_map(static function (array $fila): array {
        return [
            'id' => (int) $fila['id'],
            'posicion' => (int) $fila['posicion'],
            'nombre' => (string) $fila['nombre'],
            'jugados' => (int) $fila['jugados'],
            'ganados' => (int) $fila['ganados'],
            'empatados' => (int) $fila['empatados'],
            'perdidos' => (int) $fila['perdidos'],
            'favor' => (int) $fila['favor'],
            'contra' => (int) $fila['contra'],
            'diferencia' => (int) $fila['diferencia'],
            'puntos' => (int) $fila['puntos'],
            'estado' => (string) $fila['estado']
        ];
    }, $datos['clasificacion']);
    responderPublico([
        'exito' => true,
        'torneo' => $torneo,
        'clasificacion' => $filas,
        'resumen' => [
            'participantes' => (int) ($datos['resumen']['participantes'] ?? 0),
            'ronda_actual' => (string) ($datos['resumen']['ronda_actual'] ?? 'Sin disputar'),
            'progreso' => (int) ($datos['resumen']['progreso'] ?? 0),
            'campeon' => $datos['resumen']['campeon'] ?? null,
            'campeon_id' => $datos['resumen']['campeon_id'] ?? null
        ]
    ]);
} catch (Throwable $error) {
    responderPublico(['exito' => false, 'mensaje' => 'No se pudo cargar la clasificación pública.'], 500);
}
