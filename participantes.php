<?php
require_once __DIR__ . '/config/proteger.php';
$rolesParticipantesPagina = $_SESSION['roles'] ?? [];
$esGestorParticipantesPagina = in_array('administrador', $rolesParticipantesPagina, true)
    || in_array('organizador', $rolesParticipantesPagina, true);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Participantes - ArenaCJD</title>
<link rel="icon" type="image/png" href="imagenes/arena-cjd-isotipo.png">
<script src="js/tema-inicial.js"></script>
<script src="js/iconos.js?v=20260909-sprint11"></script>
<script>
  if (window.matchMedia('(min-width: 992px)').matches &&
      localStorage.getItem('menuColapsado') !== 'true') {
    document.documentElement.classList.add('menu-pre-abierto');
  }
</script>

<link rel="stylesheet" href="css/variables.css?v=20260909-audit12">
<link rel="stylesheet" href="css/layout.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/componentes.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/experiencia.css?v=20260910-guia1">
<link rel="stylesheet" href="css/animaciones.css">
<link rel="stylesheet" href="css/paginas/participantes.css?v=20260910-mobile1">
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

<main class="panel participantes-panel">
<header class="cabecera-pagina-participantes">
<div class="cabecera-pagina-participantes-texto">
<h1 class="titulo-pagina titulo-modulo">Participantes</h1>
<p class="subtitulo-participantes"><?= $esGestorParticipantesPagina
    ? 'Administra equipos, participantes individuales y sus inscripciones.'
    : 'Gestiona tus equipos, tus inscripciones individuales y tus invitaciones.' ?></p>
</div>
<div class="acciones-cabecera-participantes">
<button class="boton boton-principal boton-crear-equipo-cabecera" id="abrirCrearEquipo" type="button">Crear equipo</button>
</div>
</header>

<section class="resumen-participantes" aria-label="Resumen de participantes">
<article class="resumen-participante">
<span class="resumen-icono resumen-icono-morado"><span data-icono="participantes" aria-hidden="true"></span></span>
<div><span class="resumen-etiqueta">Total registrados</span><strong id="resumenTotalParticipantes">0</strong></div>
</article>
<article class="resumen-participante">
<span class="resumen-icono resumen-icono-verde"><span data-icono="exito" aria-hidden="true"></span></span>
<div><span class="resumen-etiqueta">Aprobados</span><strong id="resumenAprobadosParticipantes">0</strong></div>
</article>
<article class="resumen-participante">
<span class="resumen-icono resumen-icono-naranja"><span data-icono="reloj" aria-hidden="true"></span></span>
<div><span class="resumen-etiqueta">Pendientes</span><strong id="resumenPendientesParticipantes">0</strong></div>
</article>
</section>

<section class="guia-participantes-ah" aria-label="Cómo funciona la gestión de participantes">
<article><strong>Equipo permanente</strong><span>Define el nombre, responsable e integrantes una sola vez. Luego puedes reutilizarlo.</span></article>
<span class="guia-participantes-flecha" aria-hidden="true"><span data-icono="derecha" aria-hidden="true"></span></span>
<article><strong>Inscripción al torneo</strong><span>Elige un equipo existente o una persona y solicita su participación en una competencia concreta.</span></article>
<span class="guia-participantes-flecha" aria-hidden="true"><span data-icono="derecha" aria-hidden="true"></span></span>
<article><strong>Seguimiento</strong><span>Consulta aprobación, invitaciones y estado competitivo sin alterar el historial anterior.</span></article>
</section>

<section class="estado-competencias-participantes" aria-label="Estado competitivo de los torneos">
<header><div><h2>Estado competitivo</h2><p>Avance en tiempo real según inscripciones, sorteos, enfrentamientos y resultados.</p></div><span id="estadoCompetenciasSync">Sincronizando...</span></header>
<div class="grid-estado-competencias" id="estadoCompetenciasParticipantes"><p class="mensaje-sin-resultados">Cargando estado de los torneos...</p></div>
</section>

<section class="tarjeta controles-participantes">
<div class="pestanas-participantes" role="tablist" aria-label="Tipo de participante">
<button class="pestana-participante activa" type="button" role="tab" aria-selected="true" data-tipo-participante="equipos">Equipos <span id="contadorEquiposPestana">0</span></button>
<button class="pestana-participante" type="button" role="tab" aria-selected="false" data-tipo-participante="individuales">Individuales <span id="contadorIndividualesPestana">0</span></button>
<button class="pestana-participante" type="button" role="tab" aria-selected="false" data-tipo-participante="invitaciones" id="mis-invitaciones">Mis invitaciones <span id="contadorInvitacionesPestana">0</span></button>
<button class="pestana-participante" type="button" role="tab" aria-selected="false" data-tipo-participante="enviadas" id="invitaciones-enviadas" hidden>Invitaciones enviadas <span id="contadorInvitacionesEnviadasPestana">0</span></button>
</div>

<div class="filtros-participantes-compactos">
<label class="campo-busqueda-participante">
<span class="sr-only">Buscar participante</span>
<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.5" y2="16.5"></line></svg>
<input id="buscarEquipo" type="search" placeholder="Buscar equipo o responsable...">
</label>
<select class="selector-formulario" id="filtroDisciplinaEquipo" aria-label="Filtrar por disciplina">
<option value="todas">Todas las disciplinas</option>
</select>
<select class="selector-formulario" id="filtroEstadoEquipo" aria-label="Filtrar por estado">
<option value="todos">Todos los estados</option>
<option value="aprobada">Aprobado</option>
<option value="pendiente">Pendiente</option>
<option value="rechazada">Rechazado</option>
<option value="sin_inscripcion">Sin inscripción</option>
<option value="inactivo">Inactivo</option>
</select>
<button class="boton boton-claro boton-limpiar-participantes" id="limpiarFiltrosEquipos" type="button">Limpiar</button>
</div>
<div class="resumen-filtros-ah resumen-filtros-participantes" id="resumenFiltrosParticipantes" aria-live="polite"><span>Sin filtros activos</span></div>
</section>

<div class="cabecera-listado-participantes">
<div>
<h2 class="titulo-seccion" id="tituloListadoParticipantes">Equipos registrados</h2>
<p id="infoResultadosEquipos">0 resultados · información general y estado de inscripción</p>
</div>
<div class="acciones-listado-participantes">
<button class="boton boton-principal boton-inscripcion-individual" id="abrirRegistroParticipante" type="button" hidden>Nueva inscripción individual</button>
<button class="boton boton-claro boton-exportar-participantes boton-exportar-ui" id="exportarEquipos" type="button"><span data-icono="exportar" aria-hidden="true"></span> Exportar lista</button>
</div>
<span class="estado-sincronizacion-participantes" id="estadoSincronizacionParticipantes" aria-live="polite">Datos actualizados</span>
</div>

<section class="lista-equipos-compacta" id="listaEquipos" aria-label="Lista de equipos">
<p class="mensaje-sin-resultados" id="mensajeSinEquipos">No hay equipos registrados.</p>
</section>

<nav class="paginacion-participantes" id="paginacionEquipos" aria-label="Paginación" hidden></nav>
</main>
</div>
</div>

<div class="modal-registro" id="modalCrearEquipo" aria-hidden="true">
<div class="modal-registro-fondo" data-cerrar-crear-equipo></div>
<section class="modal-registro-contenido" role="dialog" aria-modal="true" aria-labelledby="tituloCrearEquipo">
<header class="modal-registro-cabecera">
<div>
<span class="modal-registro-etiqueta">Equipo permanente</span>
<h2 id="tituloCrearEquipo">Crear equipo</h2>
<p>Crea el equipo una sola vez y luego reutilízalo en distintos torneos.</p>
</div>
<button class="modal-registro-cerrar" type="button" aria-label="Cerrar formulario" data-cerrar-crear-equipo><span data-icono="peligro" aria-hidden="true"></span></button>
</header>
<form class="formulario-registro-participante" id="formularioCrearEquipo">
<div class="grupo-formulario grupo-formulario-completo">
<label for="crearEquipoNombre">Nombre del equipo</label>
<input id="crearEquipoNombre" class="entrada-formulario" type="text" maxlength="100" placeholder="Ejemplo: Leones FC" required>
</div>
<div class="grupo-formulario grupo-formulario-completo"><label for="crearEquipoImagen">Imagen del equipo <span class="texto-opcional">(opcional)</span></label><div class="selector-imagen-entidad"><div class="preview-imagen-entidad" id="previewCrearEquipoImagen"><span aria-hidden="true">EQ</span><img id="previewCrearEquipoImagenImg" src="imagenes/arena-cjd-isotipo.png" alt="Vista previa de la imagen del equipo"></div><div><div class="controles-imagen-entidad"><label class="boton boton-claro" for="crearEquipoImagen">Cambiar imagen</label></div><input class="entrada-imagen-entidad" id="crearEquipoImagen" type="file" accept="image/jpeg,image/png,image/webp"><small class="ayuda-imagen-entidad">Se usa una imagen de equipo estándar. Puedes cambiarla por JPG, PNG o WebP · máximo 4 MB · mínimo 120 × 120 px. La imagen seguirá al equipo en sus torneos y resultados.</small></div></div></div>
<div class="grupo-formulario grupo-formulario-completo">
<label class="recordar"><input type="checkbox" id="crearEquipoIncluirResponsable" checked> Participar también como integrante</label>
<small>Tu cuenta seguirá siendo responsable del equipo aunque no formes parte del plantel.</small>
</div>
<div class="grupo-formulario grupo-formulario-completo">
<div class="cabecera-integrantes-registro">
<div>
<label for="crearEquipoIntegrante">Invitar integrantes</label>
<small>Verifica cuentas reales por @usuario. Recibirán una invitación y solo se unirán si la aceptan.</small>
</div>
<strong id="crearEquipoContadorIntegrantes">0 invitaciones preparadas</strong>
</div>
<div class="verificador-usuario">
<input id="crearEquipoIntegrante" class="entrada-formulario" type="text" placeholder="@usuario" autocomplete="off" spellcheck="false">
<button class="boton boton-claro boton-verificar-usuario" id="crearEquipoAgregarIntegrante" type="button">Verificar e invitar</button>
</div>
<p class="mensaje-verificacion" id="crearEquipoMensajeIntegrante" aria-live="polite"></p>
<div class="lista-integrantes-registro" id="crearEquipoListaIntegrantes"></div>
</div>
<p class="mensaje-verificacion grupo-formulario-completo" id="crearEquipoMensaje" aria-live="polite"></p>
<footer class="acciones-formulario-registro grupo-formulario-completo">
<button class="boton boton-claro" type="button" data-cerrar-crear-equipo>Cancelar</button>
<button class="boton boton-principal" type="submit">Crear equipo</button>
</footer>
</form>
</section>
</div>

<div class="modal-registro" id="modalRegistroParticipante" aria-hidden="true">
<div class="modal-registro-fondo" data-cerrar-registro></div>
<section class="modal-registro-contenido" role="dialog" aria-modal="true" aria-labelledby="tituloRegistroParticipante">
<header class="modal-registro-cabecera">
<div>
<span class="modal-registro-etiqueta" id="etiquetaRegistroParticipante">Nueva inscripción</span>
<h2 id="tituloRegistroParticipante">Registrar participante</h2>
<p id="descripcionRegistroParticipante">Completa los datos necesarios para solicitar la inscripción.</p>
</div>
<button class="modal-registro-cerrar" type="button" aria-label="Cerrar formulario" data-cerrar-registro><span data-icono="peligro" aria-hidden="true"></span></button>
</header>
<form class="formulario-registro-participante" id="formularioRegistroParticipante">
<div class="grupo-formulario grupo-formulario-completo" id="grupoModalidadRegistro">
<span class="etiqueta-formulario">Modalidad de participación</span>
<div class="selector-modalidad-registro">
<label><input type="radio" name="modalidadRegistro" value="equipo" checked><span>Equipo</span></label>
<label><input type="radio" name="modalidadRegistro" value="individual"><span>Individual</span></label>
</div>
</div>
<div class="grupo-formulario">
<label for="torneoRegistro">Torneo</label>
<select id="torneoRegistro" class="selector-formulario" required>
<option value="">Seleccionar torneo</option>
</select>
</div>
<div class="grupo-formulario">
<label for="categoriaRegistro">Categoría del torneo</label>
<input id="categoriaRegistro" class="entrada-formulario" type="text" value="Selecciona un torneo" readonly>
</div>
<div class="grupo-formulario grupo-formulario-completo grupo-equipo-registro">
<label for="equipoExistenteRegistro">Equipo existente</label>
<select id="equipoExistenteRegistro" class="selector-formulario" required>
<option value="">Seleccionar equipo</option>
</select>
<small>Selecciona uno de tus equipos permanentes. Sus integrantes actuales se copiarán a esta inscripción sin modificar el historial anterior.</small>
</div>
<div class="grupo-formulario grupo-individual-registro" hidden>
<label for="usuarioIndividualRegistro">Nombre de usuario</label>
<div class="verificador-usuario">
<input id="usuarioIndividualRegistro" class="entrada-formulario" type="text" placeholder="@usuario" autocomplete="off" spellcheck="false">
<small>Si registras a otra persona y eres el organizador responsable, ArenaCJD le enviará una invitación para que la acepte.</small>
<button class="boton boton-claro boton-verificar-usuario" id="verificarUsuarioIndividual" type="button">Verificar</button>
</div>
<p class="mensaje-verificacion" id="mensajeUsuarioIndividual" aria-live="polite"></p>
</div>


<div class="aviso-registro grupo-formulario-completo">
<span><span data-icono="info" aria-hidden="true"></span></span>
<p>Los participantes son cuentas reutilizables: una misma persona puede competir en varios torneos. Solo se impide duplicarla dentro del mismo torneo. Cuando un organizador agrega a otra persona, se envía una invitación que debe aceptar.</p>
</div>
<p class="mensaje-verificacion grupo-formulario-completo" id="mensajeRegistroParticipante" aria-live="polite"></p>
<footer class="acciones-formulario-registro grupo-formulario-completo">
<button class="boton boton-claro" type="button" data-cerrar-registro>Cancelar</button>
<button class="boton boton-principal" type="submit">Guardar inscripción</button>
</footer>
</form>
</section>
</div>

<div class="modal-registro" id="modalIntegrantesEquipo" aria-hidden="true">
<div class="modal-registro-fondo" data-cerrar-integrantes-equipo></div>
<section class="modal-registro-contenido" role="dialog" aria-modal="true" aria-labelledby="tituloIntegrantesEquipo">
<header class="modal-registro-cabecera">
<div>
<span class="modal-registro-etiqueta">Equipo</span>
<h2 id="tituloIntegrantesEquipo">Integrantes</h2>
<p id="subtituloIntegrantesEquipo">Inscripciones e integrantes registrados en el sistema.</p>
</div>
<button class="modal-registro-cerrar" type="button" aria-label="Cerrar" data-cerrar-integrantes-equipo><span data-icono="peligro" aria-hidden="true"></span></button>
</header>
<div class="contenido-detalle-equipo" id="contenidoIntegrantesEquipo"></div>
<footer class="acciones-formulario-registro">
<button class="boton boton-claro" type="button" data-cerrar-integrantes-equipo>Cerrar</button>
</footer>
</section>
</div>

<div class="modal-registro" id="modalEditarEquipo" aria-hidden="true">
<div class="modal-registro-fondo" data-cerrar-editar-equipo></div>
<section class="modal-registro-contenido" role="dialog" aria-modal="true" aria-labelledby="tituloEditarEquipo">
<header class="modal-registro-cabecera">
<div>
<span class="modal-registro-etiqueta">Gestión de equipo</span>
<h2 id="tituloEditarEquipo">Editar equipo</h2>
<p>Actualiza los datos y el plantel actual. Las inscripciones anteriores conservan sus integrantes históricos.</p>
</div>
<button class="modal-registro-cerrar" type="button" aria-label="Cerrar" data-cerrar-editar-equipo><span data-icono="peligro" aria-hidden="true"></span></button>
</header>
<form class="formulario-registro-participante" id="formularioEditarEquipo">
<input type="hidden" id="editarEquipoId">
<div class="grupo-formulario grupo-formulario-completo">
<label for="editarEquipoNombre">Nombre del equipo</label>
<input class="entrada-formulario" id="editarEquipoNombre" type="text" maxlength="100" required>
</div>
<div class="grupo-formulario grupo-formulario-completo"><label for="editarEquipoImagen">Imagen del equipo</label><div class="selector-imagen-entidad"><div class="preview-imagen-entidad" id="previewEditarEquipoImagen"><span aria-hidden="true">EQ</span><img id="previewEditarEquipoImagenImg" src="imagenes/arena-cjd-isotipo.png" alt="Vista previa de la imagen del equipo"></div><div><div class="controles-imagen-entidad"><label class="boton boton-claro" for="editarEquipoImagen">Cambiar imagen</label><button class="boton boton-claro" id="quitarEquipoImagen" type="button" hidden>Quitar imagen</button></div><input class="entrada-imagen-entidad" id="editarEquipoImagen" type="file" accept="image/jpeg,image/png,image/webp"><small class="ayuda-imagen-entidad">La imagen actual se conserva si no seleccionas otra.</small></div></div></div>
<div class="grupo-formulario grupo-formulario-completo">
<label for="editarEquipoResponsable">Responsable</label>
<input class="entrada-formulario" id="editarEquipoResponsable" type="text" maxlength="25" placeholder="@usuario" autocomplete="off" spellcheck="false" required>
<small>Debe ser un usuario activo de ArenaCJD.</small>
</div>
<div class="grupo-formulario grupo-formulario-completo">
<div class="cabecera-integrantes-registro">
<div>
<label for="editarEquipoIntegrante">Integrantes e invitaciones</label>
<small>Los miembros actuales pueden retirarse. Los usuarios nuevos recibirán una invitación y no se agregarán hasta aceptarla.</small>
</div>
<strong id="editarEquipoContadorIntegrantes">0 integrantes</strong>
</div>
<div class="verificador-usuario">
<input class="entrada-formulario" id="editarEquipoIntegrante" type="text" maxlength="25" placeholder="@usuario" autocomplete="off" spellcheck="false">
<button class="boton boton-claro boton-verificar-usuario" id="editarEquipoAgregarIntegrante" type="button">Verificar e invitar</button>
</div>
<p class="mensaje-verificacion" id="editarEquipoMensajeIntegrante" aria-live="polite"></p>
<div class="lista-integrantes-registro" id="editarEquipoListaIntegrantes"></div>
</div>
<div class="grupo-formulario grupo-formulario-completo">
<label for="editarEquipoEstado">Estado del equipo</label>
<select class="selector-formulario" id="editarEquipoEstado" required>
<option value="activo">Activo</option>
<option value="inactivo">Inactivo</option>
</select>
</div>
<p class="mensaje-verificacion grupo-formulario-completo" id="mensajeEditarEquipo" aria-live="polite"></p>
<footer class="acciones-formulario-registro grupo-formulario-completo">
<button class="boton boton-claro" type="button" data-cerrar-editar-equipo>Cancelar</button>
<button class="boton boton-principal" type="submit">Guardar cambios</button>
</footer>
</form>
</section>
</div>


<div class="modal-registro" id="modalDetalleIndividual" aria-hidden="true">
<div class="modal-registro-fondo" data-cerrar-detalle-individual></div>
<section class="modal-registro-contenido" role="dialog" aria-modal="true" aria-labelledby="tituloDetalleIndividual">
<header class="modal-registro-cabecera"><div><span class="modal-registro-etiqueta">Inscripción individual</span><h2 id="tituloDetalleIndividual">Detalle</h2><p>Información registrada en el sistema.</p></div><button class="modal-registro-cerrar" type="button" data-cerrar-detalle-individual aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button></header>
<div class="contenido-detalle-equipo" id="contenidoDetalleIndividual"></div>
<footer class="acciones-formulario-registro"><button class="boton boton-claro" type="button" data-cerrar-detalle-individual>Cerrar</button></footer>
</section>
</div>

<div class="modal-registro" id="modalEditarIndividual" aria-hidden="true">
<div class="modal-registro-fondo" data-cerrar-editar-individual></div>
<section class="modal-registro-contenido" role="dialog" aria-modal="true" aria-labelledby="tituloEditarIndividual">
<header class="modal-registro-cabecera"><div><span class="modal-registro-etiqueta">Gestión de inscripción</span><h2 id="tituloEditarIndividual">Editar inscripción individual</h2><p>Los cambios se aplican a esta inscripción.</p></div><button class="modal-registro-cerrar" type="button" data-cerrar-editar-individual aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button></header>
<form class="formulario-registro-participante" id="formularioEditarIndividual">
<input type="hidden" id="editarIndividualId">
<div class="grupo-formulario grupo-formulario-completo"><label for="editarIndividualUsuario">@usuario</label><input class="entrada-formulario" id="editarIndividualUsuario" type="text" maxlength="25" required></div>
<div class="grupo-formulario grupo-formulario-completo"><label for="editarIndividualTorneo">Torneo</label><select class="selector-formulario" id="editarIndividualTorneo" required></select></div>
<div class="grupo-formulario grupo-formulario-completo"><label for="editarIndividualEstado">Estado</label><select class="selector-formulario" id="editarIndividualEstado" required><option value="pendiente">Pendiente</option><option value="aprobada">Aprobada</option><option value="rechazada">Rechazada</option></select></div>
<p class="mensaje-verificacion grupo-formulario-completo" id="mensajeEditarIndividual" aria-live="polite"></p>
<footer class="acciones-formulario-registro grupo-formulario-completo"><button class="boton boton-claro" type="button" data-cerrar-editar-individual>Cancelar</button><button class="boton boton-principal" type="submit">Guardar cambios</button></footer>
</form>
</section>
</div>

<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/datos.js?v=20260831-flow3"></script>
<script src="js/componentes.js?v=20260913-sin-presencia1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<script src="js/tema.js?v=20260909-login-tema1"></script>
<script src="js/participantes.js?v=20260911-categorias2"></script>
</body>
</html>
