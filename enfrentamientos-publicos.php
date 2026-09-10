<?php
require_once __DIR__ . '/publico/layout.php';
abrirPaginaPublica('Enfrentamientos públicos', 'enfrentamientos', 'enfrentamientos');
?>
<section class="cabecera-consulta-publica"><div><span class="etiqueta-portada">Solo lectura</span><h1>Enfrentamientos</h1><p>Revisa cruces, rondas y horarios de los torneos publicados.</p></div></section>
<section class="barra-filtros-publica"><label>Torneo<select class="selector-formulario" id="torneoEnfrentamientoPublico"><option value="">Todos los torneos</option></select></label><label>Estado<select class="selector-formulario" id="estadoEnfrentamientoPublico"><option value="">Todos</option><option value="pendiente">Pendiente</option><option value="programado">Programado</option><option value="en_curso">En curso</option><option value="finalizado">Finalizado</option></select></label></section>
<div class="estado-filtros-publicos"><span id="resumenFiltrosPublicos" aria-live="polite">Sin filtros activos</span><button class="boton boton-claro boton-limpiar-publico" id="limpiarFiltrosPublicos" type="button" hidden>Limpiar filtros</button></div>
<section class="grid-consulta-publica" id="listaEnfrentamientosPublicos"><p class="mensaje-sin-resultados">Cargando enfrentamientos...</p></section>
<?php cerrarPaginaPublica(); ?>
