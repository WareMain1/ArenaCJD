<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';

$contexto = contextoApi();
$q = trim((string) ($_GET['q'] ?? ''));

if (mb_strlen($q) < 2) {
    responderJson(['exito' => true, 'grupos' => []]);
}

try {
    $conexion = $contexto['conexion'];
    $idUsuario = (int) $contexto['usuario']['id_usuario'];
    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $esOrganizador = in_array('organizador', $contexto['roles'], true);
    $busqueda = '%' . $q . '%';
    $grupos = [];

    $sqlTorneos = "SELECT DISTINCT t.id_torneo, t.nombre, d.nombre AS disciplina, t.estado
                   FROM torneos t
                   INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina";
    $paramsTorneos = [':q1' => $busqueda, ':q2' => $busqueda];
    if ($esAdministrador) {
        $sqlTorneos .= " WHERE (t.nombre LIKE :q1 OR d.nombre LIKE :q2)";
    } elseif ($esOrganizador) {
        $sqlTorneos .= " WHERE t.id_organizador = :usuario AND (t.nombre LIKE :q1 OR d.nombre LIKE :q2)";
        $paramsTorneos[':usuario'] = $idUsuario;
    } else {
        $sqlTorneos .= " LEFT JOIN inscripciones_individuales ii ON ii.id_torneo = t.id_torneo AND ii.id_usuario = :usuario_individual
                         LEFT JOIN inscripciones_equipos ie ON ie.id_torneo = t.id_torneo
                         LEFT JOIN integrantes_inscripcion_equipo iie ON iie.id_inscripcion = ie.id_inscripcion AND iie.id_usuario = :usuario_equipo
                         WHERE (t.publicado = 1 OR ii.id_usuario IS NOT NULL OR iie.id_usuario IS NOT NULL)
                           AND (t.nombre LIKE :q1 OR d.nombre LIKE :q2)";
        $paramsTorneos[':usuario_individual'] = $idUsuario;
        $paramsTorneos[':usuario_equipo'] = $idUsuario;
    }
    $sqlTorneos .= " ORDER BY t.fecha_creacion DESC LIMIT 7";
    $stmt = $conexion->prepare($sqlTorneos);
    $stmt->execute($paramsTorneos);
    $torneos = array_map(static function (array $fila): array {
        return [
            'tipo' => 'torneo',
            'id' => (int) $fila['id_torneo'],
            'titulo' => $fila['nombre'],
            'detalle' => $fila['disciplina'] . ' · ' . str_replace('_', ' ', $fila['estado']),
            'url' => 'torneos.php?detalle=' . (int) $fila['id_torneo']
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    if ($torneos) $grupos[] = ['titulo' => 'Torneos', 'items' => $torneos];

    $stmt = $conexion->prepare("SELECT id_disciplina, nombre, estado FROM disciplinas WHERE nombre LIKE :q ORDER BY estado='activa' DESC, nombre ASC LIMIT 6");
    $stmt->execute([':q' => $busqueda]);
    $disciplinas = array_map(static function (array $fila): array {
        return [
            'tipo' => 'disciplina',
            'id' => (int) $fila['id_disciplina'],
            'titulo' => $fila['nombre'],
            'detalle' => ucfirst($fila['estado']),
            'url' => 'disciplinas.php?buscar=' . rawurlencode($fila['nombre'])
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    if ($disciplinas && $esAdministrador) $grupos[] = ['titulo' => 'Disciplinas', 'items' => $disciplinas];

    if ($esAdministrador || $esOrganizador) {
        if ($esAdministrador) {
            $sqlEquipos = "SELECT e.id_equipo, e.nombre, u.nombre_usuario AS responsable_usuario
                           FROM equipos e
                           INNER JOIN usuarios u ON u.id_usuario = e.id_creador
                           WHERE e.nombre LIKE :q1 OR u.nombre_usuario LIKE :q2
                           ORDER BY e.nombre ASC LIMIT 6";
            $paramsEquipos = [':q1' => $busqueda, ':q2' => $busqueda];
        } else {
            $sqlEquipos = "SELECT DISTINCT e.id_equipo, e.nombre, u.nombre_usuario AS responsable_usuario
                           FROM equipos e
                           INNER JOIN usuarios u ON u.id_usuario = e.id_creador
                           LEFT JOIN inscripciones_equipos ie ON ie.id_equipo = e.id_equipo
                           LEFT JOIN torneos t ON t.id_torneo = ie.id_torneo
                           WHERE (e.id_creador = :usuario OR t.id_organizador = :usuario2)
                             AND (e.nombre LIKE :q1 OR u.nombre_usuario LIKE :q2)
                           ORDER BY e.nombre ASC LIMIT 6";
            $paramsEquipos = [':usuario' => $idUsuario, ':usuario2' => $idUsuario, ':q1' => $busqueda, ':q2' => $busqueda];
        }
        $stmt = $conexion->prepare($sqlEquipos);
        $stmt->execute($paramsEquipos);
        $equipos = array_map(static function (array $fila): array {
            return [
                'tipo' => 'equipo',
                'id' => (int) $fila['id_equipo'],
                'titulo' => $fila['nombre'],
                'detalle' => 'Responsable @' . $fila['responsable_usuario'],
                'url' => 'participantes.php?buscar=' . rawurlencode($fila['nombre'])
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        if ($equipos) $grupos[] = ['titulo' => 'Equipos', 'items' => $equipos];

        $stmt = $conexion->prepare("SELECT id_usuario, nombre_completo, nombre_usuario FROM usuarios WHERE estado='activo' AND (nombre_completo LIKE :q1 OR nombre_usuario LIKE :q2) ORDER BY nombre_completo ASC LIMIT 6");
        $stmt->execute([':q1' => $busqueda, ':q2' => $busqueda]);
        $usuarios = array_map(static function (array $fila): array {
            return [
                'tipo' => 'usuario',
                'id' => (int) $fila['id_usuario'],
                'titulo' => $fila['nombre_completo'],
                'detalle' => '@' . $fila['nombre_usuario'],
                'url' => 'participantes.php?buscar=' . rawurlencode($fila['nombre_usuario'])
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        if ($usuarios) $grupos[] = ['titulo' => 'Personas', 'items' => $usuarios];
    }

    responderJson(['exito' => true, 'grupos' => $grupos]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo completar la búsqueda.'], 500);
}
