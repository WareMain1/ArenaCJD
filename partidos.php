<?php
require_once __DIR__ . '/config/proteger.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Enfrentamientos - ArenaCJD</title>
<link rel="icon" type="image/png" href="imagenes/arena-cjd-isotipo.png">
<script src="js/tema-inicial.js"></script>
<script src="js/iconos.js?v=20260909-sprint11"></script>
<script>
if (window.matchMedia('(min-width: 992px)').matches && localStorage.getItem('menuColapsado') !== 'true') {
  document.documentElement.classList.add('menu-pre-abierto');
}
</script>

<link rel="stylesheet" href="css/variables.css?v=20260909-audit12">
<link rel="stylesheet" href="css/layout.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/componentes.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/experiencia.css?v=20260910-guia1">
<link rel="stylesheet" href="css/animaciones.css">
<link rel="stylesheet" href="css/paginas/enfrentamientos.css">
<link rel="stylesheet" href="css/utilidades.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/tema-claro.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/tema-oscuro.css?v=20260908-toast1">
<link rel="stylesheet" href="css/contraste-claro.css?v=20260909-audit12">
<link rel="stylesheet" href="css/formularios.css?v=20260910-mobile1">
</head>
<body class="cuerpo-app pagina-enfrentamientos" <?= atributosSesionArenaCJD() ?>>
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
<main class="panel panel-enfrentamientos">
<div class="cabecera-pagina-enfrentamientos">
<div class="titulo-enfrentamientos">
<div>
<h1 class="titulo-modulo">Enfrentamientos</h1>
<p>Consulta los enfrentamientos y su estado</p>
</div>
</div>
<button class="boton-exportar-enfrentamientos boton-exportar-ui" type="button">
<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14a2 2 0 0 0 2-2v-4"></path><path d="M3 15v4a2 2 0 0 0 2 2"></path></svg>
<span>Exportar</span>
<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"></path></svg>
</button>
</div>
<section class="barra-filtros-enfrentamientos" aria-label="Filtros de enfrentamientos">
<div class="filtro-enfrentamiento">
<label for="filtroTorneoEnfrentamientos">Torneo</label>
<select id="filtroTorneoEnfrentamientos" class="selector-formulario">
<option value="todos">Todos los torneos</option>
</select>
</div>
<div class="filtro-enfrentamiento">
<label for="filtroDisciplinaEnfrentamientos">Disciplina</label>
<select id="filtroDisciplinaEnfrentamientos" class="selector-formulario">
<option value="todas">Todas las disciplinas</option>
</select>
</div>
<div class="filtro-enfrentamiento">
<label for="filtroEstadoEnfrentamientos">Estado</label>
<select id="filtroEstadoEnfrentamientos" class="selector-formulario">
<option value="todos">Todos los estados</option>
<option value="pendiente">Pendiente</option>
<option value="programado">Programado</option>
<option value="en_curso">En curso</option>
<option value="finalizado">Finalizado</option>
<option value="cancelado">Cancelado</option>
</select>
</div>
<div class="filtro-enfrentamiento">
<label for="filtroRondaEnfrentamientos">Ronda</label>
<select id="filtroRondaEnfrentamientos" class="selector-formulario">
<option value="todas">Todas las rondas</option>
</select>
</div>
<div class="filtro-enfrentamiento">
<label for="filtroFechaEnfrentamientos">Fecha</label>
<input id="filtroFechaEnfrentamientos" class="campo-formulario" type="date">
</div>
</section>
<section class="lista-enfrentamientos" id="listaEnfrentamientos" aria-live="polite">
<div class="sin-enfrentamientos" id="sinEnfrentamientos">No hay enfrentamientos reales registrados todavía.</div>
</section>
<div class="pie-lista-enfrentamientos"><p id="contadorEnfrentamientos">Mostrando 0 enfrentamientos</p></div>
</main>
</div>
</div>
<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/datos.js?v=20260831-flow3"></script>
<script src="js/componentes.js?v=20260913-sin-presencia1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<script src="js/tema.js?v=20260909-login-tema1"></script>
<script src="js/enfrentamientos.js?v=20260909-iconos4"></script>
</body>
</html>
