<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Equipo.php';

$contexto = contextoApi();

try {
    $gestorGlobal = in_array('administrador', $contexto['roles'], true);
    $esOrganizador = in_array('organizador', $contexto['roles'], true);
    $idActual = (int) $contexto['usuario']['id_usuario'];
    $modeloEquipo = new Equipo($contexto['conexion']);
    $equipos = $modeloEquipo->obtenerTodos();

    if (!$gestorGlobal) {
        $qMembresias = $contexto['conexion']->prepare("SELECT id_equipo FROM integrantes_equipo WHERE id_usuario = :id_usuario");
        $qMembresias->execute([':id_usuario' => $idActual]);
        $equiposMiembro = array_flip(array_map('intval', $qMembresias->fetchAll(PDO::FETCH_COLUMN)));
        $equipos = array_values(array_filter($equipos, static function (array $equipo) use ($esOrganizador, $idActual, $equiposMiembro): bool {
            if ((int) $equipo['id_creador'] === $idActual || isset($equiposMiembro[(int) $equipo['id_equipo']])) return true;
            return $esOrganizador && (int) ($equipo['torneo_id_organizador'] ?? 0) === $idActual;
        }));
    }

    foreach ($equipos as &$equipo) {
        $equipo['id_equipo'] = (int) $equipo['id_equipo'];
        $equipo['id_creador'] = (int) $equipo['id_creador'];
        $equipo['cantidad_integrantes'] = (int) $equipo['cantidad_integrantes'];
        $equipo['puede_gestionar'] = $gestorGlobal ||
            $equipo['id_creador'] === (int) $contexto['usuario']['id_usuario'];
        $competenciaIniciada = (bool) ($equipo['competencia_iniciada'] ?? false);
        $inscripcionesAbiertas = ($equipo['torneo_estado'] ?? '') === 'inscripciones';
        $equipo['puede_gestionar_inscripcion'] = !$competenciaIniciada && $inscripcionesAbiertas && ($gestorGlobal ||
            (
                in_array('organizador', $contexto['roles'], true) &&
                (int) ($equipo['torneo_id_organizador'] ?? 0) === (int) $contexto['usuario']['id_usuario']
            ));
        $equipo['competencia_iniciada'] = $competenciaIniciada;
        $equipo['puede_inscribir'] = $equipo['puede_gestionar'] && $equipo['estado_equipo'] === 'activo';
    }
    unset($equipo);

    responderJson([
        'exito' => true,
        'equipos' => $equipos,
        'resumen' => ['equipos' => count($equipos)],
        'csrf_token' => $contexto['csrf_token'],
        'actualizado_en' => date(DATE_ATOM)
    ]);
} catch (Throwable $error) {
    responderJson([
        'exito' => false,
        'mensaje' => 'No se pudieron cargar los equipos.'
    ], 500);
}
