<?php

require_once __DIR__ . '/config/proteger.php';
require_once __DIR__ . '/config/Conexion.php';
require_once __DIR__ . '/modelos/Torneo.php';

$rolesUsuario = $_SESSION['roles'] ?? [];

$esAdministrador = in_array(
    'administrador',
    $rolesUsuario,
    true
);
$esOrganizador = in_array('organizador', $rolesUsuario, true);
$puedeGestionarPanel = $esAdministrador || $esOrganizador;
$filtroOrganizadorPanel = (!$esAdministrador && $esOrganizador) ? (int) ($_SESSION['usuario_id'] ?? 0) : null;

$nombreUsuarioSesion = htmlspecialchars(
    $_SESSION['nombre_completo'] ?? 'Usuario',
    ENT_QUOTES,
    'UTF-8'
);

$aliasUsuarioSesion = htmlspecialchars(
    $_SESSION['nombre_usuario'] ?? 'usuario',
    ENT_QUOTES,
    'UTF-8'
);

$cantidadSolicitudesPendientes = 0;
$resumenTorneosPanel = [
    'total' => 0,
    'activos' => 0,
    'finalizados' => 0,
    'proximos' => 0,
    'cancelados' => 0,
    'comienzan_hoy' => 0,
    'participantes' => 0
];
$torneosRecientesPanel = [];

if ($puedeGestionarPanel) {
    try {
        $conexionTorneosPanel = (new Conexion())->conectar();
        $modeloTorneosPanel = new Torneo($conexionTorneosPanel);
        $resumenTorneosPanel = $modeloTorneosPanel->obtenerResumen($filtroOrganizadorPanel);
        $torneosRecientesPanel = $modeloTorneosPanel->obtenerRecientes(4, $filtroOrganizadorPanel);
    } catch (Throwable $error) {
    }
}

$totalEstadosPanel = max(
    1,
    $resumenTorneosPanel['activos'] +
    $resumenTorneosPanel['proximos'] +
    $resumenTorneosPanel['finalizados']
);
$porcentajeActivosPanel = (int) round(($resumenTorneosPanel['activos'] / $totalEstadosPanel) * 100);
$porcentajeProximosPanel = (int) round(($resumenTorneosPanel['proximos'] / $totalEstadosPanel) * 100);
$porcentajeFinalizadosPanel = max(0, 100 - $porcentajeActivosPanel - $porcentajeProximosPanel);

function formatearTiempoRelativoPanel(?string $fecha): string
{
    if (!$fecha) return '';
    $marca = strtotime($fecha);
    if ($marca === false) return '';
    $diferencia = max(0, time() - $marca);
    if ($diferencia < 60) return 'ahora';
    if ($diferencia < 3600) return 'hace ' . max(1, (int) floor($diferencia / 60)) . ' min';
    if ($diferencia < 86400) return 'hace ' . (int) floor($diferencia / 3600) . ' h';
    if ($diferencia < 172800) return 'ayer';
    if ($diferencia < 2592000) return 'hace ' . (int) floor($diferencia / 86400) . ' días';
    return date('d/m/Y', $marca);
}

if ($esAdministrador) {
    $conexionBD = new Conexion();
    $conexion = $conexionBD->conectar();

    $consulta = $conexion->query(
        "SELECT COUNT(*)
         FROM usuarios
         WHERE estado = 'pendiente'"
    );

    $cantidadSolicitudesPendientes =
        (int) $consulta->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Principal - ArenaCJD</title>
<link rel="icon" type="image/png" href="imagenes/arena-cjd-isotipo.png">
<script src="js/tema-inicial.js"></script>
<script src="js/iconos.js?v=20260909-sprint11"></script>
<script>
  if (window.matchMedia('(min-width: 992px)').matches &&
      localStorage.getItem('menuColapsado') !== 'true') {
    document.documentElement.classList.add('menu-pre-abierto');
  }
</script>

<link rel="stylesheet" href="css/variables.css?v=20260909-audit12">
<link rel="stylesheet" href="css/layout.css?v=20260909-sprint13">
<link rel="stylesheet" href="css/componentes.css?v=20260910-visibilidad1">
<link rel="stylesheet" href="css/experiencia.css?v=20260910-guia1">
<link rel="stylesheet" href="css/animaciones.css">
<link rel="stylesheet" href="css/paginas/panel.css?v=20260908-actividad1">
<link rel="stylesheet" href="css/utilidades.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/tema-claro.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/tema-oscuro.css?v=20260908-toast1">
<link rel="stylesheet" href="css/contraste-claro.css?v=20260909-audit12">
<link rel="stylesheet" href="css/formularios.css?v=20260910-mobile1">
</head>
<body class="cuerpo-app" <?= atributosSesionArenaCJD() ?>>
<div class="contenedor-app">
<aside class="menu-lateral" id="menuLateral" data-componente="menu-lateral"></aside>

<div class="fondo-oscuro" id="fondoOscuro"></div>

<div class="contenido-app">
<header class="encabezado-app">
<button class="boton-menu" id="botonMenu" aria-label="Abrir menú" aria-expanded="false">
<span data-icono="menu" class="icono-menu-abrir" aria-hidden="true"></span><span data-icono="peligro" class="icono-menu-cerrar" aria-hidden="true"></span>
</button>
<div class="logo-app">
<img src="imagenes/arena-cjd-logo-horizontal.png" alt="Logo de ArenaCJD">
</div>
<div class="acciones-app">
<button class="boton-notificaciones" id="botonNotificaciones" type="button" aria-label="Abrir notificaciones" aria-expanded="false" aria-controls="panelNotificaciones">
<span data-icono="notificaciones" aria-hidden="true"></span>
<span class="contador-notificaciones" id="contadorNotificaciones" hidden>0</span>
</button>
</div>
</header>

<main class="panel panel-administrativo" data-tipo-panel="<?= $puedeGestionarPanel ? 'gestion' : 'participante' ?>">
<section class="bienvenida-panel">
<div>
<span class="etiqueta-administracion">
  <?= $esAdministrador
      ? 'Centro de control administrativo'
      : ($esOrganizador ? 'Panel del organizador' : 'Panel personal') ?>
</span><h1 class="titulo-modulo">
  <span id="saludoPanel"><?= (int) date('G') >= 6 && (int) date('G') < 12 ? 'Buenos días' : ((int) date('G') >= 12 && (int) date('G') < 19 ? 'Buenas tardes' : 'Buenas noches') ?></span>, <?= $nombreUsuarioSesion ?>
  <span>(@<?= $aliasUsuarioSesion ?>)</span>
</h1>
<p><?= $puedeGestionarPanel
    ? 'Aquí tienes un resumen del estado actual de ArenaCJD.'
    : 'Aquí tienes un resumen de tus torneos, inscripciones, invitaciones y próximos enfrentamientos.' ?></p>
</div>
<div class="fecha-panel" id="fechaPanel"></div>
</section>

<?php if ($puedeGestionarPanel): ?>
<section class="grid-resumen-panel" aria-label="Resumen administrativo">
<article class="tarjeta-ah tarjeta-resumen-panel"><span class="icono-resumen-panel"><span class="icono-imagen-ui" data-icono="torneo" aria-hidden="true"></span></span><div><span>Torneos activos</span><strong id="panelTorneosActivos"><?= $resumenTorneosPanel['activos'] ?></strong><small>De <span id="panelTorneosTotal"><?= $resumenTorneosPanel['total'] ?></span> torneos registrados</small></div></article>
<article class="tarjeta-ah tarjeta-resumen-panel"><span class="icono-resumen-panel"><span class="icono-imagen-ui" data-icono="participantes" aria-hidden="true"></span></span><div><span>Participantes registrados</span><strong id="panelParticipantes"><?= $resumenTorneosPanel['participantes'] ?></strong><small>Con inscripción aprobada</small></div></article>
<article class="tarjeta-ah tarjeta-resumen-panel"><span class="icono-resumen-panel"><span class="icono-imagen-ui" data-icono="enfrentamientos" aria-hidden="true"></span></span><div><span>Enfrentamientos pendientes</span><strong id="panelEnfrentamientosPendientes">0</strong><small>Programados o en curso</small></div></article>
<article class="tarjeta-ah tarjeta-resumen-panel"><span class="icono-resumen-panel"><span class="icono-imagen-ui" data-icono="resultados" aria-hidden="true"></span></span><div><span>Resultados registrados</span><strong id="panelResultadosRegistrados">0</strong><small>Enfrentamientos finalizados</small></div></article>
</section>

<section class="tarjeta-ah seccion-panel ruta-gestion-panel" aria-label="Ruta recomendada de gestión">
<div class="cabecera-bloque-panel"><div><h2>Ruta de gestión</h2><p>Si estás comenzando un torneo, sigue este recorrido para avanzar sin perder contexto.</p></div></div>
<div class="pasos-ruta-panel">
<a href="torneos.php"><span>1</span><div><strong>Configura el torneo</strong><small>Disciplina, formato, fechas y cupos.</small></div></a>
<a href="participantes.php"><span>2</span><div><strong>Registra participantes</strong><small>Equipos, personas e inscripciones.</small></div></a>
<a href="sorteos.php"><span>3</span><div><strong>Genera el sorteo</strong><small>Revisa la vista previa antes de confirmar.</small></div></a>
<a href="partidos.php"><span>4</span><div><strong>Gestiona enfrentamientos</strong><small>Fechas, rondas y cruces.</small></div></a>
<a href="resultados.php"><span>5</span><div><strong>Carga resultados</strong><small>Registra marcadores oficiales.</small></div></a>
<a href="clasificacion.php"><span>6</span><div><strong>Consulta clasificación</strong><small>Comprueba posiciones y progreso.</small></div></a>
</div>
</section>

<?php if ($esAdministrador): ?>

<section class="tarjeta-ah bloque-solicitudes-panel">
  <div class="contenido-solicitudes-panel">
    <span class="icono-solicitudes-panel"><span data-icono="participantes" aria-hidden="true"></span></span>

    <div>
      <span>Solicitudes pendientes</span>

      <strong>
        <span data-cantidad-solicitudes>
          <?= $cantidadSolicitudesPendientes ?>
        </span>
        usuarios esperan aprobación
      </strong>

      <p>
        Revisa la identidad y asigna el rol correspondiente
        antes de habilitar el acceso.
      </p>
    </div>
  </div>

  <a
    class="boton boton-principal"
    href="configuracion.php#usuarios-configuracion"
  >
    Revisar solicitudes
  </a>
</section>

<?php endif; ?>

<section class="tarjeta-ah seccion-panel" aria-label="Siguientes acciones por torneo">
<div class="cabecera-bloque-panel"><div><h2>Qué necesita atención ahora</h2><p>La siguiente acción recomendada para avanzar cada competencia sin perder contexto.</p></div><a href="torneos.php">Ver torneos</a></div>
<div class="lista-prioridades-ah" id="panelPrioridadesTorneos"><div class="skeleton-ah skeleton-tarjeta-ah" aria-hidden="true"></div></div>
</section>

<div class="distribucion-panel">
<section class="tarjeta-ah seccion-panel acciones-panel">
<div class="cabecera-bloque-panel"><div><h2>Acciones rápidas</h2><p>Accesos frecuentes para administrar ArenaCJD.</p></div></div>
<div class="grid-acciones-rapidas">
<?php if ($esAdministrador): ?>
<a class="accion-rapida-panel" href="torneos.php#nuevo-torneo"><span><span class="icono-imagen-ui" data-icono="torneo" aria-hidden="true"></span></span><strong>Nuevo torneo</strong></a>
<?php else: ?>
<a class="accion-rapida-panel" href="torneos.php"><span><span class="icono-imagen-ui" data-icono="torneo" aria-hidden="true"></span></span><strong>Gestionar torneos</strong></a>
<?php endif; ?>
<?php if ($esAdministrador): ?><a class="accion-rapida-panel" href="disciplinas.php"><span><span class="icono-imagen-ui" data-icono="configuracion" aria-hidden="true"></span></span><strong>Gestionar disciplinas</strong></a><?php endif; ?>
<a class="accion-rapida-panel" href="participantes.php#registrar-participante"><span><span class="icono-imagen-ui" data-icono="participantes" aria-hidden="true"></span></span><strong>Inscripción individual</strong></a>
<a class="accion-rapida-panel" href="sorteos.php"><span><span class="icono-imagen-ui" data-icono="sorteo" aria-hidden="true"></span></span><strong>Generar sorteo</strong></a>
<a class="accion-rapida-panel" href="resultados.php"><span><span class="icono-imagen-ui" data-icono="resultados" aria-hidden="true"></span></span><strong>Ver resultados</strong></a>
<a class="accion-rapida-panel" href="calendario.php"><span><span class="icono-imagen-ui" data-icono="calendario" aria-hidden="true"></span></span><strong>Abrir calendario</strong></a>
</div>
</section>

<section class="tarjeta-ah seccion-panel jornada-panel">
<div class="cabecera-bloque-panel"><div><h2>Mi jornada</h2><p>Prioridades administrativas de hoy.</p></div><span class="badge-ah">Hoy</span></div>
<div class="lista-jornada-panel">
<div><span class="indicador-jornada indicador-morado"></span><div><strong id="panelHoyProgramados">0</strong><span>Enfrentamientos programados</span></div><a href="partidos.php">Ver</a></div>
<div><span class="indicador-jornada indicador-naranja"></span><div><strong id="panelHoyResultados">0</strong><span>Resultados registrados hoy</span></div><a href="resultados.php">Ver</a></div>
<div><span class="indicador-jornada indicador-verde"></span><div><strong id="panelComienzanHoy"><?= $resumenTorneosPanel['comienzan_hoy'] ?></strong><span>Torneos comienzan hoy</span></div><a href="torneos.php">Abrir</a></div>
</div>
</section>
</div>

<section class="tarjeta-ah seccion-panel proximos-panel">
<div class="cabecera-bloque-panel"><div><h2>Próximos enfrentamientos</h2><p>Agenda inmediata de las competiciones activas.</p></div><a href="partidos.php">Ver todos</a></div>
<div class="tabla-responsive-panel">
<table class="tabla-ah tabla-proximos-panel">
<thead><tr><th>Torneo</th><th>Disciplina</th><th>Ronda</th><th>Fecha</th><th>Hora</th><th>Acción</th></tr></thead>
<tbody id="panelProximosEnfrentamientos"><tr><td colspan="6"><strong>No hay enfrentamientos próximos programados.</strong></td></tr></tbody>
</table>
</div>
<a class="boton boton-claro" href="calendario.php">Ver calendario completo</a>
</section>

<div class="distribucion-panel distribucion-inferior-panel">
<section class="tarjeta-ah seccion-panel actividad-panel">
<div class="cabecera-bloque-panel"><div><h2>Actividad reciente</h2><p>Últimos movimientos realizados en el sistema.</p></div><a href="mi-actividad.php">Ver toda la actividad</a></div>
<ul class="lista-actividad-panel" id="panelActividadReciente">
<?php if ($torneosRecientesPanel): ?>
<?php foreach ($torneosRecientesPanel as $torneoRecientePanel): ?>
<li><span class="actividad-icono actividad-icono--torneo" aria-hidden="true"><span data-icono="torneo" aria-hidden="true"></span></span><div class="actividad-contenido-panel"><strong>Torneo registrado</strong><span><?= htmlspecialchars($torneoRecientePanel['nombre'], ENT_QUOTES, 'UTF-8') ?> · @<?= htmlspecialchars($torneoRecientePanel['organizador_usuario'], ENT_QUOTES, 'UTF-8') ?></span></div><time class="actividad-tiempo-panel" datetime="<?= htmlspecialchars(str_replace(' ', 'T', (string) $torneoRecientePanel['fecha_creacion']), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $torneoRecientePanel['fecha_creacion'])), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(formatearTiempoRelativoPanel((string) $torneoRecientePanel['fecha_creacion']), ENT_QUOTES, 'UTF-8') ?></time></li>
<?php endforeach; ?>
<?php else: ?>
<li><span class="actividad-icono actividad-icono--torneo" aria-hidden="true"><span data-icono="torneo" aria-hidden="true"></span></span><div class="actividad-contenido-panel"><strong>Sin actividad de torneos</strong><span>Todavía no hay torneos registrados.</span></div></li>
<?php endif; ?>
</ul>
</section>

<section class="tarjeta-ah seccion-panel estado-torneos-panel">
<div class="cabecera-bloque-panel"><div><h2>Estado de los torneos</h2><p>Distribución actual de las competiciones.</p></div></div>
<div class="resumen-estados-panel">
<div><span class="circulo-estado estado-activo"></span><div><strong id="panelEstadoActivos"><?= $resumenTorneosPanel['activos'] ?></strong><span>Activos</span></div><small id="panelPorcentajeActivos"><?= $porcentajeActivosPanel ?>%</small></div>
<div><span class="circulo-estado estado-proximo"></span><div><strong id="panelEstadoProximos"><?= $resumenTorneosPanel['proximos'] ?></strong><span>Próximos</span></div><small id="panelPorcentajeProximos"><?= $porcentajeProximosPanel ?>%</small></div>
<div><span class="circulo-estado estado-finalizado"></span><div><strong id="panelEstadoFinalizados"><?= $resumenTorneosPanel['finalizados'] ?></strong><span>Finalizados</span></div><small id="panelPorcentajeFinalizados"><?= $porcentajeFinalizadosPanel ?>%</small></div>
</div>
<div class="barra-estados-panel"><span class="barra-activos" id="panelBarraActivos" style="width:<?= $porcentajeActivosPanel ?>%"></span><span class="barra-proximos" id="panelBarraProximos" style="width:<?= $porcentajeProximosPanel ?>%"></span><span class="barra-finalizados" id="panelBarraFinalizados" style="width:<?= $porcentajeFinalizadosPanel ?>%"></span></div>
<a class="boton boton-claro boton-bloque" href="torneos.php">Administrar torneos</a>
</section>
</div>
<?php else: ?>
<section class="lo-proximo-participante-ah" id="panelLoProximoParticipante" hidden aria-live="polite"><div><span>Lo próximo</span><strong id="panelLoProximoTitulo">Revisando tu actividad…</strong><small id="panelLoProximoDetalle"></small></div><a class="boton boton-principal" id="panelLoProximoAccion" href="mi-actividad.php">Abrir</a></section>
<section class="grid-resumen-panel resumen-panel-participante" aria-label="Resumen personal">
<article class="tarjeta-ah tarjeta-resumen-panel"><span class="icono-resumen-panel"><span class="icono-imagen-ui" data-icono="torneo" aria-hidden="true"></span></span><div><span>Mis torneos activos</span><strong id="panelPersonalTorneos">0</strong><small>Inscripciones aprobadas en competencias activas</small></div></article>
<article class="tarjeta-ah tarjeta-resumen-panel"><span class="icono-resumen-panel"><span class="icono-imagen-ui" data-icono="enfrentamientos" aria-hidden="true"></span></span><div><span>Próximos enfrentamientos</span><strong id="panelPersonalProximos">0</strong><small>Partidos relacionados contigo</small></div></article>
<article class="tarjeta-ah tarjeta-resumen-panel"><span class="icono-resumen-panel"><span class="icono-imagen-ui" data-icono="participantes" aria-hidden="true"></span></span><div><span>Mis equipos</span><strong id="panelPersonalEquipos">0</strong><small>Equipos que administras o integras</small></div></article>
<article class="tarjeta-ah tarjeta-resumen-panel"><span class="icono-resumen-panel"><span data-icono="mensaje" aria-hidden="true"></span></span><div><span>Invitaciones pendientes</span><strong id="panelPersonalInvitaciones">0</strong><small>Solicitudes que requieren tu atención</small></div></article>
</section>

<section class="tarjeta-ah seccion-panel alertas-panel-participante" id="panelPersonalAlertasBloque" hidden>
<div class="cabecera-bloque-panel"><div><h2>Requiere tu atención</h2><p>Actividad relacionada directamente con tu cuenta.</p></div><span class="badge-ah" id="panelPersonalAlertasCantidad">0</span></div>
<div class="lista-alertas-panel-participante" id="panelPersonalAlertas"></div>
</section>

<div class="distribucion-panel panel-participante-distribucion">
<section class="tarjeta-ah seccion-panel acciones-panel">
<div class="cabecera-bloque-panel"><div><h2>Accesos rápidos</h2><p>Consulta y administra únicamente tu participación.</p></div></div>
<div class="grid-acciones-rapidas grid-acciones-participante">
<a class="accion-rapida-panel" href="torneos.php"><span><span class="icono-imagen-ui" data-icono="torneo" aria-hidden="true"></span></span><strong>Ver torneos</strong></a>
<a class="accion-rapida-panel" href="participantes.php"><span><span class="icono-imagen-ui" data-icono="participantes" aria-hidden="true"></span></span><strong>Mis inscripciones</strong></a>
<a class="accion-rapida-panel" href="partidos.php"><span><span class="icono-imagen-ui" data-icono="enfrentamientos" aria-hidden="true"></span></span><strong>Enfrentamientos</strong></a>
<a class="accion-rapida-panel" href="resultados.php"><span><span class="icono-imagen-ui" data-icono="resultados" aria-hidden="true"></span></span><strong>Resultados</strong></a>
<a class="accion-rapida-panel" href="clasificacion.php"><span><span class="icono-imagen-ui" data-icono="clasificacion" aria-hidden="true"></span></span><strong>Clasificación</strong></a>
<a class="accion-rapida-panel" href="calendario.php"><span><span class="icono-imagen-ui" data-icono="calendario" aria-hidden="true"></span></span><strong>Calendario</strong></a>
</div>
</section>

<section class="tarjeta-ah seccion-panel jornada-panel">
<div class="cabecera-bloque-panel"><div><h2>Mi participación</h2><p>Estado actual de tus inscripciones.</p></div><a href="mi-actividad.php">Ver actividad</a></div>
<div class="lista-jornada-panel" id="panelPersonalInscripciones"><div><span class="indicador-jornada indicador-morado"></span><div><strong>Sin inscripciones activas</strong><span>Tus participaciones aparecerán aquí.</span></div></div></div>
</section>
</div>

<section class="tarjeta-ah seccion-panel proximos-panel">
<div class="cabecera-bloque-panel"><div><h2>Mis próximos enfrentamientos</h2><p>Partidos individuales o de equipos en los que participas.</p></div><a href="partidos.php">Ver todos</a></div>
<div class="lista-proximos-panel-participante" id="panelPersonalListaProximos"><p class="mensaje-panel-personal">No tienes enfrentamientos próximos programados.</p></div>
<a class="boton boton-claro" href="calendario.php">Ver calendario completo</a>
</section>
<?php endif; ?>
</main>

</div>
</div>

<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/datos.js"></script>
<script src="js/componentes.js?v=20260910-confirmacion1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<?php if ($puedeGestionarPanel): ?>
<script src="js/panel.js?v=20260909-iconos4"></script>
<?php else: ?>
<script src="js/panel-participante.js?v=20260907-media-entidades2"></script>
<?php endif; ?>
<script src="js/tema.js?v=20260909-login-tema1"></script>
</body>
</html>
