<?php
require_once __DIR__ . '/config/proteger.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi actividad - ArenaCJD</title>
<link rel="icon" type="image/png" href="imagenes/arena-cjd-isotipo.png">
<script src="js/tema-inicial.js"></script>
<script src="js/iconos.js?v=20260909-sprint11"></script>
<script>if(window.matchMedia('(min-width: 992px)').matches&&localStorage.getItem('menuColapsado')!=='true'){document.documentElement.classList.add('menu-pre-abierto');}</script>
<link rel="stylesheet" href="css/variables.css?v=20260909-audit12">
<link rel="stylesheet" href="css/layout.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/componentes.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/experiencia.css?v=20260910-guia1">
<link rel="stylesheet" href="css/paginas/mi-actividad.css?v=20260907-media-entidades2">
<link rel="stylesheet" href="css/utilidades.css?v=20260911-stableui1">
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
<span data-icono="menu" class="icono-menu-abrir" aria-hidden="true"></span><span data-icono="peligro" class="icono-menu-cerrar" aria-hidden="true"></span></button>
<div class="logo-app"><img src="imagenes/arena-cjd-logo-horizontal.png" alt="Logo de ArenaCJD"></div>
<div class="acciones-app"><button class="boton-notificaciones" id="botonNotificaciones" type="button" aria-label="Abrir notificaciones" aria-expanded="false" aria-controls="panelNotificaciones">
<span data-icono="notificaciones" aria-hidden="true"></span><span class="contador-notificaciones" id="contadorNotificaciones" hidden>0</span></button></div>
</header>
<main class="panel actividad-usuario-panel">
<header class="cabecera-pagina-actividad">
<div>
<h1 class="titulo-pagina titulo-modulo">Mi actividad</h1>
<p>Consulta tus equipos, inscripciones, invitaciones y próximos enfrentamientos.</p>
</div>
<span id="estadoMiActividad" class="estado-mi-actividad">Sincronizando...</span>
</header>

<div id="errorMiActividad" hidden></div>

<section class="alerta-mi-actividad" id="alertaMiActividad" hidden aria-live="polite">
<header class="cabecera-alerta-mi-actividad">
<div><span class="icono-alerta-mi-actividad" aria-hidden="true"><span data-icono="alerta" aria-hidden="true"></span></span><div><strong>Tienes actividad relacionada contigo</strong><p>Invitaciones, participaciones o enfrentamientos que requieren tu atención.</p></div></div>
<span class="contador-alerta-mi-actividad" id="cantidadAlertasMiActividad">0</span>
</header>
<div class="lista-alertas-mi-actividad" id="listaAlertasMiActividad"></div>
</section>

<section class="resumen-actividad-usuario" aria-label="Resumen de mi actividad">
<article class="tarjeta-ah tarjeta-resumen-actividad resumen-proximos">
<span class="icono-resumen-actividad" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path><path d="m9 16 2 2 4-5"></path></svg></span>
<div><strong id="resumenProximosActividad">0</strong><span>Próximos partidos</span><small id="resumenProximosDetalle">En los próximos días</small></div>
</article>
<article class="tarjeta-ah tarjeta-resumen-actividad resumen-equipos">
<span class="icono-resumen-actividad" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
<div><strong id="resumenEquiposActividad">0</strong><span>Equipos</span><small>A los que perteneces</small></div>
</article>
<article class="tarjeta-ah tarjeta-resumen-actividad resumen-inscripciones">
<span class="icono-resumen-actividad" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="8" cy="10" r="2"></circle><path d="M5.5 16c.8-2 4.2-2 5 0M14 9h4M14 13h4"></path></svg></span>
<div><strong id="resumenInscripcionesActividad">0</strong><span>Inscripciones</span><small>En torneos activos</small></div>
</article>
<article class="tarjeta-ah tarjeta-resumen-actividad resumen-invitaciones">
<span class="icono-resumen-actividad" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg></span>
<div><strong id="resumenInvitacionesActividad">0</strong><span>Invitaciones</span><small>Pendientes</small></div>
</article>
</section>

<section class="grid-actividad-usuario">
<article class="tarjeta-ah bloque-actividad-usuario actividad-doble">
<header class="cabecera-bloque-actividad">
<div class="identidad-bloque-actividad">
<span class="icono-bloque-actividad" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg></span>
<div><h2>Próximos enfrentamientos</h2><p>Partidos que te involucran directamente o mediante uno de tus equipos.</p></div>
</div>
<div class="acciones-bloque-actividad"><span class="badge-bloque-actividad" id="badgeProximosActividad">0</span><a href="partidos.php">Ver todos</a></div>
</header>
<div id="miActividadProximos" class="lista-actividad-usuario"><p class="mensaje-sin-resultados">Cargando...</p></div>
</article>

<article class="tarjeta-ah bloque-actividad-usuario">
<header class="cabecera-bloque-actividad">
<div class="identidad-bloque-actividad">
<span class="icono-bloque-actividad" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
<div><h2>Mis equipos</h2><p>Equipos que administras o integras.</p></div>
</div>
<div class="acciones-bloque-actividad"><span class="badge-bloque-actividad" id="badgeEquiposActividad">0</span><a href="participantes.php">Administrar</a></div>
</header>
<div id="miActividadEquipos" class="lista-actividad-usuario"><p class="mensaje-sin-resultados">Cargando...</p></div>
</article>

<article class="tarjeta-ah bloque-actividad-usuario">
<header class="cabecera-bloque-actividad">
<div class="identidad-bloque-actividad">
<span class="icono-bloque-actividad" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg></span>
<div><h2>Invitaciones</h2><p>Solicitudes de participación recibidas.</p></div>
</div>
<div class="acciones-bloque-actividad"><span class="badge-bloque-actividad" id="badgeInvitacionesActividad">0</span><a href="participantes.php#mis-invitaciones">Abrir</a></div>
</header>
<div id="miActividadInvitaciones" class="lista-actividad-usuario"><p class="mensaje-sin-resultados">Cargando...</p></div>
</article>

<article class="tarjeta-ah bloque-actividad-usuario">
<header class="cabecera-bloque-actividad">
<div class="identidad-bloque-actividad">
<span class="icono-bloque-actividad" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"></rect><path d="M9 4.5h6M8 9h8M8 13h8M8 17h5"></path></svg></span>
<div><h2>Torneos e inscripciones</h2><p>Estado de tus participaciones reales.</p></div>
</div>
<div class="acciones-bloque-actividad"><span class="badge-bloque-actividad" id="badgeInscripcionesActividad">0</span><a href="torneos.php">Ver torneos</a></div>
</header>
<div id="miActividadInscripciones" class="lista-actividad-usuario"><p class="mensaje-sin-resultados">Cargando...</p></div>
</article>

<article class="tarjeta-ah bloque-actividad-usuario">
<header class="cabecera-bloque-actividad">
<div class="identidad-bloque-actividad">
<span class="icono-bloque-actividad" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 4h8v5a4 4 0 0 1-8 0V4Z"></path><path d="M8 6H4v2a4 4 0 0 0 4 4M16 6h4v2a4 4 0 0 1-4 4M12 13v4M8 21h8M9 17h6"></path></svg></span>
<div><h2>Últimos resultados</h2><p>Resultados recientes en los que participaste.</p></div>
</div>
<div class="acciones-bloque-actividad"><span class="badge-bloque-actividad" id="badgeResultadosActividad">0</span><a href="resultados.php">Resultados</a></div>
</header>
<div id="miActividadResultados" class="lista-actividad-usuario"><p class="mensaje-sin-resultados">Cargando...</p></div>
</article>
</section>

<section class="tarjeta-ah informacion-actividad-usuario">
<div class="contenido-informacion-actividad">
<span class="icono-informacion-actividad" aria-hidden="true"><span data-icono="info" aria-hidden="true"></span></span>
<div><h2>Todo en un solo lugar</h2><p>Desde aquí puedes acceder rápidamente a tus partidos, equipos, invitaciones, torneos y resultados.</p></div>
</div>
<picture>
<source srcset="imagenes/ilustracion-trofeo-registro.webp" type="image/webp">
<img src="imagenes/ilustracion-trofeo-registro.png" alt="" aria-hidden="true">
</picture>
</section>
</main>
</div>
</div>
<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/componentes.js?v=20260913-sin-presencia1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<script src="js/mi-actividad.js?v=20260907-media-entidades2"></script>
<script src="js/tema.js?v=20260909-login-tema1"></script>
</body>
</html>
