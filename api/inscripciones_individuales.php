<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Inscripcion.php';

$contexto = contextoApi();

try {
    $modelo = new Inscripcion($contexto['conexion']);
    $inscripciones = $modelo->obtenerIndividuales();
    $esAdmin = in_array('administrador', $contexto['roles'], true);
    $esOrganizador = in_array('organizador', $contexto['roles'], true);
    $idActual = (int) $contexto['usuario']['id_usuario'];

    if (!$esAdmin) {
        $inscripciones = array_values(array_filter($inscripciones, static function (array $inscripcion) use ($esOrganizador, $idActual): bool {
            if ((int) $inscripcion['id_usuario'] === $idActual) return true;
            return $esOrganizador && (int) $inscripcion['id_organizador'] === $idActual;
        }));
    }

    foreach ($inscripciones as &$inscripcion) {
        $esGestorTorneo = $esAdmin || ($esOrganizador && (int) $inscripcion['id_organizador'] === $idActual);
        $esPropia = (int) $inscripcion['id_usuario'] === $idActual;
        $inscripcion['id_inscripcion'] = (int) $inscripcion['id_inscripcion'];
        $inscripcion['id_torneo'] = (int) $inscripcion['id_torneo'];
        $inscripcion['id_usuario'] = (int) $inscripcion['id_usuario'];
        $competenciaIniciada = (bool) ($inscripcion['competencia_iniciada'] ?? false);
        $inscripcionesAbiertas = ($inscripcion['torneo_estado'] ?? '') === 'inscripciones';
        $inscripcion['puede_editar'] = !$competenciaIniciada && $inscripcionesAbiertas && ($esGestorTorneo || ($esPropia && $inscripcion['estado'] === 'pendiente'));
        $inscripcion['puede_eliminar'] = !$competenciaIniciada && in_array($inscripcion['estado'], ['pendiente', 'rechazada'], true) && ($esGestorTorneo || $esPropia);
        $inscripcion['puede_gestionar_estado'] = !$competenciaIniciada && $inscripcionesAbiertas && $esGestorTorneo;
        $inscripcion['competencia_iniciada'] = $competenciaIniciada;
    }
    unset($inscripcion);

    responderJson([
        'exito' => true,
        'inscripciones' => $inscripciones,
        'csrf_token' => $contexto['csrf_token'],
        'actualizado_en' => date(DATE_ATOM)
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudieron cargar las inscripciones individuales.'], 500);
}
