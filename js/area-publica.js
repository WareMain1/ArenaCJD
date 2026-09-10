(function () {
  'use strict';

  const pagina = document.body && document.body.dataset.paginaPublica || '';

  const botonMenuPublico = document.getElementById('botonMenuPublico');
  const navegacionPublica = document.getElementById('navegacionPublica');

  function cerrarMenuPublico() {
    if (!botonMenuPublico || !navegacionPublica) return;
    navegacionPublica.classList.remove('abierta');
    botonMenuPublico.classList.remove('abierto');
    botonMenuPublico.setAttribute('aria-expanded', 'false');
  }

  if (botonMenuPublico && navegacionPublica) {
    botonMenuPublico.addEventListener('click', function () {
      const abierta = navegacionPublica.classList.toggle('abierta');
      botonMenuPublico.classList.toggle('abierto', abierta);
      botonMenuPublico.setAttribute('aria-expanded', abierta ? 'true' : 'false');
    });
    navegacionPublica.querySelectorAll('a').forEach(function (enlace) {
      enlace.addEventListener('click', cerrarMenuPublico);
    });
    document.addEventListener('keydown', function (evento) {
      if (evento.key === 'Escape') cerrarMenuPublico();
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 992) cerrarMenuPublico();
    });
  }

  function escapar(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function iniciales(nombre) {
    const partes = String(nombre || '').trim().split(/\s+/).filter(Boolean).slice(0, 2);
    return (partes.map(function (parte) { return parte.charAt(0); }).join('') || '•').toUpperCase();
  }

  function mediaPublica(tipo, id, nombre, clase) {
    const numero = Number(id || 0);
    const fallback = tipo === 'torneo' ? window.ArenaCJDIcono('torneo') : iniciales(nombre);
    const src = numero ? (tipo === 'equipo' ? 'api/publico/imagen_equipo.php?id_equipo=' + numero : 'api/publico/imagen_torneo.php?id_torneo=' + numero) : '';
    return '<span class="media-publica ' + (clase || '') + '"><span>' + (tipo === 'torneo' ? fallback : escapar(fallback)) + '</span>' + (src ? '<img src="' + escapar(src) + '" alt="' + escapar((tipo === 'equipo' ? 'Imagen del equipo ' : 'Imagen del torneo ') + (nombre || '')) + '" loading="lazy" onerror="this.style.display=\'none\'">' : '') + '</span>';
  }

  function participantePublico(item, lado) {
    const nombre = lado === 'a' ? (item.participante_a || 'Por definir') : (item.participante_b || 'Pase automático');
    const idEquipo = lado === 'a' ? item.id_equipo_a : item.id_equipo_b;
    if (item.tipo_participante === 'equipo' && idEquipo) {
      return '<span class="participante-publico-con-media">' + mediaPublica('equipo', idEquipo, nombre, 'media-publica-pequena') + '<strong>' + escapar(nombre) + '</strong></span>';
    }
    return '<strong>' + escapar(nombre) + '</strong>';
  }

  function activarEstadoFiltros(controles, render) {
    const resumen = document.getElementById('resumenFiltrosPublicos');
    const limpiar = document.getElementById('limpiarFiltrosPublicos');
    const lista = (controles || []).filter(Boolean);
    const clave = 'arenaCJD-filtros-publicos-v2-' + pagina;
    const parametros = new URLSearchParams(window.location.search);
    function leerGuardados() {
      try {
        const datos = JSON.parse(localStorage.getItem(clave) || 'null');
        return datos && typeof datos === 'object' ? datos : {};
      } catch (error) {
        return {};
      }
    }
    function guardar() {
      const datos = {};
      lista.forEach(function (control) { if (control.id) datos[control.id] = control.value; });
      try { localStorage.setItem(clave, JSON.stringify(datos)); } catch (error) {}
    }
    function restaurar() {
      const datos = leerGuardados();
      lista.forEach(function (control) {
        if (!control.id || !Object.prototype.hasOwnProperty.call(datos, control.id)) return;
        if (parametros.has('torneo') && /torneo/i.test(control.id)) return;
        const valor = String(datos[control.id] == null ? '' : datos[control.id]);
        if (control.tagName === 'SELECT' && !Array.from(control.options).some(function (opcion) { return String(opcion.value) === valor; })) return;
        control.value = valor;
      });
    }
    function actualizar() {
      const activos = lista.filter(function (control) { return String(control.value || '').trim() !== ''; }).length;
      if (resumen) resumen.textContent = activos ? activos + (activos === 1 ? ' filtro activo' : ' filtros activos') : 'Sin filtros activos';
      if (limpiar) limpiar.hidden = activos === 0;
    }
    restaurar();
    lista.forEach(function (control) {
      control.addEventListener(control.tagName === 'INPUT' ? 'input' : 'change', function () {
        guardar();
        actualizar();
      });
    });
    if (limpiar) limpiar.addEventListener('click', function () {
      lista.forEach(function (control) { control.value = ''; });
      try { localStorage.removeItem(clave); } catch (error) {}
      actualizar();
      render();
      const primero = lista[0];
      if (primero) primero.focus();
    });
    actualizar();
  }

  function mostrarErrorPublico(contenedor, mensaje, reintentar) {
    if (!contenedor) return;
    contenedor.innerHTML = '<div class="estado-error-publico" role="alert"><strong>No pudimos cargar esta información</strong><span>' + escapar(mensaje || 'Comprueba tu conexión e inténtalo nuevamente.') + '</span>' + (typeof reintentar === 'function' ? '<button class="boton boton-claro" type="button" data-reintentar-publico>Reintentar</button>' : '') + '</div>';
    const boton = contenedor.querySelector('[data-reintentar-publico]');
    if (boton) boton.addEventListener('click', function () {
      boton.disabled = true;
      boton.textContent = 'Reintentando…';
      Promise.resolve(reintentar()).finally(function () {
        if (boton.isConnected) {
          boton.disabled = false;
          boton.textContent = 'Reintentar';
        }
      });
    });
  }

  function skeletonPublico(contenedor, cantidad) {
    if (!contenedor) return;
    const total = Math.max(1, Math.min(Number(cantidad || 3), 6));
    contenedor.setAttribute('aria-busy', 'true');
    contenedor.innerHTML = Array.from({length: total}, function () {
      return '<div class="skeleton-publico" aria-hidden="true"><span></span><span></span><span></span></div>';
    }).join('');
  }

  function finalizarCargaPublica(contenedor) {
    if (contenedor) contenedor.removeAttribute('aria-busy');
  }

  function normalizar(valor) {
    return String(valor || '')
      .toLocaleLowerCase('es')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  function fecha(valor, conHora) {
    if (!valor) return 'Sin fecha';
    const texto = String(valor).replace(' ', 'T');
    // YYYY-MM-DD es una fecha civil; Date la interpretaría como medianoche UTC.
    const partes = /^(\d{4})-(\d{2})-(\d{2})$/.exec(texto);
    const objeto = partes ? new Date(Number(partes[1]), Number(partes[2]) - 1, Number(partes[3])) : new Date(texto);
    if (Number.isNaN(objeto.getTime())) return String(valor);
    return new Intl.DateTimeFormat('es-UY', conHora ? {dateStyle: 'medium', timeStyle: 'short'} : {dateStyle: 'medium'}).format(objeto);
  }

  function etiquetaEstado(estado) {
    const estados = {
      inscripciones: 'Inscripciones',
      en_curso: 'En curso',
      finalizado: 'Finalizado',
      pendiente: 'Pendiente',
      programado: 'Programado'
    };
    return estados[estado] || String(estado || '').replace(/_/g, ' ');
  }

  async function pedir(url) {
    const respuesta = await fetch(url + (url.includes('?') ? '&' : '?') + '_=' + Date.now(), {cache: 'no-store'});
    const datos = await respuesta.json();
    if (!respuesta.ok || !datos.exito) throw new Error(datos.mensaje || 'No se pudo cargar la información.');
    return datos;
  }

  function tarjetaTorneo(torneo) {
    const cupo = torneo.cupo_maximo == null ? 'Sin límite' : torneo.cantidad_inscritos + '/' + torneo.cupo_maximo;
    return '<article class="tarjeta-consulta-publica tarjeta-torneo-publico">' +
      '<div class="cabecera-tarjeta-publica">' + mediaPublica('torneo', torneo.id_torneo, torneo.nombre, 'media-publica-torneo') + '<span class="estado-publico estado-' + escapar(torneo.estado) + '">' + escapar(etiquetaEstado(torneo.estado)) + '</span></div>' +
      '<h2>' + escapar(torneo.nombre) + '</h2>' +
      '<p>' + escapar(torneo.disciplina) + ' · ' + escapar(torneo.categoria) + '</p>' +
      '<div class="metas-publicas"><span><b>Formato</b>' + escapar(torneo.tipo_torneo) + '</span><span><b>Participación</b>' + (torneo.modalidad === 'equipo' ? 'Equipos' : 'Individual') + '</span><span><b>Cupo</b>' + escapar(cupo) + '</span></div>' +
      '<div class="fechas-publicas"><span>Inicio: <strong>' + escapar(fecha(torneo.fecha_inicio + ' ' + (torneo.hora_inicio || '09:00:00'), true)) + '</strong></span><span>Fin: <strong>' + escapar(fecha(torneo.fecha_fin, false)) + '</strong></span></div>' +
      '<a class="boton boton-principal boton-publico-tarjeta" href="torneo-publico.php?id=' + encodeURIComponent(torneo.id_torneo) + '">Ver torneo</a>' +
      '</article>';
  }

  function tarjetaEnfrentamiento(item, resultado) {
    const a = item.participante_a || 'Por definir';
    const b = item.participante_b || 'Pase automático';
    const marcador = resultado ? '<div class="marcador-publico">' + participantePublico(item, 'a') + '<span>' + escapar(item.puntaje_a) + ' - ' + escapar(item.puntaje_b) + '</span>' + participantePublico(item, 'b') + '</div>' : '<div class="cruce-publico">' + participantePublico(item, 'a') + '<span>VS</span>' + participantePublico(item, 'b') + '</div>';
    return '<article class="tarjeta-consulta-publica tarjeta-enfrentamiento-publico">' +
      '<div class="cabecera-tarjeta-publica"><span>' + escapar(item.ronda) + '</span><span class="estado-publico estado-' + escapar(item.estado || 'finalizado') + '">' + escapar(resultado ? 'Finalizado' : etiquetaEstado(item.estado)) + '</span></div>' +
      marcador +
      '<div class="meta-principal-publica fila-torneo-publico-media">' + mediaPublica('torneo', item.id_torneo, item.torneo, 'media-publica-mini') + '<span>' + escapar(item.torneo) + ' · ' + escapar(item.disciplina) + '</span></div>' +
      '<p class="fecha-publica">' + escapar(item.fecha_hora ? fecha(item.fecha_hora, true) : 'Fecha por definir') + '</p>' +
      '<a class="enlace-publico-secundario" href="torneo-publico.php?id=' + encodeURIComponent(item.id_torneo) + '">Ver torneo</a>' +
      '</article>';
  }

  async function cargarTorneos() {
    const lista = document.getElementById('listaTorneosPublicos');
    const buscar = document.getElementById('buscarTorneoPublico');
    const estado = document.getElementById('estadoTorneoPublico');
    const disciplina = document.getElementById('disciplinaTorneoPublico');
    if (!lista) return;
    skeletonPublico(lista, 6);
    try {
      const datos = await pedir('api/publico/torneos.php');
      const torneos = datos.torneos || [];
      const disciplinas = Array.from(new Set(torneos.map(function (t) { return t.disciplina; }).filter(Boolean))).sort(function (a, b) { return a.localeCompare(b, 'es'); });
      if (disciplina) disciplina.innerHTML = '<option value="">Todas</option>' + disciplinas.map(function (d) { return '<option value="' + escapar(d) + '">' + escapar(d) + '</option>'; }).join('');
      function render() {
        const texto = normalizar(buscar && buscar.value);
        const valorEstado = estado && estado.value || '';
        const valorDisciplina = disciplina && disciplina.value || '';
        const filtrados = torneos.filter(function (t) {
          if (valorEstado && t.estado !== valorEstado) return false;
          if (valorDisciplina && t.disciplina !== valorDisciplina) return false;
          if (texto && !normalizar(t.nombre + ' ' + t.disciplina + ' ' + t.categoria + ' ' + t.tipo_torneo).includes(texto)) return false;
          return true;
        });
        lista.innerHTML = filtrados.length ? filtrados.map(tarjetaTorneo).join('') : '<div class="mensaje-sin-resultados estado-vacio-publico"><strong>No encontramos torneos con esos filtros</strong><span>Prueba con otro nombre, estado o disciplina para ampliar la búsqueda.</span></div>';
      }
      [buscar, estado, disciplina].forEach(function (control) { if (control) control.addEventListener(control.tagName === 'INPUT' ? 'input' : 'change', render); });
      activarEstadoFiltros([buscar, estado, disciplina], render);
      render();
      finalizarCargaPublica(lista);
    } catch (error) {
      finalizarCargaPublica(lista);
      mostrarErrorPublico(lista, error.message, function () { window.location.reload(); });
    }
  }

  async function llenarSelectTorneos(select) {
    if (!select) return [];
    const datos = await pedir('api/publico/torneos.php');
    const torneos = datos.torneos || [];
    const inicial = select.querySelector('option') ? select.querySelector('option').outerHTML : '<option value="">Todos los torneos</option>';
    select.innerHTML = inicial + torneos.map(function (t) { return '<option value="' + escapar(t.id_torneo) + '">' + escapar(t.nombre) + '</option>'; }).join('');
    return torneos;
  }

  async function cargarEnfrentamientos() {
    const lista = document.getElementById('listaEnfrentamientosPublicos');
    const selectTorneo = document.getElementById('torneoEnfrentamientoPublico');
    const selectEstado = document.getElementById('estadoEnfrentamientoPublico');
    if (!lista) return;
    skeletonPublico(lista, 6);
    try {
      const torneos = await llenarSelectTorneos(selectTorneo);
      const solicitado = new URLSearchParams(window.location.search).get('torneo');
      if (solicitado && selectTorneo && torneos.some(function (t) { return String(t.id_torneo) === solicitado; })) selectTorneo.value = solicitado;
      const datos = await pedir('api/publico/enfrentamientos.php');
      const items = datos.enfrentamientos || [];
      function render() {
        const idTorneo = selectTorneo && selectTorneo.value || '';
        const estado = selectEstado && selectEstado.value || '';
        const filtrados = items.filter(function (item) {
          return (!idTorneo || String(item.id_torneo) === idTorneo) && (!estado || item.estado === estado);
        });
        lista.innerHTML = filtrados.length ? filtrados.map(function (item) { return tarjetaEnfrentamiento(item, false); }).join('') : '<div class="mensaje-sin-resultados estado-vacio-publico"><strong>No hay enfrentamientos para esta selección</strong><span>Cambia el torneo o el estado para consultar otros cruces publicados.</span></div>';
      }
      [selectTorneo, selectEstado].forEach(function (control) { if (control) control.addEventListener('change', render); });
      activarEstadoFiltros([selectTorneo, selectEstado], render);
      render();
      finalizarCargaPublica(lista);
    } catch (error) {
      finalizarCargaPublica(lista);
      mostrarErrorPublico(lista, error.message, function () { window.location.reload(); });
    }
  }

  async function cargarResultados() {
    const lista = document.getElementById('listaResultadosPublicos');
    const selectTorneo = document.getElementById('torneoResultadoPublico');
    const buscar = document.getElementById('buscarResultadoPublico');
    if (!lista) return;
    skeletonPublico(lista, 6);
    try {
      const torneos = await llenarSelectTorneos(selectTorneo);
      const solicitado = new URLSearchParams(window.location.search).get('torneo');
      if (solicitado && selectTorneo && torneos.some(function (t) { return String(t.id_torneo) === solicitado; })) selectTorneo.value = solicitado;
      const datos = await pedir('api/publico/resultados.php');
      const items = datos.resultados || [];
      function render() {
        const idTorneo = selectTorneo && selectTorneo.value || '';
        const texto = normalizar(buscar && buscar.value);
        const filtrados = items.filter(function (item) {
          if (idTorneo && String(item.id_torneo) !== idTorneo) return false;
          if (texto && !normalizar((item.participante_a || '') + ' ' + (item.participante_b || '') + ' ' + item.torneo + ' ' + item.disciplina).includes(texto)) return false;
          return true;
        });
        lista.innerHTML = filtrados.length ? filtrados.map(function (item) { return tarjetaEnfrentamiento(item, true); }).join('') : '<div class="mensaje-sin-resultados estado-vacio-publico"><strong>No hay resultados para esta búsqueda</strong><span>Prueba con otro torneo o participante, o vuelve más tarde cuando se publiquen resultados.</span></div>';
      }
      if (selectTorneo) selectTorneo.addEventListener('change', render);
      if (buscar) buscar.addEventListener('input', render);
      activarEstadoFiltros([selectTorneo, buscar], render);
      render();
      finalizarCargaPublica(lista);
    } catch (error) {
      finalizarCargaPublica(lista);
      mostrarErrorPublico(lista, error.message, function () { window.location.reload(); });
    }
  }

  function tablaClasificacion(filas, modalidad) {
    if (!filas.length) return '<p class="mensaje-sin-resultados">Todavía no hay posiciones calculadas.</p>';
    return '<div class="tabla-publica-scroll"><table class="tabla-clasificacion-publica"><thead><tr><th>Pos.</th><th>Participante</th><th>PJ</th><th>PG</th><th>PE</th><th>PP</th><th>Dif.</th><th>Pts.</th></tr></thead><tbody>' + filas.map(function (fila) {
      const participante = modalidad === 'equipo' ? '<span class="participante-tabla-publica">' + mediaPublica('equipo', fila.id, fila.nombre, 'media-publica-mini') + '<span>' + escapar(fila.nombre) + '</span></span>' : escapar(fila.nombre);
      return '<tr><td data-label="Posición"><strong>' + escapar(fila.posicion) + '</strong></td><td data-label="Participante">' + participante + '</td><td data-label="Jugados">' + escapar(fila.jugados) + '</td><td data-label="Ganados">' + escapar(fila.ganados) + '</td><td data-label="Empatados">' + escapar(fila.empatados) + '</td><td data-label="Perdidos">' + escapar(fila.perdidos) + '</td><td data-label="Diferencia">' + escapar(fila.diferencia) + '</td><td data-label="Puntos"><strong>' + escapar(fila.puntos) + '</strong></td></tr>';
    }).join('') + '</tbody></table></div>';
  }

  async function cargarClasificacion() {
    const select = document.getElementById('torneoClasificacionPublica');
    const contenedor = document.getElementById('contenedorClasificacionPublica');
    const resumen = document.getElementById('resumenClasificacionPublica');
    if (!select || !contenedor) return;
    try {
      const torneos = await llenarSelectTorneos(select);
      const parametros = new URLSearchParams(window.location.search);
      const solicitado = parametros.get('torneo');
      if (solicitado && torneos.some(function (t) { return String(t.id_torneo) === solicitado; })) select.value = solicitado;
      async function render() {
        if (!select.value) {
          contenedor.innerHTML = '<p class="mensaje-sin-resultados">Selecciona un torneo para ver la clasificación.</p>';
          if (resumen) resumen.hidden = true;
          return;
        }
        skeletonPublico(contenedor, 4);
        const datos = await pedir('api/publico/clasificacion.php?id_torneo=' + encodeURIComponent(select.value));
        contenedor.innerHTML = tablaClasificacion(datos.clasificacion || [], datos.torneo && datos.torneo.modalidad);
        if (resumen) {
          resumen.hidden = false;
          resumen.innerHTML = '<div><span>Torneo</span><strong class="resumen-media-publica">' + mediaPublica('torneo', datos.torneo.id_torneo, datos.torneo.nombre, 'media-publica-mini') + '<span>' + escapar(datos.torneo.nombre) + '</span></strong></div><div><span>Participantes</span><strong>' + escapar(datos.resumen.participantes) + '</strong></div><div><span>Ronda</span><strong>' + escapar(datos.resumen.ronda_actual) + '</strong></div><div><span>Progreso</span><strong>' + escapar(datos.resumen.progreso) + '%</strong></div>' + (datos.resumen.campeon ? '<div><span>Campeón</span><strong class="resumen-media-publica">' + (datos.torneo.modalidad === 'equipo' ? mediaPublica('equipo', datos.resumen.campeon_id, datos.resumen.campeon, 'media-publica-mini') : window.ArenaCJDIcono('torneo')) + '<span>' + escapar(datos.resumen.campeon) + '</span></strong></div>' : '');
        }
        const url = new URL(window.location.href);
        url.searchParams.set('torneo', select.value);
        history.replaceState(null, '', url);
        finalizarCargaPublica(contenedor);
      }
      select.addEventListener('change', function () {
        render().catch(function (error) {
          finalizarCargaPublica(contenedor);
          mostrarErrorPublico(contenedor, error.message, render);
        });
      });
      if (select.value) await render();
    } catch (error) {
      finalizarCargaPublica(contenedor);
      mostrarErrorPublico(contenedor, error.message, function () { window.location.reload(); });
    }
  }

  async function cargarCalendario() {
    const lista = document.getElementById('listaCalendarioPublico');
    const buscar = document.getElementById('buscarCalendarioPublico');
    const tipo = document.getElementById('tipoCalendarioPublico');
    if (!lista) return;
    skeletonPublico(lista, 5);
    try {
      const datos = await pedir('api/publico/calendario.php');
      const eventos = datos.eventos || [];
      function render() {
        const texto = normalizar(buscar && buscar.value);
        const tipoValor = tipo && tipo.value || '';
        const filtrados = eventos.filter(function (evento) {
          if (tipoValor && evento.tipo !== tipoValor) return false;
          if (texto && !normalizar(evento.torneo + ' ' + evento.disciplina + ' ' + evento.titulo + ' ' + evento.detalle).includes(texto)) return false;
          return true;
        });
        lista.innerHTML = filtrados.length ? filtrados.map(function (evento) {
          return '<article class="evento-calendario-publico"><div class="fecha-evento-publico"><strong>' + escapar(fecha(evento.fecha_hora, false)) + '</strong><span>' + escapar(String(evento.fecha_hora || '').slice(11, 16) || '—') + '</span></div><div class="contenido-evento-publico"><span class="tipo-evento-publico">' + escapar(evento.titulo) + '</span><h2 class="titulo-evento-con-media">' + mediaPublica('torneo', evento.id_torneo, evento.torneo, 'media-publica-mini') + '<span>' + escapar(evento.torneo) + '</span></h2><p>' + escapar(evento.detalle || evento.disciplina) + '</p><div class="metas-publicas"><span>' + escapar(evento.disciplina) + '</span><span>' + escapar(etiquetaEstado(evento.estado)) + '</span></div></div><a class="boton boton-claro" href="torneo-publico.php?id=' + encodeURIComponent(evento.id_torneo) + '">Ver torneo</a></article>';
        }).join('') : '<div class="mensaje-sin-resultados estado-vacio-publico"><strong>No hay eventos para estos filtros</strong><span>Cambia el tipo o la búsqueda para consultar otras fechas y torneos.</span></div>';
      }
      if (buscar) buscar.addEventListener('input', render);
      if (tipo) tipo.addEventListener('change', render);
      activarEstadoFiltros([buscar, tipo], render);
      render();
      finalizarCargaPublica(lista);
    } catch (error) {
      finalizarCargaPublica(lista);
      mostrarErrorPublico(lista, error.message, function () { window.location.reload(); });
    }
  }

  async function cargarDetalleTorneo() {
    const detalle = document.getElementById('detalleTorneoPublico');
    if (!detalle) return;
    const id = new URLSearchParams(window.location.search).get('id');
    if (!id) {
      detalle.innerHTML = '<p class="mensaje-sin-resultados">No se indicó un torneo.</p>';
      return;
    }
    skeletonPublico(detalle, 2);
    try {
      const datos = await pedir('api/publico/torneo.php?id=' + encodeURIComponent(id));
      const t = datos.torneo;
      detalle.innerHTML = '<a class="enlace-volver-publico" href="torneos-publicos.php"><span data-icono="izquierda" aria-hidden="true"></span> Volver a torneos</a><div class="hero-torneo-publico"><div class="hero-torneo-identidad">' + mediaPublica('torneo', t.id_torneo, t.nombre, 'media-publica-hero') + '<div><span class="estado-publico estado-' + escapar(t.estado) + '">' + escapar(etiquetaEstado(t.estado)) + '</span><h1>' + escapar(t.nombre) + '</h1><p>' + escapar(t.disciplina) + ' · ' + escapar(t.categoria) + ' · ' + escapar(t.tipo_torneo) + '</p></div></div><div class="datos-torneo-publico"><div><span>Modalidad</span><strong>' + (t.modalidad === 'equipo' ? 'Por equipos' : 'Individual') + '</strong></div><div><span>Organizador</span><strong>' + escapar(t.organizador) + '</strong></div><div><span>Participantes</span><strong>' + escapar(t.cantidad_inscritos) + '</strong></div><div><span>Inicio</span><strong>' + escapar(fecha(t.fecha_inicio + ' ' + (t.hora_inicio || '09:00:00'), true)) + '</strong></div><div><span>Finalización</span><strong>' + escapar(fecha(t.fecha_fin, false)) + '</strong></div></div></div>';
      const enlace = document.getElementById('enlaceClasificacionTorneoPublico');
      if (enlace) enlace.href = 'clasificacion-publica.php?torneo=' + encodeURIComponent(id);
      const [enf, res, clas] = await Promise.all([
        pedir('api/publico/enfrentamientos.php?id_torneo=' + encodeURIComponent(id)),
        pedir('api/publico/resultados.php?id_torneo=' + encodeURIComponent(id)),
        pedir('api/publico/clasificacion.php?id_torneo=' + encodeURIComponent(id)).catch(function () { return {clasificacion: []}; })
      ]);
      const listaEnf = document.getElementById('detalleEnfrentamientosPublicos');
      if (listaEnf) {
        const pendientes = (enf.enfrentamientos || []).filter(function (e) { return e.estado !== 'finalizado'; }).slice(0, 4);
        listaEnf.innerHTML = pendientes.length ? pendientes.map(function (item) { return tarjetaEnfrentamiento(item, false); }).join('') : '<p class="mensaje-sin-resultados">No hay enfrentamientos pendientes publicados.</p>';
      }
      const listaRes = document.getElementById('detalleResultadosPublicos');
      if (listaRes) {
        const resultados = (res.resultados || []).slice(0, 4);
        listaRes.innerHTML = resultados.length ? resultados.map(function (item) { return tarjetaEnfrentamiento(item, true); }).join('') : '<p class="mensaje-sin-resultados">Todavía no hay resultados publicados.</p>';
      }
      const tabla = document.getElementById('detalleClasificacionPublica');
      if (tabla) tabla.innerHTML = tablaClasificacion((clas.clasificacion || []).slice(0, 8), t.modalidad);
      finalizarCargaPublica(detalle);
    } catch (error) {
      finalizarCargaPublica(detalle);
      mostrarErrorPublico(detalle, error.message, function () { window.location.reload(); });
    }
  }

  if (pagina === 'torneos') cargarTorneos();
  if (pagina === 'enfrentamientos') cargarEnfrentamientos();
  if (pagina === 'resultados') cargarResultados();
  if (pagina === 'clasificacion') cargarClasificacion();
  if (pagina === 'calendario') cargarCalendario();
  if (pagina === 'torneo') cargarDetalleTorneo();
}());
