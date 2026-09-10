(function () {
  'use strict';

  const selector = document.getElementById('filtroTorneoClasificacion');
  if (!selector) return;

  const elementos = {
    disciplina: document.getElementById('opcionDisciplinaClasificacion'),
    estado: document.getElementById('filtroEstadoClasificacion'),
    ronda: document.getElementById('filtroRondaClasificacion'),
    resumen: document.getElementById('resumenClasificacion'),
    estadisticas: document.getElementById('estadisticasDisciplina'),
    encabezado: document.getElementById('encabezadoTablaClasificacion'),
    cuerpo: document.getElementById('cuerpoTablaClasificacion'),
    sin: document.getElementById('sinClasificacion'),
    contador: document.getElementById('contadorClasificacion'),
    insignia: document.getElementById('insigniaDisciplina'),
    descripcion: document.getElementById('descripcionEstadisticas'),
    textoRonda: document.getElementById('textoRondaActual'),
    porcentaje: document.getElementById('porcentajeProgreso'),
    barra: document.getElementById('barraProgresoTorneo'),
    listaRondas: document.getElementById('listaRondas'),
    campeon: document.getElementById('campeonTorneo'),
    buscar: document.getElementById('buscarClasificacion'),
    exportar: document.querySelector('.boton-exportar-clasificacion'),
    modal: document.getElementById('fondoModalClasificacion'),
    cerrarModal: document.getElementById('cerrarModalClasificacion'),
    avatarModal: document.getElementById('avatarModalClasificacion'),
    estadoModal: document.getElementById('estadoModalClasificacion'),
    tituloModal: document.getElementById('tituloModalClasificacion'),
    subtituloModal: document.getElementById('subtituloModalClasificacion'),
    datosModal: document.getElementById('datosModalClasificacion'),
    ultimosModal: document.getElementById('ultimosResultadosClasificacion'),
    verEnfrentamientos: document.getElementById('verEnfrentamientosParticipante')
  };

  let datos = null;
  let cargando = false;
  let idParticipanteModal = null;

  function escapar(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function normalizar(valor) {
    return String(valor || '').toLocaleLowerCase('es')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
  }

  function iniciales(nombre) {
    return String(nombre || '?').split(/\s+/).slice(0, 2)
      .map(function (parte) { return parte.charAt(0); }).join('').toUpperCase();
  }

  function cerrarModal() {
    if (elementos.modal) elementos.modal.hidden = true;
    document.body.classList.remove('sin-scroll');
    idParticipanteModal = null;
  }

  function abrirModal(fila) {
    if (!elementos.modal || !fila) return;
    idParticipanteModal = Number(fila.id);
    if (datos.torneo.modalidad === 'individual' && window.ArenaCJDAvatar) {
      elementos.avatarModal.innerHTML = window.ArenaCJDAvatar.html(fila.id, fila.nombre, 'avatar-grande');
      window.ArenaCJDAvatar.activar(elementos.avatarModal);
    } else if (datos.torneo.modalidad === 'equipo' && window.ArenaCJDMedia) {
      elementos.avatarModal.innerHTML = window.ArenaCJDMedia.html('equipo', fila.id, fila.nombre, {clase: 'media-grande'});
    } else {
      elementos.avatarModal.textContent = iniciales(fila.nombre);
    }
    elementos.estadoModal.textContent = fila.estado === 'eliminado' ? 'Eliminado' : 'En competencia';
    elementos.tituloModal.textContent = fila.nombre;
    elementos.subtituloModal.textContent = fila.usuario ? '@' + fila.usuario : (datos.torneo.modalidad === 'equipo' ? 'Equipo' : 'Participante');
    elementos.datosModal.innerHTML =
      '<div><span>Posición</span><strong>' + Number(fila.posicion) + '</strong></div>' +
      '<div><span>Puntos</span><strong>' + Number(fila.puntos) + '</strong></div>' +
      '<div><span>Jugados</span><strong>' + Number(fila.jugados) + '</strong></div>' +
      '<div><span>Ganados</span><strong>' + Number(fila.ganados) + '</strong></div>' +
      '<div><span>Empatados</span><strong>' + Number(fila.empatados) + '</strong></div>' +
      '<div><span>Perdidos</span><strong>' + Number(fila.perdidos) + '</strong></div>' +
      '<div><span>Diferencia</span><strong>' + (fila.diferencia > 0 ? '+' : '') + Number(fila.diferencia) + '</strong></div>' +
      '<div><span>Ronda alcanzada</span><strong>' + escapar(fila.ronda) + '</strong></div>';

    elementos.ultimosModal.innerHTML = (fila.ultimos || []).map(function (partido) {
      return '<div class="resultado-mini-clasificacion"><span>' + escapar(partido.ronda) + '</span><strong>vs ' +
        escapar(partido.rival) + '</strong><small>' +
        (partido.puntaje_a == null ? 'Sin marcador' : escapar(partido.puntaje_a + ' - ' + partido.puntaje_b)) +
        '</small></div>';
    }).join('') || '<p>No tiene enfrentamientos finalizados.</p>';

    elementos.modal.hidden = false;
    document.body.classList.add('sin-scroll');
  }

  function actualizarFiltroRondas() {
    if (!elementos.ronda) return;
    const anterior = elementos.ronda.value;
    const rondas = (datos && datos.rondas ? datos.rondas : []).map(function (r) { return r.nombre; });
    elementos.ronda.innerHTML = '<option value="todas">Todas</option>' +
      rondas.map(function (ronda) { return '<option value="' + escapar(ronda) + '">' + escapar(ronda) + '</option>'; }).join('');
    if (rondas.includes(anterior)) elementos.ronda.value = anterior;
  }

  function filasFiltradas() {
    if (!datos) return [];
    const texto = normalizar(elementos.buscar && elementos.buscar.value);
    const estado = elementos.estado ? elementos.estado.value : 'todos';
    const ronda = elementos.ronda ? elementos.ronda.value : 'todas';

    return datos.clasificacion.filter(function (fila) {
      const nombre = normalizar(fila.nombre + ' ' + (fila.usuario || ''));
      return (!texto || nombre.includes(texto))
        && (estado === 'todos' || fila.estado === estado)
        && (ronda === 'todas' || fila.ronda === ronda);
    });
  }

  function actualizarDisponibilidadControles() {
    const hayParticipantes = Boolean(datos && Array.isArray(datos.clasificacion) && datos.clasificacion.length);
    if (elementos.buscar) {
      elementos.buscar.disabled = !hayParticipantes;
      if (!hayParticipantes) elementos.buscar.value = '';
    }
    if (elementos.exportar) {
      elementos.exportar.disabled = !hayParticipantes;
      elementos.exportar.setAttribute('aria-disabled', hayParticipantes ? 'false' : 'true');
      if (hayParticipantes) elementos.exportar.removeAttribute('title');
      else elementos.exportar.title = 'No hay datos de clasificación para exportar';
    }
  }

  function renderTabla() {
    if (!elementos.cuerpo || !elementos.encabezado) return;
    const filas = filasFiltradas();
    elementos.encabezado.innerHTML =
      '<th>Pos.</th><th>Participante</th><th>PJ</th><th>G</th><th>E</th><th>P</th><th>PF</th><th>PC</th><th>Dif.</th><th>Pts.</th><th>Estado</th><th>Ronda</th>';
    elementos.cuerpo.innerHTML = filas.map(function (fila) {
      const avatar = datos.torneo.modalidad === 'individual' && window.ArenaCJDAvatar
        ? window.ArenaCJDAvatar.html(fila.id, fila.nombre, 'avatar-pequeno')
        : (datos.torneo.modalidad === 'equipo' && window.ArenaCJDMedia
          ? window.ArenaCJDMedia.html('equipo', fila.id, fila.nombre, {clase: 'media-pequena'})
          : '<span class="avatar-clasificacion">' + escapar(iniciales(fila.nombre)) + '</span>');
      return '<tr data-clasificacion-id="' + Number(fila.id) + '" tabindex="0">' +
        '<td data-label="Posición"><strong>' + Number(fila.posicion) + '</strong></td>' +
        '<td data-label="Participante"><div class="celda-participante">' + avatar + '<span class="nombre-participante-clasificacion"><strong>' + escapar(fila.nombre) + '</strong>' + (fila.usuario ? '<span>@' + escapar(fila.usuario) + '</span>' : '') + '</span></div></td>' +
        '<td data-label="PJ">' + Number(fila.jugados) + '</td><td data-label="PG">' + Number(fila.ganados) + '</td><td data-label="PE">' + Number(fila.empatados) + '</td><td data-label="PP">' + Number(fila.perdidos) + '</td>' +
        '<td data-label="A favor">' + Number(fila.favor) + '</td><td data-label="En contra">' + Number(fila.contra) + '</td><td data-label="Diferencia">' + (fila.diferencia > 0 ? '+' : '') + Number(fila.diferencia) + '</td>' +
        '<td data-label="Puntos"><strong>' + Number(fila.puntos) + '</strong></td>' +
        '<td data-label="Estado"><span class="estado-clasificacion ' + (fila.estado === 'eliminado' ? 'eliminado' : 'clasificado') + '">' + (fila.estado === 'eliminado' ? 'Eliminado' : 'En competencia') + '</span></td>' +
        '<td data-label="Ronda">' + escapar(fila.ronda) + '</td></tr>';
    }).join('');
    if (window.ArenaCJDAvatar) window.ArenaCJDAvatar.activar(elementos.cuerpo);

    elementos.cuerpo.querySelectorAll('[data-clasificacion-id]').forEach(function (filaDom) {
      function abrir() {
        const fila = datos.clasificacion.find(function (item) { return Number(item.id) === Number(filaDom.dataset.clasificacionId); });
        abrirModal(fila);
      }
      filaDom.addEventListener('click', abrir);
      filaDom.addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter' || evento.key === ' ') {
          evento.preventDefault();
          abrir();
        }
      });
    });

    if (elementos.sin) {
      elementos.sin.hidden = filas.length > 0;
      elementos.sin.textContent = datos.clasificacion.length
        ? 'No hay participantes que coincidan con los filtros.'
        : 'El torneo todavía no tiene inscripciones aprobadas.';
    }
    if (elementos.contador) elementos.contador.textContent = filas.length + (filas.length === 1 ? ' participante' : ' participantes');
  }

  function renderDatos() {
    if (!datos) return;
    const r = datos.resumen;
    const t = datos.torneo;

    if (elementos.disciplina) elementos.disciplina.textContent = t.disciplina;
    if (elementos.insignia) {
      elementos.insignia.textContent = t.disciplina;
      elementos.insignia.hidden = false;
    }
    if (elementos.descripcion) elementos.descripcion.textContent = t.nombre + ' · ' + t.tipo_torneo + ' · clasificación actualizada.';

    elementos.resumen.innerHTML = [
      [window.ArenaCJDIcono('participantes'), 'Participantes', r.participantes, 'Inscripciones aprobadas'],
      [window.ArenaCJDIcono('exito'), 'En competencia', r.clasificados, 'Según resultados guardados'],
      [window.ArenaCJDIcono('peligro'), 'Eliminados', r.eliminados, 'Según enfrentamientos finalizados'],
      [window.ArenaCJDIcono('sorteo'), 'Ronda actual', r.ronda_actual, r.finalizados + ' resultados registrados'],
      [window.ArenaCJDIcono('torneo'), 'Campeón', r.campeon || 'Por definir', t.estado === 'finalizado' ? 'Torneo finalizado' : 'Torneo en desarrollo']
    ].map(function (item) {
      return '<article class="tarjeta-resumen-clasificacion"><span class="icono-resumen-clasificacion">' + item[0] +
        '</span><div class="contenido-resumen-clasificacion"><span class="etiqueta-resumen-clasificacion">' + escapar(item[1]) +
        '</span><strong class="valor-resumen-clasificacion">' + escapar(item[2]) +
        '</strong><span class="detalle-resumen-clasificacion">' + escapar(item[3]) + '</span></div></article>';
    }).join('');

    elementos.estadisticas.innerHTML = [
      ['Enfrentamientos', r.enfrentamientos],
      ['Resultados', r.finalizados],
      ['Pendientes', r.pendientes],
      ['Participantes', r.participantes]
    ].map(function (item) {
      return '<div class="dato-disciplina"><span>' + escapar(item[0]) + '</span><strong>' + Number(item[1]) + '</strong></div>';
    }).join('');

    if (elementos.textoRonda) elementos.textoRonda.textContent = 'Ronda actual: ' + r.ronda_actual;
    if (elementos.porcentaje) elementos.porcentaje.textContent = Number(r.progreso) + '%';
    if (elementos.barra) elementos.barra.style.width = Number(r.progreso) + '%';
    if (elementos.campeon) {
      const contenido = elementos.campeon.querySelector('div');
      if (contenido) {
        const avatarCampeon = t.modalidad === 'individual' && r.campeon && window.ArenaCJDAvatar
          ? window.ArenaCJDAvatar.html(r.campeon_id, r.campeon, 'avatar-mediano')
          : (t.modalidad === 'equipo' && r.campeon && window.ArenaCJDMedia
            ? window.ArenaCJDMedia.html('equipo', r.campeon_id, r.campeon, {clase: 'media-mediana'})
            : '<span class="icono-campeon"><span data-icono="torneo" aria-hidden="true"></span></span>');
        elementos.campeon.innerHTML = avatarCampeon + '<div><span>Campeón</span><strong>' + escapar(r.campeon || 'Por definir') + '</strong></div>';
        if (window.ArenaCJDAvatar) window.ArenaCJDAvatar.activar(elementos.campeon);
      }
    }

    if (elementos.listaRondas) {
      elementos.listaRondas.innerHTML = datos.rondas.map(function (ronda) {
        const completa = Number(ronda.finalizados) === Number(ronda.total) && Number(ronda.total) > 0;
        return '<div class="ronda-progreso ' + (completa ? 'completada' : 'actual') + '"><span>' +
          escapar(ronda.nombre) + '</span><strong>' + Number(ronda.finalizados) + '/' + Number(ronda.total) + '</strong></div>';
      }).join('') || '<p>El sorteo todavía no generó rondas.</p>';
    }

    actualizarDisponibilidadControles();
    actualizarFiltroRondas();
    renderTabla();
  }

  function renderVacio(mensaje) {
    datos = null;
    if (elementos.disciplina) elementos.disciplina.textContent = 'Sin torneo seleccionado';
    if (elementos.insignia) {
      elementos.insignia.textContent = '-';
      elementos.insignia.hidden = true;
    }
    if (elementos.descripcion) elementos.descripcion.textContent = mensaje || 'Selecciona un torneo real.';
    if (elementos.resumen) elementos.resumen.innerHTML = '';
    if (elementos.estadisticas) elementos.estadisticas.innerHTML = '';
    if (elementos.encabezado) elementos.encabezado.innerHTML = '<th>Pos.</th><th>Participante</th><th>Estado</th>';
    if (elementos.cuerpo) elementos.cuerpo.innerHTML = '';
    if (elementos.sin) { elementos.sin.hidden = false; elementos.sin.textContent = mensaje || 'Selecciona un torneo para calcular su clasificación.'; }
    if (elementos.contador) elementos.contador.textContent = '0 participantes';
    if (elementos.textoRonda) elementos.textoRonda.textContent = 'Ronda actual: Sin datos';
    if (elementos.porcentaje) elementos.porcentaje.textContent = '0%';
    if (elementos.barra) elementos.barra.style.width = '0%';
    if (elementos.listaRondas) elementos.listaRondas.innerHTML = '';
    if (elementos.campeon) elementos.campeon.innerHTML = '<span class="icono-campeon"><span data-icono="torneo" aria-hidden="true"></span></span><div><span>Campeón</span><strong>Por definir</strong></div>';
    actualizarDisponibilidadControles();
  }

  async function cargar() {
    const id = Number(selector.value || 0);
    if (!id || cargando) {
      if (!id) renderVacio('Selecciona un torneo para calcular su clasificación.');
      return;
    }
    cargando = true;
    try {
      const respuesta = await fetch('api/clasificacion.php?id_torneo=' + encodeURIComponent(id) + '&_=' + Date.now(), {cache: 'no-store'});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo cargar la clasificación.');
      datos = resultado.datos;
      renderDatos();
    } catch (error) {
      renderVacio(error.message);
      if (elementos.sin && window.ArenaCJDEstadoError) window.ArenaCJDEstadoError(elementos.sin, error.message, cargar);
    } finally {
      cargando = false;
    }
  }

  selector.addEventListener('change', cargar);
  if (elementos.buscar) elementos.buscar.addEventListener('input', renderTabla);
  if (elementos.estado) elementos.estado.addEventListener('change', renderTabla);
  if (elementos.ronda) elementos.ronda.addEventListener('change', renderTabla);

  if (elementos.exportar) {
    elementos.exportar.addEventListener('click', function () {
      const filas = filasFiltradas();
      if (!filas.length) {
        elementos.exportar.textContent = 'Sin datos para exportar';
        window.setTimeout(function () { elementos.exportar.textContent = 'Exportar clasificación'; }, 1200);
        return;
      }
      const contenido = [['Posición','Participante','Usuario','PJ','G','E','P','PF','PC','Diferencia','Puntos','Estado','Ronda']]
        .concat(filas.map(function (f) {
          return [f.posicion,f.nombre,f.usuario || '',f.jugados,f.ganados,f.empatados,f.perdidos,f.favor,f.contra,f.diferencia,f.puntos,f.estado,f.ronda];
        }))
        .map(function (fila) {
          return fila.map(function (v) { return '"' + String(v == null ? '' : v).replace(/"/g,'""') + '"'; }).join(',');
        }).join('\n');
      const url = URL.createObjectURL(new Blob(['\uFEFF' + contenido], {type:'text/csv;charset=utf-8'}));
      const a = document.createElement('a'); a.href = url; a.download = 'clasificacion-arenacjd.csv';
      document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
    });
  }

  if (elementos.cerrarModal) elementos.cerrarModal.addEventListener('click', cerrarModal);
  if (elementos.modal) elementos.modal.addEventListener('click', function (e) { if (e.target === elementos.modal) cerrarModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && elementos.modal && !elementos.modal.hidden) cerrarModal(); });

  if (elementos.verEnfrentamientos) {
    elementos.verEnfrentamientos.addEventListener('click', function () {
      window.location.href = 'partidos.php?torneo=' + encodeURIComponent(selector.value || '');
    });
  }

  window.addEventListener('arenacjd:torneos-cargados', function () {
    const idUrl = new URLSearchParams(location.search).get('torneo');
    if (idUrl && Array.from(selector.options).some(function (o) { return o.value === idUrl; })) {
      selector.value = idUrl;
    } else if (!selector.value && selector.options.length > 1) {
      selector.value = selector.options[1].value;
    }
    cargar();
  });

  renderVacio('Selecciona un torneo para calcular su clasificación.');
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('clasificacion', function () {
    if (selector.value && !(elementos.modal && !elementos.modal.hidden)) return cargar();
  }, 8000, {ejecutarAhora:false});
}());
