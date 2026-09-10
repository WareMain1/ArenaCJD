<?php
require_once __DIR__ . '/publico/layout.php';
abrirPaginaPublica('Resultados públicos', 'resultados', 'resultados');
?>
<section class="cabecera-consulta-publica"><div><span class="etiqueta-portada">Resultados oficiales</span><h1>Resultados</h1><p>Consulta marcadores finalizados de torneos publicados.</p></div></section>
<section class="barra-filtros-publica"><label>Torneo<select class="selector-formulario" id="torneoResultadoPublico"><option value="">Todos los torneos</option></select></label><label>Buscar<input class="campo-formulario" id="buscarResultadoPublico" type="search" placeholder="Participante o torneo"></label></section>
<div class="estado-filtros-publicos"><span id="resumenFiltrosPublicos" aria-live="polite">Sin filtros activos</span><button class="boton boton-claro boton-limpiar-publico" id="limpiarFiltrosPublicos" type="button" hidden>Limpiar filtros</button></div>
<section class="grid-consulta-publica" id="listaResultadosPublicos"><p class="mensaje-sin-resultados">Cargando resultados...</p></section>
<?php cerrarPaginaPublica(); ?>
