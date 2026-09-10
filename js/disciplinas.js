(function () {
  'use strict';

  const cuerpo = document.getElementById('cuerpoDisciplinas');
  if (!cuerpo) return;

  const modal = document.getElementById('modalDisciplina');
  const formulario = document.getElementById('formularioDisciplina');
  const crear = document.getElementById('crearDisciplina');
  const buscar = document.getElementById('buscarDisciplina');
  const filtroEstado = document.getElementById('filtroEstadoDisciplina');
  const actualizar = document.getElementById('actualizarDisciplinas');
  const mensaje = document.getElementById('mensajeDisciplina');
  const titulo = document.getElementById('tituloModalDisciplina');
  const descripcion = document.getElementById('descripcionModalDisciplina');
  const guardar = document.getElementById('guardarDisciplina');
  const campoId = document.getElementById('disciplinaId');
  const campoNombre = document.getElementById('disciplinaNombre');
  const campoEstado = document.getElementById('disciplinaEstado');
  const grupoCategorias = document.getElementById('disciplinaCategorias');
  const grupoTipos = document.getElementById('disciplinaTipos');
  const sincronizacion = document.getElementById('sincronizacionDisciplinas');
  const sin = document.getElementById('sinDisciplinas');
  const tituloSin = document.getElementById('tituloSinDisciplinas');
  const detalleSin = document.getElementById('detalleSinDisciplinas');
  const accionSin = document.getElementById('accionSinDisciplinas');
  const resumenFiltros = document.getElementById('resumenFiltrosDisciplinas');
  const modalConfirmacion = document.getElementById('modalConfirmacionCatalogo');
  const tituloConfirmacion = document.getElementById('tituloConfirmacionCatalogo');
  const mensajeConfirmacion = document.getElementById('mensajeConfirmacionCatalogo');
  const iconoConfirmacion = document.getElementById('iconoConfirmacionCatalogo');
  const cancelarConfirmacion = document.getElementById('cancelarConfirmacionCatalogo');
  const aceptarConfirmacion = document.getElementById('aceptarConfirmacionCatalogo');
  const abrirCatalogo = document.getElementById('abrirCatalogoCategorias');
  const modalCatalogo = document.getElementById('modalCatalogoCategorias');
  const listaCatalogo = document.getElementById('listaCatalogoCategorias');
  const formularioCategoria = document.getElementById('formularioCategoria');
  const categoriaId = document.getElementById('categoriaId');
  const categoriaNombre = document.getElementById('categoriaNombre');
  const guardarCategoria = document.getElementById('guardarCategoria');
  const cancelarCategoria = document.getElementById('cancelarEdicionCategoria');
  const mensajeCategoria = document.getElementById('mensajeCategoria');
  let guardandoCategoria = false;
  let disciplinas = [];
  let csrf = '';
  let editando = false;
  let firma = '';
  let catalogos = {categorias: [], tipos: []};
  let resolverConfirmacion = null;
  let focoAntesConfirmacion = null;
  let focoAntesModal = null;
  let focoAntesCatalogo = null;

  const parametros = new URLSearchParams(window.location.search);

  function escapar(valor) {
    return String(valor == null ? '' : valor).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }

  function normalizar(valor) {
    return String(valor || '').toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();
  }

  function avisar(texto, tipo, opciones) {
    if (window.ArenaCJDAvisar) return window.ArenaCJDAvisar(texto, tipo, opciones);
    return null;
  }

  function abrirModal() {
    focoAntesModal = document.activeElement;
    modal.classList.add('abierto');
    modal.setAttribute('aria-hidden','false');
    document.body.classList.add('modal-torneo-activo');
    window.setTimeout(function(){ campoNombre.focus(); }, 20);
  }

  function cerrarModal() {
    modal.classList.remove('abierto');
    modal.setAttribute('aria-hidden','true');
    document.body.classList.remove('modal-torneo-activo');
    if (focoAntesModal && typeof focoAntesModal.focus === 'function') focoAntesModal.focus();
    focoAntesModal = null;
  }

  function mensajeModal(texto, tipo) {
    mensaje.textContent = texto || '';
    mensaje.className = 'mensaje-edicion-torneo' + (tipo ? ' ' + tipo : '');
  }

  function cerrarConfirmacion(valor) {
    if (!modalConfirmacion || !modalConfirmacion.classList.contains('abierto')) return;
    modalConfirmacion.classList.remove('abierto', 'informativo');
    if (focoAntesConfirmacion && focoAntesConfirmacion.isConnected) focoAntesConfirmacion.focus();
    modalConfirmacion.setAttribute('aria-hidden','true');
    const resolver = resolverConfirmacion;
    resolverConfirmacion = null;
    if (resolver) resolver(Boolean(valor));
  }

  function pedirConfirmacion(opciones) {
    if (!modalConfirmacion) return Promise.resolve(false);
    const config = opciones || {};
    tituloConfirmacion.textContent = config.titulo || 'Confirmar acción';
    mensajeConfirmacion.textContent = config.mensaje || '';
    iconoConfirmacion.innerHTML = config.icono || window.ArenaCJDIcono('alerta');
    cancelarConfirmacion.hidden = Boolean(config.soloAviso);
    aceptarConfirmacion.textContent = config.textoAceptar || (config.soloAviso ? 'Entendido' : 'Confirmar');
    aceptarConfirmacion.classList.toggle('boton-confirmacion-peligro', Boolean(config.peligro));
    modalConfirmacion.classList.toggle('informativo', Boolean(config.soloAviso) || !config.peligro);
    modalConfirmacion.classList.add('abierto');
    modalConfirmacion.setAttribute('aria-hidden','false');
    window.setTimeout(function(){ aceptarConfirmacion.focus(); }, 20);
    return new Promise(function(resolve){ resolverConfirmacion = resolve; });
  }

  function mostrarAviso(tituloAviso, textoAviso) {
    return pedirConfirmacion({titulo:tituloAviso, mensaje:textoAviso, textoAceptar:'Entendido', soloAviso:true, icono:window.ArenaCJDIcono('info')});
  }

  function idsMarcados(nombre) {
    return Array.from(formulario.querySelectorAll('input[name="' + nombre + '"]:checked')).map(function(input){ return Number(input.value); });
  }

  function nombreCategoria(id) {
    const item = (catalogos.categorias || []).find(function(categoria){ return Number(categoria.id) === Number(id); });
    return item ? item.nombre : '';
  }

  function nombreTipo(id) {
    const item = (catalogos.tipos || []).find(function(tipo){ return Number(tipo.id) === Number(id); });
    return item ? item.nombre : '';
  }

  function chipsAsociacion(ids, obtenerNombre, clase) {
    const nombres = (ids || []).map(obtenerNombre).filter(Boolean);
    if (!nombres.length) return '<span class="texto-asociacion-vacia">Sin asociaciones</span>';
    return '<div class="chips-asociaciones-disciplina">' + nombres.map(function(nombre){
      return '<span class="chip-asociacion-disciplina ' + clase + '">' + escapar(nombre) + '</span>';
    }).join('') + '</div>';
  }

  function renderAsociaciones(seleccionCategorias, seleccionTipos) {
    const cats = new Set((seleccionCategorias || []).map(Number));
    const tipos = new Set((seleccionTipos || []).map(Number));
    if (grupoCategorias) {
      grupoCategorias.innerHTML = (catalogos.categorias || []).map(function(item){
        const id = Number(item.id);
        const idCampo = 'disciplina-categoria-' + id;
        return '<label class="opcion-asociacion-disciplina" for="' + idCampo + '"><input id="' + idCampo + '" type="checkbox" name="disciplina_categoria" value="' + id + '"' + (cats.has(id) ? ' checked' : '') + '><span>' + escapar(item.nombre) + '</span></label>';
      }).join('');
    }
    if (grupoTipos) {
      grupoTipos.innerHTML = (catalogos.tipos || []).map(function(item){
        const id = Number(item.id);
        const idCampo = 'disciplina-tipo-' + id;
        return '<label class="opcion-asociacion-disciplina" for="' + idCampo + '"><input id="' + idCampo + '" type="checkbox" name="disciplina_tipo" value="' + id + '"' + (tipos.has(id) ? ' checked' : '') + '><span>' + escapar(item.nombre) + '</span></label>';
      }).join('');
    }
  }

  function renderCatalogoCategorias() {
    if (!listaCatalogo) return;
    const items = catalogos.categorias || [];
    if (!items.length) {
      listaCatalogo.innerHTML = '<div class="estado-vacio-ah"><strong>No hay categorías registradas</strong><p>Las categorías disponibles aparecerán aquí.</p></div>';
      return;
    }
    listaCatalogo.innerHTML = items.map(function(item){
      const torneos = Number(item.cantidad_torneos || 0);
      const disciplinasAsociadas = Number(item.cantidad_disciplinas || 0);
      const bloqueada = torneos > 0 || disciplinasAsociadas > 0;
      const motivo = torneos > 0 ? 'No se elimina: utilizada por torneos, incluidos los históricos.' : disciplinasAsociadas > 0 ? 'No se elimina: tiene disciplinas asociadas.' : 'Sin asociaciones ni torneos; se puede eliminar.';
      const nombres = (item.disciplinas || []).map(d => escapar(d.nombre) + (d.estado === 'inactiva' ? ' (inactiva)' : '')).join(', ') || 'Ninguna';
      return '<article class="item-catalogo-categoria"><div><strong>' + escapar(item.nombre) + '</strong><span>' + disciplinasAsociadas + ' disciplinas · ' + torneos + ' torneos</span><small>Disciplinas: ' + nombres + '</small><small>' + motivo + '</small></div><div class="acciones-item-categoria"><button class="boton boton-claro" type="button" data-editar-categoria="' + Number(item.id) + '" aria-label="Editar ' + escapar(item.nombre) + '">' + window.ArenaCJDIcono('editar') + ' Editar</button><button class="boton boton-claro boton-eliminar-catalogo" type="button" data-eliminar-categoria="' + Number(item.id) + '" data-nombre-categoria="' + escapar(item.nombre) + '"' + (bloqueada ? ' disabled aria-disabled="true" title="' + motivo + '"' : '') + '>' + window.ArenaCJDIcono('eliminar') + (bloqueada ? ' En uso' : ' Eliminar') + '</button></div></article>';
    }).join('');
  }

  function abrirModalCatalogo() {
    if (!modalCatalogo) return;
    focoAntesCatalogo = document.activeElement;
    reiniciarEditorCategoria();
    renderCatalogoCategorias();
    modalCatalogo.classList.add('abierto');
    modalCatalogo.setAttribute('aria-hidden','false');
    const primerBoton = modalCatalogo.querySelector('button:not([disabled])');
    if (primerBoton) window.setTimeout(function(){ primerBoton.focus(); }, 20);
  }

  function cerrarModalCatalogo() {
    if (!modalCatalogo) return;
    modalCatalogo.classList.remove('abierto');
    modalCatalogo.setAttribute('aria-hidden','true');
    if (focoAntesCatalogo && typeof focoAntesCatalogo.focus === 'function') focoAntesCatalogo.focus();
    focoAntesCatalogo = null;
  }

  async function eliminarCategoria(idCategoria, nombreCategoria) {
    const id = Number(idCategoria || 0);
    if (!id) return;
    const nombre = String(nombreCategoria || 'esta categoría');
    const confirmar = await pedirConfirmacion({
      titulo:'Eliminar categoría del catálogo',
      mensaje:'¿Quieres eliminar la categoría "' + nombre + '" de ArenaCJD?\n\nDejará de estar disponible en todas las disciplinas y formularios. La operación se bloqueará si tiene disciplinas asociadas o cualquier torneo que la utilice.',
      textoAceptar:'Eliminar categoría',
      peligro:true,
      icono:window.ArenaCJDIcono('peligro')
    });
    if (!confirmar) return;

    const categoriasSeleccionadas = modal.classList.contains('abierto') ? idsMarcados('disciplina_categoria').filter(function(valor){ return valor !== id; }) : [];
    const tiposSeleccionados = modal.classList.contains('abierto') ? idsMarcados('disciplina_tipo') : [];
    try {
      const respuesta = await fetch('api/categoria_eliminar.php', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
        body:JSON.stringify({id_categoria:id})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) {
        const errorSolicitud = new Error(resultado.mensaje || 'No se pudo eliminar la categoría.');
        errorSolicitud.status = respuesta.status;
        throw errorSolicitud;
      }
      await cargar(true);
      if (modal.classList.contains('abierto')) renderAsociaciones(categoriasSeleccionadas, tiposSeleccionados);
      renderCatalogoCategorias();
      window.dispatchEvent(new CustomEvent('arenacjd:categorias-actualizadas'));
      avisar(resultado.mensaje || 'Categoría eliminada correctamente.', 'exito');
    } catch (error) {
      if (Number(error.status) === 409) {
        await mostrarAviso('No se puede eliminar', error.message || 'La categoría no se puede eliminar mientras esté en uso.');
      } else {
        avisar(error.message || 'No se pudo eliminar la categoría.', 'error', {titulo:'No se pudo eliminar'});
      }
    }
  }

  async function eliminarDisciplina(idDisciplina, nombreDisciplina, cantidadTorneos) {
    const id = Number(idDisciplina || 0);
    if (!id) return;
    const nombre = String(nombreDisciplina || 'esta disciplina');
    const torneos = Number(cantidadTorneos || 0);
    if (torneos > 0) {
      await mostrarAviso('Disciplina protegida por historial', 'La disciplina "' + nombre + '" está utilizada por ' + torneos + (torneos === 1 ? ' torneo.' : ' torneos.') + '\n\nEdítala y cambia su estado a Inactiva si ya no quieres ofrecerla en torneos nuevos.');
      return;
    }
    const confirmar = await pedirConfirmacion({
      titulo:'Eliminar disciplina',
      mensaje:'¿Quieres eliminar la disciplina "' + nombre + '"?\n\nDesaparecerá del catálogo. Esta acción solo está disponible cuando la disciplina no tiene torneos asociados.',
      textoAceptar:'Eliminar disciplina',
      peligro:true,
      icono:window.ArenaCJDIcono('peligro')
    });
    if (!confirmar) return;

    try {
      const respuesta = await fetch('api/disciplina_eliminar.php', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
        body:JSON.stringify({id_disciplina:id})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) {
        const errorSolicitud = new Error(resultado.mensaje || 'No se pudo eliminar la disciplina.');
        errorSolicitud.status = respuesta.status;
        throw errorSolicitud;
      }
      await cargar(true);
      window.dispatchEvent(new CustomEvent('arenacjd:disciplinas-actualizadas'));
      avisar(resultado.mensaje || 'Disciplina eliminada correctamente.', 'exito');
    } catch (error) {
      if (Number(error.status) === 409) {
        await mostrarAviso('No se puede eliminar', error.message || 'La disciplina no se puede eliminar mientras esté en uso.');
      } else {
        avisar(error.message || 'No se pudo eliminar la disciplina.', 'error', {titulo:'No se pudo eliminar'});
      }
    }
  }

  function abrirCrear() {
    editando = false;
    formulario.reset();
    campoId.value = '';
    campoEstado.value = 'activa';
    titulo.textContent = 'Nueva disciplina';
    descripcion.textContent = 'Define explícitamente las categorías y formatos disponibles para esta disciplina.';
    guardar.textContent = 'Crear disciplina';
    renderAsociaciones([], []);
    mensajeModal('');
    abrirModal();
  }

  function abrirEditar(id) {
    const disciplina = disciplinas.find(function(item){ return String(item.id_disciplina) === String(id); });
    if (!disciplina) return;
    editando = true;
    campoId.value = disciplina.id_disciplina;
    campoNombre.value = disciplina.nombre;
    campoEstado.value = disciplina.estado;
    titulo.textContent = 'Editar disciplina';
    descripcion.textContent = 'Los cambios definen las opciones disponibles en configuraciones futuras. Los torneos ya creados conservan sus datos e historial.';
    guardar.textContent = 'Guardar cambios';
    renderAsociaciones(disciplina.categorias_ids || [], disciplina.tipos_ids || []);
    mensajeModal('');
    abrirModal();
  }

  function actualizarResumenFiltros() {
    if (!resumenFiltros) return;
    const chips = [];
    const texto = buscar.value.trim();
    if (texto) chips.push('<span class="chip-filtro-ah">Búsqueda: ' + escapar(texto) + '</span>');
    if (filtroEstado.value !== 'todas') chips.push('<span class="chip-filtro-ah">Estado: ' + escapar(filtroEstado.options[filtroEstado.selectedIndex].text) + '</span>');
    if (!chips.length) {
      resumenFiltros.hidden = true;
      resumenFiltros.innerHTML = '';
      return;
    }
    resumenFiltros.hidden = false;
    resumenFiltros.innerHTML = chips.join('') + '<button class="limpiar-filtros-ah" type="button" id="limpiarFiltrosDisciplinas">Quitar filtros</button>';
  }

  function render() {
    const texto = normalizar(buscar.value);
    const estado = filtroEstado.value;
    const filtradas = disciplinas.filter(function(item){
      return (!texto || normalizar(item.nombre).includes(texto)) && (estado === 'todas' || item.estado === estado);
    });

    cuerpo.innerHTML = filtradas.map(function(item){
      const cantidadTorneos = Number(item.cantidad_torneos || 0);
      const eliminar = cantidadTorneos > 0
        ? '<button class="boton boton-claro accion-eliminar-disciplina" type="button" disabled aria-disabled="true" title="Tiene torneos asociados; cambia su estado a Inactiva desde Editar">En uso</button>'
        : '<button class="boton boton-claro accion-eliminar-disciplina" type="button" data-eliminar-disciplina="' + Number(item.id_disciplina) + '" data-nombre-disciplina="' + escapar(item.nombre) + '" data-torneos-disciplina="0">Eliminar</button>';
      return '<tr data-id-disciplina="' + Number(item.id_disciplina) + '"><td data-label="Disciplina"><strong>' + escapar(item.nombre) + '</strong></td><td data-label="Estado"><span class="estado-disciplina ' + escapar(item.estado) + '">' + escapar(item.estado === 'activa' ? 'Activa' : 'Inactiva') + '</span></td><td data-label="Torneos"><strong class="dato-torneos-disciplina">' + cantidadTorneos + '</strong></td><td data-label="Categorías">' + chipsAsociacion(item.categorias_ids, nombreCategoria, 'categoria') + '</td><td data-label="Tipos de torneo">' + chipsAsociacion(item.tipos_ids, nombreTipo, 'tipo') + '</td><td data-label="Acciones"><div class="acciones-disciplina-tabla"><button class="boton boton-claro accion-editar-disciplina" type="button" data-editar-disciplina="' + Number(item.id_disciplina) + '">Editar</button>' + eliminar + '</div></td></tr>';
    }).join('');

    const hayDisciplinas = disciplinas.length > 0;
    const hayResultados = filtradas.length > 0;
    sin.hidden = hayResultados;
    if (!hayResultados) {
      if (!hayDisciplinas) {
        tituloSin.textContent = 'No hay disciplinas todavía';
        detalleSin.textContent = 'Crea una disciplina para empezar a configurar los torneos.';
        accionSin.textContent = 'Crear disciplina';
        accionSin.dataset.accionVacio = 'crear';
      } else {
        tituloSin.textContent = 'No hay disciplinas que coincidan con tu búsqueda';
        detalleSin.textContent = 'Prueba cambiando los filtros o el término de búsqueda.';
        accionSin.textContent = 'Quitar filtros';
        accionSin.dataset.accionVacio = 'limpiar';
      }
    }
    document.getElementById('totalDisciplinas').textContent = disciplinas.length;
    document.getElementById('disciplinasActivas').textContent = disciplinas.filter(function(item){return item.estado === 'activa';}).length;
    document.getElementById('disciplinasInactivas').textContent = disciplinas.filter(function(item){return item.estado === 'inactiva';}).length;
    actualizarResumenFiltros();
  }

  function construirFirma(datos) {
    return JSON.stringify((datos || []).map(function(item){return [item.id_disciplina,item.nombre,item.estado,item.cantidad_torneos,item.cantidad_categorias,item.cantidad_tipos,item.categorias_ids,item.tipos_ids];}));
  }

  async function cargar(forzar, notificar) {
    try {
      if (actualizar) actualizar.disabled = true;
      if (notificar) sincronizacion.textContent = 'Actualizando...';
      const respuesta = await fetch('api/disciplinas.php?_=' + Date.now(), {cache:'no-store'});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron cargar las disciplinas.');
      const nuevaFirma = construirFirma(resultado.disciplinas);
      csrf = resultado.csrf_token || csrf;
      catalogos = resultado.catalogos || catalogos;
      if (forzar || nuevaFirma !== firma) {
        disciplinas = resultado.disciplinas || [];
        firma = nuevaFirma;
        render();
      }
      sincronizacion.textContent = 'Actualizado · ' + new Date().toLocaleTimeString('es-UY',{hour:'2-digit',minute:'2-digit'});
      if (notificar) avisar('Disciplinas actualizadas.', 'info');
    } catch (error) {
      sincronizacion.textContent = 'No se pudo actualizar';
      if (!disciplinas.length) {
        if (window.ArenaCJDEstadoError) window.ArenaCJDEstadoError(sin, error.message, function () { return cargar(true); });
        else { sin.hidden = false; sin.textContent = error.message; }
      } else {
        avisar(error.message || 'No se pudieron actualizar las disciplinas.', 'error', {titulo:'Actualización fallida'});
      }
    } finally {
      if (actualizar) actualizar.disabled = false;
    }
  }

  crear.addEventListener('click', abrirCrear);
  buscar.addEventListener('input', render);
  filtroEstado.addEventListener('change', render);
  actualizar.addEventListener('click', function(){ cargar(true, true); });
  cuerpo.addEventListener('click', function(evento){
    const editar = evento.target.closest('[data-editar-disciplina]');
    if (editar) {
      abrirEditar(editar.dataset.editarDisciplina);
      return;
    }
    const eliminar = evento.target.closest('[data-eliminar-disciplina]');
    if (eliminar) eliminarDisciplina(eliminar.dataset.eliminarDisciplina, eliminar.dataset.nombreDisciplina, eliminar.dataset.torneosDisciplina);
  });

  if (resumenFiltros) {
    resumenFiltros.addEventListener('click', function(evento){
      if (!evento.target.closest('#limpiarFiltrosDisciplinas')) return;
      buscar.value = '';
      filtroEstado.value = 'todas';
      render();
      buscar.focus();
    });
  }

  if (accionSin) {
    accionSin.addEventListener('click', function(){
      if (accionSin.dataset.accionVacio === 'limpiar') {
        buscar.value = '';
        filtroEstado.value = 'todas';
        render();
        buscar.focus();
      } else {
        abrirCrear();
      }
    });
  }

  function reiniciarEditorCategoria() {
    formularioCategoria.reset();
    categoriaId.value = '';
    document.getElementById('etiquetaNombreCategoria').textContent = 'Nueva categoría';
    guardarCategoria.textContent = 'Crear categoría';
    cancelarCategoria.hidden = true;
    mensajeCategoria.textContent = '';
  }
  cancelarCategoria.addEventListener('click', function () { if (!guardandoCategoria) { reiniciarEditorCategoria(); categoriaNombre.focus(); } });
  formularioCategoria.addEventListener('submit', async function (evento) {
    evento.preventDefault();
    if (guardandoCategoria || !formularioCategoria.reportValidity()) return;
    guardandoCategoria = true; guardarCategoria.disabled = true; cancelarCategoria.disabled = true;
    mensajeCategoria.textContent = 'Guardando…';
    const id = Number(categoriaId.value);
    const cats = idsMarcados('disciplina_categoria'), tipos = idsMarcados('disciplina_tipo');
    try {
      const respuesta = await fetch(id ? 'api/categoria_actualizar.php' : 'api/categoria_crear.php', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
        body:JSON.stringify({id_categoria:id, nombre:categoriaNombre.value.trim()})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo guardar la categoría.');
      await cargar(true);
      if (modal.classList.contains('abierto')) renderAsociaciones(cats, tipos);
      renderCatalogoCategorias(); reiniciarEditorCategoria();
      window.dispatchEvent(new CustomEvent('arenacjd:categorias-actualizadas'));
      avisar(resultado.mensaje, 'exito');
      categoriaNombre.focus();
    } catch (error) { mensajeCategoria.textContent = error.message; avisar(error.message, 'error'); }
    finally { guardandoCategoria = false; guardarCategoria.disabled = false; cancelarCategoria.disabled = false; }
  });
  document.getElementById('abrirGestionCategorias').addEventListener('click', abrirModalCatalogo);
  if (abrirCatalogo) abrirCatalogo.addEventListener('click', abrirModalCatalogo);
  if (modalCatalogo) {
    modalCatalogo.querySelectorAll('[data-cerrar-catalogo-categorias]').forEach(function(elemento){ elemento.addEventListener('click', cerrarModalCatalogo); });
    modalCatalogo.addEventListener('click', function(evento){
      if (guardandoCategoria) return;
      const editarCategoria = evento.target.closest('[data-editar-categoria]');
      if (editarCategoria) {
        const item = catalogos.categorias.find(c => Number(c.id) === Number(editarCategoria.dataset.editarCategoria));
        if (!item) return;
        categoriaId.value = item.id; categoriaNombre.value = item.nombre;
        document.getElementById('etiquetaNombreCategoria').textContent = 'Editar categoría';
        guardarCategoria.textContent = 'Guardar cambios'; cancelarCategoria.hidden = false;
        mensajeCategoria.textContent = ''; categoriaNombre.focus(); return;
      }
      const boton = evento.target.closest('[data-eliminar-categoria]');
      if (boton && !boton.disabled) eliminarCategoria(boton.dataset.eliminarCategoria, boton.dataset.nombreCategoria);
    });
  }

  modalCatalogo.addEventListener('keydown', function (e) { if (e.key === 'Escape') { e.stopPropagation(); cerrarModalCatalogo(); } });
  modalConfirmacion.addEventListener('keydown', function (e) { if (e.key === 'Escape') { e.stopPropagation(); cerrarConfirmacion(false); } });

  document.querySelectorAll('[data-cerrar-disciplina]').forEach(function(boton){ boton.addEventListener('click', cerrarModal); });
  if (modalConfirmacion) {
    modalConfirmacion.querySelectorAll('[data-cancelar-confirmacion]').forEach(function(elemento){ elemento.addEventListener('click', function(){ cerrarConfirmacion(false); }); });
    aceptarConfirmacion.addEventListener('click', function(){ cerrarConfirmacion(true); });
  }

  document.addEventListener('keydown', function(evento){
    if (evento.key !== 'Escape') return;
    if (modalConfirmacion && modalConfirmacion.classList.contains('abierto')) {
      cerrarConfirmacion(false);
      return;
    }
    if (modalCatalogo && modalCatalogo.classList.contains('abierto')) {
      cerrarModalCatalogo();
      return;
    }
    if (modal.classList.contains('abierto')) cerrarModal();
  });

  formulario.addEventListener('submit', async function(evento){
    evento.preventDefault();
    const nombre = campoNombre.value.trim();
    if (nombre.length < 2) {
      mensajeModal('Escribe un nombre válido para la disciplina.', 'error');
      campoNombre.focus();
      return;
    }
    const categoriasSeleccionadas = idsMarcados('disciplina_categoria');
    const tiposSeleccionados = idsMarcados('disciplina_tipo');
    if (!categoriasSeleccionadas.length) {
      mensajeModal('Selecciona al menos una categoría para continuar.', 'error');
      const primerCheck = grupoCategorias.querySelector('input');
      if (primerCheck) primerCheck.focus();
      return;
    }
    if (!tiposSeleccionados.length) {
      mensajeModal('Selecciona al menos un tipo de torneo para continuar.', 'error');
      const primerCheck = grupoTipos.querySelector('input');
      if (primerCheck) primerCheck.focus();
      return;
    }
    const disciplinaOriginal = editando ? disciplinas.find(function(item){ return String(item.id_disciplina) === String(campoId.value); }) : null;
    const estadoAnterior = disciplinaOriginal ? disciplinaOriginal.estado : '';
    const estadoNuevo = campoEstado.value;
    guardar.disabled = true;
    mensajeModal(editando ? 'Guardando cambios...' : 'Creando disciplina...', 'informativo');
    try {
      const respuesta = await fetch(editando ? 'api/disciplina_actualizar.php' : 'api/disciplina_crear.php', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
        body:JSON.stringify({id_disciplina: editando ? Number(campoId.value) : undefined, nombre:nombre, estado:campoEstado.value, categorias:categoriasSeleccionadas, tipos:tiposSeleccionados})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo guardar la disciplina.');
      const idGuardado = Number(resultado.disciplina && resultado.disciplina.id_disciplina ? resultado.disciplina.id_disciplina : campoId.value || 0);
      let textoExito = resultado.mensaje || (editando ? 'Disciplina actualizada correctamente.' : 'Disciplina creada correctamente.');
      const seDesactivo = editando && estadoAnterior !== estadoNuevo && estadoNuevo === 'inactiva';
      if (seDesactivo) textoExito = 'Disciplina desactivada. Los torneos existentes se conservan.';
      else if (editando && estadoAnterior !== estadoNuevo && estadoNuevo === 'activa') textoExito = 'Disciplina activada correctamente.';
      await cargar(true);
      window.dispatchEvent(new CustomEvent('arenacjd:disciplinas-actualizadas'));
      mensajeModal('');
      cerrarModal();
      if (idGuardado && window.ArenaCJDResaltar) window.ArenaCJDResaltar(document.querySelector('[data-id-disciplina="' + idGuardado + '"]'));
      if (seDesactivo && disciplinaOriginal) {
        const anterior = {
          id_disciplina: Number(disciplinaOriginal.id_disciplina),
          nombre: disciplinaOriginal.nombre,
          estado: disciplinaOriginal.estado,
          categorias: Array.isArray(disciplinaOriginal.categorias_ids) ? disciplinaOriginal.categorias_ids.map(Number) : [],
          tipos: Array.isArray(disciplinaOriginal.tipos_ids) ? disciplinaOriginal.tipos_ids.map(Number) : []
        };
        avisar(textoExito, 'exito', {
          accion: {
            texto: 'Deshacer',
            ejecutar: async function () {
              const revertir = await fetch('api/disciplina_actualizar.php', {
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
                body:JSON.stringify(anterior)
              });
              const datosReversion = await revertir.json();
              if (!revertir.ok || !datosReversion.exito) throw new Error(datosReversion.mensaje || 'No se pudo deshacer el cambio.');
              await cargar(true);
              if (window.ArenaCJDResaltar) window.ArenaCJDResaltar(document.querySelector('[data-id-disciplina="' + anterior.id_disciplina + '"]'));
              avisar('Disciplina reactivada.', 'exito');
            }
          }
        });
      } else {
        avisar(textoExito, 'exito');
      }
    } catch (error) {
      mensajeModal(error.message, 'error');
    } finally {
      guardar.disabled = false;
    }
  });

  cargar(true).then(function () {
    if (parametros.get('accion') === 'nueva') abrirCrear();
  });

  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('disciplinas', function(){
    if (modal.classList.contains('abierto') || (modalCatalogo && modalCatalogo.classList.contains('abierto'))) return;
    return cargar(false);
  }, 10000, {ejecutarAhora:false});
}());
