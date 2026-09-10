<?php
require_once __DIR__ . '/publico/layout.php';
abrirPaginaPublica('Calendario público', 'calendario', 'calendario');
?>
<section class="cabecera-consulta-publica"><div><span class="etiqueta-portada">Agenda pública</span><h1>Calendario</h1><p>Consulta fechas de inicio, finalización y enfrentamientos programados de torneos publicados.</p></div></section>
<section class="barra-filtros-publica"><label>Buscar<input class="campo-formulario" id="buscarCalendarioPublico" type="search" placeholder="Torneo, disciplina o evento"></label><label>Mostrar<select class="selector-formulario" id="tipoCalendarioPublico"><option value="">Todos los eventos</option><option value="enfrentamiento">Enfrentamientos</option><option value="inicio_torneo">Inicio de torneo</option><option value="fin_torneo">Finalización de torneo</option></select></label></section>
<div class="estado-filtros-publicos"><span id="resumenFiltrosPublicos" aria-live="polite">Sin filtros activos</span><button class="boton boton-claro boton-limpiar-publico" id="limpiarFiltrosPublicos" type="button" hidden>Limpiar filtros</button></div>
<section class="lista-calendario-publica" id="listaCalendarioPublico"><p class="mensaje-sin-resultados">Cargando calendario...</p></section>
<?php cerrarPaginaPublica(); ?>
