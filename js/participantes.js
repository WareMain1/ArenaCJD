(function () {
  'use strict';

  const lista = document.getElementById('listaEquipos');
  if (!lista) return;

  let sesionPagina = {};
  let primeraCargaParticipantes = true;
  try {
    sesionPagina = JSON.parse(document.body.dataset.sesionArenaCjd || '{}');
  } catch (error) {
    sesionPagina = {};
  }
  const usuarioSesion = sesionPagina.usuario || {};
  const rolesSesion = Array.isArray(usuarioSesion.roles) ? usuarioSesion.roles : [];
  const esAdministradorSesion = rolesSesion.includes('administrador');
  const puedeInvitarDesdeRegistro = esAdministradorSesion || rolesSesion.includes('organizador');

  const pestanas = Array.from(document.querySelectorAll('[data-tipo-participante]'));
  const buscar = document.getElementById('buscarEquipo');
  const filtroDisciplina = document.getElementById('filtroDisciplinaEquipo');
  const filtroEstado = document.getElementById('filtroEstadoEquipo');
  const limpiar = document.getElementById('limpiarFiltrosEquipos');
  const exportar = document.getElementById('exportarEquipos');
  const paginacion = document.getElementById('paginacionEquipos');
  const infoResultados = document.getElementById('infoResultadosEquipos');
  const tituloListado = document.getElementById('tituloListadoParticipantes');
  const contadorEquipos = document.getElementById('contadorEquiposPestana');
  const contadorIndividuales = document.getElementById('contadorIndividualesPestana');
  const contadorInvitaciones = document.getElementById('contadorInvitacionesPestana');
  const contadorInvitacionesEnviadas = document.getElementById('contadorInvitacionesEnviadasPestana');
  const pestanaInvitacionesEnviadas = document.getElementById('invitaciones-enviadas');
  const resumenTotal = document.getElementById('resumenTotalParticipantes');
  const resumenAprobados = document.getElementById('resumenAprobadosParticipantes');
  const resumenPendientes = document.getElementById('resumenPendientesParticipantes');
  const sincronizacion = document.getElementById('estadoSincronizacionParticipantes');
  const resumenFiltros = document.getElementById('resumenFiltrosParticipantes');

  const modalIntegrantes = document.getElementById('modalIntegrantesEquipo');
  const tituloIntegrantes = document.getElementById('tituloIntegrantesEquipo');
  const subtituloIntegrantes = document.getElementById('subtituloIntegrantesEquipo');
  const contenidoIntegrantes = document.getElementById('contenidoIntegrantesEquipo');
  const modalEditar = document.getElementById('modalEditarEquipo');
  const formularioEditar = document.getElementById('formularioEditarEquipo');
  const editarId = document.getElementById('editarEquipoId');
  const editarNombre = document.getElementById('editarEquipoNombre');
  const editarResponsable = document.getElementById('editarEquipoResponsable');
  const editarEstado = document.getElementById('editarEquipoEstado');
  const editarIntegrante = document.getElementById('editarEquipoIntegrante');
  const editarAgregarIntegrante = document.getElementById('editarEquipoAgregarIntegrante');
  const editarMensajeIntegrante = document.getElementById('editarEquipoMensajeIntegrante');
  const editarListaIntegrantes = document.getElementById('editarEquipoListaIntegrantes');
  const editarContadorIntegrantes = document.getElementById('editarEquipoContadorIntegrantes');
  const mensajeEditar = document.getElementById('mensajeEditarEquipo');

  const modalDetalleIndividual = document.getElementById('modalDetalleIndividual');
  const tituloDetalleIndividual = document.getElementById('tituloDetalleIndividual');
  const contenidoDetalleIndividual = document.getElementById('contenidoDetalleIndividual');
  const modalEditarIndividual = document.getElementById('modalEditarIndividual');
  const formularioEditarIndividual = document.getElementById('formularioEditarIndividual');
  const editarIndividualId = document.getElementById('editarIndividualId');
  const editarIndividualUsuario = document.getElementById('editarIndividualUsuario');
  const editarIndividualTorneo = document.getElementById('editarIndividualTorneo');
  const editarIndividualEstado = document.getElementById('editarIndividualEstado');
  const mensajeEditarIndividual = document.getElementById('mensajeEditarIndividual');

  const modalRegistro = document.getElementById('modalRegistroParticipante');
  const abrirRegistro = document.getElementById('abrirRegistroParticipante');
  const formularioRegistro = document.getElementById('formularioRegistroParticipante');
  const etiquetaRegistro = document.getElementById('etiquetaRegistroParticipante');
  const tituloRegistro = document.getElementById('tituloRegistroParticipante');
  const descripcionRegistro = document.getElementById('descripcionRegistroParticipante');
  const grupoModalidadRegistro = document.getElementById('grupoModalidadRegistro');
  const cerrarRegistro = modalRegistro ? modalRegistro.querySelectorAll('[data-cerrar-registro]') : [];
  const radiosModalidad = formularioRegistro ? formularioRegistro.querySelectorAll('input[name="modalidadRegistro"]') : [];
  const gruposEquipo = formularioRegistro ? formularioRegistro.querySelectorAll('.grupo-equipo-registro') : [];
  const gruposIndividual = formularioRegistro ? formularioRegistro.querySelectorAll('.grupo-individual-registro') : [];
  const torneoRegistro = document.getElementById('torneoRegistro');
  const categoriaRegistro = document.getElementById('categoriaRegistro');
  const nombreEquipoRegistro = document.getElementById('nombreEquipoRegistro');
  const equipoExistenteRegistro = document.getElementById('equipoExistenteRegistro');
  const gruposEquipoNuevo = formularioRegistro ? formularioRegistro.querySelectorAll('.grupo-equipo-nuevo') : [];
  const usuarioIndividualRegistro = document.getElementById('usuarioIndividualRegistro');
  const verificarIndividual = document.getElementById('verificarUsuarioIndividual');
  const mensajeUsuarioIndividual = document.getElementById('mensajeUsuarioIndividual');
  const usuarioIntegrante = document.getElementById('usuarioIntegranteRegistro');
  const verificarIntegrante = document.getElementById('verificarIntegranteRegistro');
  const mensajeIntegrante = document.getElementById('mensajeIntegranteRegistro');
  const listaIntegrantes = document.getElementById('listaIntegrantesRegistro');
  const contadorIntegrantes = document.getElementById('contadorIntegrantesRegistro');
  const mensajeRegistro = document.getElementById('mensajeRegistroParticipante');
  const botonGuardarRegistro = formularioRegistro ? formularioRegistro.querySelector('[type="submit"]') : null;

  const modalCrearEquipo = document.getElementById('modalCrearEquipo');
  const abrirCrearEquipo = document.getElementById('abrirCrearEquipo');
  const cerrarCrearEquipo = modalCrearEquipo ? modalCrearEquipo.querySelectorAll('[data-cerrar-crear-equipo]') : [];
  const formularioCrearEquipo = document.getElementById('formularioCrearEquipo');
  const crearEquipoNombre = document.getElementById('crearEquipoNombre');
  const crearEquipoIncluirResponsable = document.getElementById('crearEquipoIncluirResponsable');
  const crearEquipoIntegrante = document.getElementById('crearEquipoIntegrante');
  const crearEquipoAgregarIntegrante = document.getElementById('crearEquipoAgregarIntegrante');
  const crearEquipoMensajeIntegrante = document.getElementById('crearEquipoMensajeIntegrante');
  const crearEquipoListaIntegrantes = document.getElementById('crearEquipoListaIntegrantes');
  const crearEquipoContadorIntegrantes = document.getElementById('crearEquipoContadorIntegrantes');
  const crearEquipoMensaje = document.getElementById('crearEquipoMensaje');
  const crearEquipoImagen = document.getElementById('crearEquipoImagen');
  const previewCrearEquipoImagen = document.getElementById('previewCrearEquipoImagenImg');
  const editarEquipoImagen = document.getElementById('editarEquipoImagen');
  const previewEditarEquipoImagen = document.getElementById('previewEditarEquipoImagenImg');
  const quitarEquipoImagen = document.getElementById('quitarEquipoImagen');
  let urlPreviewCrearEquipo = '';
  let urlPreviewEditarEquipo = '';

  let ITEMS_POR_PAGINA = 10;
  let tipoActual = 'equipos';
  let equipos = [];
  let individuales = [];
  let invitaciones = [];
  let invitacionesEnviadas = [];
  let resumen = {};
  let filtrados = [];
  let paginaActual = 1;
  let csrfToken = '';
  let usuarioIndividualVerificado = null;
  let torneosRegistroDirectos = [];
  let modoInscripcionEquipoDirecta = false;
  let torneosYaInscritosEquipo = new Set();
  let tokenCargaEquipoRegistro = 0;
  const integrantesSeleccionados = new Map();
  const integrantesEdicion = new Map();
  const integrantesNuevoEquipo = new Map();
  const inscripcionesSeleccionadas = new Set();
  let barraAccionesMasivas = null;

  if (pestanaInvitacionesEnviadas) pestanaInvitacionesEnviadas.hidden = !puedeInvitarDesdeRegistro;

  function liberarUrlPreview(clave) {
    if (clave === 'crear' && urlPreviewCrearEquipo) { URL.revokeObjectURL(urlPreviewCrearEquipo); urlPreviewCrearEquipo = ''; }
    if (clave === 'editar' && urlPreviewEditarEquipo) { URL.revokeObjectURL(urlPreviewEditarEquipo); urlPreviewEditarEquipo = ''; }
  }

  const IMAGEN_EQUIPO_ESTANDAR = 'imagenes/arena-cjd-isotipo.png';

  function ocultarPreviewEquipo(img, botonQuitar, clave) {
    liberarUrlPreview(clave);
    if (img) { img.hidden = false; img.src = IMAGEN_EQUIPO_ESTANDAR; }
    if (botonQuitar) botonQuitar.hidden = true;
  }

  function mostrarPreviewEquipo(img, src, botonQuitar, mostrarQuitar) {
    if (!img || !src) return;
    img.onload = function () { img.hidden = false; if (botonQuitar) botonQuitar.hidden = !mostrarQuitar; };
    img.onerror = function () {
      if (img.src.indexOf(IMAGEN_EQUIPO_ESTANDAR) === -1) img.src = IMAGEN_EQUIPO_ESTANDAR;
      else img.hidden = true;
      if (botonQuitar) botonQuitar.hidden = true;
    };
    img.src = src;
  }

  async function subirImagenEquipo(idEquipo, archivo) {
    if (!archivo) return null;
    if (archivo.size > 4 * 1024 * 1024) throw new Error('El equipo se guardó, pero la imagen supera el máximo de 4 MB.');
    const datos = new FormData();
    datos.append('id_equipo', String(idEquipo));
    datos.append('imagen', archivo);
    const respuesta = await fetch('api/equipo_imagen_subir.php', {method:'POST',headers:{'X-CSRF-Token':csrfToken},body:datos});
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.exito) throw new Error('El equipo se guardó, pero ' + (resultado.mensaje || 'no se pudo guardar su imagen.'));
    return resultado;
  }

  function actualizarDisciplinasFiltro() {
    if (!filtroDisciplina) return;

    const anterior = filtroDisciplina.value;
    const nombres = datosActuales()
      .filter(function (item) {
        return !(tipoActual === 'invitaciones' && item.clase_invitacion === 'equipo_membresia');
      })
      .map(function (item) { return String(item.disciplina || '').trim(); })
      .filter(Boolean);

    const disciplinas = Array.from(new Set(nombres)).sort(function (a, b) {
      return a.localeCompare(b, 'es', {sensitivity: 'base'});
    });

    filtroDisciplina.innerHTML = '<option value="todas">Todas las disciplinas</option>' + disciplinas.map(function (nombre) {
      return '<option value="' + escaparHTML(nombre) + '">' + escaparHTML(nombre) + '</option>';
    }).join('');

    if (disciplinas.includes(anterior)) filtroDisciplina.value = anterior;
    else filtroDisciplina.value = 'todas';
  }

  async function cargarPreferenciaPaginacion() {
    try {
      const respuesta = await fetch('api/preferencias.php?_=' + Date.now(), {cache: 'no-store'});
      const resultado = await respuesta.json();
      const cantidad = Number(resultado && resultado.preferencias && resultado.preferencias.elementosPagina);
      if (respuesta.ok && resultado.exito && [10, 20, 50].includes(cantidad) && cantidad !== ITEMS_POR_PAGINA) {
        ITEMS_POR_PAGINA = cantidad;
        paginaActual = 1;
        aplicarFiltros(false);
      }
    } catch (error) {
    }
  }

  function escaparHTML(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function normalizarTexto(valor) {
    return String(valor || '').toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
  }

  function normalizarUsuario(valor) {
    const limpio = String(valor || '').trim().toLowerCase();
    return limpio && limpio.charAt(0) !== '@' ? '@' + limpio : limpio;
  }

  function validarFormatoUsuario(usuario) {
    return /^@[a-z0-9._-]{4,24}$/.test(usuario);
  }

  function mostrarMensaje(elemento, texto, tipo) {
    if (!elemento) return;
    elemento.textContent = texto;
    elemento.className = 'mensaje-verificacion' + (tipo ? ' ' + tipo : '');
  }

  function etiquetaEstado(estado) {
    const etiquetas = {
      aprobada: 'Aprobado',
      pendiente: 'Pendiente',
      rechazada: 'Rechazado',
      sin_inscripcion: 'Sin inscripción',
      inactivo: 'Inactivo'
    };
    return etiquetas[estado] || estado;
  }

  function claseEstado(estado) {
    if (estado === 'aprobada') return 'estado-aprobado';
    if (estado === 'pendiente') return 'estado-pendiente';
    return 'estado-incompleto';
  }

  function avatarDisciplina(disciplina) {
    const mapa = {
      'Vóleibol': [window.ArenaCJDIcono('balon'), 'avatar-morado'],
      'Ajedrez': [window.ArenaCJDIcono('ajedrez'), 'avatar-oscuro'],
      'eSports': [window.ArenaCJDIcono('juego'), 'avatar-verde'],
      'Básquetbol': [window.ArenaCJDIcono('balon'), 'avatar-azul'],
      'Tenis de mesa': [window.ArenaCJDIcono('raqueta'), 'avatar-rojo'],
      'Juego de cartas': [window.ArenaCJDIcono('cartas'), 'avatar-naranja']
    };
    return mapa[disciplina] || [window.ArenaCJDIcono('usuario'), 'avatar-morado'];
  }

  function formatearFechaHora(valor) {
    if (!valor) return '-';
    const fecha = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(fecha.getTime())) return valor;
    return fecha.toLocaleString('es-UY', { dateStyle: 'short', timeStyle: 'short' });
  }

  function estadoRealEquipo(equipo) {
    if (equipo.estado_equipo === 'inactivo') return 'inactivo';
    return equipo.estado_inscripcion || 'sin_inscripcion';
  }

  function renderEquipo(equipo) {
    const estado = estadoRealEquipo(equipo);
    const avatar = avatarDisciplina(equipo.disciplina);
    const mediaEquipo = window.ArenaCJDMedia ? window.ArenaCJDMedia.html('equipo', equipo.id_equipo, equipo.nombre, {clase:'media-mediana'}) : '<span class="avatar-equipo ' + avatar[1] + '">' + avatar[0] + '</span>';
    const disciplina = equipo.disciplina || 'Sin inscripción';
    const integrantes = Number(equipo.cantidad_integrantes || 0);
    const tieneHistorial = Boolean(equipo.ultima_inscripcion_id);
    const textoRetirar = tieneHistorial ? 'Archivar equipo' : 'Eliminar equipo';
    const accionesGestion = equipo.puede_gestionar
      ? '<button class="boton-accion" type="button" data-accion-equipo="editar" data-id-equipo="' + equipo.id_equipo + '">Editar</button>' +
        '<button class="boton-mas boton-eliminar-equipo" type="button" data-accion-equipo="eliminar" data-id-equipo="' + equipo.id_equipo + '" aria-label="' + textoRetirar + '" title="' + textoRetirar + '"><span data-icono="eliminar" aria-hidden="true"></span></button>'
      : '';
    const esResponsableEquipo = Number(equipo.id_creador) === Number(usuarioSesion.id || 0);
    const accionInscribir = Boolean(equipo.puede_inscribir) && equipo.estado_equipo === 'activo'
      ? '<button class="boton-accion boton-inscribir-equipo" type="button" data-accion-equipo="inscribir" data-id-equipo="' + equipo.id_equipo + '">' + (esResponsableEquipo ? 'Inscribir en torneo' : 'Invitar a torneo') + '</button>'
      : '';
    const accionesInscripcion = equipo.puede_gestionar_inscripcion && equipo.estado_inscripcion === 'pendiente' && equipo.ultima_inscripcion_id
      ? '<button class="boton-accion" type="button" data-accion-equipo="aprobar-inscripcion" data-id-inscripcion="' + Number(equipo.ultima_inscripcion_id) + '">Aprobar</button>' +
        '<button class="boton-accion" type="button" data-accion-equipo="rechazar-inscripcion" data-id-inscripcion="' + Number(equipo.ultima_inscripcion_id) + '">Rechazar</button>'
      : '';

    return '<article class="tarjeta-equipo-compacta" data-id-equipo="' + equipo.id_equipo + '">' +
      '<div class="identidad-equipo">' + mediaEquipo + '<div><h3>' + escaparHTML(equipo.nombre) + '</h3><p>Responsable: ' + escaparHTML(equipo.responsable) + ' (@' + escaparHTML(equipo.responsable_usuario) + ')</p></div></div>' +
      '<div class="datos-equipo"><span><b>Disciplina</b> ' + escaparHTML(disciplina) + '</span><span><b>Integrantes</b> ' + integrantes + '</span><span><b>Modalidad</b> Equipo</span><span><b>Torneo</b> ' + escaparHTML(equipo.torneo || 'Sin inscripción') + '</span></div>' +
      '<span class="estado-inscripcion ' + claseEstado(estado) + '"><span data-icono="circulo" aria-hidden="true"></span> ' + escaparHTML(etiquetaEstado(estado)) + '</span>' +
      '<div class="acciones-equipo"><button class="boton-accion" type="button" data-accion-equipo="integrantes" data-id-equipo="' + equipo.id_equipo + '">Ver integrantes</button>' + accionInscribir + accionesInscripcion + accionesGestion + '</div>' +
    '</article>';
  }

  function renderIndividual(inscripcion) {
    const avatarPerfil = window.ArenaCJDAvatar
      ? window.ArenaCJDAvatar.html(inscripcion.id_usuario, inscripcion.nombre_completo, 'avatar-mediano')
      : '<span class="avatar-equipo avatar-morado"><span data-icono="usuario" aria-hidden="true"></span></span>';
    const acciones = [];
    const seleccionable = Boolean(inscripcion.puede_gestionar_estado) && inscripcion.estado === 'pendiente';
    const selector = seleccionable
      ? '<label class="check-inscripcion-masiva-ah"><span class="sr-only">Seleccionar inscripción de ' + escaparHTML(inscripcion.nombre_completo) + '</span><input type="checkbox" data-seleccionar-inscripcion="' + Number(inscripcion.id_inscripcion) + '"' + (inscripcionesSeleccionadas.has(Number(inscripcion.id_inscripcion)) ? ' checked' : '') + '></label>'
      : '';
    acciones.push('<button class="boton-accion" type="button" data-accion-individual="detalle" data-id-inscripcion="' + inscripcion.id_inscripcion + '">Ver detalle</button>');
    if (puedeInvitarDesdeRegistro) acciones.push('<button class="boton-accion boton-reutilizar-participante" type="button" data-accion-individual="reutilizar" data-id-inscripcion="' + inscripcion.id_inscripcion + '">Inscribir en otro torneo</button>');
    if (inscripcion.puede_editar) acciones.push('<button class="boton-accion" type="button" data-accion-individual="editar" data-id-inscripcion="' + inscripcion.id_inscripcion + '">Editar</button>');
    if (inscripcion.puede_eliminar) acciones.push('<button class="boton-mas boton-eliminar-equipo" type="button" data-accion-individual="eliminar" data-id-inscripcion="' + inscripcion.id_inscripcion + '" title="Eliminar inscripción" aria-label="Eliminar inscripción"><span data-icono="eliminar" aria-hidden="true"></span></button>');

    return '<article class="tarjeta-equipo-compacta' + (seleccionable ? ' tarjeta-individual-seleccionable-ah' : '') + '" data-id-inscripcion="' + inscripcion.id_inscripcion + '">' +
      selector + '<div class="identidad-equipo">' + avatarPerfil + '<div><h3>' + escaparHTML(inscripcion.nombre_completo) + '</h3><p>@' + escaparHTML(inscripcion.nombre_usuario) + '</p></div></div>' +
      '<div class="datos-equipo"><span><b>Disciplina</b> ' + escaparHTML(inscripcion.disciplina) + '</span><span><b>Categoría</b> ' + escaparHTML(inscripcion.categoria) + '</span><span><b>Torneo</b> ' + escaparHTML(inscripcion.torneo) + '</span><span><b>Solicitud</b> ' + escaparHTML(formatearFechaHora(inscripcion.fecha_inscripcion)) + '</span></div>' +
      '<span class="estado-inscripcion ' + claseEstado(inscripcion.estado) + '"><span data-icono="circulo" aria-hidden="true"></span> ' + escaparHTML(etiquetaEstado(inscripcion.estado)) + '</span>' +
      '<div class="acciones-equipo">' + acciones.join('') + '</div>' +
    '</article>';
  }

  function asegurarBarraAccionesMasivas() {
    if (barraAccionesMasivas || !lista || !lista.parentNode) return barraAccionesMasivas;
    barraAccionesMasivas = document.createElement('div');
    barraAccionesMasivas.className = 'barra-acciones-masivas-ah';
    barraAccionesMasivas.hidden = true;
    barraAccionesMasivas.innerHTML = '<label class="selector-masivo-ah"><input type="checkbox" data-seleccionar-todas-inscripciones><span>Seleccionar visibles</span></label><strong data-conteo-seleccionadas>0 seleccionadas</strong><button class="boton boton-principal" type="button" data-accion-masiva="aprobar">Aprobar</button><button class="boton boton-claro" type="button" data-accion-masiva="rechazar">Rechazar</button><button class="boton boton-claro" type="button" data-accion-masiva="limpiar">Quitar selección</button>';
    lista.parentNode.insertBefore(barraAccionesMasivas, lista);
    barraAccionesMasivas.addEventListener('change', function (evento) {
      if (!evento.target.matches('[data-seleccionar-todas-inscripciones]')) return;
      const marcar = evento.target.checked;
      lista.querySelectorAll('[data-seleccionar-inscripcion]').forEach(function (check) {
        check.checked = marcar;
        const id = Number(check.dataset.seleccionarInscripcion);
        if (marcar) inscripcionesSeleccionadas.add(id); else inscripcionesSeleccionadas.delete(id);
      });
      actualizarBarraAccionesMasivas();
    });
    barraAccionesMasivas.addEventListener('click', async function (evento) {
      const boton = evento.target.closest('[data-accion-masiva]');
      if (!boton) return;
      const accion = boton.dataset.accionMasiva;
      if (accion === 'limpiar') {
        inscripcionesSeleccionadas.clear();
        lista.querySelectorAll('[data-seleccionar-inscripcion]').forEach(function (check) { check.checked = false; });
        actualizarBarraAccionesMasivas();
        return;
      }
      await ejecutarAccionMasivaInscripciones(accion === 'aprobar' ? 'aprobada' : 'rechazada');
    });
    return barraAccionesMasivas;
  }

  function actualizarBarraAccionesMasivas() {
    const barra = asegurarBarraAccionesMasivas();
    if (!barra) return;
    const checks = Array.from(lista.querySelectorAll('[data-seleccionar-inscripcion]'));
    const seleccionadasVisibles = checks.filter(function (check) { return inscripcionesSeleccionadas.has(Number(check.dataset.seleccionarInscripcion)); });
    barra.hidden = tipoActual !== 'individuales' || checks.length === 0;
    const conteo = barra.querySelector('[data-conteo-seleccionadas]');
    if (conteo) conteo.textContent = inscripcionesSeleccionadas.size + (inscripcionesSeleccionadas.size === 1 ? ' seleccionada' : ' seleccionadas');
    const todas = barra.querySelector('[data-seleccionar-todas-inscripciones]');
    if (todas) {
      todas.checked = checks.length > 0 && seleccionadasVisibles.length === checks.length;
      todas.indeterminate = seleccionadasVisibles.length > 0 && seleccionadasVisibles.length < checks.length;
    }
    barra.querySelectorAll('[data-accion-masiva="aprobar"],[data-accion-masiva="rechazar"],[data-accion-masiva="limpiar"]').forEach(function (boton) {
      boton.disabled = inscripcionesSeleccionadas.size === 0;
    });
  }

  async function ejecutarAccionMasivaInscripciones(estado) {
    const ids = Array.from(inscripcionesSeleccionadas);
    if (!ids.length) return;
    if (estado === 'rechazada' && window.ArenaCJDConfirmar) {
      const confirmar = await window.ArenaCJDConfirmar({
        titulo: 'Rechazar inscripciones seleccionadas',
        mensaje: 'Se rechazarán ' + ids.length + (ids.length === 1 ? ' solicitud pendiente. La persona podrá volver a inscribirse si el torneo lo permite.' : ' solicitudes pendientes. Las personas podrán volver a inscribirse si el torneo lo permite.'),
        confirmar: 'Rechazar seleccionadas'
      });
      if (!confirmar) return;
    }
    const barra = asegurarBarraAccionesMasivas();
    const botones = barra ? barra.querySelectorAll('button') : [];
    botones.forEach(function (boton) { boton.disabled = true; });
    try {
      const respuesta = await fetch('api/inscripciones_individuales_masivo.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({ids: ids, estado: estado})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron actualizar las inscripciones.');
      inscripcionesSeleccionadas.clear();
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(resultado.mensaje || 'Inscripciones actualizadas.', 'exito');
      await cargarDatos(true);
    } catch (error) {
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(error.message || 'No se pudieron actualizar las inscripciones.', 'error');
    } finally {
      actualizarBarraAccionesMasivas();
    }
  }

  function renderInvitacion(invitacion) {
    const esEquipo = invitacion.clase_invitacion === 'equipo_membresia';
    const avatar = avatarDisciplina(esEquipo ? 'Equipo' : invitacion.disciplina);
    const estado = invitacion.estado || 'pendiente';
    const acciones = estado === 'pendiente'
      ? (esEquipo
        ? '<button class="boton-accion" type="button" data-accion-invitacion-equipo="aceptada" data-id-invitacion-equipo="' + Number(invitacion.id_invitacion_equipo) + '">Aceptar</button>' +
          '<button class="boton-accion" type="button" data-accion-invitacion-equipo="rechazada" data-id-invitacion-equipo="' + Number(invitacion.id_invitacion_equipo) + '">Rechazar</button>'
        : '<button class="boton-accion" type="button" data-accion-invitacion="aceptada" data-id-invitacion="' + Number(invitacion.id_invitacion) + '">Aceptar</button>' +
          '<button class="boton-accion" type="button" data-accion-invitacion="rechazada" data-id-invitacion="' + Number(invitacion.id_invitacion) + '">Rechazar</button>' +
          '<a class="boton-accion" href="torneos.php?detalle=' + Number(invitacion.id_torneo) + '">Ver torneo</a>')
      : (!esEquipo ? '<a class="boton-accion" href="torneos.php?detalle=' + Number(invitacion.id_torneo) + '">Ver torneo</a>' : '');
    const etiquetas = {pendiente:'Pendiente', aceptada:'Aceptada', rechazada:'Rechazada', cancelada:'Cancelada'};
    const clase = estado === 'aceptada' ? 'estado-aprobado' : (estado === 'pendiente' ? 'estado-pendiente' : 'estado-incompleto');

    if (esEquipo) {
      return '<article class="tarjeta-equipo-compacta tarjeta-invitacion-torneo" data-id-invitacion-equipo="' + Number(invitacion.id_invitacion_equipo) + '">' +
        '<div class="identidad-equipo"><span class="avatar-equipo ' + avatar[1] + '">' + avatar[0] + '</span><div><h3>' + escaparHTML(invitacion.equipo) + '</h3><p>Invitación para formar parte del equipo · envió ' + escaparHTML(invitacion.invitador) + ' (@' + escaparHTML(invitacion.invitador_usuario) + ')</p></div></div>' +
        '<div class="datos-equipo"><span><b>Tipo</b> Equipo permanente</span><span><b>Decisión</b> Tú decides si unirte</span><span><b>Fecha</b> ' + escaparHTML(formatearFechaHora(invitacion.fecha_creacion)) + '</span></div>' +
        '<span class="estado-inscripcion ' + clase + '"><span data-icono="circulo" aria-hidden="true"></span> ' + escaparHTML(etiquetas[estado] || estado) + '</span>' +
        '<div class="acciones-equipo">' + acciones + '</div>' +
      '</article>';
    }

    return '<article class="tarjeta-equipo-compacta tarjeta-invitacion-torneo" data-id-invitacion="' + Number(invitacion.id_invitacion) + '">' +
      '<div class="identidad-equipo"><span class="avatar-equipo ' + avatar[1] + '">' + avatar[0] + '</span><div><h3>' + escaparHTML(invitacion.torneo) + '</h3><p>Invitación de ' + escaparHTML(invitacion.invitador) + ' (@' + escaparHTML(invitacion.invitador_usuario) + ')</p></div></div>' +
      '<div class="datos-equipo"><span><b>Disciplina</b> ' + escaparHTML(invitacion.disciplina) + '</span><span><b>Categoría</b> ' + escaparHTML(invitacion.categoria) + '</span><span><b>Invitación</b> ' + escaparHTML(invitacion.tipo === 'equipo' ? ('Equipo · ' + (invitacion.equipo || '')) : 'Individual') + '</span><span><b>Fecha</b> ' + escaparHTML(formatearFechaHora(invitacion.fecha_creacion)) + '</span><span><b>Inicio</b> ' + escaparHTML((invitacion.fecha_inicio || '-') + (invitacion.hora_inicio ? ' · ' + String(invitacion.hora_inicio).slice(0,5) : '')) + '</span></div>' +
      '<span class="estado-inscripcion ' + clase + '"><span data-icono="circulo" aria-hidden="true"></span> ' + escaparHTML(etiquetas[estado] || estado) + '</span>' +
      '<div class="acciones-equipo">' + acciones + '</div>' +
    '</article>';
  }

  function renderInvitacionEnviada(invitacion) {
    const avatarPerfil = window.ArenaCJDAvatar
      ? window.ArenaCJDAvatar.html(invitacion.id_invitado, invitacion.invitado || invitacion.invitado_usuario || 'Usuario', 'avatar-mediano')
      : '<span class="avatar-equipo avatar-morado"><span data-icono="usuario" aria-hidden="true"></span></span>';
    const estado = invitacion.estado || 'pendiente';
    const etiquetas = {pendiente:'Esperando respuesta', aceptada:'Aceptada', rechazada:'Rechazada', cancelada:'Cancelada'};
    const clase = estado === 'aceptada' ? 'estado-aprobado' : (estado === 'pendiente' ? 'estado-pendiente' : 'estado-incompleto');
    const tipo = invitacion.tipo === 'equipo' ? ('Equipo' + (invitacion.equipo ? ' · ' + invitacion.equipo : '')) : 'Individual';

    return '<article class="tarjeta-equipo-compacta tarjeta-invitacion-torneo" data-id-invitacion-enviada="' + Number(invitacion.id_invitacion) + '">' +
      '<div class="identidad-equipo">' + avatarPerfil + '<div><h3>' + escaparHTML(invitacion.invitado || ('@' + invitacion.invitado_usuario)) + '</h3><p>@' + escaparHTML(invitacion.invitado_usuario || '') + ' · invitado a ' + escaparHTML(invitacion.torneo || '') + '</p></div></div>' +
      '<div class="datos-equipo"><span><b>Disciplina</b> ' + escaparHTML(invitacion.disciplina || '-') + '</span><span><b>Torneo</b> ' + escaparHTML(invitacion.torneo || '-') + '</span><span><b>Modalidad</b> ' + escaparHTML(tipo) + '</span><span><b>Enviada</b> ' + escaparHTML(formatearFechaHora(invitacion.fecha_creacion)) + '</span></div>' +
      '<span class="estado-inscripcion ' + clase + '"><span data-icono="circulo" aria-hidden="true"></span> ' + escaparHTML(etiquetas[estado] || estado) + '</span>' +
      '<div class="acciones-equipo"><a class="boton-accion" href="torneos.php?detalle=' + Number(invitacion.id_torneo) + '">Ver torneo</a></div>' +
    '</article>';
  }

  function actualizarEquiposReutilizables() {
    if (!equipoExistenteRegistro) return;
    const anterior = equipoExistenteRegistro.value;
    const disponibles = equipos.filter(function (equipo) { return Boolean(equipo.puede_inscribir) || (puedeInvitarDesdeRegistro && equipo.estado_equipo === 'activo'); });
    equipoExistenteRegistro.innerHTML = '<option value="">Seleccionar equipo</option>' + disponibles.map(function (equipo) {
      return '<option value="' + Number(equipo.id_equipo) + '">' + escaparHTML(equipo.nombre) + ' · @' + escaparHTML(equipo.responsable_usuario || '') + ' · ' + Number(equipo.cantidad_integrantes || 0) + ' integrantes</option>';
    }).join('');
    if (disponibles.some(function (equipo) { return String(equipo.id_equipo) === String(anterior); })) {
      equipoExistenteRegistro.value = anterior;
    }
    actualizarModoEquipoRegistro();
  }

  function actualizarModoEquipoRegistro() {
    if (equipoExistenteRegistro) {
      equipoExistenteRegistro.required = modalidadRegistroActual() === 'equipo';
    }
  }

  async function actualizarTorneosSegunEquipoSeleccionado() {
    const token = ++tokenCargaEquipoRegistro;
    torneosYaInscritosEquipo = new Set();
    if (modalidadRegistroActual() !== 'equipo' || !equipoExistenteRegistro || !Number(equipoExistenteRegistro.value)) {
      actualizarTorneosRegistro();
      return;
    }

    try {
      const respuesta = await fetch('api/equipo_detalle.php?id=' + encodeURIComponent(equipoExistenteRegistro.value) + '&_=' + Date.now(), { cache: 'no-store' });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito || !resultado.equipo) throw new Error(resultado.mensaje || 'No se pudo comprobar el historial del equipo.');
      if (token !== tokenCargaEquipoRegistro) return;
      const inscripciones = Array.isArray(resultado.equipo.inscripciones) ? resultado.equipo.inscripciones : [];
      torneosYaInscritosEquipo = new Set(inscripciones.map(function (inscripcion) { return String(inscripcion.id_torneo); }));
      actualizarTorneosRegistro();
    } catch (error) {
      if (token !== tokenCargaEquipoRegistro) return;
      torneosYaInscritosEquipo = new Set();
      actualizarTorneosRegistro();
      mostrarMensaje(mensajeRegistro, error.message || 'No se pudo comprobar el historial del equipo.', 'error');
    }
  }

  function actualizarResumen() {
    if (contadorEquipos) contadorEquipos.textContent = Number(resumen.equipos || equipos.length || 0);
    if (contadorIndividuales) contadorIndividuales.textContent = individuales.length;
    if (contadorInvitaciones) contadorInvitaciones.textContent = invitaciones.filter(function (i) { return i.estado === 'pendiente'; }).length;
    if (contadorInvitacionesEnviadas) contadorInvitacionesEnviadas.textContent = invitacionesEnviadas.filter(function (i) { return i.estado === 'pendiente'; }).length;
    if (resumenTotal) resumenTotal.textContent = Number(resumen.total_registros || 0);
    if (resumenAprobados) resumenAprobados.textContent = Number(resumen.aprobados || 0);
    if (resumenPendientes) resumenPendientes.textContent = Number(resumen.pendientes || 0);
  }

  function datosActuales() {
    if (tipoActual === 'equipos') return equipos;
    if (tipoActual === 'invitaciones') return invitaciones;
    if (tipoActual === 'enviadas') return invitacionesEnviadas;
    return individuales;
  }

  function estadoElemento(item) {
    if (tipoActual === 'equipos') return estadoRealEquipo(item);
    if (tipoActual === 'invitaciones' || tipoActual === 'enviadas') return item.estado === 'aceptada' ? 'aprobada' : item.estado;
    return item.estado;
  }

  function aplicarFiltros(resetPagina) {
    const texto = normalizarTexto(buscar && buscar.value);
    const disciplina = filtroDisciplina ? filtroDisciplina.value : 'todas';
    const estado = filtroEstado ? filtroEstado.value : 'todos';
    if (resumenFiltros) {
      const activos = [];
      if (texto) activos.push('Búsqueda');
      if (disciplina !== 'todas') activos.push(disciplina);
      if (estado !== 'todos') activos.push(etiquetaEstado(estado));
      resumenFiltros.innerHTML = activos.length ? activos.map(function (item) { return '<span class="chip-filtro-ah">' + escaparHTML(item) + '</span>'; }).join('') : '<span>Sin filtros activos</span>';
    }

    filtrados = datosActuales().filter(function (item) {
      const baseTexto = tipoActual === 'equipos'
        ? item.nombre + ' ' + item.responsable + ' ' + item.responsable_usuario + ' ' + (item.torneo || '')
        : (tipoActual === 'invitaciones'
          ? (item.torneo || '') + ' ' + (item.equipo || '') + ' ' + (item.disciplina || '') + ' ' + (item.categoria || '') + ' ' + (item.invitador || '') + ' ' + (item.invitador_usuario || '')
          : (tipoActual === 'enviadas'
            ? (item.torneo || '') + ' ' + (item.equipo || '') + ' ' + (item.disciplina || '') + ' ' + (item.invitado || '') + ' ' + (item.invitado_usuario || '')
            : item.nombre_completo + ' ' + item.nombre_usuario + ' ' + item.torneo));
      return (!texto || normalizarTexto(baseTexto).includes(texto)) &&
        (disciplina === 'todas' || item.disciplina === disciplina) &&
        (estado === 'todos' || estadoElemento(item) === estado);
    });

    if (resetPagina !== false) paginaActual = 1;
    renderizarListado();
  }

  function renderizarListado() {
    const total = filtrados.length;
    const totalPaginas = Math.max(1, Math.ceil(total / ITEMS_POR_PAGINA));
    paginaActual = Math.min(Math.max(1, paginaActual), totalPaginas);
    const inicio = (paginaActual - 1) * ITEMS_POR_PAGINA;
    const visibles = filtrados.slice(inicio, inicio + ITEMS_POR_PAGINA);

    if (tituloListado) tituloListado.textContent = tipoActual === 'equipos' ? 'Equipos registrados' : (tipoActual === 'invitaciones' ? 'Mis invitaciones' : (tipoActual === 'enviadas' ? 'Invitaciones enviadas' : 'Participantes individuales'));
    if (buscar) buscar.placeholder = tipoActual === 'equipos' ? 'Buscar equipo o responsable...' : (tipoActual === 'invitaciones' ? 'Buscar torneo, disciplina u organizador...' : (tipoActual === 'enviadas' ? 'Buscar invitado, @usuario o torneo...' : 'Buscar nombre, @usuario o torneo...'));
    if (abrirRegistro) {
      const mostrarInscripcionIndividual = tipoActual === 'individuales';
      abrirRegistro.hidden = !mostrarInscripcionIndividual;
      abrirRegistro.textContent = puedeInvitarDesdeRegistro ? 'Nueva inscripción individual' : 'Inscribirme en torneo';
    }
    if (infoResultados) infoResultados.textContent = total + (total === 1 ? ' resultado' : ' resultados') + ' · información actualizada';

    if (!visibles.length) {
      const conFiltros = Boolean((buscar && buscar.value.trim()) || (filtroDisciplina && filtroDisciplina.value !== 'todas') || (filtroEstado && filtroEstado.value !== 'todos'));
      const tituloVacio = conFiltros ? 'No encontramos resultados con esos filtros' : (tipoActual === 'equipos' ? 'Todavía no hay equipos registrados' : (tipoActual === 'invitaciones' ? 'No tienes invitaciones pendientes' : (tipoActual === 'enviadas' ? 'Todavía no enviaste invitaciones' : 'Todavía no hay inscripciones individuales')));
      const detalleVacio = conFiltros ? 'Limpia o cambia los filtros para ampliar la búsqueda.' : (tipoActual === 'equipos' ? 'Crea un equipo permanente y luego podrás inscribirlo en diferentes torneos.' : 'Cuando exista actividad relacionada con esta sección aparecerá aquí.');
      lista.innerHTML = '<div class="estado-vacio-ah"><strong>' + escaparHTML(tituloVacio) + '</strong><span>' + escaparHTML(detalleVacio) + '</span>' + (conFiltros ? '<button class="boton boton-claro" type="button" data-limpiar-vacio-participantes>Limpiar filtros</button>' : '') + '</div>';
      const limpiarVacio = lista.querySelector('[data-limpiar-vacio-participantes]');
      if (limpiarVacio) limpiarVacio.addEventListener('click', function () { if (limpiar) limpiar.click(); });
    } else {
      const render = tipoActual === 'equipos' ? renderEquipo : (tipoActual === 'invitaciones' ? renderInvitacion : (tipoActual === 'enviadas' ? renderInvitacionEnviada : renderIndividual));
      lista.innerHTML = visibles.map(render).join('');
      if (window.ArenaCJDAvatar) window.ArenaCJDAvatar.activar(lista);
    }

    renderizarPaginacion(totalPaginas, total);
    actualizarBarraAccionesMasivas();
  }

  function renderizarPaginacion(totalPaginas, total) {
    if (!paginacion) return;
    if (total <= ITEMS_POR_PAGINA) {
      paginacion.hidden = true;
      paginacion.innerHTML = '';
      return;
    }
    paginacion.hidden = false;
    let html = '<button type="button" data-pagina-participante="anterior"' + (paginaActual === 1 ? ' disabled' : '') + '><span data-icono="izquierda" aria-hidden="true"></span></button>';
    for (let pagina = 1; pagina <= totalPaginas; pagina += 1) {
      html += '<button type="button" data-pagina-participante="' + pagina + '"' + (pagina === paginaActual ? ' class="activa" aria-current="page"' : '') + '>' + pagina + '</button>';
    }
    html += '<button type="button" data-pagina-participante="siguiente"' + (paginaActual === totalPaginas ? ' disabled' : '') + '><span data-icono="derecha" aria-hidden="true"></span></button>';
    paginacion.innerHTML = html;
  }

  function actualizarSincronizacion() {
    if (!sincronizacion) return;
    sincronizacion.textContent = 'Sincronizado · ' + new Date().toLocaleTimeString('es-UY', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }

  async function cargarDatos(mantenerPagina) {
    const paginaAnterior = paginaActual;
    if (primeraCargaParticipantes && window.ArenaCJDSkeleton) window.ArenaCJDSkeleton.mostrar(lista, 5);
    try {
      const respuestas = await Promise.all([
        fetch('api/equipos.php', { cache: 'no-store', headers: { Accept: 'application/json' } }),
        fetch('api/inscripciones_individuales.php', { cache: 'no-store', headers: { Accept: 'application/json' } }),
        fetch('api/invitaciones.php?_=' + Date.now(), { cache: 'no-store', headers: { Accept: 'application/json' } })
      ]);
      const resultados = await Promise.all(respuestas.map(function (respuesta) { return respuesta.json(); }));
      const equiposResultado = resultados[0];
      const individualesResultado = resultados[1];
      const invitacionesResultado = resultados[2];
      if (!respuestas[0].ok || !equiposResultado.exito) throw new Error(equiposResultado.mensaje || 'No se pudieron cargar los equipos.');
      if (!respuestas[1].ok || !individualesResultado.exito) throw new Error(individualesResultado.mensaje || 'No se pudieron cargar las inscripciones individuales.');
      if (!respuestas[2].ok || !invitacionesResultado.exito) throw new Error(invitacionesResultado.mensaje || 'No se pudieron cargar las invitaciones.');

      equipos = Array.isArray(equiposResultado.equipos) ? equiposResultado.equipos : [];
      individuales = Array.isArray(individualesResultado.inscripciones) ? individualesResultado.inscripciones : [];
      const invitacionesTorneo = Array.isArray(invitacionesResultado.recibidas) ? invitacionesResultado.recibidas.map(function (item) {
        return Object.assign({clase_invitacion: 'torneo'}, item);
      }) : [];
      const invitacionesEquipo = Array.isArray(invitacionesResultado.recibidas_equipo) ? invitacionesResultado.recibidas_equipo.map(function (item) {
        return Object.assign({
          clase_invitacion: 'equipo_membresia',
          torneo: item.equipo || 'Equipo',
          disciplina: 'Equipo',
          categoria: 'Membresía'
        }, item);
      }) : [];
      invitaciones = invitacionesTorneo.concat(invitacionesEquipo).sort(function (a, b) {
        return String(b.fecha_creacion || '').localeCompare(String(a.fecha_creacion || ''));
      });
      invitacionesEnviadas = Array.isArray(invitacionesResultado.enviadas) ? invitacionesResultado.enviadas.map(function (item) {
        return Object.assign({clase_invitacion: 'torneo_enviada'}, item);
      }).sort(function (a, b) {
        return String(b.fecha_creacion || '').localeCompare(String(a.fecha_creacion || ''));
      }) : [];
      const registrosEquipo = equipos.filter(function(e){ return e.ultima_inscripcion_id != null; });
      resumen = {
        equipos: equipos.length,
        individuales: individuales.length,
        total_registros: registrosEquipo.length + individuales.length,
        aprobados: registrosEquipo.filter(function(e){return e.estado_inscripcion === 'aprobada';}).length + individuales.filter(function(i){return i.estado === 'aprobada';}).length,
        pendientes: registrosEquipo.filter(function(e){return e.estado_inscripcion === 'pendiente';}).length + individuales.filter(function(i){return i.estado === 'pendiente';}).length
      };
      actualizarDisciplinasFiltro();
      actualizarEquiposReutilizables();
      csrfToken = equiposResultado.csrf_token || individualesResultado.csrf_token || invitacionesResultado.csrf_token || csrfToken;
      actualizarResumen();
      paginaActual = mantenerPagina ? paginaAnterior : 1;
      aplicarFiltros(false);
      actualizarSincronizacion();
      if (window.ArenaCJDSkeleton) window.ArenaCJDSkeleton.ocultar(lista);
    } catch (error) {
      const mensaje = error.message || 'No se pudieron actualizar los participantes.';
      if (window.ArenaCJDEstadoError) window.ArenaCJDEstadoError(lista, mensaje, function () { return cargarDatos(true); });
      else lista.innerHTML = '<p class="mensaje-sin-resultados">' + escaparHTML(mensaje) + '</p>';
      if (paginacion) paginacion.hidden = true;
      if (window.ArenaCJDSkeleton) window.ArenaCJDSkeleton.ocultar(lista);
    } finally {
      primeraCargaParticipantes = false;
    }
  }

  function abrirModal(modal) {
    if (!modal) return;
    modal.classList.add('abierto');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-registro-activo');
  }

  function cerrarModal(modal) {
    if (!modal) return;
    modal.classList.remove('abierto');
    modal.setAttribute('aria-hidden', 'true');
    if (!document.querySelector('.modal-registro.abierto')) document.body.classList.remove('modal-registro-activo');
  }

  async function obtenerEquipo(id) {
    const respuesta = await fetch('api/equipo_detalle.php?id=' + encodeURIComponent(id), { cache: 'no-store' });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo cargar el equipo.');
    return resultado.equipo;
  }

  function bloqueInscripcion(inscripcion) {
    const integrantes = Array.isArray(inscripcion.integrantes) ? inscripcion.integrantes : [];
    const listaHtml = integrantes.length
      ? '<div class="lista-integrantes-detalle-equipo">' + integrantes.map(function (integrante) {
          const avatar = window.ArenaCJDAvatar ? window.ArenaCJDAvatar.html(integrante.id_usuario, integrante.nombre, 'avatar-pequeno') : '';
          return '<div class="integrante-detalle-equipo">' + avatar + '<div><strong>' + escaparHTML(integrante.nombre) + '</strong><span>@' + escaparHTML(integrante.usuario) + '</span></div></div>';
        }).join('') + '</div>'
      : '<p class="mensaje-detalle-equipo">Esta inscripción todavía no tiene integrantes registrados.</p>';
    return '<article class="inscripcion-detalle-equipo"><div class="cabecera-inscripcion-detalle-equipo"><div><h3>' + escaparHTML(inscripcion.torneo) + '</h3><p>' + escaparHTML(inscripcion.disciplina) + ' · ' + escaparHTML(formatearFechaHora(inscripcion.fecha_inscripcion)) + '</p></div><span class="estado-inscripcion ' + claseEstado(inscripcion.estado) + '"><span data-icono="circulo" aria-hidden="true"></span> ' + escaparHTML(etiquetaEstado(inscripcion.estado)) + '</span></div>' + listaHtml + '</article>';
  }

  async function verIntegrantes(id) {
    if (contenidoIntegrantes) contenidoIntegrantes.innerHTML = '<p class="mensaje-detalle-equipo">Cargando integrantes...</p>';
    abrirModal(modalIntegrantes);
    try {
      const equipo = await obtenerEquipo(id);
      if (tituloIntegrantes) tituloIntegrantes.textContent = equipo.nombre;
      if (subtituloIntegrantes) subtituloIntegrantes.textContent = 'Responsable: ' + equipo.responsable + ' (@' + equipo.responsable_usuario + ')';
      const mediaCabeceraEquipo = window.ArenaCJDMedia ? '<div class="cabecera-media-equipo-detalle">' + window.ArenaCJDMedia.html('equipo', equipo.id_equipo, equipo.nombre, {clase:'media-grande'}) + '<div><strong>' + escaparHTML(equipo.nombre) + '</strong><span>Equipo permanente de ArenaCJD</span></div></div>' : '';
      const actuales = Array.isArray(equipo.integrantes_actuales) ? equipo.integrantes_actuales : [];
      const bloqueActual = '<article class="inscripcion-detalle-equipo"><div class="cabecera-inscripcion-detalle-equipo"><div><h3>Integrantes actuales</h3><p>Plantel permanente usado al crear nuevas inscripciones</p></div><span class="estado-inscripcion estado-aprobado"><span data-icono="circulo" aria-hidden="true"></span> ' + actuales.length + ' actuales</span></div>' +
        (actuales.length ? '<div class="lista-integrantes-detalle-equipo">' + actuales.map(function (integrante) { const avatar = window.ArenaCJDAvatar ? window.ArenaCJDAvatar.html(integrante.id_usuario, integrante.nombre, 'avatar-pequeno') : ''; return '<div class="integrante-detalle-equipo">' + avatar + '<div><strong>' + escaparHTML(integrante.nombre) + '</strong><span>@' + escaparHTML(integrante.usuario) + '</span></div></div>'; }).join('') + '</div>' : '<p class="mensaje-detalle-equipo">Este equipo todavía no tiene integrantes actuales.</p>') + '</article>';
      const pendientes = Array.isArray(equipo.invitaciones_pendientes) ? equipo.invitaciones_pendientes : [];
      const bloquePendientes = pendientes.length
        ? '<article class="inscripcion-detalle-equipo"><div class="cabecera-inscripcion-detalle-equipo"><div><h3>Invitaciones pendientes</h3><p>Estas cuentas todavía NO forman parte del equipo.</p></div><span class="estado-inscripcion estado-pendiente"><span data-icono="circulo" aria-hidden="true"></span> ' + pendientes.length + ' pendientes</span></div><div class="lista-integrantes-detalle-equipo">' + pendientes.map(function (invitacion) { const avatar = window.ArenaCJDAvatar ? window.ArenaCJDAvatar.html(invitacion.id_usuario, invitacion.nombre, 'avatar-pequeno') : ''; return '<div class="integrante-detalle-equipo">' + avatar + '<div><strong>' + escaparHTML(invitacion.nombre) + '</strong><span>@' + escaparHTML(invitacion.usuario) + ' · esperando respuesta</span></div></div>'; }).join('') + '</div></article>'
        : '';
      const historial = equipo.inscripciones && equipo.inscripciones.length
        ? '<div class="separador-historial-equipo"><strong>Historial por torneo</strong><span>Cada inscripción conserva sus integrantes originales.</span></div>' + equipo.inscripciones.map(bloqueInscripcion).join('')
        : '<p class="mensaje-detalle-equipo">Este equipo no tiene inscripciones visibles asociadas a torneos vigentes o finalizados.</p>';
      contenidoIntegrantes.innerHTML = mediaCabeceraEquipo + bloqueActual + bloquePendientes + historial;
      if (window.ArenaCJDAvatar) window.ArenaCJDAvatar.activar(contenidoIntegrantes);
    } catch (error) {
      contenidoIntegrantes.innerHTML = '<p class="mensaje-detalle-equipo">' + escaparHTML(error.message) + '</p>';
    }
  }

  function renderIntegrantesEdicion() {
    if (!editarListaIntegrantes) return;
    editarListaIntegrantes.innerHTML = '';
    integrantesEdicion.forEach(function (usuario, alias) {
      const elemento = document.createElement('div');
      elemento.className = 'integrante-seleccionado';
      const estado = usuario.es_actual ? 'Integrante actual' : 'Invitación pendiente';
      elemento.innerHTML = '<div><strong>@' + escaparHTML(alias) + '</strong><span>' + escaparHTML(usuario.nombre) + ' · ' + estado + '</span></div><button type="button" aria-label="Quitar @' + escaparHTML(alias) + '"><span data-icono="peligro" aria-hidden="true"></span></button>';
      elemento.querySelector('button').addEventListener('click', function () {
        integrantesEdicion.delete(alias);
        renderIntegrantesEdicion();
      });
      editarListaIntegrantes.appendChild(elemento);
    });
    if (editarContadorIntegrantes) {
      const actuales = Array.from(integrantesEdicion.values()).filter(function (u) { return u.es_actual; }).length;
      const pendientes = integrantesEdicion.size - actuales;
      editarContadorIntegrantes.textContent = actuales + ' actuales · ' + pendientes + ' pendientes';
    }
  }

  async function agregarIntegranteEdicion() {
    if (!editarIntegrante) return;
    const verificado = await verificarUsuario(editarIntegrante.value, editarMensajeIntegrante);
    if (!verificado) return;
    if (integrantesEdicion.has(verificado.nombre_usuario)) {
      mostrarMensaje(editarMensajeIntegrante, 'Ese usuario ya es integrante o ya tiene una invitación pendiente.', 'error');
      return;
    }
    integrantesEdicion.set(verificado.nombre_usuario, Object.assign({}, verificado, {es_actual: false}));
    editarIntegrante.value = '';
    mostrarMensaje(editarMensajeIntegrante, 'Invitación preparada para @' + verificado.nombre_usuario + '. No se agregará hasta que acepte.', 'exito');
    renderIntegrantesEdicion();
  }

  async function abrirEditarEquipo(id) {
    mostrarMensaje(mensajeEditar, 'Cargando equipo...', 'informativo');
    abrirModal(modalEditar);
    try {
      const equipo = await obtenerEquipo(id);
      if (!equipo.puede_gestionar) throw new Error('No tienes permiso para editar este equipo.');
      editarId.value = equipo.id_equipo;
      if (editarEquipoImagen) editarEquipoImagen.value = '';
      ocultarPreviewEquipo(previewEditarEquipoImagen, quitarEquipoImagen, 'editar');
      mostrarPreviewEquipo(previewEditarEquipoImagen, 'api/imagen_equipo.php?id_equipo=' + Number(equipo.id_equipo) + '&_=' + Date.now(), quitarEquipoImagen, true);
      editarNombre.value = equipo.nombre;
      editarResponsable.value = '@' + equipo.responsable_usuario;
      editarEstado.value = equipo.estado;
      integrantesEdicion.clear();
      (Array.isArray(equipo.integrantes_actuales) ? equipo.integrantes_actuales : []).forEach(function (integrante) {
        integrantesEdicion.set(integrante.usuario, { nombre_usuario: integrante.usuario, nombre: integrante.nombre, es_actual: true });
      });
      (Array.isArray(equipo.invitaciones_pendientes) ? equipo.invitaciones_pendientes : []).forEach(function (invitacion) {
        if (!integrantesEdicion.has(invitacion.usuario)) {
          integrantesEdicion.set(invitacion.usuario, { nombre_usuario: invitacion.usuario, nombre: invitacion.nombre, es_actual: false });
        }
      });
      renderIntegrantesEdicion();
      if (editarIntegrante) editarIntegrante.value = '';
      mostrarMensaje(editarMensajeIntegrante, '', '');
      mostrarMensaje(mensajeEditar, 'Edita los datos o el plantel actual y guarda los cambios.', 'informativo');
    } catch (error) {
      mostrarMensaje(mensajeEditar, error.message, 'error');
    }
  }

  if (formularioEditar) {
    formularioEditar.addEventListener('submit', async function (evento) {
      evento.preventDefault();
      if (integrantesEdicion.size < 2) {
        mostrarMensaje(mensajeEditar, 'El equipo debe conservar al menos dos lugares entre integrantes actuales e invitaciones pendientes.', 'error');
        return;
      }
      const boton = formularioEditar.querySelector('[type="submit"]');
      if (boton) boton.disabled = true;
      mostrarMensaje(mensajeEditar, 'Guardando cambios...', 'informativo');
      try {
        const respuesta = await fetch('api/equipo_actualizar.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
          body: JSON.stringify({
            id_equipo: Number(editarId.value),
            nombre: editarNombre.value.trim(),
            responsable_usuario: editarResponsable.value.trim(),
            estado: editarEstado.value,
            integrantes: Array.from(integrantesEdicion.entries()).filter(function (entrada) { return entrada[1].es_actual; }).map(function (entrada) { return entrada[0]; }),
            invitados: Array.from(integrantesEdicion.entries()).filter(function (entrada) { return !entrada[1].es_actual; }).map(function (entrada) { return entrada[0]; })
          })
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo actualizar el equipo.');
        if (editarEquipoImagen && editarEquipoImagen.files && editarEquipoImagen.files[0]) await subirImagenEquipo(Number(editarId.value), editarEquipoImagen.files[0]);
        const idActualizado = Number(editarId.value);
        mostrarMensaje(mensajeEditar, resultado.mensaje, 'exito');
        await cargarDatos(true);
        if (window.ArenaCJDResaltar) window.ArenaCJDResaltar(lista.querySelector('[data-id-equipo="' + idActualizado + '"]'));
        window.setTimeout(function () { cerrarModal(modalEditar); }, 450);
      } catch (error) {
        mostrarMensaje(mensajeEditar, error.message, 'error');
      } finally {
        if (boton) boton.disabled = false;
      }
    });
  }

  if (editarAgregarIntegrante) editarAgregarIntegrante.addEventListener('click', agregarIntegranteEdicion);
  if (editarIntegrante) editarIntegrante.addEventListener('keydown', function (evento) {
    if (evento.key === 'Enter') {
      evento.preventDefault();
      agregarIntegranteEdicion();
    }
  });

  async function eliminarEquipo(id) {
    const equipo = equipos.find(function (item) { return Number(item.id_equipo) === Number(id); });
    const nombre = equipo ? equipo.nombre : 'este equipo';
    const tieneHistorial = Boolean(equipo && equipo.ultima_inscripcion_id);
    const confirmar = window.ArenaCJDConfirmar ? await window.ArenaCJDConfirmar(tieneHistorial
      ? {titulo:'Archivar equipo',mensaje:'“' + nombre + '” ya tiene historial competitivo. Se archivará para impedir nuevas inscripciones, pero sus torneos, integrantes históricos y resultados se conservarán.',confirmar:'Archivar equipo'}
      : {titulo:'Eliminar equipo',mensaje:'¿Eliminar “' + nombre + '”? Como nunca participó en un torneo, se eliminará su plantilla actual.',confirmar:'Eliminar definitivamente'}) : false;
    if (!confirmar) return;
    try {
      const respuesta = await fetch('api/equipo_eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ id_equipo: Number(id) })
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo retirar el equipo.');
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(resultado.mensaje || 'Equipo actualizado.', 'exito');
      await cargarDatos(false);
    } catch (error) {
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(error.message, 'error'); else console.error(error.message);
    }
  }

  async function obtenerDetalleIndividual(id) {
    const respuesta = await fetch('api/inscripcion_individual_detalle.php?id=' + encodeURIComponent(id), { cache: 'no-store' });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo cargar la inscripción.');
    csrfToken = resultado.csrf_token || csrfToken;
    return resultado;
  }

  async function verDetalleIndividual(id) {
    if (contenidoDetalleIndividual) contenidoDetalleIndividual.innerHTML = '<p class="mensaje-detalle-equipo">Cargando inscripción...</p>';
    abrirModal(modalDetalleIndividual);
    try {
      const resultado = await obtenerDetalleIndividual(id);
      const i = resultado.inscripcion;
      if (tituloDetalleIndividual) tituloDetalleIndividual.textContent = i.nombre_completo + ' (@' + i.nombre_usuario + ')';
      contenidoDetalleIndividual.innerHTML = '<article class="inscripcion-detalle-equipo"><div class="cabecera-inscripcion-detalle-equipo"><div><h3>' + escaparHTML(i.torneo) + '</h3><p>' + escaparHTML(i.disciplina) + ' · ' + escaparHTML(i.categoria) + ' · ' + escaparHTML(i.tipo_torneo) + '</p></div><span class="estado-inscripcion ' + claseEstado(i.estado) + '"><span data-icono="circulo" aria-hidden="true"></span> ' + escaparHTML(etiquetaEstado(i.estado)) + '</span></div><div class="datos-equipo"><span><b>Participante</b> ' + escaparHTML(i.nombre_completo) + '</span><span><b>@usuario</b> @' + escaparHTML(i.nombre_usuario) + '</span><span><b>Solicitud</b> ' + escaparHTML(formatearFechaHora(i.fecha_inscripcion)) + '</span><span><b>Modalidad</b> Individual</span></div></article>';
    } catch (error) {
      contenidoDetalleIndividual.innerHTML = '<p class="mensaje-detalle-equipo">' + escaparHTML(error.message) + '</p>';
    }
  }

  async function abrirEditarIndividual(id) {
    mostrarMensaje(mensajeEditarIndividual, 'Cargando inscripción...', 'informativo');
    abrirModal(modalEditarIndividual);
    try {
      const resultado = await obtenerDetalleIndividual(id);
      if (!resultado.puede_editar) throw new Error('No tienes permiso para editar esta inscripción.');
      const i = resultado.inscripcion;
      editarIndividualId.value = i.id_inscripcion;
      editarIndividualUsuario.value = '@' + i.nombre_usuario;
      editarIndividualUsuario.disabled = !resultado.puede_gestionar_estado;
      editarIndividualEstado.disabled = !resultado.puede_gestionar_estado;
      editarIndividualEstado.value = i.estado;
      editarIndividualTorneo.innerHTML = (resultado.torneos || []).map(function (t) {
        return '<option value="' + t.id_torneo + '"' + (String(t.id_torneo) === String(i.id_torneo) ? ' selected' : '') + '>' + escaparHTML(t.nombre) + '</option>';
      }).join('');
      mostrarMensaje(mensajeEditarIndividual, resultado.puede_gestionar_estado ? 'Puedes modificar participante, torneo y estado.' : 'Puedes cambiar tu torneo mientras la inscripción siga pendiente.', 'informativo');
    } catch (error) {
      mostrarMensaje(mensajeEditarIndividual, error.message, 'error');
    }
  }

  if (formularioEditarIndividual) {
    formularioEditarIndividual.addEventListener('submit', async function (evento) {
      evento.preventDefault();
      const boton = formularioEditarIndividual.querySelector('[type="submit"]');
      if (boton) boton.disabled = true;
      mostrarMensaje(mensajeEditarIndividual, 'Guardando cambios...', 'informativo');
      try {
        const respuesta = await fetch('api/inscripcion_individual_actualizar.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
          body: JSON.stringify({
            id_inscripcion: Number(editarIndividualId.value),
            id_torneo: Number(editarIndividualTorneo.value),
            nombre_usuario: editarIndividualUsuario.value.trim(),
            estado: editarIndividualEstado.value
          })
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo actualizar la inscripción.');
        const idActualizado = Number(editarIndividualId.value);
        mostrarMensaje(mensajeEditarIndividual, resultado.mensaje, 'exito');
        await cargarDatos(true);
        if (window.ArenaCJDResaltar) window.ArenaCJDResaltar(lista.querySelector('[data-id-inscripcion="' + idActualizado + '"]'));
        window.setTimeout(function () { cerrarModal(modalEditarIndividual); }, 450);
      } catch (error) {
        mostrarMensaje(mensajeEditarIndividual, error.message, 'error');
      } finally {
        if (boton) boton.disabled = false;
      }
    });
  }

  async function eliminarIndividual(id) {
    const inscripcion = individuales.find(function (item) { return Number(item.id_inscripcion) === Number(id); });
    const nombre = inscripcion ? inscripcion.nombre_completo + ' en ' + inscripcion.torneo : 'esta inscripción';
    const confirmar = window.ArenaCJDConfirmar ? await window.ArenaCJDConfirmar({titulo:'Eliminar inscripción',mensaje:'¿Eliminar la inscripción de ' + nombre + '?',confirmar:'Eliminar inscripción'}) : false;
    if (!confirmar) return;
    try {
      const respuesta = await fetch('api/inscripcion_individual_eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ id_inscripcion: Number(id) })
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo eliminar la inscripción.');
      await cargarDatos(false);
    } catch (error) {
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(error.message, 'error'); else console.error(error.message);
    }
  }

  async function verificarUsuario(valor, elementoMensaje) {
    const usuario = normalizarUsuario(valor);
    if (!validarFormatoUsuario(usuario)) {
      mostrarMensaje(elementoMensaje, 'Escribe un @usuario válido.', 'error');
      return null;
    }
    mostrarMensaje(elementoMensaje, 'Verificando usuario...', 'informativo');
    try {
      const respuesta = await fetch('api/usuario_verificar.php?usuario=' + encodeURIComponent(usuario), { cache: 'no-store' });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'El usuario no pudo verificarse.');
      mostrarMensaje(elementoMensaje, 'Usuario verificado: ' + resultado.usuario.nombre + ' (@' + resultado.usuario.nombre_usuario + ').', 'exito');
      return resultado.usuario;
    } catch (error) {
      mostrarMensaje(elementoMensaje, error.message, 'error');
      return null;
    }
  }

  function renderIntegrantesSeleccionados() {
    if (!listaIntegrantes) return;
    listaIntegrantes.innerHTML = '';
    integrantesSeleccionados.forEach(function (usuario, alias) {
      const elemento = document.createElement('div');
      elemento.className = 'integrante-seleccionado';
      elemento.innerHTML = '<div><strong>@' + escaparHTML(alias) + '</strong><span>' + escaparHTML(usuario.nombre) + '</span></div><button type="button" aria-label="Quitar @' + escaparHTML(alias) + '"><span data-icono="peligro" aria-hidden="true"></span></button>';
      elemento.querySelector('button').addEventListener('click', function () {
        integrantesSeleccionados.delete(alias);
        renderIntegrantesSeleccionados();
      });
      listaIntegrantes.appendChild(elemento);
    });
    if (contadorIntegrantes) contadorIntegrantes.textContent = integrantesSeleccionados.size + (integrantesSeleccionados.size === 1 ? ' integrante' : ' integrantes');
  }

  async function agregarIntegrante() {
    const verificado = await verificarUsuario(usuarioIntegrante.value, mensajeIntegrante);
    if (!verificado) return;
    if (integrantesSeleccionados.has(verificado.nombre_usuario)) {
      mostrarMensaje(mensajeIntegrante, 'Ese usuario ya fue agregado.', 'error');
      return;
    }
    integrantesSeleccionados.set(verificado.nombre_usuario, verificado);
    usuarioIntegrante.value = '';
    mostrarMensaje(mensajeIntegrante, '@' + verificado.nombre_usuario + ' fue agregado al equipo.', 'exito');
    renderIntegrantesSeleccionados();
  }

  function modalidadRegistroActual() {
    const seleccionado = formularioRegistro && formularioRegistro.querySelector('input[name="modalidadRegistro"]:checked');
    return seleccionado ? seleccionado.value : 'equipo';
  }

  function esInvitacionIndividualActual() {
    if (modalidadRegistroActual() !== 'individual' || !puedeInvitarDesdeRegistro || !usuarioIndividualVerificado) return false;
    const verificado = String(usuarioIndividualVerificado.nombre_usuario || '').toLowerCase();
    const actual = String(usuarioSesion.nombre_usuario || '').toLowerCase();
    return Boolean(verificado && actual && verificado !== actual);
  }

  function actualizarAccionRegistro() {
    if (!botonGuardarRegistro) return;
    if (esInvitacionIndividualActual()) {
      botonGuardarRegistro.textContent = 'Enviar invitación';
      return;
    }
    if (modalidadRegistroActual() === 'equipo' && equipoExistenteRegistro && Number(equipoExistenteRegistro.value)) {
      const equipo = equipos.find(function (item) { return Number(item.id_equipo) === Number(equipoExistenteRegistro.value); });
      const esResponsable = equipo && Number(equipo.id_creador) === Number(usuarioSesion.id || 0);
      botonGuardarRegistro.textContent = esResponsable ? 'Solicitar inscripción' : 'Enviar invitación';
      return;
    }
    botonGuardarRegistro.textContent = 'Guardar inscripción';
  }

  function normalizarTorneoRegistro(torneo) {
    const modalidadBase = String(torneo.modalidadBD || torneo.modalidad || 'individual').toLowerCase();
    const estadoBase = String(torneo.estadoBD || torneo.estado || 'borrador').toLowerCase();
    const cupoBase = Object.prototype.hasOwnProperty.call(torneo, 'cupoMaximo') ? torneo.cupoMaximo : torneo.cupo_maximo;
    const inscritosBase = Object.prototype.hasOwnProperty.call(torneo, 'cantidadInscritos')
      ? torneo.cantidadInscritos
      : (Object.prototype.hasOwnProperty.call(torneo, 'cantidadParticipantes') ? torneo.cantidadParticipantes : torneo.cantidad_inscritos);
    return {
      id: String(torneo.id_torneo != null ? torneo.id_torneo : torneo.id),
      nombre: torneo.nombre || '',
      disciplina: torneo.disciplina || '',
      categoria: torneo.categoria || '',
      modalidadBD: modalidadBase === 'equipo' || modalidadBase === 'por equipos' ? 'equipo' : 'individual',
      estadoBD: estadoBase === 'inscripciones' ? 'inscripciones' : estadoBase.replace('en curso', 'en_curso'),
      cupoMaximo: cupoBase == null || cupoBase === '' ? null : Number(cupoBase),
      cantidadInscritos: Number(inscritosBase || 0)
    };
  }

  function torneosDisponiblesRegistro() {
    const fuente = torneosRegistroDirectos.length
      ? torneosRegistroDirectos
      : (window.ArenaCJDDatos && Array.isArray(window.ArenaCJDDatos.torneos) ? window.ArenaCJDDatos.torneos : []);
    return fuente.map(normalizarTorneoRegistro);
  }

  async function cargarTorneosRegistroDirecto(mostrarEstado) {
    if (!torneoRegistro) return [];
    if (mostrarEstado) mostrarMensaje(mensajeRegistro, 'Actualizando torneos disponibles...', 'informativo');
    try {
      const respuesta = await fetch('api/torneos.php?_=' + Date.now(), {cache: 'no-store', headers: {Accept: 'application/json'}});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito || !Array.isArray(resultado.torneos)) {
        throw new Error(resultado.mensaje || 'No se pudieron cargar los torneos.');
      }
      torneosRegistroDirectos = resultado.torneos.map(normalizarTorneoRegistro);
      actualizarTorneosRegistro();
      if (mostrarEstado) mostrarMensaje(mensajeRegistro, '', '');
      return torneosRegistroDirectos;
    } catch (error) {
      torneosRegistroDirectos = [];
      actualizarTorneosRegistro();
      if (mostrarEstado) mostrarMensaje(mensajeRegistro, error.message || 'No se pudieron cargar los torneos.', 'error');
      return [];
    }
  }

  function torneosBloqueadosParaUsuarioIndividual() {
    const bloqueados = new Set();
    if (!usuarioIndividualVerificado) return bloqueados;

    const idUsuario = Number(usuarioIndividualVerificado.id || 0);
    const alias = String(usuarioIndividualVerificado.nombre_usuario || '').toLowerCase();

    individuales.forEach(function (inscripcion) {
      const coincideId = idUsuario > 0 && Number(inscripcion.id_usuario) === idUsuario;
      const coincideAlias = alias && String(inscripcion.nombre_usuario || '').toLowerCase() === alias;
      if (coincideId || coincideAlias) bloqueados.add(String(inscripcion.id_torneo));
    });

    invitacionesEnviadas.forEach(function (invitacion) {
      if (invitacion.tipo !== 'individual' || !['pendiente', 'aceptada'].includes(invitacion.estado)) return;
      const coincideId = idUsuario > 0 && Number(invitacion.id_invitado) === idUsuario;
      const coincideAlias = alias && String(invitacion.invitado_usuario || '').toLowerCase() === alias;
      if (coincideId || coincideAlias) bloqueados.add(String(invitacion.id_torneo));
    });

    return bloqueados;
  }

  function actualizarTorneosRegistro() {
    if (!torneoRegistro) return;
    const modalidad = modalidadRegistroActual();
    const torneos = torneosDisponiblesRegistro();
    const anterior = torneoRegistro.value;
    const bloqueadosIndividual = modalidad === 'individual' ? torneosBloqueadosParaUsuarioIndividual() : new Set();
    const compatibles = torneos.filter(function (torneo) {
      const conCupo = torneo.cupoMaximo === null || torneo.cantidadInscritos < torneo.cupoMaximo;
      const yaInscritoEquipo = modalidad === 'equipo' && torneosYaInscritosEquipo.has(String(torneo.id));
      const yaRegistradoIndividual = modalidad === 'individual' && bloqueadosIndividual.has(String(torneo.id));
      return torneo.modalidadBD === modalidad && torneo.estadoBD === 'inscripciones' && conCupo && !yaInscritoEquipo && !yaRegistradoIndividual;
    });
    const botonGuardar = formularioRegistro ? formularioRegistro.querySelector('[type="submit"]') : null;
    if (!compatibles.length) {
      const mensajeSinTorneos = modalidad === 'equipo' && torneosYaInscritosEquipo.size
        ? 'No hay otros torneos abiertos disponibles'
        : (modalidad === 'individual' && usuarioIndividualVerificado && bloqueadosIndividual.size
          ? 'No hay otros torneos individuales disponibles'
          : 'No hay torneos abiertos con cupo disponible');
      torneoRegistro.innerHTML = '<option value="">' + mensajeSinTorneos + '</option>';
      torneoRegistro.value = '';
      torneoRegistro.disabled = true;
      if (categoriaRegistro) categoriaRegistro.value = 'Sin torneos disponibles';
      if (botonGuardar) botonGuardar.disabled = true;
      return;
    }
    torneoRegistro.disabled = false;
    if (botonGuardar) botonGuardar.disabled = false;
    torneoRegistro.innerHTML = '<option value="">Seleccionar torneo</option>' + compatibles.map(function (torneo) {
      const cupo = torneo.cupoMaximo === null ? 'sin límite' : (torneo.cantidadInscritos + '/' + torneo.cupoMaximo);
      const categoria = torneo.categoria || 'Sin categoría';
      return '<option value="' + escaparHTML(torneo.id) + '">' + escaparHTML(torneo.nombre) + ' — ' + escaparHTML(torneo.disciplina) + ' · ' + escaparHTML(categoria) + ' · cupo ' + escaparHTML(cupo) + '</option>';
    }).join('');
    if (compatibles.some(function (torneo) { return String(torneo.id) === String(anterior); })) torneoRegistro.value = anterior;
    actualizarCategoriaRegistro();
  }

  function actualizarCategoriaRegistro() {
    if (!categoriaRegistro || !torneoRegistro) return;
    const torneos = torneosDisponiblesRegistro();
    const torneo = torneos.find(function (item) { return String(item.id) === String(torneoRegistro.value); }) || null;
    categoriaRegistro.value = torneo
      ? (torneo.categoria || 'Sin categoría')
      : (torneoRegistro.options.length === 1 && !torneoRegistro.value ? 'Sin torneos disponibles' : 'Selecciona un torneo');
  }

  function actualizarModalidadRegistro() {
    const individual = modalidadRegistroActual() === 'individual';
    if (individual) torneosYaInscritosEquipo = new Set();
    gruposEquipo.forEach(function (grupo) { grupo.hidden = individual; });
    gruposIndividual.forEach(function (grupo) {
      grupo.hidden = !individual || !puedeInvitarDesdeRegistro;
    });
    if (equipoExistenteRegistro) {
      equipoExistenteRegistro.disabled = individual;
      equipoExistenteRegistro.required = !individual;
    }

    if (individual && !puedeInvitarDesdeRegistro && usuarioSesion.nombre_usuario) {
      usuarioIndividualVerificado = {
        nombre_usuario: usuarioSesion.nombre_usuario,
        nombre: usuarioSesion.nombre_completo || usuarioSesion.nombre_usuario
      };
      mostrarMensaje(
        mensajeUsuarioIndividual,
        'La inscripción usará tu cuenta @' + usuarioSesion.nombre_usuario + '.',
        'informativo'
      );
    } else {
      usuarioIndividualVerificado = null;
      mostrarMensaje(mensajeUsuarioIndividual, '', '');
    }

    integrantesSeleccionados.clear();
    renderIntegrantesSeleccionados();
    mostrarMensaje(mensajeIntegrante, '', '');
    mostrarMensaje(mensajeRegistro, '', '');
    actualizarAccionRegistro();
    actualizarTorneosRegistro();
  }

  function restaurarPresentacionRegistro() {
    modoInscripcionEquipoDirecta = false;
    torneosYaInscritosEquipo = new Set();
    tokenCargaEquipoRegistro += 1;
    if (grupoModalidadRegistro) grupoModalidadRegistro.hidden = false;
    if (equipoExistenteRegistro) equipoExistenteRegistro.disabled = false;
    if (etiquetaRegistro) etiquetaRegistro.textContent = 'Nueva inscripción';
    if (tituloRegistro) tituloRegistro.textContent = 'Registrar participante';
    if (descripcionRegistro) descripcionRegistro.textContent = 'Completa los datos necesarios para solicitar la inscripción.';
  }

  async function abrirModalRegistro() {
    if (!modalRegistro || !formularioRegistro) return;
    restaurarPresentacionRegistro();
    formularioRegistro.reset();

    const radioIndividual = formularioRegistro.querySelector('input[name="modalidadRegistro"][value="individual"]');
    if (radioIndividual) radioIndividual.checked = true;
    if (grupoModalidadRegistro) grupoModalidadRegistro.hidden = true;
    if (etiquetaRegistro) etiquetaRegistro.textContent = 'Inscripción individual';
    if (tituloRegistro) tituloRegistro.textContent = puedeInvitarDesdeRegistro ? 'Nueva inscripción individual' : 'Inscribirme en torneo';
    if (descripcionRegistro) {
      descripcionRegistro.textContent = puedeInvitarDesdeRegistro
        ? 'Selecciona un torneo individual y tu cuenta u otro usuario para gestionar su participación.'
        : 'Selecciona un torneo individual con inscripciones abiertas para solicitar tu participación.';
    }

    abrirModal(modalRegistro);
    actualizarModalidadRegistro();
    actualizarEquiposReutilizables();
    await cargarTorneosRegistroDirecto(true);
    window.setTimeout(function () { if (torneoRegistro) torneoRegistro.focus(); }, 0);
  }

  async function abrirModalReutilizarIndividual(idInscripcion) {
    const inscripcion = individuales.find(function (item) { return Number(item.id_inscripcion) === Number(idInscripcion); });
    if (!inscripcion) {
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar('No se pudo localizar al participante seleccionado.', 'error');
      return;
    }

    await abrirModalRegistro();
    usuarioIndividualVerificado = {
      id: Number(inscripcion.id_usuario),
      nombre_usuario: inscripcion.nombre_usuario,
      nombre: inscripcion.nombre_completo
    };
    if (usuarioIndividualRegistro) usuarioIndividualRegistro.value = '@' + inscripcion.nombre_usuario;
    mostrarMensaje(
      mensajeUsuarioIndividual,
      inscripcion.nombre_completo + ' (@' + inscripcion.nombre_usuario + ') puede participar en varios torneos. Selecciona otro torneo para enviarle una nueva invitación.',
      'exito'
    );
    actualizarAccionRegistro();
    actualizarTorneosRegistro();
    if (torneoRegistro && !torneoRegistro.disabled) torneoRegistro.focus();
  }

  async function abrirModalInscribirEquipo(idEquipo) {
    if (!modalRegistro || !formularioRegistro) return;
    const equipo = equipos.find(function (item) { return Number(item.id_equipo) === Number(idEquipo); });
    if (!equipo) {
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar('No se pudo localizar el equipo seleccionado.', 'error');
      return;
    }

    restaurarPresentacionRegistro();
    formularioRegistro.reset();
    modoInscripcionEquipoDirecta = true;
    const radioEquipo = formularioRegistro.querySelector('input[name="modalidadRegistro"][value="equipo"]');
    if (radioEquipo) radioEquipo.checked = true;
    if (grupoModalidadRegistro) grupoModalidadRegistro.hidden = true;
    if (etiquetaRegistro) etiquetaRegistro.textContent = 'Inscripción de equipo';
    if (tituloRegistro) tituloRegistro.textContent = 'Inscribir equipo en torneo';
    if (descripcionRegistro) descripcionRegistro.textContent = 'Selecciona un torneo por equipos con inscripciones abiertas para “' + equipo.nombre + '”.';

    abrirModal(modalRegistro);
    actualizarModalidadRegistro();
    actualizarEquiposReutilizables();
    if (equipoExistenteRegistro) {
      equipoExistenteRegistro.value = String(equipo.id_equipo);
      equipoExistenteRegistro.disabled = true;
    }
    actualizarAccionRegistro();
    await actualizarTorneosSegunEquipoSeleccionado();
    await cargarTorneosRegistroDirecto(true);
    actualizarTorneosRegistro();

    if (torneoRegistro && torneoRegistro.disabled) {
      mostrarMensaje(mensajeRegistro, 'Este equipo no tiene torneos por equipos disponibles para inscribirse. Revisa que exista un torneo en estado Inscripciones, con cupo y en el que el equipo aún no esté registrado.', 'informativo');
    }
    window.setTimeout(function () { if (torneoRegistro && !torneoRegistro.disabled) torneoRegistro.focus(); }, 0);
  }

  function cerrarModalRegistro() {
    cerrarModal(modalRegistro);
    if (formularioRegistro) formularioRegistro.reset();
    integrantesSeleccionados.clear();
    usuarioIndividualVerificado = null;
    restaurarPresentacionRegistro();
    renderIntegrantesSeleccionados();
    actualizarModalidadRegistro();
    actualizarAccionRegistro();
  }

  document.addEventListener('click', function (evento) {
    const botonRegistro = evento.target.closest('#abrirRegistroParticipante');
    if (!botonRegistro) return;
    evento.preventDefault();
    abrirModalRegistro();
  });
  cerrarRegistro.forEach(function (boton) { boton.addEventListener('click', cerrarModalRegistro); });
  radiosModalidad.forEach(function (radio) { radio.addEventListener('change', actualizarModalidadRegistro); });
  if (torneoRegistro) torneoRegistro.addEventListener('change', actualizarCategoriaRegistro);
  if (equipoExistenteRegistro) equipoExistenteRegistro.addEventListener('change', function () {
    actualizarModoEquipoRegistro();
    actualizarAccionRegistro();
    actualizarTorneosSegunEquipoSeleccionado();
  });
  if (verificarIndividual) verificarIndividual.addEventListener('click', async function () {
    usuarioIndividualVerificado = await verificarUsuario(usuarioIndividualRegistro.value, mensajeUsuarioIndividual);
    if (usuarioIndividualVerificado) usuarioIndividualRegistro.value = '@' + usuarioIndividualVerificado.nombre_usuario;
    actualizarAccionRegistro();
    actualizarTorneosRegistro();
  });
  if (usuarioIndividualRegistro) usuarioIndividualRegistro.addEventListener('input', function () { usuarioIndividualVerificado = null; mostrarMensaje(mensajeUsuarioIndividual, '', ''); actualizarAccionRegistro(); actualizarTorneosRegistro(); });
  if (verificarIntegrante) verificarIntegrante.addEventListener('click', agregarIntegrante);
  if (usuarioIntegrante) usuarioIntegrante.addEventListener('keydown', function (evento) { if (evento.key === 'Enter') { evento.preventDefault(); agregarIntegrante(); } });

  if (formularioRegistro) {
    formularioRegistro.addEventListener('submit', async function (evento) {
      evento.preventDefault();
      const modalidad = modalidadRegistroActual();
      const idTorneo = Number(torneoRegistro.value);
      if (!idTorneo) {
        mostrarMensaje(mensajeRegistro, 'Selecciona un torneo con inscripciones abiertas.', 'error');
        return;
      }
      if (modalidad === 'individual' && !usuarioIndividualVerificado) {
        mostrarMensaje(mensajeRegistro, puedeInvitarDesdeRegistro ? 'Verifica primero el @usuario que se va a inscribir o invitar.' : 'No se pudo identificar tu cuenta.', 'error');
        return;
      }
      const idEquipoExistente = modalidad === 'equipo' && equipoExistenteRegistro ? Number(equipoExistenteRegistro.value) : 0;
      if (modalidad === 'equipo' && !idEquipoExistente) {
        mostrarMensaje(mensajeRegistro, 'Selecciona un equipo existente. Si todavía no tienes uno, créalo primero con el botón Crear equipo.', 'error');
        return;
      }
      const boton = formularioRegistro.querySelector('[type="submit"]');
      if (boton) boton.disabled = true;
      mostrarMensaje(mensajeRegistro, 'Guardando inscripción...', 'informativo');
      try {
        const esInvitacionIndividual = modalidad === 'individual' && esInvitacionIndividualActual();
        const endpoint = esInvitacionIndividual ? 'api/invitacion_crear.php' : 'api/inscripcion_registrar.php';
        const cuerpo = esInvitacionIndividual
          ? {
              tipo: 'individual',
              id_torneo: idTorneo,
              nombre_usuario: usuarioIndividualVerificado.nombre_usuario
            }
          : {
              modalidad: modalidad,
              accion: 'inscribir',
              id_torneo: idTorneo,
              nombre_usuario: modalidad === 'individual' ? usuarioIndividualVerificado.nombre_usuario : '',
              id_equipo: modalidad === 'equipo' ? idEquipoExistente : 0
            };
        const respuesta = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
          body: JSON.stringify(cuerpo)
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo registrar la inscripción.');
        mostrarMensaje(mensajeRegistro, resultado.mensaje, 'exito');
        if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(resultado.tipo === 'invitacion' ? 'Invitación enviada. Puedes seguir su estado en esta pantalla.' : 'Inscripción registrada. Puedes seguir su estado en esta pantalla.', 'exito');
        if (resultado.tipo === 'invitacion') {
          window.dispatchEvent(new CustomEvent('arenacjd:invitaciones-actualizadas'));
          if (window.ArenaCJDActualizarNotificaciones) window.ArenaCJDActualizarNotificaciones(false);
          if (puedeInvitarDesdeRegistro) {
            tipoActual = 'enviadas';
            pestanas.forEach(function (p) {
              const activa = p.dataset.tipoParticipante === tipoActual;
              p.classList.toggle('activa', activa);
              p.setAttribute('aria-selected', activa ? 'true' : 'false');
            });
          }
        }
        if (resultado.tipo !== 'invitacion') {
          tipoActual = modalidad === 'equipo' ? 'equipos' : 'individuales';
          pestanas.forEach(function (p) {
            const activa = p.dataset.tipoParticipante === tipoActual;
            p.classList.toggle('activa', activa);
            p.setAttribute('aria-selected', activa ? 'true' : 'false');
          });
        }
        await cargarDatos(false);
        window.setTimeout(cerrarModalRegistro, 650);
      } catch (error) {
        mostrarMensaje(mensajeRegistro, error.message, 'error');
      } finally {
        if (boton) boton.disabled = false;
      }
    });
  }


  function renderIntegrantesNuevoEquipo() {
    if (!crearEquipoListaIntegrantes) return;
    crearEquipoListaIntegrantes.innerHTML = '';
    integrantesNuevoEquipo.forEach(function (usuario, alias) {
      const fila = document.createElement('div');
      fila.className = 'integrante-registro';
      fila.innerHTML = '<div><strong>' + escaparHTML(usuario.nombre || alias) + '</strong><span>@' + escaparHTML(alias) + ' · invitación pendiente al crear</span></div><button type="button" aria-label="Quitar invitación a @' + escaparHTML(alias) + '"><span data-icono="peligro" aria-hidden="true"></span></button>';
      fila.querySelector('button').addEventListener('click', function () {
        integrantesNuevoEquipo.delete(alias);
        renderIntegrantesNuevoEquipo();
      });
      crearEquipoListaIntegrantes.appendChild(fila);
    });
    if (crearEquipoContadorIntegrantes) {
      crearEquipoContadorIntegrantes.textContent = integrantesNuevoEquipo.size + (integrantesNuevoEquipo.size === 1 ? ' invitación preparada' : ' invitaciones preparadas');
    }
  }

  async function agregarIntegranteNuevoEquipo() {
    if (!crearEquipoIntegrante) return;
    const usuario = await verificarUsuario(crearEquipoIntegrante.value, crearEquipoMensajeIntegrante);
    if (!usuario) return;
    if (usuario.nombre_usuario === usuarioSesion.nombre_usuario) {
      mostrarMensaje(crearEquipoMensajeIntegrante, 'Tu cuenta se controla con la opción “Participar también como integrante”.', 'informativo');
      crearEquipoIntegrante.value = '';
      return;
    }
    if (integrantesNuevoEquipo.has(usuario.nombre_usuario)) {
      mostrarMensaje(crearEquipoMensajeIntegrante, 'Ese usuario ya tiene una invitación preparada.', 'error');
      return;
    }
    integrantesNuevoEquipo.set(usuario.nombre_usuario, usuario);
    crearEquipoIntegrante.value = '';
    mostrarMensaje(crearEquipoMensajeIntegrante, 'Invitación preparada para @' + usuario.nombre_usuario + '. No será integrante hasta que acepte.', 'exito');
    renderIntegrantesNuevoEquipo();
  }

  function abrirModalCrearEquipo() {
    if (!modalCrearEquipo) return;
    integrantesNuevoEquipo.clear();
    if (formularioCrearEquipo) formularioCrearEquipo.reset();
    ocultarPreviewEquipo(previewCrearEquipoImagen, null, 'crear');
    if (crearEquipoIncluirResponsable) {
      crearEquipoIncluirResponsable.checked = !esAdministradorSesion;
      crearEquipoIncluirResponsable.disabled = esAdministradorSesion;
    }
    renderIntegrantesNuevoEquipo();
    mostrarMensaje(crearEquipoMensajeIntegrante, '', '');
    mostrarMensaje(crearEquipoMensaje, '', '');
    abrirModal(modalCrearEquipo);
    window.setTimeout(function () {
      if (crearEquipoNombre) crearEquipoNombre.focus();
    }, 0);
  }

  function cerrarModalCrearEquipo() {
    cerrarModal(modalCrearEquipo);
    integrantesNuevoEquipo.clear();
    if (formularioCrearEquipo) formularioCrearEquipo.reset();
    ocultarPreviewEquipo(previewCrearEquipoImagen, null, 'crear');
    renderIntegrantesNuevoEquipo();
  }

  if (crearEquipoImagen) crearEquipoImagen.addEventListener('change', function () {
    const archivo = crearEquipoImagen.files && crearEquipoImagen.files[0];
    if (!archivo) { ocultarPreviewEquipo(previewCrearEquipoImagen, null, 'crear'); return; }
    liberarUrlPreview('crear');
    urlPreviewCrearEquipo = URL.createObjectURL(archivo);
    mostrarPreviewEquipo(previewCrearEquipoImagen, urlPreviewCrearEquipo, null, false);
  });

  if (editarEquipoImagen) editarEquipoImagen.addEventListener('change', function () {
    const archivo = editarEquipoImagen.files && editarEquipoImagen.files[0];
    if (!archivo) {
      if (editarId && editarId.value) mostrarPreviewEquipo(previewEditarEquipoImagen, 'api/imagen_equipo.php?id_equipo=' + Number(editarId.value) + '&_=' + Date.now(), quitarEquipoImagen, true);
      return;
    }
    liberarUrlPreview('editar');
    urlPreviewEditarEquipo = URL.createObjectURL(archivo);
    mostrarPreviewEquipo(previewEditarEquipoImagen, urlPreviewEditarEquipo, quitarEquipoImagen, false);
  });

  if (quitarEquipoImagen) quitarEquipoImagen.addEventListener('click', async function () {
    const idEquipo = Number(editarId && editarId.value || 0);
    if (!idEquipo) return;
    const confirmar = window.ArenaCJDConfirmar ? await window.ArenaCJDConfirmar({titulo:'Quitar imagen del equipo',mensaje:'La imagen dejará de mostrarse en ArenaCJD. El equipo, sus integrantes y su historial se conservarán.',confirmar:'Quitar imagen'}) : true;
    if (!confirmar) return;
    try {
      const respuesta = await fetch('api/equipo_imagen_eliminar.php', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({id_equipo:idEquipo})});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo quitar la imagen.');
      if (editarEquipoImagen) editarEquipoImagen.value = '';
      ocultarPreviewEquipo(previewEditarEquipoImagen, quitarEquipoImagen, 'editar');
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(resultado.mensaje || 'Imagen eliminada.', 'exito');
      await cargarDatos(false);
    } catch (error) {
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(error.message, 'error');
    }
  });

  if (abrirCrearEquipo) abrirCrearEquipo.addEventListener('click', abrirModalCrearEquipo);
  cerrarCrearEquipo.forEach(function (boton) {
    boton.addEventListener('click', cerrarModalCrearEquipo);
  });
  if (crearEquipoAgregarIntegrante) {
    crearEquipoAgregarIntegrante.addEventListener('click', agregarIntegranteNuevoEquipo);
  }
  if (crearEquipoIntegrante) {
    crearEquipoIntegrante.addEventListener('keydown', function (evento) {
      if (evento.key === 'Enter') {
        evento.preventDefault();
        agregarIntegranteNuevoEquipo();
      }
    });
  }
  if (formularioCrearEquipo) {
    formularioCrearEquipo.addEventListener('submit', async function (evento) {
      evento.preventDefault();
      const incluirResponsable = Boolean(crearEquipoIncluirResponsable && crearEquipoIncluirResponsable.checked && !esAdministradorSesion);
      const cantidadTotal = integrantesNuevoEquipo.size + (incluirResponsable ? 1 : 0);
      if (cantidadTotal < 2) {
        mostrarMensaje(crearEquipoMensaje, 'El equipo debe quedar planificado con al menos dos integrantes entre tu cuenta e invitaciones.', 'error');
        return;
      }

      const boton = formularioCrearEquipo.querySelector('[type="submit"]');
      if (boton) boton.disabled = true;
      mostrarMensaje(crearEquipoMensaje, 'Creando equipo...', 'informativo');

      try {
        const respuesta = await fetch('api/equipo_crear.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
          body: JSON.stringify({
            nombre: crearEquipoNombre.value.trim(),
            incluir_responsable: incluirResponsable,
            invitados: Array.from(integrantesNuevoEquipo.keys())
          })
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) {
          throw new Error(resultado.mensaje || 'No se pudo crear el equipo.');
        }
        if (crearEquipoImagen && crearEquipoImagen.files && crearEquipoImagen.files[0]) await subirImagenEquipo(Number(resultado.id_equipo), crearEquipoImagen.files[0]);
        mostrarMensaje(crearEquipoMensaje, resultado.mensaje, 'exito');
        if (window.ArenaCJDAvisar) window.ArenaCJDAvisar('Equipo creado. Ahora puedes reutilizarlo para solicitar una inscripción a un torneo.', 'exito');
        await cargarDatos(false);
        actualizarEquiposReutilizables();
        window.setTimeout(cerrarModalCrearEquipo, 650);
      } catch (error) {
        mostrarMensaje(crearEquipoMensaje, error.message || 'No se pudo crear el equipo.', 'error');
      } finally {
        if (boton) boton.disabled = false;
      }
    });
  }



  async function actualizarInscripcionEquipo(idInscripcion, estado) {
    try {
      const respuesta = await fetch('api/inscripcion_equipo_actualizar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({id_inscripcion: Number(idInscripcion), estado: estado})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) {
        throw new Error(resultado.mensaje || 'No se pudo actualizar la inscripción.');
      }
      await cargarDatos(false);
    } catch (error) {
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(error.message || 'No se pudo actualizar la inscripción.', 'error'); else console.error(error.message);
    }
  }


  async function responderInvitacion(id, respuestaInvitacion, tipoInvitacion) {
    try {
      const esEquipo = tipoInvitacion === 'equipo';
      const respuesta = await fetch(esEquipo ? 'api/invitacion_equipo_responder.php' : 'api/invitacion_responder.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify(esEquipo
          ? {id_invitacion_equipo: Number(id), respuesta: respuestaInvitacion}
          : {id_invitacion: Number(id), respuesta: respuestaInvitacion})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo responder la invitación.');
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(resultado.mensaje || 'Invitación actualizada.', 'exito');
      await cargarDatos(true);
      window.dispatchEvent(new CustomEvent('arenacjd:invitaciones-actualizadas'));
    } catch (error) {
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(error.message || 'No se pudo responder la invitación.', 'error'); else console.error(error.message);
    }
  }

  lista.addEventListener('change', function (evento) {
    const check = evento.target.closest('[data-seleccionar-inscripcion]');
    if (!check) return;
    const id = Number(check.dataset.seleccionarInscripcion);
    if (check.checked) inscripcionesSeleccionadas.add(id); else inscripcionesSeleccionadas.delete(id);
    actualizarBarraAccionesMasivas();
  });

  lista.addEventListener('click', function (evento) {
    const botonInvitacionEquipo = evento.target.closest('[data-accion-invitacion-equipo]');
    if (botonInvitacionEquipo) {
      responderInvitacion(botonInvitacionEquipo.dataset.idInvitacionEquipo, botonInvitacionEquipo.dataset.accionInvitacionEquipo, 'equipo');
      return;
    }
    const botonInvitacion = evento.target.closest('[data-accion-invitacion]');
    if (botonInvitacion) {
      responderInvitacion(botonInvitacion.dataset.idInvitacion, botonInvitacion.dataset.accionInvitacion, 'torneo');
      return;
    }
    const botonEquipo = evento.target.closest('[data-accion-equipo]');
    if (botonEquipo) {
      const accion = botonEquipo.dataset.accionEquipo;
      const id = botonEquipo.dataset.idEquipo;
      if (accion === 'integrantes') verIntegrantes(id);
      if (accion === 'inscribir') abrirModalInscribirEquipo(id);
      if (accion === 'editar') abrirEditarEquipo(id);
      if (accion === 'eliminar') eliminarEquipo(id);
      if (accion === 'aprobar-inscripcion') actualizarInscripcionEquipo(botonEquipo.dataset.idInscripcion, 'aprobada');
      if (accion === 'rechazar-inscripcion') actualizarInscripcionEquipo(botonEquipo.dataset.idInscripcion, 'rechazada');
      return;
    }
    const botonIndividual = evento.target.closest('[data-accion-individual]');
    if (!botonIndividual) return;
    const accion = botonIndividual.dataset.accionIndividual;
    const id = botonIndividual.dataset.idInscripcion;
    if (accion === 'detalle') verDetalleIndividual(id);
    if (accion === 'reutilizar') abrirModalReutilizarIndividual(id);
    if (accion === 'editar') abrirEditarIndividual(id);
    if (accion === 'eliminar') eliminarIndividual(id);
  });

  pestanas.forEach(function (pestana) {
    pestana.addEventListener('click', function () {
      tipoActual = pestana.dataset.tipoParticipante;
      inscripcionesSeleccionadas.clear();
      pestanas.forEach(function (item) {
        const activa = item === pestana;
        item.classList.toggle('activa', activa);
        item.setAttribute('aria-selected', activa ? 'true' : 'false');
      });
      paginaActual = 1;
      actualizarDisciplinasFiltro();
      aplicarFiltros(false);
    });
  });

  [buscar, filtroDisciplina, filtroEstado].forEach(function (control) {
    if (!control) return;
    control.addEventListener(control === buscar ? 'input' : 'change', function () { aplicarFiltros(true); });
  });
  if (limpiar) limpiar.addEventListener('click', function () {
    if (buscar) buscar.value = '';
    if (filtroDisciplina) filtroDisciplina.value = 'todas';
    if (filtroEstado) filtroEstado.value = 'todos';
    aplicarFiltros(true);
  });

  if (paginacion) paginacion.addEventListener('click', function (evento) {
    const boton = evento.target.closest('[data-pagina-participante]');
    if (!boton) return;
    const accion = boton.dataset.paginaParticipante;
    const totalPaginas = Math.max(1, Math.ceil(filtrados.length / ITEMS_POR_PAGINA));
    if (accion === 'anterior') paginaActual -= 1;
    else if (accion === 'siguiente') paginaActual += 1;
    else paginaActual = Number(accion);
    paginaActual = Math.min(Math.max(1, paginaActual), totalPaginas);
    renderizarListado();
    lista.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  if (exportar) exportar.addEventListener('click', function () {
    const filas = tipoActual === 'equipos'
      ? [['Equipo', 'Responsable', 'Disciplina', 'Torneo', 'Estado']].concat(filtrados.map(function (e) { return [e.nombre, '@' + e.responsable_usuario, e.disciplina || '', e.torneo || '', etiquetaEstado(estadoRealEquipo(e))]; }))
      : (tipoActual === 'invitaciones'
        ? [['Torneo', 'Disciplina', 'Invitador', 'Estado', 'Fecha']].concat(filtrados.map(function (i) { return [i.torneo, i.disciplina, '@' + i.invitador_usuario, i.estado, i.fecha_creacion]; }))
        : (tipoActual === 'enviadas'
          ? [['Invitado', '@usuario', 'Torneo', 'Disciplina', 'Tipo', 'Estado', 'Fecha']].concat(filtrados.map(function (i) { return [i.invitado, '@' + i.invitado_usuario, i.torneo, i.disciplina, i.tipo, i.estado, i.fecha_creacion]; }))
          : [['Participante', '@usuario', 'Disciplina', 'Torneo', 'Estado']].concat(filtrados.map(function (i) { return [i.nombre_completo, '@' + i.nombre_usuario, i.disciplina, i.torneo, etiquetaEstado(i.estado)]; }))));
    const csv = filas.map(function (fila) { return fila.map(function (valor) { return '"' + String(valor == null ? '' : valor).replace(/"/g, '""') + '"'; }).join(','); }).join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const enlace = document.createElement('a');
    enlace.href = url;
    enlace.download = tipoActual === 'equipos' ? 'equipos-arenacjd.csv' : (tipoActual === 'invitaciones' ? 'mis-invitaciones-arenacjd.csv' : (tipoActual === 'enviadas' ? 'invitaciones-enviadas-arenacjd.csv' : 'inscripciones-individuales-arenacjd.csv'));
    enlace.click();
    URL.revokeObjectURL(url);
  });

  document.querySelectorAll('[data-cerrar-integrantes-equipo]').forEach(function (boton) { boton.addEventListener('click', function () { cerrarModal(modalIntegrantes); }); });
  document.querySelectorAll('[data-cerrar-editar-equipo]').forEach(function (boton) { boton.addEventListener('click', function () { cerrarModal(modalEditar); }); });
  document.querySelectorAll('[data-cerrar-detalle-individual]').forEach(function (boton) { boton.addEventListener('click', function () { cerrarModal(modalDetalleIndividual); }); });
  document.querySelectorAll('[data-cerrar-editar-individual]').forEach(function (boton) { boton.addEventListener('click', function () { cerrarModal(modalEditarIndividual); }); });
  document.addEventListener('keydown', function (evento) {
    if (evento.key === 'Escape') {
      [modalIntegrantes, modalEditar, modalDetalleIndividual, modalEditarIndividual, modalRegistro].forEach(cerrarModal);
    }
  });

  window.addEventListener('arenacjd:torneos-cargados', actualizarTorneosRegistro);
  if (window.location.hash === '#mis-invitaciones') {
    tipoActual = 'invitaciones';
  } else if (window.location.hash === '#registrar-participante' || window.location.hash === '#individuales') {
    tipoActual = 'individuales';
  }
  if (window.location.hash === '#mis-invitaciones' || window.location.hash === '#registrar-participante' || window.location.hash === '#individuales') {
    pestanas.forEach(function (p) {
      const activa = p.dataset.tipoParticipante === tipoActual;
      p.classList.toggle('activa', activa);
      p.setAttribute('aria-selected', activa ? 'true' : 'false');
    });
  }
  cargarDatos(false);
  cargarTorneosRegistroDirecto(false);
  actualizarDisciplinasFiltro();
  if (window.location.hash === '#registrar-participante' && abrirRegistro) {
    window.setTimeout(abrirModalRegistro, 0);
  }
  window.addEventListener('arenacjd:invitaciones-actualizadas', function () { cargarDatos(true); });
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('participantes-listados', function () {
    if (!document.querySelector('.modal-registro.abierto')) return cargarDatos(true);
    if (modalRegistro && modalRegistro.classList.contains('abierto')) {
      cargarTorneosRegistroDirecto(false);
      actualizarDisciplinasFiltro();
    }
  }, 5000, {ejecutarAhora:false});
}());


(function(){'use strict';const cont=document.getElementById('estadoCompetenciasParticipantes'),sync=document.getElementById('estadoCompetenciasSync');if(!cont)return;function esc(v){return String(v??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;')}function render(items){if(!items.length){cont.innerHTML='<p class="mensaje-sin-resultados">No hay torneos para mostrar.</p>';return}cont.innerHTML=items.map(t=>{const ap=Number(t.aprobados||0),en=Number(t.enfrentamientos||0),fi=Number(t.finalizados||0);const sorteo=en>0;const resultados=fi>0;let accion=ap>=2&&!sorteo?'<a class="boton boton-principal boton-competencia-viva" href="sorteos.php?torneo='+Number(t.id_torneo)+'">Ir a Sorteos</a>':'';if(sorteo)accion+='<a class="boton boton-principal boton-competencia-viva" href="partidos.php?torneo='+Number(t.id_torneo)+'">Ver enfrentamientos</a>';if(resultados)accion+='<a class="boton boton-claro boton-competencia-viva" href="resultados.php?torneo='+Number(t.id_torneo)+'">Ver resultados</a>';const mediaTorneo=window.ArenaCJDMedia?window.ArenaCJDMedia.html('torneo',t.id_torneo,t.nombre,{clase:'media-mediana'}):'';return '<article class="tarjeta-estado-competencia"><div class="cabecera-estado-competencia-media">'+mediaTorneo+'<div><h3>'+esc(t.nombre)+'</h3><small>'+esc(t.disciplina)+' · '+(t.modalidad==='equipo'?'Equipos':'Individual')+'</small></div></div><div class="pasos-competencia"><span class="'+(ap>=2?'completo':'')+'">'+ap+(ap===1?' aprobado':' aprobados')+'</span><span class="'+(sorteo?'completo':'')+'">Sorteo</span><span class="'+(en?'completo':'')+'">'+en+' cruces</span><span class="'+(resultados?'completo':'')+'">'+fi+' resultados</span></div><div class="acciones-competencia-viva">'+(accion||'<span>Se habilita Sorteos con 2 o más aprobados.</span>')+'</div></article>'}).join('')}async function cargar(){try{const r=await fetch('api/estado_competencia.php?_='+Date.now(),{cache:'no-store'}),d=await r.json();if(!r.ok||!d.exito)throw new Error(d.mensaje||'Error');render(d.torneos||[]);if(sync)sync.textContent='Actualizado '+new Date().toLocaleTimeString('es-UY')}catch(e){const m=e.message||'No se pudo cargar el estado competitivo.';if(window.ArenaCJDEstadoError)window.ArenaCJDEstadoError(cont,m,cargar);else cont.innerHTML='<p class="mensaje-sin-resultados">'+esc(m)+'</p>';if(sync)sync.textContent='Sin conexión'}}cargar();if(window.ArenaCJDSync)window.ArenaCJDSync.registrar('estado-competitivo-participantes',cargar,10000,{ejecutarAhora:false});}());
