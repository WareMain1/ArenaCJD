<?php
require_once __DIR__ . '/config/protegerRol.php';
exigirUnoDeLosRoles(['administrador']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Disciplinas - ArenaCJD</title>
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
<link rel="stylesheet" href="css/paginas/disciplinas.css?v=20260913-alineacion1">
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
<button class="boton-menu" id="botonMenu" type="button" aria-label="Abrir menú" aria-expanded="false">
<span data-icono="menu" class="icono-menu-abrir" aria-hidden="true"></span><span data-icono="peligro" class="icono-menu-cerrar" aria-hidden="true"></span></button>
<div class="logo-app"><img src="imagenes/arena-cjd-logo-horizontal.png" alt="Logo de ArenaCJD"></div>
<div class="acciones-app">
<button class="boton-notificaciones" id="botonNotificaciones" type="button" aria-label="Abrir notificaciones" aria-expanded="false" aria-controls="panelNotificaciones">
<span data-icono="notificaciones" aria-hidden="true"></span><span class="contador-notificaciones" id="contadorNotificaciones" hidden>0</span>
</button>
</div>
</header>
<main class="panel panel-disciplinas">
<header class="cabecera-disciplinas">
<div><p class="miga-pagina">Catálogos / Disciplinas</p><h1 class="titulo-pagina titulo-modulo">Disciplinas</h1><p class="subtitulo-pagina">Crea o edita las disciplinas disponibles para los torneos sin modificar la estructura de la base de datos.</p></div>
<div class="acciones-cabecera-disciplinas"><button class="boton boton-claro" id="abrirGestionCategorias" type="button">Categorías</button><button class="boton boton-principal" id="crearDisciplina" type="button"><span data-icono="agregar" aria-hidden="true"></span> Nueva disciplina</button></div>
</header>

<section class="grid-resumen-disciplinas" aria-label="Resumen de disciplinas">
<article class="tarjeta-ah resumen-disciplina"><span data-icono="torneo" aria-hidden="true"></span><div><small>Total</small><strong id="totalDisciplinas">0</strong></div></article>
<article class="tarjeta-ah resumen-disciplina"><span data-icono="exito" aria-hidden="true"></span><div><small>Activas</small><strong id="disciplinasActivas">0</strong></div></article>
<article class="tarjeta-ah resumen-disciplina"><span><span data-icono="pausa" aria-hidden="true"></span></span><div><small>Inactivas</small><strong id="disciplinasInactivas">0</strong></div></article>
</section>

<section class="tarjeta-ah bloque-disciplinas">
<header class="cabecera-bloque-disciplinas"><div><h2>Catálogo de disciplinas</h2><p>Las disciplinas activas aparecen en la creación y edición de torneos.</p></div><span id="sincronizacionDisciplinas">Actualizando...</span></header>
<div class="barra-disciplinas"><label class="busqueda-disciplinas"><span aria-hidden="true"><span data-icono="buscar" aria-hidden="true"></span></span><input id="buscarDisciplina" type="search" placeholder="Buscar disciplina" autocomplete="off" aria-label="Buscar disciplina"></label><select id="filtroEstadoDisciplina" class="selector-formulario" aria-label="Filtrar disciplinas por estado"><option value="todas">Todos los estados</option><option value="activa">Activas</option><option value="inactiva">Inactivas</option></select><button class="boton boton-claro" id="actualizarDisciplinas" type="button">Actualizar</button></div><div class="resumen-filtros-ah" id="resumenFiltrosDisciplinas" hidden></div>
<div class="contenedor-tabla-disciplinas">
<table class="tabla-ah tabla-disciplinas"><thead><tr><th>Disciplina</th><th>Estado</th><th>Torneos</th><th>Categorías asociadas</th><th>Tipos asociados</th><th>Acciones</th></tr></thead><tbody id="cuerpoDisciplinas"></tbody></table>
</div>
<div class="estado-vacio-ah" id="sinDisciplinas" hidden><strong id="tituloSinDisciplinas">No hay disciplinas todavía</strong><p id="detalleSinDisciplinas">Crea una disciplina para empezar a configurar los torneos.</p><button class="boton boton-principal" id="accionSinDisciplinas" type="button">Crear disciplina</button></div>
</section>
</main>
</div>
</div>

<div class="modal-torneo" id="modalDisciplina" aria-hidden="true">
<div class="modal-torneo-fondo" data-cerrar-disciplina></div>
<section class="modal-torneo-contenido modal-disciplina-contenido" role="dialog" aria-modal="true" aria-labelledby="tituloModalDisciplina">
<header class="modal-torneo-cabecera"><div><span class="modal-torneo-etiqueta">Catálogo</span><h2 id="tituloModalDisciplina">Nueva disciplina</h2><p id="descripcionModalDisciplina">La disciplina quedará disponible para los torneos.</p></div><button class="modal-torneo-cerrar" type="button" data-cerrar-disciplina aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button></header>
<form class="formulario-disciplina" id="formularioDisciplina">
<input type="hidden" id="disciplinaId">
<div class="grupo-formulario"><label for="disciplinaNombre">Nombre</label><input class="campo-formulario" id="disciplinaNombre" type="text" minlength="2" maxlength="80" required autocomplete="off" placeholder="Ejemplo: Handball"></div>
<div class="grupo-formulario"><label for="disciplinaEstado">Estado</label><select class="selector-formulario" id="disciplinaEstado" required><option value="activa">Activa</option><option value="inactiva">Inactiva</option></select><small>Una disciplina inactiva deja de aparecer para torneos nuevos, pero conserva sus torneos existentes.</small></div>
<div class="grupo-formulario grupo-formulario-completo"><div class="cabecera-asociacion-disciplina"><label>Categorías permitidas</label><button class="boton-catalogo-secundario" id="abrirCatalogoCategorias" type="button">Administrar catálogo</button></div><div id="disciplinaCategorias" class="opciones-asociacion-disciplina"></div><small>Selecciona únicamente las categorías que se podrán usar con esta disciplina. La eliminación global de categorías se gestiona por separado.</small></div>
<div class="grupo-formulario grupo-formulario-completo"><label>Tipos de torneo permitidos</label><div id="disciplinaTipos" class="opciones-asociacion-disciplina"></div><small>Selecciona los formatos que estarán disponibles al configurar torneos de esta disciplina.</small></div>
<p class="mensaje-edicion-torneo" id="mensajeDisciplina" aria-live="polite"></p>
<footer class="acciones-modal-torneo"><button class="boton boton-claro" type="button" data-cerrar-disciplina>Cancelar</button><button class="boton boton-principal" id="guardarDisciplina" type="submit">Crear disciplina</button></footer>
</form>
</section>
</div>

<div class="modal-confirmacion-catalogo modal-catalogo-categorias" id="modalCatalogoCategorias" aria-hidden="true">
<div class="modal-confirmacion-fondo" data-cerrar-catalogo-categorias></div>
<section class="modal-confirmacion-contenido contenido-catalogo-categorias" role="dialog" aria-modal="true" aria-labelledby="tituloCatalogoCategorias">
<div class="cabecera-catalogo-categorias"><div><span class="modal-torneo-etiqueta">Catálogo global</span><h3 id="tituloCatalogoCategorias">Categorías de ArenaCJD</h3><p>Gestiona nombres y consulta su uso. Las asociaciones se configuran al editar cada disciplina. Renombrar una categoría también actualiza su nombre en los torneos existentes.</p></div><button class="cerrar-catalogo-categorias" type="button" data-cerrar-catalogo-categorias aria-label="Cerrar catálogo"><span data-icono="peligro" aria-hidden="true"></span></button></div>
<form id="formularioCategoria" class="formulario-categoria">
<input type="hidden" id="categoriaId">
<div class="grupo-formulario"><label for="categoriaNombre" id="etiquetaNombreCategoria">Nueva categoría</label><input class="campo-formulario" id="categoriaNombre" type="text" minlength="2" maxlength="50" required autocomplete="off" placeholder="Nombre de la categoría"></div>
<div class="acciones-editor-categoria"><button class="boton boton-principal" id="guardarCategoria" type="submit">Crear categoría</button><button class="boton boton-claro" id="cancelarEdicionCategoria" type="button" hidden>Cancelar edición</button></div>
<p id="mensajeCategoria" role="status" aria-live="polite"></p>
</form>
<div class="lista-catalogo-categorias" id="listaCatalogoCategorias"></div>
<div class="acciones-confirmacion-catalogo"><button class="boton boton-claro" type="button" data-cerrar-catalogo-categorias>Cerrar</button></div>
</section>
</div>

<div class="modal-confirmacion-catalogo" id="modalConfirmacionCatalogo" aria-hidden="true">
<div class="modal-confirmacion-fondo" data-cancelar-confirmacion></div>
<section class="modal-confirmacion-contenido" role="alertdialog" aria-modal="true" aria-labelledby="tituloConfirmacionCatalogo" aria-describedby="mensajeConfirmacionCatalogo">
<div class="modal-confirmacion-icono" id="iconoConfirmacionCatalogo" aria-hidden="true"><span data-icono="alerta" aria-hidden="true"></span></div>
<h3 id="tituloConfirmacionCatalogo">Confirmar acción</h3>
<p id="mensajeConfirmacionCatalogo"></p>
<div class="acciones-confirmacion-catalogo">
<button class="boton boton-claro" id="cancelarConfirmacionCatalogo" type="button" data-cancelar-confirmacion>Cancelar</button>
<button class="boton boton-principal" id="aceptarConfirmacionCatalogo" type="button">Confirmar</button>
</div>
</section>
</div>
<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/componentes.js?v=20260913-sin-presencia1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<script src="js/tema.js?v=20260909-login-tema1"></script>
<script src="js/disciplinas.js?v=20260909-categorias2"></script>
</body>
</html>
