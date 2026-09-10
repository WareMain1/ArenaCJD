<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';

$contexto = contextoApi();

try {
    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $esOrganizador = in_array('organizador', $contexto['roles'], true);
    $idUsuario = (int) $contexto['usuario']['id_usuario'];
    $idOrganizador = (!$esAdministrador && $esOrganizador) ? $idUsuario : null;

    $modelo = new Enfrentamiento($contexto['conexion']);
    $torneos = $modelo->resumenPorTorneo($idOrganizador);

    if (!$esAdministrador && !$esOrganizador) {
        $q = $contexto['conexion']->prepare(
            "SELECT DISTINCT id_torneo
             FROM (
                 SELECT ii.id_torneo
                 FROM inscripciones_individuales ii
                 WHERE ii.id_usuario = :id_individual
                   AND ii.estado <> 'rechazada'
                 UNION
                 SELECT ie.id_torneo
                 FROM integrantes_inscripcion_equipo iie
                 INNER JOIN inscripciones_equipos ie ON ie.id_inscripcion = iie.id_inscripcion
                 WHERE iie.id_usuario = :id_equipo
                   AND ie.estado <> 'rechazada'
             ) participaciones"
        );
        $q->execute([
            ':id_individual' => $idUsuario,
            ':id_equipo' => $idUsuario
        ]);
        $permitidos = array_fill_keys(array_map('strval', $q->fetchAll(PDO::FETCH_COLUMN)), true);
        $torneos = array_values(array_filter($torneos, static function (array $torneo) use ($permitidos): bool {
            return isset($permitidos[(string) ($torneo['id_torneo'] ?? '')]);
        }));
    }

    responderJson([
        'exito' => true,
        'torneos' => $torneos
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo obtener el estado competitivo.'], 500);
}
