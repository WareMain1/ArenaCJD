<?php
require_once __DIR__ . '/config/proteger.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resultados - ArenaCJD</title>
<link rel="icon" type="image/png" href="imagenes/arena-cjd-isotipo.png">
<script src="js/tema-inicial.js"></script>
<script src="js/iconos.js?v=20260909-sprint11"></script>
<script>
if (window.matchMedia('(min-width: 992px)').matches && localStorage.getItem('menuColapsado') !== 'true') {
  document.documentElement.classList.add('menu-pre-abierto');
}
</script>

<link rel="stylesheet" href="css/variables.css?v=20260909-audit12">
<link rel="stylesheet" href="css/layout.css?v=20260909-sprint13">
<link rel="stylesheet" href="css/componentes.css?v=20260910-visibilidad1">
<link rel="stylesheet" href="css/experiencia.css?v=20260910-guia1">
<link rel="stylesheet" href="css/animaciones.css">
<link rel="stylesheet" href="css/paginas/resultados.css">
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
<main class="panel panel-resultados">
<div class="cabecera-pagina-resultados">
<div class="identidad-pagina-resultados">
<div><h1 class="titulo-pagina titulo-modulo">Resultados</h1><p>Registra, valida y consulta los resultados de los enfrentamientos.</p></div>
</div>
<button class="boton boton-claro boton-exportar-resultados boton-exportar-ui" type="button">Exportar <span aria-hidden="true"><span data-icono="abajo" aria-hidden="true"></span></span></button>
</div>
<section class="barra-filtros-resultados" aria-label="Filtros de resultados">
<div class="grupo-filtro-resultados grupo-busqueda-resultados"><label for="buscarResultado">Buscar</label><div class="campo-con-icono"><span aria-hidden="true"><span data-icono="buscar" aria-hidden="true"></span></span><input id="buscarResultado" type="search" placeholder="Buscar equipo o participante"></div></div>
<div class="grupo-filtro-resultados"><label for="filtroTorneoResultado">Torneo</label><select id="filtroTorneoResultado">
<option value="todos">Todos los torneos</option>
</select></div>
<div class="grupo-filtro-resultados"><label for="filtroDisciplinaResultado">Disciplina</label><select id="filtroDisciplinaResultado"><option value="todas">Todas las disciplinas</option></select></div>
<div class="grupo-filtro-resultados"><label for="filtroEstadoResultado">Estado</label><select id="filtroEstadoResultado"><option value="todos">Todos los estados</option><option value="pendiente">Pendientes</option><option value="confirmado">Confirmados</option><option value="cancelado">Cancelados</option></select></div>
<div class="grupo-filtro-resultados"><label for="filtroRondaResultado">Ronda</label><select id="filtroRondaResultado"><option value="todas">Todas las rondas</option></select></div>
<div class="grupo-filtro-resultados"><label for="filtroFechaResultado">Fecha</label><input id="filtroFechaResultado" type="date"></div>
</section>
<section class="lista-resultados" id="listaResultados" aria-label="Listado de resultados"></section>
<p class="mensaje-sin-resultados" id="mensajeSinResultados">No hay enfrentamientos disponibles para registrar o consultar resultados.</p>
<div class="pie-listado-resultados"><span id="contadorResultados">Mostrando 0 resultados</span></div>
</main>
</div>
</div>
<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/datos.js?v=20260831-flow3"></script>
<script src="js/componentes.js?v=20260910-confirmacion1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<script src="js/tema.js?v=20260909-login-tema1"></script>
<script src="js/resultados.js?v=20260909-iconos4"></script>
</body>
</html>
