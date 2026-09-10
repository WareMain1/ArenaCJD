<?php
require_once __DIR__ . '/../servicios/OrientacionTorneo.php';

$casos = [
    ['borrador', 0, 0, 0, 'Revisar configuración'],
    ['inscripciones', 1, 0, 0, 'Agregar participantes'],
    ['en_curso', 0, 0, 0, 'Agregar participantes'],
    ['en_curso', 1, 0, 0, 'Agregar participantes'],
    ['en_curso', 2, 0, 0, 'Generar sorteo'],
    ['inscripciones', 2, 0, 0, 'Generar sorteo'],
    ['en_curso', 4, 3, 1, 'Cargar resultados'],
    ['en_curso', 4, 3, 3, 'Revisar clasificación'],
    ['finalizado', 4, 3, 3, null],
    ['cancelado', 4, 3, 1, null],
];
foreach ($casos as [$estado, $aprobados, $cruces, $finalizados, $esperada]) {
    $accion = siguienteAccionTorneo(['id_torneo' => 7, 'estado' => $estado, 'aprobados' => $aprobados, 'enfrentamientos' => $cruces, 'finalizados' => $finalizados]);
    if (($accion['texto'] ?? null) !== $esperada) throw new RuntimeException('Orientación incorrecta: ' . $estado);
    if ($cruces === 0 && str_contains($accion['detalle'] ?? '', '0/1')) throw new RuntimeException('Cruce ficticio');
}
foreach ([[0, 0, 0], [3, 1, 33], [3, 3, 100]] as [$total, $finalizados, $esperado]) {
    if (calcularProgresoTorneo(['total_enfrentamientos' => $total, 'enfrentamientos_finalizados' => $finalizados]) !== $esperado) throw new RuntimeException('Progreso incorrecto');
}
echo "13 casos de orientación y progreso correctos.\n";
