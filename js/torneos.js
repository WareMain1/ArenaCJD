(function () {
  'use strict';

  const lista = document.querySelector('.lista-torneos');
  if (!lista) return;

  const formularioFiltros = document.getElementById('formularioFiltrosTorneos');
  const buscar = document.getElementById('buscarTorneo');
  const categoria = document.getElementById('filtroCategoria');
  const disciplina = document.getElementById('filtroDisciplina');
  const estado = document.getElementById('filtroEstado');
  const fechaDesde = document.getElementById('fechaDesde');
  const fechaHasta = document.getElementById('fechaHasta');
  const mensajeSin = document.getElementById('mensajeSinTorneos');
  const tituloSin = document.getElementById('tituloSinTorneos');
  const detalleSin = document.getElementById('detalleSinTorneos');
  const accionSin = document.getElementById('accionSinTorneos');
  const resumenFiltros = document.getElementById('resumenFiltrosTorneos');
  const paginacion = document.getElementById('paginacionTorneos');
  const pieResultados = document.getElementById('pieResultadosTorneos');
  const controlesPaginacion = document.getElementById('controlesPaginacionTorneos');
  const infoPaginacion = document.getElementById('infoPaginacionTorneos');
  const sincronizacion = document.getElementById('estadoSincronizacionTorneos');
  const actualizarAhora = document.getElementById('actualizarTorneosAhora');
  const crearNuevoTorneo = document.getElementById('crearNuevoTorneo');

  const modalDetalle = document.getElementById('modalDetalleTorneo');
  const contenidoDetalle = document.getElementById('contenidoDetalleTorneo');
  const tituloDetalle = document.getElementById('tituloDetalleTorneo');
  const modalEditar = document.getElementById('modalEditarTorneo');
  const formularioEditar = document.getElementById('formularioEditarTorneo');
  const mensajeEditar = document.getElementById('mensajeEditarTorneo');
  const tituloEditar = document.getElementById('tituloEditarTorneo');
  const descripcionEditar = document.getElementById('descripcionEditarTorneo');
  const guardarTorneoModal = document.getElementById('guardarTorneoModal');
  const modalCancelar = document.getElementById('modalCancelarTorneo');
  const nombreCancelar = document.getElementById('nombreCancelarTorneo');
  const mensajeCancelar = document.getElementById('mensajeCancelarTorneo');
  const confirmarCancelar = document.getElementById('confirmarCancelarTorneo');
  const motivoCancelar = document.getElementById('motivoCancelarTorneo');
  const grupoTorneoEstado = document.getElementById('grupoTorneoEstado');
  const estadoInicialTorneo = document.getElementById('estadoInicialTorneo');
  const grupoTorneoPublicado = document.getElementById('grupoTorneoPublicado');
  const inputImagenTorneo = document.getElementById('editarTorneoImagen');
  const previewImagenTorneo = document.getElementById('previewTorneoImagenImg');
  const quitarImagenTorneo = document.getElementById('quitarTorneoImagen');
  const opcionesRealizacion = Array.from(document.querySelectorAll('input[name="realizacionTorneo"]'));
  const avisoRealizacionSinDefinir = document.getElementById('avisoRealizacionSinDefinir');
  const modalidadesTorneos = window.ArenaCJDModalidadesTorneos;
  let urlPreviewTorneo = '';

  const camposEditar = {
    id: document.getElementById('editarTorneoId'),
    nombre: document.getElementById('editarTorneoNombre'),
    disciplina: document.getElementById('editarTorneoDisciplina'),
    categoria: document.getElementById('editarTorneoCategoria'),
    tipo: document.getElementById('editarTorneoTipo'),
    modalidad: document.getElementById('editarTorneoModalidad'),
    organizador: document.getElementById('editarTorneoOrganizador'),
    inicio: document.getElementById('editarTorneoInicio'),
    horaInicio: document.getElementById('editarTorneoHoraInicio'),
    fin: document.getElementById('editarTorneoFin'),
    estado: document.getElementById('editarTorneoEstado'),
    publicado: document.getElementById('editarTorneoPublicado'),
    cupo: document.getElementById('editarTorneoCupo'),
    periodoGracia: document.getElementById('editarTorneoPeriodoGracia')
  };

  let TORNEOS_POR_PAGINA = 10;
  let paginaActual = 1;
  let tarjetas = Array.from(lista.querySelectorAll('.tarjeta-torneo'));
  let filtradas = tarjetas.slice();
  let csrfToken = '';
  let firmaServidor = null;
  let modoFormulario = 'editar';
  let torneoPendienteCancelar = null;
  let catalogosFormulario = null;
  let formularioTorneoModificado = false;
  let temporizadorBorradorTorneo = 0;
  const claveBorradorTorneo = 'arenaCJD-borrador-torneo-v1';
  let indicadorGuardadoTorneo = null;
  let avisoBorradorTorneo = null;

  function realizacionSeleccionada() {
    const seleccionada = opcionesRealizacion.find(function (opcion) { return opcion.checked; });
    return seleccionada ? seleccionada.value : '';
  }

  function seleccionarRealizacion(valor) {
    opcionesRealizacion.forEach(function (opcion) { opcion.checked = opcion.value === valor; });
    if (avisoRealizacionSinDefinir) avisoRealizacionSinDefinir.hidden = Boolean(valor);
  }

  function actualizarRealizacionesEnTarjetas() {
    tarjetas.forEach(function (tarjeta) {
      const idTorneo = tarjeta.dataset.idTorneo;
      const realizacion = modalidadesTorneos.obtener(idTorneo);
      let fila = tarjeta.querySelector('.fila-realizacion-torneo-tarjeta');

      if (!fila) {
        fila = document.createElement('div');
        fila.className = 'fila-realizacion-torneo-tarjeta';
        const metadatos = tarjeta.querySelector('.fila-meta-torneo');
        if (metadatos && metadatos.parentNode) metadatos.insertAdjacentElement('beforebegin', fila);
      }

      fila.innerHTML = '<span class="etiqueta-realizacion-torneo-tarjeta">Modalidad de realización</span>' + modalidadesTorneos.indicadorHTML(realizacion, 'insignia-realizacion-torneo');
    });
  }

  function asegurarIndicadorGuardadoTorneo() {
    if (indicadorGuardadoTorneo || !formularioEditar) return indicadorGuardadoTorneo;
    indicadorGuardadoTorneo = document.createElement('span');
    indicadorGuardadoTorneo.className = 'estado-guardado-ah grupo-formulario-completo';
    indicadorGuardadoTorneo.dataset.estado = 'guardado';
    indicadorGuardadoTorneo.textContent = 'Sin cambios pendientes';
    if (mensajeEditar && mensajeEditar.parentNode) mensajeEditar.parentNode.insertBefore(indicadorGuardadoTorneo, mensajeEditar);
    return indicadorGuardadoTorneo;
  }

  function actualizarEstadoGuardadoTorneo(estadoGuardado, texto) {
    const indicador = asegurarIndicadorGuardadoTorneo();
    if (!indicador) return;
    indicador.dataset.estado = estadoGuardado;
    indicador.textContent = texto;
  }

  function datosBorradorTorneo() {
    return {
      nombre: camposEditar.nombre ? camposEditar.nombre.value : '',
      id_disciplina: camposEditar.disciplina ? camposEditar.disciplina.value : '',
      id_categoria: camposEditar.categoria ? camposEditar.categoria.value : '',
      id_tipo_torneo: camposEditar.tipo ? camposEditar.tipo.value : '',
      id_organizador: camposEditar.organizador ? camposEditar.organizador.value : '',
      modalidad: camposEditar.modalidad ? camposEditar.modalidad.value : 'individual',
      realizacion: realizacionSeleccionada() || 'presencial',
      fecha_inicio: camposEditar.inicio ? camposEditar.inicio.value : '',
      hora_inicio: camposEditar.horaInicio ? camposEditar.horaInicio.value : '',
      fecha_fin: camposEditar.fin ? camposEditar.fin.value : '',
      cupo_maximo: camposEditar.cupo ? camposEditar.cupo.value : '',
      guardado_en: Date.now()
    };
  }

  function guardarBorradorTorneoLocal() {
    if (modoFormulario !== 'crear' || !formularioTorneoModificado) return;
    try {
      localStorage.setItem(claveBorradorTorneo, JSON.stringify(datosBorradorTorneo()));
      actualizarEstadoGuardadoTorneo('guardado', 'Borrador guardado en este dispositivo');
    } catch (error) {
      actualizarEstadoGuardadoTorneo('error', 'No se pudo guardar el borrador local');
    }
  }

  function marcarFormularioTorneoModificado() {
    if (!modalEditar || !modalEditar.classList.contains('abierto')) return;
    formularioTorneoModificado = true;
    actualizarEstadoGuardadoTorneo('pendiente', modoFormulario === 'crear' ? 'Guardando borrador…' : 'Cambios sin guardar');
    if (modoFormulario === 'crear') {
      window.clearTimeout(temporizadorBorradorTorneo);
      temporizadorBorradorTorneo = window.setTimeout(guardarBorradorTorneoLocal, 450);
    }
  }

  function limpiarAvisoBorradorTorneo() {
    if (avisoBorradorTorneo) avisoBorradorTorneo.remove();
    avisoBorradorTorneo = null;
  }

  function aplicarBorradorTorneo(borrador) {
    if (!borrador || !catalogosFormulario) return;
    if (camposEditar.nombre) camposEditar.nombre.value = borrador.nombre || '';
    if (camposEditar.disciplina && borrador.id_disciplina) camposEditar.disciplina.value = String(borrador.id_disciplina);
    actualizarCatalogosDependientes(borrador.id_categoria || '', borrador.id_tipo_torneo || '');
    if (camposEditar.organizador && borrador.id_organizador && Array.from(camposEditar.organizador.options).some(function (opcion) { return opcion.value === String(borrador.id_organizador); })) camposEditar.organizador.value = String(borrador.id_organizador);
    if (camposEditar.modalidad) camposEditar.modalidad.value = borrador.modalidad || 'individual';
    seleccionarRealizacion(borrador.realizacion || 'presencial');
    if (camposEditar.inicio) camposEditar.inicio.value = borrador.fecha_inicio || camposEditar.inicio.value;
    if (camposEditar.horaInicio) camposEditar.horaInicio.value = borrador.hora_inicio || camposEditar.horaInicio.value;
    if (camposEditar.fin) camposEditar.fin.value = borrador.fecha_fin || camposEditar.fin.value;
    if (camposEditar.cupo) camposEditar.cupo.value = borrador.cupo_maximo || '';
    formularioTorneoModificado = true;
    actualizarEstadoGuardadoTorneo('pendiente', 'Borrador restaurado · pendiente de crear');
    limpiarAvisoBorradorTorneo();
  }

  function ofrecerBorradorTorneo() {
    limpiarAvisoBorradorTorneo();
    let borrador = null;
    try { borrador = JSON.parse(localStorage.getItem(claveBorradorTorneo) || 'null'); } catch (error) { borrador = null; }
    if (!borrador || !formularioEditar) return;
    avisoBorradorTorneo = document.createElement('div');
    avisoBorradorTorneo.className = 'aviso-borrador-ah grupo-formulario-completo';
    const fecha = borrador.guardado_en ? new Date(borrador.guardado_en).toLocaleString('es-UY', {dateStyle:'short', timeStyle:'short'}) : '';
    avisoBorradorTorneo.innerHTML = '<div><strong>Tienes un torneo sin terminar</strong><span>' + (fecha ? 'Borrador local guardado ' + escaparHTML(fecha) + '.' : 'Puedes recuperar los datos que habías escrito.') + '</span></div><div class="acciones-borrador-ah"><button class="boton boton-principal" type="button" data-restaurar-borrador-torneo>Restaurar</button><button class="boton boton-claro" type="button" data-descartar-borrador-torneo>Descartar</button></div>';
    formularioEditar.insertBefore(avisoBorradorTorneo, formularioEditar.firstChild);
    avisoBorradorTorneo.querySelector('[data-restaurar-borrador-torneo]').addEventListener('click', function () { aplicarBorradorTorneo(borrador); });
    avisoBorradorTorneo.querySelector('[data-descartar-borrador-torneo]').addEventListener('click', function () {
      localStorage.removeItem(claveBorradorTorneo);
      limpiarAvisoBorradorTorneo();
      actualizarEstadoGuardadoTorneo('guardado', 'Sin cambios pendientes');
    });
  }

  async function intentarCerrarFormularioTorneo() {
    if (!formularioTorneoModificado) {
      cerrarModal(modalEditar);
      limpiarAvisoBorradorTorneo();
      return true;
    }
    const confirmar = window.ArenaCJDConfirmar ? await window.ArenaCJDConfirmar({
      titulo: 'Salir sin guardar',
      mostrarIcono: false,
      mensaje: modoFormulario === 'crear' ? 'El torneo todavía no fue creado. El borrador local se conservará para que puedas retomarlo después.' : 'Hay cambios que todavía no se guardaron en el torneo.',
      confirmar: 'Salir'
    }) : window.confirm('Hay cambios sin guardar. ¿Salir igualmente?');
    if (!confirmar) return false;
    cerrarModal(modalEditar);
    limpiarAvisoBorradorTorneo();
    formularioTorneoModificado = false;
    return true;
  }

  function escaparHTML(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function normalizar(valor) {
    return String(valor || '')
      .toLocaleLowerCase('es')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  function formatearFecha(fecha) {
    if (!fecha) return '-';
    const partes = String(fecha).split('-');
    return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : fecha;
  }

  function etiquetaEstado(valor) {
    const estados = {
      borrador: 'Borrador',
      inscripciones: 'Inscripciones',
      en_curso: 'En curso',
      finalizado: 'Finalizado',
      cancelado: 'Cancelado'
    };
    return estados[valor] || valor;
  }

  function etiquetaModalidad(valor) {
    return valor === 'equipo' ? 'Por equipos' : 'Individual';
  }

  function cerrarMenusContextuales(excepto) {
    document.querySelectorAll('.menu-contextual-torneo').forEach(function (menu) {
      if (menu !== excepto) menu.hidden = true;
    });
    document.querySelectorAll('[data-accion-torneo="menu"]').forEach(function (boton) {
      const menu = document.querySelector('[data-menu-torneo="' + boton.dataset.idTorneo + '"]');
      boton.setAttribute('aria-expanded', menu && !menu.hidden ? 'true' : 'false');
    });
  }

  async function cargarPreferenciaPaginacion() {
    try {
      const respuesta = await fetch('api/preferencias.php?_=' + Date.now(), {cache: 'no-store'});
      const resultado = await respuesta.json();
      const cantidad = Number(resultado && resultado.preferencias && resultado.preferencias.elementosPagina);
      if (respuesta.ok && resultado.exito) {
        if ([10, 20, 50].includes(cantidad)) TORNEOS_POR_PAGINA = cantidad;
        paginaActual = 1;
        aplicarFiltros();
      }
    } catch (error) {
    }
  }

  function renderizarPaginacion() {
    const total = filtradas.length;
    const totalPaginas = Math.max(1, Math.ceil(total / TORNEOS_POR_PAGINA));
    paginaActual = Math.min(Math.max(1, paginaActual), totalPaginas);

    tarjetas.forEach(function (tarjeta) { tarjeta.hidden = true; });
    const inicio = (paginaActual - 1) * TORNEOS_POR_PAGINA;
    filtradas.slice(inicio, inicio + TORNEOS_POR_PAGINA).forEach(function (tarjeta) {
      tarjeta.hidden = false;
    });

    if (mensajeSin) {
      mensajeSin.hidden = total !== 0;
      if (total === 0) {
        const valores = valoresFiltros();
        const hayFiltros = Boolean(valores.texto || valores.categoria || valores.disciplina || valores.estado || valores.desde || valores.hasta);
        if (tituloSin) tituloSin.textContent = hayFiltros ? 'No hay coincidencias' : 'Todavía no hay torneos';
        if (detalleSin) detalleSin.textContent = hayFiltros ? 'Prueba con otros filtros o restablece la búsqueda.' : 'Cuando se cree un torneo aparecerá aquí con su estado y próximas acciones.';
        if (accionSin) {
          accionSin.textContent = hayFiltros ? 'Quitar filtros' : (crearNuevoTorneo ? 'Crear torneo' : 'Actualizar');
          accionSin.dataset.accionVacioTorneo = hayFiltros ? 'limpiar' : (crearNuevoTorneo ? 'crear' : 'actualizar');
        }
      }
    }

    if (infoPaginacion) {
      if (total === 0) {
        infoPaginacion.textContent = 'Sin torneos para mostrar';
      } else if (total <= TORNEOS_POR_PAGINA) {
        const totalGeneral = tarjetas.length;
        infoPaginacion.textContent = 'Mostrando ' + total + ' de ' + totalGeneral + (totalGeneral === 1 ? ' torneo' : ' torneos');
      } else {
        const desde = inicio + 1;
        const hasta = Math.min(inicio + TORNEOS_POR_PAGINA, total);
        infoPaginacion.textContent = 'Mostrando ' + desde + ' a ' + hasta + ' de ' + total + (total === 1 ? ' torneo' : ' torneos');
      }
    }

    if (pieResultados) pieResultados.hidden = total === 0;

    if (!paginacion || !controlesPaginacion) return;
    if (total <= TORNEOS_POR_PAGINA) {
      paginacion.hidden = total === 0;
      controlesPaginacion.innerHTML = '';
      return;
    }

    paginacion.hidden = false;
    let html = '<button class="flecha-pagina" type="button" data-pagina-torneo="anterior"' + (paginaActual === 1 ? ' disabled' : '') + '><span data-icono="izquierda" aria-hidden="true"></span></button>';
    for (let pagina = 1; pagina <= totalPaginas; pagina += 1) {
      html += '<button class="numero-pagina' + (pagina === paginaActual ? ' activo' : '') + '" type="button" data-pagina-torneo="' + pagina + '"' + (pagina === paginaActual ? ' aria-current="page"' : '') + '>' + pagina + '</button>';
    }
    html += '<button class="flecha-pagina" type="button" data-pagina-torneo="siguiente"' + (paginaActual === totalPaginas ? ' disabled' : '') + '><span data-icono="derecha" aria-hidden="true"></span></button>';
    controlesPaginacion.innerHTML = html;
  }

  const opcionesCategoriaOriginales = categoria
    ? Array.from(categoria.options).slice(1).map(function (opcion) { return { valor: opcion.value, texto: opcion.textContent }; })
    : [];
  const opcionesDisciplinaOriginales = disciplina
    ? Array.from(disciplina.options).slice(1).map(function (opcion) { return { valor: opcion.value, texto: opcion.textContent }; })
    : [];

  function valoresFiltros() {
    return {
      texto: normalizar(buscar && buscar.value),
      categoria: categoria ? categoria.value : '',
      disciplina: disciplina ? disciplina.value : '',
      estado: estado ? estado.value : '',
      desde: fechaDesde ? fechaDesde.value : '',
      hasta: fechaHasta ? fechaHasta.value : ''
    };
  }

  function coincideEstadoTorneo(tarjeta, valorEstado) {
    const estadoTorneo = tarjeta.dataset.estado || '';

    if (valorEstado === 'todos') return true;
    if (valorEstado === 'cancelado') return estadoTorneo === 'cancelado';
    if (valorEstado) return estadoTorneo === valorEstado;

    if (estadoTorneo === 'cancelado' || estadoTorneo === 'finalizado') return false;
    return true;
  }

  function coincideTarjeta(tarjeta, valores, ignorar) {
    const omitir = ignorar || '';
    const fecha = tarjeta.dataset.fechaInicio || '';

    if (omitir !== 'texto' && valores.texto && !normalizar(tarjeta.dataset.nombre).includes(valores.texto)) return false;
    if (omitir !== 'categoria' && valores.categoria && tarjeta.dataset.categoria !== valores.categoria) return false;
    if (omitir !== 'disciplina' && valores.disciplina && tarjeta.dataset.disciplina !== valores.disciplina) return false;
    if (omitir !== 'estado' && !coincideEstadoTorneo(tarjeta, valores.estado)) return false;
    if (omitir !== 'desde' && valores.desde && fecha < valores.desde) return false;
    if (omitir !== 'hasta' && valores.hasta && fecha > valores.hasta) return false;

    return true;
  }

  function reconstruirSelector(select, opcionesOriginales, disponibles, textoTodos) {
    if (!select) return false;

    const seleccionado = select.value;
    const fragmento = document.createDocumentFragment();
    const opcionTodos = document.createElement('option');
    opcionTodos.value = '';
    opcionTodos.textContent = textoTodos;
    fragmento.appendChild(opcionTodos);

    opcionesOriginales.forEach(function (opcion) {
      if (!disponibles.has(opcion.valor)) return;
      const elemento = document.createElement('option');
      elemento.value = opcion.valor;
      elemento.textContent = opcion.texto;
      fragmento.appendChild(elemento);
    });

    select.replaceChildren(fragmento);
    if (seleccionado && disponibles.has(seleccionado)) {
      select.value = seleccionado;
      return false;
    }

    const cambio = seleccionado !== '';
    select.value = '';
    return cambio;
  }

  function actualizarOpcionesDependientes() {
    for (let pasada = 0; pasada < 2; pasada += 1) {
      const valores = valoresFiltros();

      const categoriasDisponibles = new Set(
        tarjetas
          .filter(function (tarjeta) { return coincideTarjeta(tarjeta, valores, 'categoria'); })
          .map(function (tarjeta) { return tarjeta.dataset.categoria; })
          .filter(Boolean)
      );
      const cambioCategoria = reconstruirSelector(categoria, opcionesCategoriaOriginales, categoriasDisponibles, 'Todas las categorías');

      const valoresActualizados = valoresFiltros();
      const disciplinasDisponibles = new Set(
        tarjetas
          .filter(function (tarjeta) { return coincideTarjeta(tarjeta, valoresActualizados, 'disciplina'); })
          .map(function (tarjeta) { return tarjeta.dataset.disciplina; })
          .filter(Boolean)
      );
      const cambioDisciplina = reconstruirSelector(disciplina, opcionesDisciplinaOriginales, disciplinasDisponibles, 'Todas las disciplinas');

      if (!cambioCategoria && !cambioDisciplina) break;
    }
  }

  function actualizarResumenFiltros() {
    if (!resumenFiltros) return;
    const valores = valoresFiltros();
    const chips = [];
    if (valores.texto) chips.push('<span class="chip-filtro-ah">Búsqueda: ' + escaparHTML(buscar.value.trim()) + '</span>');
    if (valores.categoria) chips.push('<span class="chip-filtro-ah">Categoría: ' + escaparHTML(categoria.options[categoria.selectedIndex].textContent) + '</span>');
    if (valores.disciplina) chips.push('<span class="chip-filtro-ah">Disciplina: ' + escaparHTML(disciplina.options[disciplina.selectedIndex].textContent) + '</span>');
    if (valores.estado) chips.push('<span class="chip-filtro-ah">Estado: ' + escaparHTML(estado.options[estado.selectedIndex].textContent) + '</span>');
    if (valores.desde) chips.push('<span class="chip-filtro-ah">Desde: ' + escaparHTML(formatearFecha(valores.desde)) + '</span>');
    if (valores.hasta) chips.push('<span class="chip-filtro-ah">Hasta: ' + escaparHTML(formatearFecha(valores.hasta)) + '</span>');
    if (!chips.length) {
      resumenFiltros.hidden = true;
      resumenFiltros.innerHTML = '';
      return;
    }
    resumenFiltros.hidden = false;
    resumenFiltros.innerHTML = chips.join('') + '<button class="limpiar-filtros-ah" type="button" data-limpiar-filtros-torneos>Quitar filtros</button>';
  }

  function limpiarFiltros() {
    if (formularioFiltros) formularioFiltros.reset();
    if (buscar) buscar.value = '';
    if (categoria) categoria.value = '';
    if (disciplina) disciplina.value = '';
    if (estado) estado.value = '';
    if (fechaDesde) fechaDesde.value = '';
    if (fechaHasta) fechaHasta.value = '';
    aplicarFiltros();
  }

  function aplicarFiltros() {
    actualizarOpcionesDependientes();
    const valores = valoresFiltros();

    filtradas = tarjetas.filter(function (tarjeta) {
      return coincideTarjeta(tarjeta, valores, '');
    });

    paginaActual = 1;
    renderizarPaginacion();
    actualizarResumenFiltros();
  }

  function abrirModal(modal) {
    if (!modal) return;
    modal.classList.add('abierto');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-torneo-activo');
  }

  function cerrarModal(modal) {
    if (!modal) return;
    modal.classList.remove('abierto');
    modal.setAttribute('aria-hidden', 'true');
    if (!document.querySelector('.modal-torneo.abierto')) {
      document.body.classList.remove('modal-torneo-activo');
    }
  }

  function itemsPermitidosPorDisciplina(tipoRelacion, items, idDisciplina) {
    if (!catalogosFormulario || !idDisciplina) return items || [];
    const relaciones = catalogosFormulario[tipoRelacion] || [];
    const claveDestino = tipoRelacion === 'disciplina_categorias' ? 'id_categoria' : 'id_tipo_torneo';
    const ids = relaciones.filter(function(r){ return Number(r.id_disciplina) === Number(idDisciplina); }).map(function(r){ return Number(r[claveDestino]); });
    if (!ids.length) return items || [];
    return (items || []).filter(function(item){ return ids.includes(Number(item.id)); });
  }

  function actualizarCatalogosDependientes(valorCategoria, valorTipo) {
    if (!catalogosFormulario) return;
    const idDisciplina = Number(camposEditar.disciplina && camposEditar.disciplina.value);
    const categoriasPermitidas = itemsPermitidosPorDisciplina('disciplina_categorias', catalogosFormulario.categorias, idDisciplina);
    const tiposPermitidos = itemsPermitidosPorDisciplina('disciplina_tipos', catalogosFormulario.tipos, idDisciplina);
    poblarSelect(camposEditar.categoria, categoriasPermitidas, valorCategoria || '');
    poblarSelect(camposEditar.tipo, tiposPermitidos, valorTipo || '');
  }

  function fechaMananaLocal() {
    const fecha = new Date();
    fecha.setDate(fecha.getDate() + 1);
    const offset = fecha.getTimezoneOffset() * 60000;
    return new Date(fecha.getTime() - offset).toISOString().slice(0, 10);
  }

  function poblarSelect(select, items, valor, formato) {
    if (!select) return;
    select.innerHTML = (items || []).map(function (item) {
      const texto = formato ? formato(item) : item.nombre;
      return '<option value="' + escaparHTML(item.id) + '"' + (String(item.id) === String(valor) ? ' selected' : '') + '>' + escaparHTML(texto) + '</option>';
    }).join('');
  }

  function liberarPreviewTorneo() {
    if (urlPreviewTorneo) {
      URL.revokeObjectURL(urlPreviewTorneo);
      urlPreviewTorneo = '';
    }
  }



  function ocultarPreviewTorneo() {
    liberarPreviewTorneo();
    if (previewImagenTorneo) {
      previewImagenTorneo.hidden = true;
      previewImagenTorneo.removeAttribute('src');
    }
    if (quitarImagenTorneo) quitarImagenTorneo.hidden = true;
  }

  function mostrarPreviewTorneo(src, permitirQuitar) {
    if (!previewImagenTorneo || !src) return;
    previewImagenTorneo.onload = function () {
      previewImagenTorneo.hidden = false;
      if (quitarImagenTorneo) quitarImagenTorneo.hidden = !permitirQuitar;
    };
    previewImagenTorneo.onerror = function () {
      previewImagenTorneo.hidden = true;
      if (quitarImagenTorneo) quitarImagenTorneo.hidden = true;
    };
    previewImagenTorneo.src = src;
  }

  function cargarImagenTorneoExistente(idTorneo) {
    ocultarPreviewTorneo();
    if (!idTorneo) return;
    mostrarPreviewTorneo('api/imagen_torneo.php?id_torneo=' + Number(idTorneo) + '&_=' + Date.now(), true);
  }

  async function subirImagenTorneo(idTorneo) {
    if (!inputImagenTorneo || !inputImagenTorneo.files || !inputImagenTorneo.files[0]) return null;
    const archivo = inputImagenTorneo.files[0];
    if (archivo.size > 4 * 1024 * 1024) throw new Error('El torneo se guardó, pero la imagen supera el máximo de 4 MB.');
    const datos = new FormData();
    datos.append('id_torneo', String(idTorneo));
    datos.append('imagen', archivo);
    const respuesta = await fetch('api/torneo_imagen_subir.php', {
      method: 'POST',
      headers: {'X-CSRF-Token': csrfToken},
      body: datos
    });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.exito) throw new Error('El torneo se guardó, pero ' + (resultado.mensaje || 'no se pudo guardar su imagen.'));
    return resultado;
  }

  function renderDetalle(torneo) {
    if (!contenidoDetalle) return;
    const cupo = torneo.cupo_maximo == null ? 'Sin límite' : torneo.cupo_maximo;
    const realizacion = modalidadesTorneos.obtener(torneo.id_torneo);
    contenidoDetalle.innerHTML = [
      '<div class="detalle-torneo-imagen"><span>Imagen</span><strong>' + (window.ArenaCJDMedia ? window.ArenaCJDMedia.html('torneo', torneo.id_torneo, torneo.nombre, {clase:'media-grande'}) : window.ArenaCJDIcono('torneo')) + '</strong></div>',
      '<div><span>Disciplina</span><strong>' + escaparHTML(torneo.disciplina) + '</strong></div>',
      '<div><span>Categoría</span><strong>' + escaparHTML(torneo.categoria) + '</strong></div>',
      '<div><span>Tipo</span><strong>' + escaparHTML(torneo.tipo_torneo) + '</strong></div>',
      '<div><span>Modalidad de participación</span><strong>' + escaparHTML(etiquetaModalidad(torneo.modalidad)) + '</strong></div>',
      '<div><span>Modalidad de realización</span><strong class="detalle-realizacion-torneo">' + modalidadesTorneos.indicadorHTML(realizacion, 'valor-realizacion-torneo') + '</strong></div>',
      '<div><span>Organizador</span><strong>' + escaparHTML(torneo.organizador) + ' (@' + escaparHTML(torneo.organizador_usuario) + ')</strong></div>',
      '<div><span>Estado</span><strong>' + escaparHTML(etiquetaEstado(torneo.estado)) + '</strong></div>',
      '<div><span>Área pública</span><strong>' + (Number(torneo.publicado || 0) === 1 ? 'Visible' : 'No publicado') + '</strong></div>',
      '<div><span>Inicio</span><strong>' + escaparHTML(formatearFecha(torneo.fecha_inicio)) + ' · ' + escaparHTML(String(torneo.hora_inicio || '09:00').slice(0,5)) + '</strong></div>',
      '<div><span>Finalización</span><strong>' + escaparHTML(formatearFecha(torneo.fecha_fin)) + '</strong></div>',
      '<div><span>Cupo máximo</span><strong>' + escaparHTML(cupo) + '</strong></div>',
      '<div><span>Plazas ocupadas</span><strong>' + escaparHTML(torneo.cupo_ocupado || 0) + '</strong></div>',
      '<div><span>Plazas disponibles</span><strong>' + escaparHTML(torneo.cupo_disponible == null ? 'Sin límite' : torneo.cupo_disponible) + '</strong></div>',
      '<div><span>Inscripciones aprobadas</span><strong>' + escaparHTML(torneo.cantidad_inscritos || 0) + '</strong></div>'
    ].join('');
  }

  async function obtenerDetalle(idTorneo) {
    const respuesta = await fetch('api/torneo_detalle.php?id=' + encodeURIComponent(idTorneo), { cache: 'no-store' });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.exito) {
      throw new Error(resultado.mensaje || 'No se pudo cargar el torneo.');
    }
    csrfToken = resultado.csrf_token || csrfToken;
    return resultado;
  }

  async function obtenerCatalogos() {
    const respuesta = await fetch('api/torneo_catalogos.php?_=' + Date.now(), { cache: 'no-store' });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron cargar las opciones del torneo.');
    csrfToken = resultado.csrf_token || csrfToken;
    return resultado;
  }

  function actualizarDisponibilidadPublicacion() {
    if (!camposEditar.publicado || modoFormulario === 'crear') return;
    const estadoActual = camposEditar.estado ? camposEditar.estado.value : '';
    const permitido = ['inscripciones', 'en_curso', 'finalizado'].includes(estadoActual);
    camposEditar.publicado.disabled = !permitido || ['finalizado', 'cancelado'].includes(estadoActual);
    if (!permitido) camposEditar.publicado.checked = false;
  }

  function configurarOpcionesEstado(modo, estadoActual, restricciones) {
    if (!camposEditar.estado) return;
    Array.from(camposEditar.estado.options).forEach(function (opcion) { opcion.disabled = false; });
    if (modo === 'crear') {
      Array.from(camposEditar.estado.options).forEach(function (opcion) {
        opcion.disabled = !['borrador', 'inscripciones'].includes(opcion.value);
      });
      return;
    }

    const r = restricciones || {};
    Array.from(camposEditar.estado.options).forEach(function (opcion) {
      if (opcion.value === 'finalizado' && estadoActual !== 'finalizado') opcion.disabled = true;
      if (opcion.value === 'cancelado' && estadoActual !== 'cancelado') opcion.disabled = true;
      if (Number(r.inscripciones || 0) > 0 && opcion.value === 'borrador') opcion.disabled = true;
      if (Number(r.enfrentamientos || 0) > 0 && ['borrador', 'inscripciones'].includes(opcion.value)) opcion.disabled = true;
    });
  }

  function aplicarRestriccionesEdicion(resultado) {
    const torneo = resultado.torneo || {};
    const r = resultado.restricciones_edicion || {};
    const bloqueadoHistorico = ['finalizado', 'cancelado'].includes(torneo.estado);

    [camposEditar.disciplina, camposEditar.categoria, camposEditar.tipo, camposEditar.modalidad].forEach(function (campo) {
      if (campo) campo.disabled = bloqueadoHistorico || Boolean(r.estructura_bloqueada);
    });
    if (camposEditar.organizador) {
      camposEditar.organizador.disabled = bloqueadoHistorico || !resultado.es_administrador || Boolean(r.organizador_bloqueado);
    }
    [camposEditar.nombre, camposEditar.inicio, camposEditar.horaInicio, camposEditar.fin, camposEditar.estado, camposEditar.publicado, camposEditar.cupo].forEach(function (campo) {
      if (campo) campo.disabled = bloqueadoHistorico;
    });
    if (guardarTorneoModal) guardarTorneoModal.disabled = bloqueadoHistorico;
    configurarOpcionesEstado('editar', torneo.estado, r);
    actualizarDisponibilidadPublicacion();

    return bloqueadoHistorico;
  }

  function fechaHoyLocal() {
    const hoy = new Date();
    const offset = hoy.getTimezoneOffset() * 60000;
    return new Date(hoy.getTime() - offset).toISOString().slice(0, 10);
  }

  function configurarVistaFormulario(modo) {
    const creando = modo === 'crear';
    if (grupoTorneoEstado) grupoTorneoEstado.hidden = creando;
    if (estadoInicialTorneo) estadoInicialTorneo.hidden = !creando;
    if (grupoTorneoPublicado) grupoTorneoPublicado.hidden = creando;
    if (camposEditar.estado) {
      camposEditar.estado.required = !creando;
      if (creando) camposEditar.estado.value = 'borrador';
    }
  }

  async function abrirCrear() {
    modoFormulario = 'crear';
    formularioTorneoModificado = false;
    formularioEditar.reset();
    actualizarEstadoGuardadoTorneo('guardado', 'Sin cambios pendientes');
    ocultarPreviewTorneo();
    configurarVistaFormulario('crear');
    camposEditar.id.value = '';
    Object.values(camposEditar).forEach(function (campo) { if (campo) campo.disabled = false; });
    if (guardarTorneoModal) guardarTorneoModal.disabled = false;
    camposEditar.organizador.disabled = false;
    configurarOpcionesEstado('crear', 'borrador', {});
    if (tituloEditar) tituloEditar.textContent = 'Nuevo torneo';
    if (descripcionEditar) descripcionEditar.textContent = 'Completa los datos principales y revisa el estado antes de guardar el torneo.';
    if (guardarTorneoModal) guardarTorneoModal.textContent = 'Crear torneo';
    if (mensajeEditar) {
      mensajeEditar.textContent = 'Cargando opciones disponibles...';
      mensajeEditar.className = 'mensaje-edicion-torneo informativo grupo-formulario-completo';
    }
    abrirModal(modalEditar);

    try {
      const resultado = await obtenerCatalogos();
      const catalogos = resultado.catalogos || {};
      catalogosFormulario = catalogos;
      poblarSelect(camposEditar.disciplina, catalogos.disciplinas, '');
      actualizarCatalogosDependientes('', '');
      poblarSelect(camposEditar.organizador, catalogos.organizadores, resultado.usuario_actual && resultado.usuario_actual.id, function (item) {
        return item.nombre + ' (@' + item.usuario + ')';
      });
      camposEditar.organizador.disabled = !resultado.es_administrador;
      camposEditar.modalidad.value = 'individual';
      seleccionarRealizacion('presencial');
      camposEditar.estado.value = 'borrador';
      if (camposEditar.publicado) camposEditar.publicado.checked = false;
      camposEditar.cupo.value = '';
      if (camposEditar.periodoGracia) camposEditar.periodoGracia.value = 60;

      const faltantes = [];
      if (!(catalogos.disciplinas || []).length) faltantes.push('una disciplina activa');
      if (!(catalogos.tipos || []).length) faltantes.push('un tipo de torneo');
      if (!(catalogos.organizadores || []).length) faltantes.push('un organizador disponible');
      if (faltantes.length && guardarTorneoModal) guardarTorneoModal.disabled = true;
      const hoy = fechaHoyLocal();
      const manana = fechaMananaLocal();
      camposEditar.inicio.min = hoy;
      camposEditar.fin.min = manana;
      camposEditar.inicio.value = manana;
      if (camposEditar.horaInicio) camposEditar.horaInicio.value = '09:00';
      camposEditar.fin.value = manana;
      if (mensajeEditar) {
        if (faltantes.length) {
          mensajeEditar.textContent = 'Antes de crear el torneo debes tener ' + faltantes.join(', ') + '.';
          mensajeEditar.className = 'mensaje-edicion-torneo error grupo-formulario-completo';
        } else {
          mensajeEditar.textContent = 'Solo se muestran disciplinas activas. El torneo se creará como Borrador y quedará asignado al organizador seleccionado.';
          mensajeEditar.className = 'mensaje-edicion-torneo informativo grupo-formulario-completo';
        }
      }
      ofrecerBorradorTorneo();
    } catch (error) {
      if (mensajeEditar) {
        mensajeEditar.textContent = error.message;
        mensajeEditar.className = 'mensaje-edicion-torneo error grupo-formulario-completo';
      }
    }
  }

  async function abrirDetalles(idTorneo) {
    if (contenidoDetalle) contenidoDetalle.innerHTML = '<p class="mensaje-sin-resultados">Cargando torneo...</p>';
    abrirModal(modalDetalle);
    try {
      const resultado = await obtenerDetalle(idTorneo);
      if (tituloDetalle) tituloDetalle.textContent = resultado.torneo.nombre;
      renderDetalle(resultado.torneo);
    } catch (error) {
      if (contenidoDetalle) contenidoDetalle.innerHTML = '<p class="mensaje-sin-resultados">' + escaparHTML(error.message) + '</p>';
    }
  }

  async function abrirEditar(idTorneo) {
    modoFormulario = 'editar';
    formularioTorneoModificado = false;
    limpiarAvisoBorradorTorneo();
    actualizarEstadoGuardadoTorneo('guardado', 'Sin cambios pendientes');
    configurarVistaFormulario('editar');
    if (tituloEditar) tituloEditar.textContent = 'Editar torneo';
    if (descripcionEditar) descripcionEditar.textContent = 'Los cambios se guardan directamente en la tabla torneos.';
    if (guardarTorneoModal) guardarTorneoModal.textContent = 'Guardar cambios';
    if (mensajeEditar) {
      mensajeEditar.textContent = 'Cargando datos del torneo...';
      mensajeEditar.className = 'mensaje-edicion-torneo informativo grupo-formulario-completo';
    }
    abrirModal(modalEditar);

    try {
      const resultado = await obtenerDetalle(idTorneo);
      if (!resultado.puede_gestionar) throw new Error('No tienes permiso para editar este torneo.');
      const torneo = resultado.torneo;
      const catalogos = resultado.catalogos || {};
      catalogosFormulario = catalogos;

      camposEditar.id.value = torneo.id_torneo;
      if (inputImagenTorneo) inputImagenTorneo.value = '';
      cargarImagenTorneoExistente(torneo.id_torneo);
      camposEditar.nombre.value = torneo.nombre || '';
      poblarSelect(camposEditar.disciplina, catalogos.disciplinas, torneo.id_disciplina);
      actualizarCatalogosDependientes(torneo.id_categoria, torneo.id_tipo_torneo);
      poblarSelect(camposEditar.organizador, catalogos.organizadores, torneo.id_organizador, function (item) {
        return item.nombre + ' (@' + item.usuario + ')';
      });
      camposEditar.modalidad.value = torneo.modalidad;
      seleccionarRealizacion(modalidadesTorneos.obtener(torneo.id_torneo));
      camposEditar.inicio.value = torneo.fecha_inicio;
      if (camposEditar.horaInicio) camposEditar.horaInicio.value = String(torneo.hora_inicio || '09:00').slice(0, 5);
      camposEditar.fin.value = torneo.fecha_fin;
      camposEditar.inicio.removeAttribute('min');
      camposEditar.fin.min = torneo.fecha_inicio || '';
      camposEditar.estado.value = torneo.estado;
      if (camposEditar.publicado) camposEditar.publicado.checked = Number(torneo.publicado || 0) === 1;
      camposEditar.cupo.value = torneo.cupo_maximo == null ? '' : torneo.cupo_maximo;
      if (camposEditar.periodoGracia) camposEditar.periodoGracia.value = Number(torneo.periodo_gracia_resultado || 60);
      const bloqueadoHistorico = aplicarRestriccionesEdicion(resultado);

      if (mensajeEditar) {
        const r = resultado.restricciones_edicion || {};
        if (bloqueadoHistorico) {
          mensajeEditar.textContent = 'Este torneo conserva su historial y está bloqueado para edición.';
        } else if (r.estructura_bloqueada) {
          mensajeEditar.textContent = 'El torneo ya tiene actividad: disciplina, categoría, tipo y modalidad están protegidos. El cupo no puede quedar por debajo de las plazas ocupadas.';
        } else {
          mensajeEditar.textContent = resultado.es_administrador
            ? 'Puedes modificar los datos del torneo mientras no exista actividad competitiva.'
            : 'Puedes modificar el torneo que organizas; el organizador no puede cambiarse.';
        }
        mensajeEditar.className = 'mensaje-edicion-torneo informativo grupo-formulario-completo';
      }
    } catch (error) {
      if (mensajeEditar) {
        mensajeEditar.textContent = error.message;
        mensajeEditar.className = 'mensaje-edicion-torneo error grupo-formulario-completo';
      }
    }
  }

  function cancelarTorneo(idTorneo) {
    torneoPendienteCancelar = Number(idTorneo);
    let nombre = 'Torneo';
    const tarjeta = lista.querySelector('[data-id-torneo="' + idTorneo + '"]');
    if (tarjeta) {
      const titulo = tarjeta.querySelector('.nombre-torneo');
      if (titulo) nombre = titulo.textContent.trim();
    }
    if (nombreCancelar) nombreCancelar.textContent = '¿Cancelar “' + nombre + '”?';
    if (mensajeCancelar) { mensajeCancelar.textContent = ''; mensajeCancelar.className = 'mensaje-edicion-torneo grupo-formulario-completo'; }
    if (motivoCancelar) motivoCancelar.value = '';
    abrirModal(modalCancelar);
  }

  async function ejecutarCancelacionTorneo() {
    if (!torneoPendienteCancelar) return;
    if (confirmarCancelar) confirmarCancelar.disabled = true;
    if (mensajeCancelar) { mensajeCancelar.textContent = 'Cancelando torneo...'; mensajeCancelar.className = 'mensaje-edicion-torneo informativo grupo-formulario-completo'; }
    try {
      if (!csrfToken) {
        const detalle = await obtenerDetalle(torneoPendienteCancelar);
        csrfToken = detalle.csrf_token || '';
      }
      const motivo = motivoCancelar ? motivoCancelar.value.trim() : '';
      if (motivo.length < 5 || motivo.length > 300) throw new Error('Indica un motivo de cancelación de entre 5 y 300 caracteres.');
      const respuesta = await fetch('api/torneo_cancelar.php', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({id_torneo:torneoPendienteCancelar,motivo:motivo})});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo cancelar el torneo.');
      if (mensajeCancelar) { mensajeCancelar.textContent = resultado.mensaje || 'Torneo cancelado.'; mensajeCancelar.className = 'mensaje-edicion-torneo exito grupo-formulario-completo'; }
      window.setTimeout(function(){ window.location.reload(); }, 500);
    } catch (error) {
      if (mensajeCancelar) { mensajeCancelar.textContent = error.message; mensajeCancelar.className = 'mensaje-edicion-torneo error grupo-formulario-completo'; }
    } finally { if (confirmarCancelar) confirmarCancelar.disabled = false; }
  }


  actualizarRealizacionesEnTarjetas();

  if (camposEditar.estado) {
    camposEditar.estado.addEventListener('change', actualizarDisponibilidadPublicacion);
  }

  if (formularioEditar) {
    formularioEditar.addEventListener('submit', async function (evento) {
      evento.preventDefault();
      const boton = formularioEditar.querySelector('[type="submit"]');
      if (boton) boton.disabled = true;
      if (mensajeEditar) {
        mensajeEditar.textContent = 'Guardando cambios...';
        mensajeEditar.className = 'mensaje-edicion-torneo informativo grupo-formulario-completo';
      }

      try {
        const datos = {
          nombre: camposEditar.nombre.value.trim(),
          id_disciplina: Number(camposEditar.disciplina.value),
          id_categoria: Number(camposEditar.categoria.value),
          id_tipo_torneo: Number(camposEditar.tipo.value),
          id_organizador: Number(camposEditar.organizador.value),
          modalidad: camposEditar.modalidad.value,
          fecha_inicio: camposEditar.inicio.value,
          hora_inicio: camposEditar.horaInicio ? camposEditar.horaInicio.value : '09:00',
          fecha_fin: camposEditar.fin.value,
          estado: modoFormulario === 'crear' ? 'borrador' : camposEditar.estado.value,
          publicado: modoFormulario === 'crear' ? false : Boolean(camposEditar.publicado && camposEditar.publicado.checked),
          cupo_maximo: camposEditar.cupo.value.trim(),
          periodo_gracia_resultado: camposEditar.periodoGracia ? Number(camposEditar.periodoGracia.value || 60) : 60
        };
        if (modoFormulario === 'editar') datos.id_torneo = Number(camposEditar.id.value);

        const respuesta = await fetch(modoFormulario === 'crear' ? 'api/torneo_crear.php' : 'api/torneo_actualizar.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify(datos)
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || (modoFormulario === 'crear' ? 'No se pudo crear el torneo.' : 'No se pudo actualizar el torneo.'));
        const idGuardado = Number(resultado.torneo && resultado.torneo.id_torneo ? resultado.torneo.id_torneo : datos.id_torneo);
        const realizacion = realizacionSeleccionada();
        if (idGuardado && realizacion) modalidadesTorneos.guardar(idGuardado, realizacion);
        actualizarRealizacionesEnTarjetas();
        if (idGuardado && inputImagenTorneo && inputImagenTorneo.files && inputImagenTorneo.files[0]) await subirImagenTorneo(idGuardado);
        formularioTorneoModificado = false;
        window.clearTimeout(temporizadorBorradorTorneo);
        if (modoFormulario === 'crear') localStorage.removeItem(claveBorradorTorneo);
        actualizarEstadoGuardadoTorneo('guardado', 'Guardado correctamente');
        if (mensajeEditar) {
          mensajeEditar.textContent = resultado.mensaje;
          mensajeEditar.className = 'mensaje-edicion-torneo exito grupo-formulario-completo';
        }
        window.setTimeout(function () {
          if (modoFormulario === 'crear' && idGuardado) window.location.href = 'torneos.php?creado=' + encodeURIComponent(idGuardado);
          else window.location.reload();
        }, 650);
      } catch (error) {
        if (mensajeEditar) {
          mensajeEditar.textContent = error.message;
          mensajeEditar.className = 'mensaje-edicion-torneo error grupo-formulario-completo';
        }
      } finally {
        if (boton) boton.disabled = false;
      }
    });
  }

  if (formularioEditar) {
    formularioEditar.addEventListener('input', function (evento) {
      if (evento.target === inputImagenTorneo) return;
      marcarFormularioTorneoModificado();
    });
    formularioEditar.addEventListener('change', function () { marcarFormularioTorneoModificado(); });
  }

  if (inputImagenTorneo) {
    inputImagenTorneo.addEventListener('change', function () {
      marcarFormularioTorneoModificado();
      const archivo = inputImagenTorneo.files && inputImagenTorneo.files[0];
      if (!archivo) {
        if (modoFormulario === 'editar' && camposEditar.id.value) cargarImagenTorneoExistente(camposEditar.id.value);
        else ocultarPreviewTorneo();
        return;
      }
      liberarPreviewTorneo();
      urlPreviewTorneo = URL.createObjectURL(archivo);
      mostrarPreviewTorneo(urlPreviewTorneo, false);
    });
  }

  if (quitarImagenTorneo) {
    quitarImagenTorneo.addEventListener('click', async function () {
      if (modoFormulario !== 'editar' || !camposEditar.id.value) {
        if (inputImagenTorneo) inputImagenTorneo.value = '';
        ocultarPreviewTorneo();
        return;
      }
      const confirmar = window.ArenaCJDConfirmar ? await window.ArenaCJDConfirmar({titulo:'Quitar imagen del torneo',mensaje:'La imagen dejará de mostrarse en ArenaCJD, pero el torneo y su historial se conservarán.',confirmar:'Quitar imagen'}) : true;
      if (!confirmar) return;
      try {
        const respuesta = await fetch('api/torneo_imagen_eliminar.php', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({id_torneo:Number(camposEditar.id.value)})});
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo quitar la imagen.');
        if (inputImagenTorneo) inputImagenTorneo.value = '';
        ocultarPreviewTorneo();
        if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(resultado.mensaje || 'Imagen eliminada.', 'exito');
      } catch (error) {
        if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(error.message, 'error');
      }
    });
  }

  lista.addEventListener('click', function (evento) {
    const boton = evento.target.closest('[data-accion-torneo]');
    if (!boton) return;
    const accion = boton.dataset.accionTorneo;
    const id = boton.dataset.idTorneo;

    if (accion === 'menu') {
      const menu = document.querySelector('[data-menu-torneo="' + id + '"]');
      if (!menu) return;
      const abrir = menu.hidden;
      cerrarMenusContextuales(menu);
      menu.hidden = !abrir;
      if (abrir) {
        const rect = boton.getBoundingClientRect();
        const cabecera = document.querySelector('.encabezado-app');
        const limiteSuperior = Math.max(12, cabecera ? cabecera.getBoundingClientRect().bottom + 8 : 84);
        const arriba = Math.max(0, rect.top - limiteSuperior - 8);
        const abajo = Math.max(0, window.innerHeight - rect.bottom - 20);
        const haciaAbajo = abajo > arriba;
        menu.classList.toggle('menu-hacia-abajo', haciaAbajo);
        menu.style.maxHeight = Math.max(80, haciaAbajo ? abajo : arriba) + 'px';
      }
      boton.setAttribute('aria-expanded', abrir ? 'true' : 'false');
      return;
    }

    cerrarMenusContextuales();
    if (accion === 'detalles') abrirDetalles(id);
    if (accion === 'editar') abrirEditar(id);
    if (accion === 'cancelar') cancelarTorneo(id);
  });

  const torneoCreado = new URLSearchParams(window.location.search).get('creado');
  if (torneoCreado) {
    window.setTimeout(function () {
      if (typeof window.ArenaCJDAvisar === 'function') window.ArenaCJDAvisar('Torneo creado. El siguiente paso recomendado es registrar sus participantes.', 'exito');
      const url = new URL(window.location.href);
      url.searchParams.delete('creado');
      history.replaceState(null, '', url);
    }, 250);
  }

  document.addEventListener('click', function (evento) {
    if (!evento.target.closest('.contenedor-menu-torneo')) cerrarMenusContextuales();
  });

  document.querySelectorAll('[data-cerrar-modal-torneo]').forEach(function (boton) {
    boton.addEventListener('click', async function () {
      const tipo = boton.dataset.cerrarModalTorneo;
      if (tipo === 'editar') {
        await intentarCerrarFormularioTorneo();
        return;
      }
      cerrarModal(tipo === 'cancelar' ? modalCancelar : modalDetalle);
      if (tipo === 'cancelar') torneoPendienteCancelar = null;
    });
  });

  document.addEventListener('keydown', async function (evento) {
    if (evento.key === 'Escape') {
      cerrarModal(modalDetalle);
      if (modalEditar && modalEditar.classList.contains('abierto')) await intentarCerrarFormularioTorneo();
      cerrarModal(modalCancelar);
      torneoPendienteCancelar = null;
      cerrarMenusContextuales();
    }
  });


  if (crearNuevoTorneo) {
    crearNuevoTorneo.addEventListener('click', abrirCrear);
  }

  if (confirmarCancelar) confirmarCancelar.addEventListener('click', ejecutarCancelacionTorneo);
  if (camposEditar.disciplina) camposEditar.disciplina.addEventListener('change', function () { actualizarCatalogosDependientes('', ''); });
  if (camposEditar.inicio) camposEditar.inicio.addEventListener('change', function () { if (camposEditar.inicio.value) camposEditar.fin.min = camposEditar.inicio.value; if (camposEditar.fin.value && camposEditar.fin.value < camposEditar.inicio.value) camposEditar.fin.value = camposEditar.inicio.value; });

  if (formularioFiltros) {
    formularioFiltros.addEventListener('submit', function (evento) {
      evento.preventDefault();
      aplicarFiltros();
    });
    formularioFiltros.addEventListener('reset', function () {
      window.setTimeout(aplicarFiltros, 0);
    });
  }
  [buscar, categoria, disciplina, estado, fechaDesde, fechaHasta].forEach(function (control) {
    if (!control) return;
    control.addEventListener(control === buscar ? 'input' : 'change', aplicarFiltros);
  });

  if (controlesPaginacion) {
    controlesPaginacion.addEventListener('click', function (evento) {
      const boton = evento.target.closest('[data-pagina-torneo]');
      if (!boton) return;
      const accion = boton.dataset.paginaTorneo;
      const totalPaginas = Math.max(1, Math.ceil(filtradas.length / TORNEOS_POR_PAGINA));
      if (accion === 'anterior') paginaActual -= 1;
      else if (accion === 'siguiente') paginaActual += 1;
      else paginaActual = Number(accion);
      paginaActual = Math.min(Math.max(1, paginaActual), totalPaginas);
      renderizarPaginacion();
      lista.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  function construirFirma(torneos) {
    return JSON.stringify((torneos || []).map(function (torneo) {
      return [
        torneo.id_torneo,
        torneo.nombre,
        torneo.estado,
        torneo.fecha_inicio,
        torneo.hora_inicio,
        torneo.fecha_fin,
        torneo.cantidad_inscritos,
        torneo.total_enfrentamientos,
        torneo.enfrentamientos_finalizados,
        torneo.id_disciplina,
        torneo.id_categoria,
        torneo.id_tipo_torneo,
        torneo.id_organizador
      ];
    }));
  }

  function actualizarTextoSincronizacion() {
    if (!sincronizacion) return;
    const ahora = new Date();
    sincronizacion.textContent = 'Actualizado · ' + ahora.toLocaleTimeString('es-UY', { hour: '2-digit', minute: '2-digit' });
  }

  async function comprobarCambios(forzarRecarga) {
    try {
      const respuesta = await fetch('api/torneos.php', { cache: 'no-store' });
      if (!respuesta.ok) return;
      const resultado = await respuesta.json();
      if (!resultado.exito || !Array.isArray(resultado.torneos)) return;
      const firma = construirFirma(resultado.torneos);
      actualizarTextoSincronizacion();

      if (firmaServidor === null) {
        firmaServidor = firma;
        if (forzarRecarga) window.location.reload();
        return;
      }

      if (forzarRecarga || firma !== firmaServidor) {
        if (!document.querySelector('.modal-torneo.abierto')) window.location.reload();
      }
    } catch (error) {
      if (sincronizacion) sincronizacion.textContent = 'Sin conexión de sincronización · se mantiene la vista actual';
    }
  }

  if (actualizarAhora) {
    actualizarAhora.addEventListener('click', function () {
      comprobarCambios(true);
    });
  }

  if (resumenFiltros) {
    resumenFiltros.addEventListener('click', function (evento) {
      if (!evento.target.closest('[data-limpiar-filtros-torneos]')) return;
      limpiarFiltros();
      if (buscar) buscar.focus();
    });
  }

  if (accionSin) {
    accionSin.addEventListener('click', function () {
      const accion = accionSin.dataset.accionVacioTorneo;
      if (accion === 'limpiar') {
        limpiarFiltros();
        if (buscar) buscar.focus();
      } else if (accion === 'crear' && crearNuevoTorneo) {
        abrirCrear();
      } else {
        comprobarCambios(true);
      }
    });
  }

  aplicarFiltros();
  cargarPreferenciaPaginacion();
  comprobarCambios(false);
  if (window.location.hash === '#nuevo-torneo' && crearNuevoTorneo) {
    window.setTimeout(abrirCrear, 0);
  }
  const detalleUrl = Number(new URLSearchParams(window.location.search).get('detalle') || 0);
  if (detalleUrl > 0) {
    window.setTimeout(function () { abrirDetalles(detalleUrl); }, 0);
  }
  window.addEventListener('beforeunload', function (evento) {
    if (!formularioTorneoModificado) return;
    evento.preventDefault();
    evento.returnValue = '';
  });
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('torneos-vista', function () { return comprobarCambios(false); }, 10000, {ejecutarAhora:false});
}());
