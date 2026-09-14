<?php
require_once __DIR__ . '/config/protegerRol.php';
exigirUnoDeLosRoles(['administrador', 'organizador']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sorteos - ArenaCJD</title>
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
<link rel="stylesheet" href="css/paginas/sorteos.css?v=20260913-media2">
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

<main class="panel">
<div class="notificacion-sorteo" id="notificacionSorteo" role="alert" aria-live="assertive" hidden>
  <div class="notificacion-sorteo-icono" aria-hidden="true"><span data-icono="alerta" aria-hidden="true"></span></div>
  <div class="notificacion-sorteo-contenido">
    <strong id="tituloNotificacionSorteo">No se puede generar el sorteo</strong>
    <p id="mensajeNotificacionSorteo"></p>
  </div>
  <button class="notificacion-sorteo-cerrar" id="cerrarNotificacionSorteo" type="button" aria-label="Cerrar aviso"><span data-icono="peligro" aria-hidden="true"></span></button>
  <span class="notificacion-sorteo-progreso" id="progresoNotificacionSorteo" aria-hidden="true"></span>
</div>
<div class="cabecera-pagina-compacta">
  <div>
    <p class="miga-pagina">Sorteos / Generar enfrentamientos</p>
    <h1 class="titulo-pagina titulo-modulo">Sorteos</h1>
    <p class="subtitulo-pagina">Genera los cruces iniciales de torneos por equipos o individuales.</p>
  </div>
</div>

<section class="resumen-sorteo-compacto" aria-label="Resumen del torneo seleccionado">
  <article class="resumen-torneo-principal">
    <div class="icono-resumen-sorteo" id="iconoTorneoSorteo"><span data-icono="torneo" aria-hidden="true"></span></div>
    <div class="resumen-torneo-texto">
      <strong id="nombreTorneoSorteo">Selecciona un torneo</strong>
      <span id="detalleTorneoSorteo">Selecciona un torneo para cargar sus inscripciones</span>
    </div>
  </article>
  <article class="dato-resumen-sorteo">
    <span class="icono-dato"><span data-icono="participantes" aria-hidden="true"></span></span>
    <div><strong id="cantidadSorteo">0</strong><span id="tipoCantidadSorteo">Inscripciones aprobadas</span></div>
  </article>
  <article class="dato-resumen-sorteo">
    <span class="icono-dato"><span data-icono="calendario" aria-hidden="true"></span></span>
    <div><strong id="fechaSorteo">-</strong><span>Fecha de inicio</span></div>
  </article>
  <article class="dato-resumen-sorteo">
    <span class="icono-dato"><span data-icono="sorteo" aria-hidden="true"></span></span>
    <div><strong class="estado-sorteo pendiente" id="estadoSorteo">Pendiente</strong><span>Estado del sorteo</span></div>
  </article>
</section>

<div class="layout-sorteo-operativo">
  <div class="columna-configuracion-sorteo">
    <section class="tarjeta tarjeta-seccion-sorteo">
      <div class="cabecera-seccion-numerada">
        <span>1</span>
        <div><h2>Configuración del sorteo</h2><p>Selecciona el torneo y la forma de generar los cruces.</p></div>
      </div>

      <div class="grid-formulario-sorteo">
        <div class="grupo-formulario grupo-formulario-completo">
          <label for="torneoSorteo">Torneo y disciplina</label>
          <select id="torneoSorteo" class="selector-formulario">
<option value="">Seleccionar torneo</option>
</select>
          <?php if (puedeGestionarSesionArenaCJD()): ?>
          <div class="acciones-catalogo-sorteo">
            <a class="boton boton-claro boton-gestion-sorteo" href="torneos.php">Gestionar torneos</a>
          </div>
          <?php endif; ?>
        </div>
        <div class="grupo-formulario">
          <label for="metodoSorteo">Método</label>
          <select id="metodoSorteo" class="selector-formulario">
            <option>Aleatorio</option>
            <option>Por cabezas de serie</option>
            <option>Orden actual</option>
          </select>
        </div>
        <div class="grupo-formulario">
          <label for="participantesSorteo">Inscripciones incluidas</label>
          <select id="participantesSorteo" class="selector-formulario">
            <option id="opcionAprobados">Solo aprobadas (0)</option>
          </select>
        </div>
      </div>

      <label class="opcion-interruptor-sorteo">
        <input type="checkbox" id="permitirPasesAutomaticos" checked>
        <span class="interruptor-visual" aria-hidden="true"></span>
        <span><strong>Permitir pases automáticos</strong><small>Se utilizarán cuando la cantidad de participantes no complete la ronda.</small></span>
      </label>

      <p class="ayuda-generar-sorteo" id="ayudaGenerarSorteo" role="status" hidden></p>
      <button class="boton boton-principal boton-bloque" id="generarSorteo" type="button" disabled>Generar sorteo</button>
    </section>

    <section class="tarjeta tarjeta-seccion-sorteo">
      <div class="cabecera-seccion-numerada">
        <span>2</span>
        <div><h2 id="tituloParticipantesSorteo">Participantes incluidos (0)</h2><p id="descripcionParticipantesSorteo">Selecciona un torneo para cargar sus inscripciones reales.</p></div>
      </div>
      <div class="contenedor-scroll">
        <table class="tabla tabla-participantes-sorteo">
          <thead><tr><th>#</th><th id="cabeceraNombreParticipante">Equipo</th><th>Disciplina</th><th>Estado</th></tr></thead>
          <tbody id="cuerpoParticipantesSorteo"></tbody>
        </table>
      </div>
    </section>
  </div>

  <section class="tarjeta tarjeta-resultado-sorteo">
    <div class="cabecera-seccion-numerada">
      <span>3</span>
      <div><h2>Resultado del sorteo</h2><p>Revisa la vista previa generada con las inscripciones reales del torneo.</p></div>
    </div>

    <div class="aviso-sorteo aviso-sorteo-previo" id="avisoEstadoResultadoSorteo">Todavía no hay una vista previa del sorteo. Selecciona un torneo y genera los cruces cuando se cumplan los requisitos.</div>

    <div class="lista-emparejamientos-previa" id="listaEmparejamientosSorteo" aria-label="Vista previa de enfrentamientos">
      <div class="estado-previo-sorteo"><strong>Sin vista previa</strong><span>Los enfrentamientos aparecerán aquí después de generar el sorteo.</span></div>
    </div>

    <div class="resumen-generacion-sorteo" id="resumenGeneracionSorteo" hidden>
      <div><strong id="cantidadEnfrentamientos">0 enfrentamientos</strong><span>Primera ronda</span></div>
      <div><strong id="cantidadParticipantesResumen">0 participantes</strong><span>Inscripciones aprobadas</span></div>
      <div><strong id="cantidadPases">0 pases</strong><span>Ronda completa</span></div>
    </div>

    <div class="acciones-resultado-sorteo">
      <button class="boton boton-claro" id="regenerarSorteo" type="button" disabled>Volver a generar</button>
      <a class="boton boton-claro" id="verCuadroCompletoSorteo" href="#vistaCompletaSorteo" aria-disabled="true">Ver cuadro completo</a>
      <button class="boton boton-principal" id="confirmarSorteo" type="button" disabled>Confirmar sorteo</button>
      <a class="boton boton-principal continuar-sorteo" id="continuarEnfrentamientosSorteo" href="partidos.php" hidden>Continuar a enfrentamientos</a>
    </div>
  </section>
</div>

<details class="tarjeta tarjeta-cuadro-sorteo" id="vistaCompletaSorteo" hidden>
  <summary>
    <span><strong id="tituloVistaCompletaSorteo">Vista completa del sorteo</strong><small id="descripcionVistaCompletaSorteo">Resultado visual de la primera ronda</small></span>
    <span aria-hidden="true"><span data-icono="abajo" aria-hidden="true"></span></span>
  </summary>
  <div class="barra-llave">
    <div><h2 id="tituloCuadroSorteo">Selecciona un torneo</h2><p id="subtituloCuadroSorteo">Sin datos cargados</p></div>
  </div>
  <div class="contenedor-scroll scroll-llave-completa">
    <div class="llave-eliminacion" id="cuadroSorteoCompleto"></div>
  </div>
</details>
</main>
</div>
</div>
<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/datos.js?v=20260906-sorteos-estados1"></script>
<script src="js/componentes.js?v=20260913-sin-presencia1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<script src="js/tema.js?v=20260909-login-tema1"></script>
<script src="js/sorteos.js?v=20260913-media2"></script>
</body>
</html>
