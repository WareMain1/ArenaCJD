<?php
require_once __DIR__ . '/publico/layout.php';
abrirPaginaPublica('Torneos públicos', 'torneos', 'torneos');
?>
<section class="cabecera-consulta-publica"><div><span class="etiqueta-portada">Consulta pública</span><h1>Torneos publicados</h1><p>Consulta competencias que sus organizadores decidieron hacer visibles. No necesitas una cuenta para ver esta información.</p></div></section>
<section class="barra-filtros-publica"><label>Buscar<input class="campo-formulario" id="buscarTorneoPublico" type="search" placeholder="Nombre o disciplina"></label><label>Estado<select class="selector-formulario" id="estadoTorneoPublico"><option value="">Todos</option><option value="inscripciones">Inscripciones</option><option value="en_curso">En curso</option><option value="finalizado">Finalizado</option></select></label><label>Disciplina<select class="selector-formulario" id="disciplinaTorneoPublico"><option value="">Todas</option></select></label></section>
<div class="estado-filtros-publicos"><span id="resumenFiltrosPublicos" aria-live="polite">Sin filtros activos</span><button class="boton boton-claro boton-limpiar-publico" id="limpiarFiltrosPublicos" type="button" hidden>Limpiar filtros</button></div>
<section class="grid-consulta-publica" id="listaTorneosPublicos"><p class="mensaje-sin-resultados">Cargando torneos públicos...</p></section>
<?php cerrarPaginaPublica(); ?>
