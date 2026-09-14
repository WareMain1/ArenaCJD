<?php
require_once __DIR__ . '/config/proteger.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Clasificación - ArenaCJD</title>
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
<link rel="stylesheet" href="css/paginas/clasificacion.css?v=20260908-ux4">
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
<main class="panel panel-clasificacion">
<section class="cabecera-clasificacion">
<div>
<p class="etiqueta-modulo">Seguimiento del torneo</p>
<h1 class="titulo-pagina titulo-modulo">Clasificación</h1>
<p class="descripcion-pagina">Consulta el avance, el rendimiento y la ronda alcanzada por cada participante.</p>
</div>
<button class="boton boton-principal boton-exportar-clasificacion boton-exportar-ui" type="button" disabled aria-disabled="true">Exportar clasificación</button>
</section>
<section class="barra-filtros-clasificacion" aria-label="Filtros de clasificación">
<div class="grupo-filtro-clasificacion grupo-filtro-amplio">
<label for="filtroTorneoClasificacion">Torneo</label>
<select id="filtroTorneoClasificacion" class="selector-formulario">
<option value="">Seleccionar torneo</option>
</select>
</div>
<div class="grupo-filtro-clasificacion"><label for="filtroDisciplinaClasificacion">Disciplina</label><select id="filtroDisciplinaClasificacion" class="selector-formulario" disabled><option id="opcionDisciplinaClasificacion">Sin torneo seleccionado</option></select></div>
<div class="grupo-filtro-clasificacion"><label for="filtroEstadoClasificacion">Estado</label><select id="filtroEstadoClasificacion" class="selector-formulario"><option value="todos">Todos</option><option value="clasificado">Clasificados</option><option value="eliminado">Eliminados</option></select></div>
<div class="grupo-filtro-clasificacion"><label for="filtroRondaClasificacion">Ronda</label><select id="filtroRondaClasificacion" class="selector-formulario"><option value="todas">Todas</option></select></div>
</section>
<section class="resumen-clasificacion" id="resumenClasificacion"></section>
<section class="estadisticas-disciplina">
<div class="encabezado-seccion-clasificacion"><div><h2>Estadísticas generales</h2><div class="contexto-estadisticas-clasificacion"><p id="descripcionEstadisticas">Selecciona un torneo real.</p><span class="insignia-disciplina" id="insigniaDisciplina">-</span></div></div></div>
<div class="grid-estadisticas-disciplina" id="estadisticasDisciplina"></div>
</section>
<section class="contenido-clasificacion">
<article class="tarjeta tabla-clasificacion-tarjeta">
<div class="encabezado-tabla-clasificacion"><div><h2>Clasificación de participantes</h2><p id="contadorClasificacion">0 participantes</p></div><div class="busqueda-clasificacion"><label class="sr-only" for="buscarClasificacion">Buscar participante</label><input id="buscarClasificacion" type="search" placeholder="Buscar participante" disabled></div></div>
<div class="contenedor-tabla-clasificacion">
<table class="tabla-clasificacion">
<thead><tr id="encabezadoTablaClasificacion"></tr></thead>
<tbody id="cuerpoTablaClasificacion"></tbody>
</table>
</div>
<div class="mensaje-sin-clasificacion" id="sinClasificacion">Selecciona un torneo para consultar su clasificación actualizada.</div>
</article>
<aside class="tarjeta progreso-torneo">
<div class="encabezado-progreso"><div><h2>Resultados completados</h2><p id="textoRondaActual">Ronda actual: Sin datos</p></div><span class="porcentaje-progreso" id="porcentajeProgreso">0%</span></div>
<div class="barra-progreso-torneo"><span id="barraProgresoTorneo"></span></div>
<div class="lista-rondas" id="listaRondas"></div>
<div class="campeon-torneo" id="campeonTorneo"><span class="icono-campeon"><span data-icono="torneo" aria-hidden="true"></span></span><div><span>Campeón</span><strong>Por definir</strong></div></div>
</aside>
</section>
</main>
</div>
</div>
<div class="fondo-modal-clasificacion" id="fondoModalClasificacion" hidden>
<section class="modal-participante-clasificacion" role="dialog" aria-modal="true" aria-labelledby="tituloModalClasificacion">
<button class="boton-cerrar-clasificacion" id="cerrarModalClasificacion" type="button" aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button>
<div class="cabecera-modal-clasificacion"><div class="avatar-modal-clasificacion" id="avatarModalClasificacion">-</div><div><span class="estado-modal-clasificacion" id="estadoModalClasificacion">Sin datos</span><h2 id="tituloModalClasificacion">Participante</h2><p id="subtituloModalClasificacion">Sin clasificación real</p></div></div>
<div class="datos-modal-clasificacion" id="datosModalClasificacion"></div>
<div class="ultimos-resultados-clasificacion"><h3>Últimos enfrentamientos</h3><div id="ultimosResultadosClasificacion"></div></div>
<button class="boton boton-principal boton-bloque" id="verEnfrentamientosParticipante" type="button">Ver enfrentamientos</button>
</section>
</div>
<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/datos.js?v=20260906-clasificacion-cancelados1"></script>
<script src="js/componentes.js?v=20260913-sin-presencia1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<script src="js/clasificacion.js?v=20260909-iconos4"></script>
<script src="js/tema.js?v=20260909-login-tema1"></script>
</body>
</html>
