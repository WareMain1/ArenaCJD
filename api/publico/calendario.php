<?php

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../../modelos/Calendario.php';

try {
    $conexion = conexionPublica();
    $ids = array_fill_keys(idsTorneosPublicos($conexion), true);
    $datos = (new Calendario($conexion))->obtener();
    $eventos = array_values(array_filter($datos['eventos'], static function (array $evento) use ($ids): bool {
        return isset($ids[(int) ($evento['id_torneo'] ?? 0)]);
    }));
    $eventos = array_map(static function (array $evento): array {
        return [
            'id' => (string) $evento['id'],
            'tipo' => (string) $evento['tipo'],
            'id_torneo' => (int) $evento['id_torneo'],
            'torneo' => (string) $evento['torneo'],
            'disciplina' => (string) $evento['disciplina'],
            'estado' => (string) $evento['estado'],
            'fecha_hora' => (string) $evento['fecha_hora'],
            'titulo' => (string) $evento['titulo'],
            'detalle' => (string) ($evento['detalle'] ?? '')
        ];
    }, $eventos);
    responderPublico(['exito' => true, 'eventos' => $eventos]);
} catch (Throwable $error) {
    responderPublico(['exito' => false, 'mensaje' => 'No se pudo cargar el calendario público.'], 500);
}
