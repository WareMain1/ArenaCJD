<?php
require_once __DIR__ . '/publico/layout.php';
abrirPaginaPublica('Clasificación pública', 'clasificacion', 'clasificacion');
?>
<section class="cabecera-consulta-publica"><div><span class="etiqueta-portada">Posiciones</span><h1>Clasificación</h1><p>Selecciona un torneo publicado para consultar su tabla de posiciones.</p></div></section>
<section class="barra-filtros-publica"><label>Torneo<select class="selector-formulario selector-torneo-clasificacion-publica" id="torneoClasificacionPublica"><option value="">Seleccionar torneo</option></select></label></section>
<section id="resumenClasificacionPublica" class="resumen-clasificacion-publica" hidden></section>
<section class="tabla-publica-contenedor" id="contenedorClasificacionPublica"><p class="mensaje-sin-resultados">Selecciona un torneo para ver la clasificación.</p></section>
<?php cerrarPaginaPublica(); ?>
