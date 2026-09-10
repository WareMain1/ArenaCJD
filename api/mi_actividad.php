<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Invitacion.php';
require_once __DIR__ . '/../modelos/InvitacionEquipo.php';

$contexto = contextoApi();
$idUsuario = (int) $contexto['usuario']['id_usuario'];

try {
    $equiposQ = $contexto['conexion']->prepare(
        "SELECT DISTINCT e.id_equipo, e.nombre, e.estado, e.id_creador,
                creador.nombre_usuario AS responsable_usuario,
                (SELECT COUNT(*) FROM integrantes_equipo x WHERE x.id_equipo = e.id_equipo) AS integrantes
         FROM equipos e
         INNER JOIN usuarios creador ON creador.id_usuario = e.id_creador
         LEFT JOIN integrantes_equipo ie ON ie.id_equipo = e.id_equipo
         WHERE e.id_creador = :id OR ie.id_usuario = :id2
         ORDER BY e.fecha_creacion DESC"
    );
    $equiposQ->execute([':id' => $idUsuario, ':id2' => $idUsuario]);
    $equipos = $equiposQ->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Se consultan por separado las participaciones individuales y por equipos.
     * Así una inscripción individual aprobada siempre aparece en Mi actividad,
     * independientemente de que el usuario también pertenezca a equipos.
     */
    $individualesQ = $contexto['conexion']->prepare(
        "SELECT
                'individual' AS modalidad,
                ii.id_inscripcion,
                ii.estado,
                ii.fecha_inscripcion,
                t.id_torneo,
                t.nombre AS torneo,
                t.estado AS estado_torneo,
                t.fecha_inicio,
                t.fecha_fin,
                d.nombre AS disciplina,
                c.nombre AS categoria,
                tt.nombre AS tipo_torneo,
                NULL AS id_equipo,
                NULL AS equipo
         FROM inscripciones_individuales ii
         INNER JOIN torneos t ON t.id_torneo = ii.id_torneo
         INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
         INNER JOIN categorias c ON c.id_categoria = t.id_categoria
         INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
         WHERE ii.id_usuario = :id_usuario
           AND t.estado <> 'cancelado'"
    );
    $individualesQ->execute([':id_usuario' => $idUsuario]);
    $inscripcionesIndividuales = $individualesQ->fetchAll(PDO::FETCH_ASSOC);

    $equiposInscritosQ = $contexto['conexion']->prepare(
        "SELECT DISTINCT
                'equipo' AS modalidad,
                ie.id_inscripcion,
                ie.estado,
                ie.fecha_inscripcion,
                t.id_torneo,
                t.nombre AS torneo,
                t.estado AS estado_torneo,
                t.fecha_inicio,
                t.fecha_fin,
                d.nombre AS disciplina,
                c.nombre AS categoria,
                tt.nombre AS tipo_torneo,
                e.id_equipo,
                e.nombre AS equipo
         FROM integrantes_inscripcion_equipo iie
         INNER JOIN inscripciones_equipos ie ON ie.id_inscripcion = iie.id_inscripcion
         INNER JOIN equipos e ON e.id_equipo = ie.id_equipo
         INNER JOIN torneos t ON t.id_torneo = ie.id_torneo
         INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
         INNER JOIN categorias c ON c.id_categoria = t.id_categoria
         INNER JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo
         WHERE iie.id_usuario = :id_usuario
           AND t.estado <> 'cancelado'"
    );
    $equiposInscritosQ->execute([':id_usuario' => $idUsuario]);
    $inscripcionesEquipo = $equiposInscritosQ->fetchAll(PDO::FETCH_ASSOC);

    $inscripciones = array_merge($inscripcionesIndividuales, $inscripcionesEquipo);
    usort($inscripciones, static function (array $a, array $b): int {
        return strcmp((string) ($b['fecha_inscripcion'] ?? ''), (string) ($a['fecha_inscripcion'] ?? ''));
    });

    $partidosQ = $contexto['conexion']->prepare(
        "SELECT e.id_enfrentamiento, e.id_torneo, e.ronda, e.fecha_hora, e.estado,
                e.puntaje_a, e.puntaje_b, e.tipo_participante,
                e.id_usuario_a, e.id_usuario_b, e.id_equipo_a, e.id_equipo_b,
                t.nombre AS torneo, t.estado AS estado_torneo, d.nombre AS disciplina,
                CASE WHEN e.tipo_participante='equipo' THEN ea.nombre ELSE ua.nombre_completo END AS participante_a,
                CASE WHEN e.tipo_participante='equipo' THEN eb.nombre ELSE ub.nombre_completo END AS participante_b
         FROM enfrentamientos e
         INNER JOIN torneos t ON t.id_torneo = e.id_torneo
         INNER JOIN disciplinas d ON d.id_disciplina = t.id_disciplina
         LEFT JOIN equipos ea ON ea.id_equipo = e.id_equipo_a
         LEFT JOIN equipos eb ON eb.id_equipo = e.id_equipo_b
         LEFT JOIN usuarios ua ON ua.id_usuario = e.id_usuario_a
         LEFT JOIN usuarios ub ON ub.id_usuario = e.id_usuario_b
         WHERE t.estado <> 'cancelado'
           AND (
                e.id_usuario_a = :id
                OR e.id_usuario_b = :id2
                OR EXISTS (
                    SELECT 1
                    FROM inscripciones_equipos ie_hist
                    INNER JOIN integrantes_inscripcion_equipo iie_hist
                            ON iie_hist.id_inscripcion = ie_hist.id_inscripcion
                    WHERE ie_hist.id_torneo = e.id_torneo
                      AND iie_hist.id_usuario = :id3
                      AND (ie_hist.id_equipo = e.id_equipo_a OR ie_hist.id_equipo = e.id_equipo_b)
                )
           )
         ORDER BY COALESCE(e.fecha_hora, '9999-12-31 23:59:59') ASC, e.id_enfrentamiento ASC"
    );
    $partidosQ->execute([':id' => $idUsuario, ':id2' => $idUsuario, ':id3' => $idUsuario]);
    $todosPartidos = $partidosQ->fetchAll(PDO::FETCH_ASSOC);

    $ahora = time();
    $proximos = [];
    $resultados = [];
    foreach ($todosPartidos as $partido) {
        $marca = $partido['fecha_hora'] ? strtotime((string) $partido['fecha_hora']) : false;
        if ($partido['estado'] === 'finalizado') {
            $resultados[] = $partido;
        } elseif ($partido['estado'] !== 'cancelado' && ($marca === false || $marca >= $ahora || $partido['estado'] === 'en_curso')) {
            $proximos[] = $partido;
        }
    }
    usort($resultados, static fn(array $a, array $b): int => strcmp((string) ($b['fecha_hora'] ?? ''), (string) ($a['fecha_hora'] ?? '')));

    $invitaciones = array_map(static function (array $invitacion): array {
        $invitacion['clase_invitacion'] = 'torneo';
        return $invitacion;
    }, (new Invitacion($contexto['conexion']))->listarRecibidas($idUsuario));

    $invitacionesEquipo = array_map(static function (array $invitacion): array {
        return [
            'clase_invitacion' => 'equipo_membresia',
            'id_equipo' => $invitacion['id_equipo'],
            'equipo' => $invitacion['equipo'],
            'torneo' => $invitacion['equipo'],
            'tipo' => 'membresia_equipo',
            'estado' => $invitacion['estado'],
            'disciplina' => 'Equipo',
            'invitador_usuario' => $invitacion['invitador_usuario'],
            'fecha_creacion' => $invitacion['fecha_creacion']
        ];
    }, (new InvitacionEquipo($contexto['conexion']))->listarRecibidas($idUsuario));

    $invitaciones = array_merge($invitaciones, $invitacionesEquipo);
    usort($invitaciones, static fn(array $a, array $b): int => strcmp((string) ($b['fecha_creacion'] ?? ''), (string) ($a['fecha_creacion'] ?? '')));

    /* Alertas personales que se muestran al entrar a Mi actividad. */
    $alertas = [];
    foreach ($invitaciones as $invitacion) {
        if (($invitacion['estado'] ?? '') !== 'pendiente') continue;
        if (($invitacion['clase_invitacion'] ?? '') === 'equipo_membresia') {
            $alertas[] = [
                'tipo' => 'invitacion',
                'titulo' => 'Invitación pendiente a un equipo',
                'detalle' => 'Te invitaron a unirte a ' . (string) ($invitacion['equipo'] ?? 'un equipo') . '.',
                'url' => 'participantes.php#mis-invitaciones'
            ];
        } else {
            $alertas[] = [
                'tipo' => 'invitacion',
                'titulo' => 'Invitación pendiente a un torneo',
                'detalle' => 'Tienes una invitación para ' . (string) ($invitacion['torneo'] ?? 'un torneo') . '.',
                'url' => 'participantes.php#mis-invitaciones'
            ];
        }
    }

    foreach ($inscripciones as $inscripcion) {
        $estadoInscripcion = (string) ($inscripcion['estado'] ?? '');
        $estadoTorneo = (string) ($inscripcion['estado_torneo'] ?? '');
        if ($estadoInscripcion === 'pendiente') {
            $alertas[] = [
                'tipo' => 'inscripcion',
                'titulo' => 'Inscripción pendiente de aprobación',
                'detalle' => (string) ($inscripcion['torneo'] ?? 'Torneo') . ' todavía está esperando aprobación.',
                'url' => 'participantes.php#individuales'
            ];
        } elseif ($estadoInscripcion === 'aprobada' && $estadoTorneo === 'en_curso') {
            $alertas[] = [
                'tipo' => 'torneo',
                'titulo' => 'Estás participando en un torneo en curso',
                'detalle' => (string) ($inscripcion['torneo'] ?? 'Torneo') . ' está actualmente en competencia.',
                'url' => 'clasificacion.php?torneo=' . (int) ($inscripcion['id_torneo'] ?? 0)
            ];
        }
    }

    foreach ($proximos as $partido) {
        if (($partido['estado'] ?? '') === 'en_curso') {
            $alertas[] = [
                'tipo' => 'partido',
                'titulo' => 'Tienes un enfrentamiento en curso',
                'detalle' => (string) ($partido['torneo'] ?? 'Torneo') . ' · ' . (string) ($partido['ronda'] ?? 'Ronda'),
                'url' => 'partidos.php?torneo=' . (int) ($partido['id_torneo'] ?? 0)
            ];
            continue;
        }
        if (!empty($partido['fecha_hora'])) {
            $marca = strtotime((string) $partido['fecha_hora']);
            if ($marca !== false && $marca >= $ahora && $marca <= $ahora + 86400) {
                $alertas[] = [
                    'tipo' => 'partido',
                    'titulo' => 'Tienes un enfrentamiento dentro de las próximas 24 horas',
                    'detalle' => (string) ($partido['torneo'] ?? 'Torneo') . ' · ' . (string) ($partido['ronda'] ?? 'Ronda'),
                    'url' => 'partidos.php?torneo=' . (int) ($partido['id_torneo'] ?? 0)
                ];
            }
        }
    }

    responderJson([
        'exito' => true,
        'equipos' => array_slice($equipos, 0, 12),
        'inscripciones' => array_slice($inscripciones, 0, 30),
        'invitaciones' => array_slice($invitaciones, 0, 16),
        'proximos_enfrentamientos' => array_slice($proximos, 0, 10),
        'ultimos_resultados' => array_slice($resultados, 0, 10),
        'alertas' => array_slice($alertas, 0, 8),
        'cantidad_alertas' => count($alertas),
        'usuario_id' => $idUsuario,
        'usuario_nombre' => (string) ($contexto['usuario']['nombre_completo'] ?? 'Usuario'),
        'actualizado_en' => date(DATE_ATOM)
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo cargar tu actividad.'], 500);
}
