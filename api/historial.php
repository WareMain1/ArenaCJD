<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/_comun.php';

$contexto = contextoApi(['administrador']);

try {
    $consulta = $contexto['conexion']->prepare(
        "SELECT a.accion, a.entidad, a.id_entidad, a.detalle, a.resultado, a.fecha_evento,
                u.nombre_usuario, u.nombre_completo
         FROM auditoria a
         LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
         ORDER BY a.fecha_evento DESC, a.id_auditoria DESC
         LIMIT 150"
    );
    $consulta->execute();

    $titulos = [
        'torneo_creado' => 'Torneo creado',
        'torneo_actualizado' => 'Torneo actualizado',
        'torneo_eliminado' => 'Torneo eliminado',
        'torneo_cancelado' => 'Torneo cancelado',
        'torneo_estado_sincronizado' => 'Estado de torneo sincronizado',
        'torneo_finalizado' => 'Torneo finalizado',
        'equipo_creado' => 'Equipo creado',
        'equipo_actualizado' => 'Equipo actualizado',
        'equipo_eliminado' => 'Equipo eliminado',
        'equipo_archivado' => 'Equipo archivado',
        'equipo_integrante_agregado' => 'Integrante de equipo agregado',
        'equipo_integrante_eliminado' => 'Integrante de equipo removido',
        'invitacion_enviada' => 'Invitación enviada',
        'invitacion_aceptada' => 'Invitación aceptada',
        'invitacion_rechazada' => 'Invitación rechazada',
        'inscripcion_registrada' => 'Inscripción registrada',
        'inscripcion_actualizada' => 'Inscripción actualizada',
        'inscripcion_aprobada' => 'Inscripción aprobada',
        'inscripcion_rechazada' => 'Inscripción rechazada',
        'inscripcion_eliminada' => 'Inscripción eliminada',
        'sorteo_confirmado' => 'Sorteo confirmado',
        'ronda_generada' => 'Ronda generada',
        'enfrentamiento_actualizado' => 'Enfrentamiento actualizado',
        'resultado_actualizado' => 'Resultado actualizado',
        'cuenta_registrada' => 'Cuenta registrada',
        'usuario_actualizado' => 'Usuario actualizado',
        'usuario_rol_actualizado' => 'Roles de usuario actualizados',
        'preferencias_actualizadas' => 'Preferencias actualizadas',
        'usuario_aprobado' => 'Usuario aprobado',
        'usuario_rechazado' => 'Usuario rechazado',
        'usuario_eliminado' => 'Usuario eliminado',
        'nombre_usuario_actualizado' => 'Nombre de usuario actualizado',
        'foto_perfil_actualizada' => 'Foto de perfil actualizada',
        'foto_perfil_eliminada' => 'Foto de perfil eliminada',
        'cambio_contrasena' => 'Contraseña cambiada',
        'cambio_contrasena_denegado' => 'Cambio de contraseña rechazado',
        'recuperacion_configurada' => 'Recuperación configurada',
        'recuperacion_config_denegada' => 'Configuración de recuperación rechazada',
        'recuperacion_completada' => 'Recuperación completada',
        'recuperacion_contrasena' => 'Contraseña recuperada',
        'recuperacion_fallida' => 'Recuperación rechazada',
        'recuperacion_bloqueada' => 'Recuperación bloqueada',
        'disciplina_creada' => 'Disciplina creada',
        'disciplina_actualizada' => 'Disciplina actualizada',
        'disciplina_eliminada' => 'Disciplina eliminada',
        'categoria_creada' => 'Categoría creada',
        'categoria_actualizada' => 'Categoría actualizada',
        'categoria_eliminada' => 'Categoría eliminada',
        'login_exitoso' => 'Inicio de sesión correcto',
        'login_fallido' => 'Intento de inicio de sesión fallido',
        'login_denegado' => 'Inicio de sesión denegado',
        'login_bloqueado_temporal' => 'Bloqueo temporal de inicio de sesión'
    ];

    $etiquetasCategorias = [
        'sesiones' => 'Inicios de sesión',
        'amenazas' => 'Alertas de seguridad',
        'torneos' => 'Torneos',
        'usuarios' => 'Usuarios y accesos',
        'participacion' => 'Participación',
        'competencia' => 'Competencia',
        'catalogos' => 'Catálogos',
        'otros' => 'Otros eventos'
    ];

    $accionesAmenaza = [
        'login_fallido',
        'login_denegado',
        'login_bloqueado_temporal',
        'cambio_contrasena_denegado',
        'recuperacion_fallida',
        'recuperacion_bloqueada',
        'recuperacion_config_denegada'
    ];

    $accionesUsuarios = [
        'cuenta_registrada',
        'usuario_actualizado',
        'usuario_rol_actualizado',
        'preferencias_actualizadas',
        'usuario_aprobado',
        'usuario_rechazado',
        'usuario_eliminado',
        'nombre_usuario_actualizado',
        'foto_perfil_actualizada',
        'foto_perfil_eliminada',
        'cambio_contrasena',
        'recuperacion_configurada',
        'recuperacion_completada',
        'recuperacion_contrasena'
    ];

    $accionesParticipacion = [
        'equipo_creado',
        'equipo_actualizado',
        'equipo_eliminado',
        'equipo_archivado',
        'equipo_integrante_agregado',
        'equipo_integrante_eliminado',
        'invitacion_enviada',
        'invitacion_aceptada',
        'invitacion_rechazada',
        'inscripcion_registrada',
        'inscripcion_actualizada',
        'inscripcion_aprobada',
        'inscripcion_rechazada',
        'inscripcion_eliminada'
    ];

    $accionesCompetencia = [
        'sorteo_confirmado',
        'ronda_generada',
        'enfrentamiento_actualizado',
        'resultado_actualizado'
    ];

    $eventos = [];
    $resumen = array_fill_keys(array_keys($etiquetasCategorias), 0);

    foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $accion = (string) $fila['accion'];
        $entidad = (string) $fila['entidad'];
        $resultado = (string) ($fila['resultado'] ?? 'exito');

        if ($accion === 'login_exitoso') {
            $categoria = 'sesiones';
        } elseif (in_array($accion, $accionesAmenaza, true) || $resultado === 'denegado') {
            $categoria = 'amenazas';
        } elseif (str_starts_with($accion, 'torneo_')) {
            $categoria = 'torneos';
        } elseif (in_array($accion, $accionesUsuarios, true)) {
            $categoria = 'usuarios';
        } elseif (in_array($accion, $accionesParticipacion, true) || in_array($entidad, ['equipo', 'invitacion', 'inscripcion_individual', 'inscripcion_equipo'], true)) {
            $categoria = 'participacion';
        } elseif (in_array($accion, $accionesCompetencia, true) || in_array($entidad, ['enfrentamiento', 'resultado'], true)) {
            $categoria = 'competencia';
        } elseif (in_array($entidad, ['disciplina', 'categoria'], true) || str_starts_with($accion, 'disciplina_') || str_starts_with($accion, 'categoria_')) {
            $categoria = 'catalogos';
        } else {
            $categoria = 'otros';
        }

        $actor = $fila['nombre_usuario'] ? '@' . $fila['nombre_usuario'] : 'Sistema';
        $detalle = trim((string) ($fila['detalle'] ?? ''));
        $nivel = $categoria === 'amenazas' ? 'alerta' : ($resultado === 'denegado' ? 'alerta' : 'normal');
        $resumen[$categoria]++;

        $eventos[] = [
            'accion' => $accion,
            'tipo' => $entidad,
            'categoria' => $categoria,
            'categoria_etiqueta' => $etiquetasCategorias[$categoria],
            'nivel' => $nivel,
            'titulo' => $titulos[$accion] ?? ucwords(str_replace('_', ' ', $accion)),
            'detalle' => $actor . ($detalle !== '' ? ' · ' . $detalle : '') . ($resultado !== 'exito' ? ' · ' . $resultado : ''),
            'fecha' => $fila['fecha_evento']
        ];
    }

    responderJson([
        'exito' => true,
        'eventos' => $eventos,
        'resumen' => $resumen,
        'categorias' => $etiquetasCategorias,
        'actualizado_en' => date(DATE_ATOM)
    ]);
} catch (Throwable $error) {
    responderJson([
        'exito' => false,
        'mensaje' => 'No se pudo cargar el historial de auditoría. Verifica la estructura de la base de datos.'
    ], 500);
}
