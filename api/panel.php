<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Torneo.php';
require_once __DIR__ . '/../servicios/OrientacionTorneo.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';

$contexto = contextoApi(['administrador','organizador']);

try {
    $torneos = new Torneo($contexto['conexion']);
    $enfrentamientosModelo = new Enfrentamiento($contexto['conexion']);
    $esAdministrador = in_array('administrador', $contexto['roles'], true);
    $idOrganizador = (!$esAdministrador && in_array('organizador', $contexto['roles'], true))
        ? (int) $contexto['usuario']['id_usuario']
        : null;
    $resumen = $torneos->obtenerResumen($idOrganizador);
    $enfrentamientos = $enfrentamientosModelo->obtenerTodos(null, $idOrganizador);

    $pendientes = 0;
    $finalizados = 0;
    $hoyProgramados = 0;
    $hoyResultados = 0;
    $proximos = [];
    $hoy = date('Y-m-d');
    $ahora = time();

    $urgentesRevision = 0;
    $enPeriodoGracia = 0;

    foreach ($enfrentamientos as $e) {
        if (in_array($e['estado'], ['programado', 'en_curso', 'en_periodo_gracia', 'pendiente_revision', 'pendiente'], true)) {
            $pendientes++;
        }
        if ($e['estado'] === 'finalizado') {
            $finalizados++;
        }
        if ($e['estado'] === 'pendiente_revision' || ($e['gracia_vencida'] && !in_array($e['estado'], ['finalizado', 'cancelado'], true))) {
            $urgentesRevision++;
        } elseif ($e['en_gracia'] || $e['estado'] === 'en_periodo_gracia') {
            $enPeriodoGracia++;
        }

        $fecha = $e['fecha_hora'] ? strtotime($e['fecha_hora']) : false;
        if ($fecha !== false && date('Y-m-d', $fecha) === $hoy) {
            if (in_array($e['estado'], ['programado', 'en_curso', 'en_periodo_gracia', 'pendiente_revision', 'pendiente'], true)) {
                $hoyProgramados++;
            }
            if ($e['estado'] === 'finalizado') {
                $hoyResultados++;
            }
        }
        if ($fecha !== false && $fecha >= $ahora && $e['estado'] !== 'cancelado') {
            $proximos[] = $e;
        }
    }

    usort($proximos, static fn(array $a, array $b): int =>
        strcmp((string) $a['fecha_hora'], (string) $b['fecha_hora'])
    );
    $proximos = array_slice($proximos, 0, 6);

    $actividadReciente = [];
    $sqlActividad = "SELECT a.accion, a.entidad, a.id_entidad, a.detalle, a.fecha_evento, u.nombre_completo, u.nombre_usuario
                     FROM auditoria a
                     LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
                     WHERE a.resultado = 'exito'
                       AND a.accion NOT IN ('login_exitoso','logout','sesion_cerrada')";
    $parametrosActividad = [];
    if (!$esAdministrador) {
        $sqlActividad .= " AND a.id_usuario = :id_usuario";
        $parametrosActividad[':id_usuario'] = (int) $contexto['usuario']['id_usuario'];
    }
    $sqlActividad .= " ORDER BY a.fecha_evento DESC, a.id_auditoria DESC LIMIT 8";
    $qActividad = $contexto['conexion']->prepare($sqlActividad);
    $qActividad->execute($parametrosActividad);
    $etiquetasActividad = [
        'torneo_creado' => 'Torneo creado',
        'torneo_actualizado' => 'Torneo actualizado',
        'torneo_cancelado' => 'Torneo cancelado',
        'torneo_eliminado' => 'Torneo eliminado',
        'disciplina_creada' => 'Disciplina creada',
        'disciplina_actualizada' => 'Disciplina actualizada',
        'disciplina_eliminada' => 'Disciplina eliminada',
        'equipo_creado' => 'Equipo creado',
        'equipo_actualizado' => 'Equipo actualizado',
        'equipo_eliminado' => 'Equipo eliminado',
        'invitacion_enviada' => 'Invitación enviada',
        'invitacion_aceptada' => 'Invitación aceptada',
        'invitacion_rechazada' => 'Invitación rechazada',
        'foto_perfil_actualizada' => 'Foto de perfil actualizada',
        'foto_perfil_eliminada' => 'Foto de perfil eliminada',
        'nombre_usuario_actualizado' => 'Nombre de usuario actualizado',
        'inscripcion_registrada' => 'Inscripción registrada',
        'inscripcion_actualizada' => 'Inscripción actualizada',
        'inscripcion_eliminada' => 'Inscripción eliminada',
        'resultado_registrado' => 'Resultado registrado',
        'resultado_actualizado' => 'Resultado actualizado',
        'sorteo_generado' => 'Sorteo generado',
        'sorteo_confirmado' => 'Sorteo confirmado',
        'enfrentamiento_actualizado' => 'Enfrentamiento actualizado'
    ];
    foreach ($qActividad->fetchAll(PDO::FETCH_ASSOC) as $evento) {
        $actividadReciente[] = [
            'accion' => (string) $evento['accion'],
            'titulo' => $etiquetasActividad[$evento['accion']] ?? ucfirst(str_replace('_', ' ', (string) $evento['accion'])),
            'detalle' => (string) ($evento['detalle'] ?: ($evento['entidad'] ?? 'Actividad del sistema')),
            'usuario' => (string) ($evento['nombre_completo'] ?: 'Sistema'),
            'nombre_usuario' => (string) ($evento['nombre_usuario'] ?: ''),
            'fecha' => (string) $evento['fecha_evento'],
            'entidad' => (string) $evento['entidad'],
            'id_entidad' => $evento['id_entidad']
        ];
    }

    $prioridadesTorneos = [];
    foreach ($enfrentamientosModelo->resumenPorTorneo($idOrganizador) as $torneoResumen) {
        $orientacion = siguienteAccionTorneo($torneoResumen);
        if ($orientacion === null) continue;
        $accion = $orientacion['texto'];
        $detalle = $orientacion['detalle'];
        $url = $orientacion['url'];
        $orden = $orientacion['orden'];
        $prioridadesTorneos[] = [
            'id_torneo' => (int) $torneoResumen['id_torneo'],
            'nombre' => (string) $torneoResumen['nombre'],
            'disciplina' => (string) $torneoResumen['disciplina'],
            'accion' => $accion,
            'detalle' => $detalle,
            'url' => $url,
            'orden' => $orden
        ];
    }
    usort($prioridadesTorneos, static fn(array $a, array $b): int => $a['orden'] <=> $b['orden']);
    $prioridadesTorneos = array_slice($prioridadesTorneos, 0, 5);

    $solicitudes = 0;
    if (in_array('administrador', $contexto['roles'], true)) {
        $q = $contexto['conexion']->prepare("SELECT COUNT(*) FROM usuarios WHERE estado='pendiente'");
        $q->execute();
        $solicitudes = (int) $q->fetchColumn();
    }

    responderJson([
        'exito' => true,
        'resumen_torneos' => $resumen,
        'enfrentamientos_pendientes' => $pendientes,
        'resultados_registrados' => $finalizados,
        'hoy_programados' => $hoyProgramados,
        'hoy_resultados' => $hoyResultados,
        'proximos_enfrentamientos' => $proximos,
        'torneos_recientes' => $torneos->obtenerRecientes(5, $idOrganizador),
        'actividad_reciente' => $actividadReciente,
        'prioridades_torneos' => $prioridadesTorneos,
        'urgentes_revision' => $urgentesRevision,
        'en_periodo_gracia' => $enPeriodoGracia,
        'solicitudes_pendientes' => $solicitudes,
        'actualizado_en' => date(DATE_ATOM)
    ]);
} catch (Throwable $error) {
    responderJson(['exito'=>false,'mensaje'=>'No se pudo actualizar el panel.'],500);
}
