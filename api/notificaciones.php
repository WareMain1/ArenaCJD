<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Invitacion.php';
require_once __DIR__ . '/../modelos/InvitacionEquipo.php';

$contexto = contextoApi();

try {
    $idUsuario = (int) $contexto['usuario']['id_usuario'];
    $esAdministrador = in_array('administrador', $contexto['roles'], true);

    $modeloInvitacion = new Invitacion($contexto['conexion']);
    $invitaciones = array_values(array_filter(
        $modeloInvitacion->listarRecibidas($idUsuario),
        static fn(array $invitacion): bool => $invitacion['estado'] === 'pendiente'
    ));

    $modeloInvitacionEquipo = new InvitacionEquipo($contexto['conexion']);
    $invitacionesEquipo = array_values(array_filter(
        $modeloInvitacionEquipo->listarRecibidas($idUsuario),
        static fn(array $invitacion): bool => $invitacion['estado'] === 'pendiente'
    ));

    $consultaAvisosTorneo = $contexto['conexion']->prepare(
        "SELECT DISTINCT
                    t.id_torneo,
                    t.nombre,
                    d.nombre AS disciplina,
                    t.estado,
                    TIMESTAMP(t.fecha_inicio, t.hora_inicio) AS fecha_hora_inicio
         FROM torneos t
         INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
         LEFT JOIN inscripciones_individuales ii
                ON ii.id_torneo = t.id_torneo
               AND ii.id_usuario = :usuario_individual
               AND ii.estado = 'aprobada'
         LEFT JOIN inscripciones_equipos ie
                ON ie.id_torneo = t.id_torneo
               AND ie.estado = 'aprobada'
         LEFT JOIN integrantes_inscripcion_equipo iie
                ON iie.id_inscripcion = ie.id_inscripcion
               AND iie.id_usuario = :usuario_equipo
         WHERE t.estado IN ('inscripciones', 'en_curso')
           AND TIMESTAMP(t.fecha_inicio, t.hora_inicio) BETWEEN DATE_SUB(NOW(), INTERVAL 24 HOUR) AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
           AND (t.id_organizador = :usuario_organizador OR ii.id_usuario IS NOT NULL OR iie.id_usuario IS NOT NULL)
         ORDER BY fecha_hora_inicio ASC"
    );
    $consultaAvisosTorneo->execute([
        ':usuario_individual' => $idUsuario,
        ':usuario_equipo' => $idUsuario,
        ':usuario_organizador' => $idUsuario
    ]);
    $avisosTorneo = array_map(static function (array $fila): array {
        $inicio = new DateTimeImmutable((string) $fila['fecha_hora_inicio']);
        $yaInicio = $inicio <= new DateTimeImmutable();
        return [
            'id_torneo' => (int) $fila['id_torneo'],
            'torneo' => $fila['nombre'],
            'disciplina' => $fila['disciplina'],
            'tipo' => $yaInicio ? 'iniciado' : 'proximo',
            'fecha_hora' => $fila['fecha_hora_inicio'],
            'url' => 'torneos.php?detalle=' . (int) $fila['id_torneo']
        ];
    }, $consultaAvisosTorneo->fetchAll(PDO::FETCH_ASSOC));

    $actividadUsuario = [];

    $consultaActividadIndividual = $contexto['conexion']->prepare(
        "SELECT ii.id_inscripcion, ii.estado AS estado_inscripcion,
                t.id_torneo, t.nombre AS torneo, t.estado AS estado_torneo,
                d.nombre AS disciplina
         FROM inscripciones_individuales ii
         INNER JOIN torneos t ON t.id_torneo = ii.id_torneo
         INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
         WHERE ii.id_usuario = :usuario
           AND (
                (ii.estado = 'pendiente' AND t.estado IN ('inscripciones', 'en_curso'))
                OR (ii.estado = 'aprobada' AND t.estado = 'en_curso')
           )"
    );
    $consultaActividadIndividual->execute([':usuario' => $idUsuario]);
    foreach ($consultaActividadIndividual->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $pendiente = (string) $fila['estado_inscripcion'] === 'pendiente';
        $actividadUsuario[] = [
            'clave' => 'actividad:inscripcion-individual:' . (int) $fila['id_inscripcion'] . ':' . (string) $fila['estado_inscripcion'] . ':' . (string) $fila['estado_torneo'],
            'titulo' => $pendiente ? 'Inscripción pendiente de aprobación' : 'Estás participando en un torneo en curso',
            'detalle' => (string) $fila['torneo'] . ' · ' . (string) $fila['disciplina'],
            'url' => $pendiente ? 'participantes.php#individuales' : 'mi-actividad.php'
        ];
    }

    $consultaActividadEquipos = $contexto['conexion']->prepare(
        "SELECT DISTINCT ie.id_inscripcion, ie.estado AS estado_inscripcion,
                t.id_torneo, t.nombre AS torneo, t.estado AS estado_torneo,
                d.nombre AS disciplina, e.nombre AS equipo
         FROM inscripciones_equipos ie
         INNER JOIN integrantes_inscripcion_equipo iie ON iie.id_inscripcion = ie.id_inscripcion
         INNER JOIN equipos e ON e.id_equipo = ie.id_equipo
         INNER JOIN torneos t ON t.id_torneo = ie.id_torneo
         INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
         WHERE iie.id_usuario = :usuario
           AND (
                (ie.estado = 'pendiente' AND t.estado IN ('inscripciones', 'en_curso'))
                OR (ie.estado = 'aprobada' AND t.estado = 'en_curso')
           )"
    );
    $consultaActividadEquipos->execute([':usuario' => $idUsuario]);
    foreach ($consultaActividadEquipos->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $pendiente = (string) $fila['estado_inscripcion'] === 'pendiente';
        $actividadUsuario[] = [
            'clave' => 'actividad:inscripcion-equipo:' . (int) $fila['id_inscripcion'] . ':' . (string) $fila['estado_inscripcion'] . ':' . (string) $fila['estado_torneo'],
            'titulo' => $pendiente ? 'Inscripción de equipo pendiente de aprobación' : 'Tu equipo está participando en un torneo en curso',
            'detalle' => (string) $fila['equipo'] . ' · ' . (string) $fila['torneo'],
            'url' => $pendiente ? 'participantes.php' : 'mi-actividad.php'
        ];
    }

    $consultaActividadPartidos = $contexto['conexion']->prepare(
        "SELECT DISTINCT e.id_enfrentamiento, e.estado, e.fecha_hora, e.ronda,
                t.id_torneo, t.nombre AS torneo, d.nombre AS disciplina
         FROM enfrentamientos e
         INNER JOIN torneos t ON t.id_torneo = e.id_torneo
         INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
         WHERE t.estado = 'en_curso'
           AND (
                e.estado = 'en_curso'
                OR (
                    e.estado IN ('pendiente', 'programado')
                    AND e.fecha_hora IS NOT NULL
                    AND e.fecha_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
                )
           )
           AND (
                e.id_usuario_a = :usuario_a
                OR e.id_usuario_b = :usuario_b
                OR EXISTS (
                    SELECT 1
                    FROM inscripciones_equipos ieh
                    INNER JOIN integrantes_inscripcion_equipo iieh ON iieh.id_inscripcion = ieh.id_inscripcion
                    WHERE ieh.id_torneo = e.id_torneo
                      AND iieh.id_usuario = :usuario_equipo
                      AND (ieh.id_equipo = e.id_equipo_a OR ieh.id_equipo = e.id_equipo_b)
                )
           )"
    );
    $consultaActividadPartidos->execute([
        ':usuario_a' => $idUsuario,
        ':usuario_b' => $idUsuario,
        ':usuario_equipo' => $idUsuario
    ]);
    foreach ($consultaActividadPartidos->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $enCurso = (string) $fila['estado'] === 'en_curso';
        $actividadUsuario[] = [
            'clave' => 'actividad:enfrentamiento:' . (int) $fila['id_enfrentamiento'] . ':' . (string) $fila['estado'] . ':' . (string) ($fila['fecha_hora'] ?? ''),
            'titulo' => $enCurso ? 'Tienes un enfrentamiento en curso' : 'Tienes un enfrentamiento dentro de las próximas 24 horas',
            'detalle' => (string) $fila['torneo'] . ' · ' . (string) $fila['ronda'],
            'url' => 'partidos.php?torneo=' . (int) $fila['id_torneo']
        ];
    }

    $esOrganizador = in_array('organizador', $contexto['roles'], true);
    if ($esAdministrador || $esOrganizador) {
        $sqlOrg = "SELECT DISTINCT e.id_enfrentamiento, e.estado, e.fecha_hora, e.ronda,
                          t.id_torneo, t.nombre AS torneo, d.nombre AS disciplina
                   FROM enfrentamientos e
                   INNER JOIN torneos t ON t.id_torneo = e.id_torneo
                   INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
                   WHERE t.estado = 'en_curso'
                     AND e.estado IN ('en_periodo_gracia', 'pendiente_revision')";
        $paramsOrg = [];
        if (!$esAdministrador) {
            $sqlOrg .= " AND t.id_organizador = :id_org";
            $paramsOrg[':id_org'] = $idUsuario;
        }
        $qOrg = $contexto['conexion']->prepare($sqlOrg);
        $qOrg->execute($paramsOrg);
        foreach ($qOrg->fetchAll(PDO::FETCH_ASSOC) as $partidoOrg) {
            $enGracia = $partidoOrg['estado'] === 'en_periodo_gracia';
            $actividadUsuario[] = [
                'clave' => 'actividad:enfrentamiento-atencion:' . (int) $partidoOrg['id_enfrentamiento'] . ':' . (string) $partidoOrg['estado'],
                'titulo' => $enGracia ? 'Período de gracia: confirma el resultado' : 'Revisión requerida: resultado pendiente',
                'detalle' => (string) $partidoOrg['torneo'] . ' · ' . (string) $partidoOrg['ronda'],
                'url' => 'resultados.php?torneo=' . (int) $partidoOrg['id_torneo']
            ];
        }
    }

    $firmasActividadUsuario = [];
    foreach ($invitaciones as $invitacion) {
        $firmasActividadUsuario[] = 'invitacion:' . (int) $invitacion['id_invitacion'];
    }
    foreach ($invitacionesEquipo as $invitacion) {
        $firmasActividadUsuario[] = 'invitacion-equipo:' . (int) $invitacion['id_invitacion_equipo'];
    }
    foreach ($actividadUsuario as $actividad) {
        $firmasActividadUsuario[] = (string) $actividad['clave'];
    }
    $firmasActividadUsuario = array_values(array_unique($firmasActividadUsuario));
    $cantidadActividadUsuario = count($firmasActividadUsuario);

    $solicitudes = [];
    if ($esAdministrador) {
        $modeloUsuario = new Usuario($contexto['conexion']);
        $solicitudes = array_map(static function (array $usuario): array {
            return [
                'id' => (int) $usuario['id_usuario'],
                'nombre' => $usuario['nombre_completo'],
                'nombre_usuario' => $usuario['nombre_usuario'],
                'correo' => $usuario['correo'],
                'fecha_registro' => $usuario['fecha_registro']
            ];
        }, $modeloUsuario->listarPendientes());
    }

    responderJson([
        'exito' => true,
        'es_administrador' => $esAdministrador,
        'cantidad_total' => count($invitaciones) + count($invitacionesEquipo) + count($solicitudes) + count($avisosTorneo),
        'cantidad_invitaciones' => count($invitaciones) + count($invitacionesEquipo),
        'cantidad_invitaciones_torneo' => count($invitaciones),
        'cantidad_invitaciones_equipo' => count($invitacionesEquipo),
        'cantidad_avisos_torneo' => count($avisosTorneo),
        'cantidad_pendientes' => count($solicitudes),
        'cantidad_actividad_usuario' => $cantidadActividadUsuario,
        'firmas_actividad_usuario' => $firmasActividadUsuario,
        'actividad_usuario' => array_slice($actividadUsuario, 0, 8),
        'invitaciones' => $invitaciones,
        'invitaciones_equipo' => $invitacionesEquipo,
        'avisos_torneo' => $avisosTorneo,
        'solicitudes' => $solicitudes,
        'ultima_gestion' => $esAdministrador ? ($_SESSION['notificacion_admin'] ?? null) : null,
        'csrf_token' => $contexto['csrf_token']
    ]);
} catch (Throwable $error) {
    responderJson([
        'exito' => false,
        'mensaje' => 'No se pudieron cargar las notificaciones.'
    ], 500);
}
