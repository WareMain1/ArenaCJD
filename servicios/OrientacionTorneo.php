<?php

 
function siguienteAccionTorneo(array $torneo): ?array
{
    $id = (int) ($torneo['id_torneo'] ?? 0);
    $estado = (string) ($torneo['estado'] ?? 'borrador');
    $aprobados = max(0, (int) ($torneo['cantidad_inscritos'] ?? $torneo['aprobados'] ?? 0));
    $cruces = max(0, (int) ($torneo['total_enfrentamientos'] ?? $torneo['enfrentamientos'] ?? 0));
    $finalizados = max(0, (int) ($torneo['enfrentamientos_finalizados'] ?? $torneo['finalizados'] ?? 0));

    if (in_array($estado, ['finalizado', 'cancelado'], true)) return null;
    if ($estado === 'borrador') {
        return ['texto' => 'Revisar configuración', 'detalle' => 'Completa los datos antes de abrir inscripciones.', 'url' => 'torneos.php?detalle=' . $id, 'orden' => 1];
    }
     
    if ($cruces === 0) {
        if ($aprobados < 2) {
            $detalle = $aprobados . ($aprobados === 1 ? ' inscripción aprobada' : ' inscripciones aprobadas');
            return ['texto' => 'Agregar participantes', 'detalle' => $detalle . ' · necesitas al menos 2 para sortear.', 'url' => 'participantes.php?torneo=' . $id, 'orden' => 2];
        }
        return ['texto' => 'Generar sorteo', 'detalle' => $aprobados . ' inscripciones aprobadas · sin enfrentamientos generados.', 'url' => 'sorteos.php?torneo=' . $id, 'orden' => 3];
    }
    $detalle = $finalizados . '/' . $cruces . ($cruces === 1 ? ' enfrentamiento finalizado.' : ' enfrentamientos finalizados.');
    if ($finalizados < $cruces) {
        return ['texto' => 'Cargar resultados', 'detalle' => $detalle, 'url' => 'resultados.php?torneo=' . $id, 'orden' => 4];
    }
    return ['texto' => 'Revisar clasificación', 'detalle' => $detalle, 'url' => 'clasificacion.php?torneo=' . $id, 'orden' => 5];
}

function calcularProgresoTorneo(array $torneo): int
{
    $total = max(0, (int) ($torneo['total_enfrentamientos'] ?? 0));
    $finalizados = max(0, (int) ($torneo['enfrentamientos_finalizados'] ?? 0));
    return $total > 0 ? (int) round($finalizados * 100 / $total) : 0;
}
