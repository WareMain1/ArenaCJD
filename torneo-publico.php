<?php
require_once __DIR__ . '/publico/layout.php';
abrirPaginaPublica('Detalle del torneo', 'torneos', 'torneo');
?>
<section class="detalle-torneo-publico" id="detalleTorneoPublico"><p class="mensaje-sin-resultados">Cargando torneo...</p></section>
<section class="seccion-detalle-publico"><div class="cabecera-seccion-publica"><h2>Próximos enfrentamientos</h2><a href="enfrentamientos-publicos.php">Ver todos</a></div><div class="grid-consulta-publica" id="detalleEnfrentamientosPublicos"><p class="mensaje-sin-resultados">Cargando...</p></div></section>
<section class="seccion-detalle-publico"><div class="cabecera-seccion-publica"><h2>Resultados recientes</h2><a href="resultados-publicos.php">Ver todos</a></div><div class="grid-consulta-publica" id="detalleResultadosPublicos"><p class="mensaje-sin-resultados">Cargando...</p></div></section>
<section class="seccion-detalle-publico"><div class="cabecera-seccion-publica"><h2>Clasificación</h2><a id="enlaceClasificacionTorneoPublico" href="clasificacion-publica.php">Ver clasificación completa</a></div><div class="tabla-publica-contenedor" id="detalleClasificacionPublica"><p class="mensaje-sin-resultados">Cargando...</p></div></section>
<?php cerrarPaginaPublica(); ?>
