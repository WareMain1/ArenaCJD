<?php
require_once __DIR__ . '/config/proteger.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calendario - ArenaCJD</title>
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
<link rel="stylesheet" href="css/paginas/calendario.css?v=20260909-audit12">
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
<main class="panel panel-calendario">
<section class="cabecera-calendario">
<div>
<p class="etiqueta-modulo">Programación de competencias</p>
<h1 class="titulo-pagina titulo-modulo">Calendario</h1>
<p class="descripcion-pagina">Consulta las fechas, horarios y ubicaciones de los enfrentamientos programados.</p>
</div>
<button class="boton boton-principal boton-exportar-calendario boton-exportar-ui" id="exportarCalendario" type="button">Exportar calendario</button>
</section>

<section class="barra-filtros-calendario" aria-label="Filtros del calendario">
<div class="grupo-filtro-calendario">
<label for="filtroTorneoCalendario">Torneo</label>
<select id="filtroTorneoCalendario" class="selector-formulario">
<option value="todos">Todos los torneos</option>
</select>
</div>
<div class="grupo-filtro-calendario">
<label for="filtroDisciplinaCalendario">Disciplina</label>
<select id="filtroDisciplinaCalendario" class="selector-formulario"><option value="todas">Todas las disciplinas</option></select>
</div>
<div class="grupo-filtro-calendario">
<label for="filtroEstadoCalendario">Estado</label>
<select id="filtroEstadoCalendario" class="selector-formulario"><option value="todos">Todos los estados</option></select>
</div>
<div class="grupo-filtro-calendario">
<label for="filtroDesdeCalendario">Fecha desde</label>
<input id="filtroDesdeCalendario" class="campo-formulario" type="date">
</div>
<div class="grupo-filtro-calendario">
<label for="filtroHastaCalendario">Fecha hasta</label>
<input id="filtroHastaCalendario" class="campo-formulario" type="date">
</div>
<div class="acciones-filtros-calendario">
<button class="boton boton-principal boton-bloque" id="aplicarFiltrosCalendario" type="button">Aplicar filtros</button>
<button class="boton boton-claro boton-bloque" id="limpiarFiltrosCalendario" type="button">Limpiar</button>
</div>
</section>

<section class="resumen-calendario" id="resumenCalendario" aria-label="Resumen del calendario"></section>

<section class="contenido-calendario">
<div class="columna-calendario">
<article class="tarjeta-calendario tarjeta-vista-calendario">
<div class="cabecera-vista-calendario">
<div class="selector-vista-calendario" role="group" aria-label="Tipo de vista">
<button class="boton-vista-calendario activo" id="verMesCalendario" type="button">Vista mensual</button>
<button class="boton-vista-calendario" id="verListaCalendario" type="button">Vista de lista</button>
</div>
<div class="navegacion-mes-calendario">
<button class="boton-navegacion-mes" id="mesAnteriorCalendario" type="button" aria-label="Mes anterior"><span data-icono="izquierda" aria-hidden="true"></span></button>
<strong id="tituloMesCalendario">Julio 2026</strong>
<button class="boton-navegacion-mes" id="mesSiguienteCalendario" type="button" aria-label="Mes siguiente"><span data-icono="derecha" aria-hidden="true"></span></button>
<button class="boton-hoy-calendario" id="irHoyCalendario" type="button">Hoy</button>
</div>
</div>
<div class="vista-mes-calendario" id="vistaMesCalendario">
<div class="dias-semana-calendario"><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span></div>
<div class="cuadricula-mes-calendario" id="cuadriculaMesCalendario"></div>
</div>
<div class="vista-lista-calendario" id="vistaListaCalendario" hidden></div>
</article>

<article class="tarjeta-calendario eventos-destacados-calendario">
<div class="cabecera-seccion-calendario">
<div><h2>Eventos destacados</h2><p>Torneos y enfrentamientos programados en el sistema.</p></div>
</div>
<div class="lista-eventos-destacados" id="eventosDestacadosCalendario"></div>
</article>
</div>

<aside class="tarjeta-calendario proximos-calendario">
<div class="cabecera-seccion-calendario">
<div><h2>Próximos eventos</h2><p id="contadorProximosCalendario">0 programados</p></div>
<button class="boton-enlace-calendario" id="mostrarTodosCalendario" type="button">Ver todos</button>
</div>
<div class="lista-proximos-calendario" id="listaProximosCalendario"></div>
<div class="mensaje-calendario-vacio" id="mensajeCalendarioVacio" hidden>No hay eventos para los filtros seleccionados.</div>
<div class="paginacion-calendario" id="paginacionCalendario"></div>
</aside>
</section>
</main>
</div>
</div>
<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/componentes.js?v=20260913-sin-presencia1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<script src="js/calendario.js?v=20260909-iconos4"></script>
<script src="js/tema.js?v=20260909-login-tema1"></script>
</body>
</html>
