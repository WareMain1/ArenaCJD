(function () {
  'use strict';

  const lista = document.getElementById('listaEnfrentamientos');
  if (!lista) return;

  const controles = {
    torneo: document.getElementById('filtroTorneoEnfrentamientos'),
    disciplina: document.getElementById('filtroDisciplinaEnfrentamientos'),
    estado: document.getElementById('filtroEstadoEnfrentamientos'),
    ronda: document.getElementById('filtroRondaEnfrentamientos'),
    fecha: document.getElementById('filtroFechaEnfrentamientos')
  };
  const contador = document.getElementById('contadorEnfrentamientos');
  const exportar = document.querySelector('.boton-exportar-enfrentamientos');
  let datos = [];
  let csrf = '';
  let usuarioId = 0;
  let esAdministrador = false;
  let esOrganizador = false;
  let torneoUrlAplicado = false;
  let enfrentamientoGestionActual = null;
  let primeraCargaEnfrentamientos = true;

  function escapar(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function normalizar(valor) {
    return String(valor || '').toLocaleLowerCase('es')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
  }

  function formatearFecha(valor) {
    if (!valor) return 'Sin programar';
    const fecha = new Date(String(valor).replace(' ', 'T'));
    return Number.isNaN(fecha.getTime())
      ? valor
      : new Intl.DateTimeFormat('es-UY', {dateStyle: 'short', timeStyle: 'short'}).format(fecha);
  }

  function iniciales(nombre) {
    return String(nombre || '?').split(/\s+/).slice(0, 2)
      .map(function (parte) { return parte[0] || ''; }).join('').toUpperCase();
  }

  function etiquetaEstado(estado) {
    return {
      pendiente: 'Pendiente',
      programado: 'Programado',
      en_curso: 'En curso',
      en_periodo_gracia: 'En período de gracia',
      pendiente_revision: 'Requiere revisión',
      finalizado: 'Finalizado',
      cancelado: 'Cancelado'
    }[estado] || estado;
  }

  function puedeGestionar(enfrentamiento) {
    return esAdministrador || (esOrganizador && Number(enfrentamiento.id_organizador) === usuarioId);
  }

  function esPaseAutomatico(enfrentamiento) {
    return enfrentamiento && (enfrentamiento.id_participante_a == null || enfrentamiento.id_participante_b == null);
  }

  function poblarFiltro(select, valorBase, textoBase, valores) {
    if (!select) return;
    const anterior = select.value;
    const opciones = Array.from(new Set(valores.filter(Boolean))).sort(function (a, b) {
      return String(a).localeCompare(String(b), 'es', {numeric: true});
    });
    select.innerHTML = '<option value="' + escapar(valorBase) + '">' + escapar(textoBase) + '</option>' +
      opciones.map(function (valor) {
        return '<option value="' + escapar(valor) + '">' + escapar(valor) + '</option>';
      }).join('');
    if (Array.from(select.options).some(function (opcion) { return opcion.value === anterior; })) {
      select.value = anterior;
    }
  }

  function poblarFiltroTorneos() {
    if (!controles.torneo) return;
    const anterior = controles.torneo.value;
    const mapa = new Map();
    datos.forEach(function (e) {
      if (e.id_torneo != null && e.torneo) mapa.set(String(e.id_torneo), e.torneo);
    });
    controles.torneo.innerHTML = '<option value="todos">Todos los torneos</option>' +
      Array.from(mapa.entries()).sort(function (a, b) { return String(a[1]).localeCompare(String(b[1]), 'es'); })
        .map(function (par) { return '<option value="' + escapar(par[0]) + '">' + escapar(par[1]) + '</option>'; }).join('');
    if (Array.from(controles.torneo.options).some(function (opcion) { return opcion.value === anterior; })) {
      controles.torneo.value = anterior;
    }
  }

  function actualizarFiltrosDinamicos() {
    poblarFiltroTorneos();
    poblarFiltro(controles.disciplina, 'todas', 'Todas las disciplinas', datos.map(function (e) { return e.disciplina; }));
    const rondas = Array.from(new Set(datos.map(function (e) { return e.ronda; }).filter(Boolean)))
      .sort(function (a, b) {
        const na = Number(String(a).match(/\d+/)?.[0] || 0);
        const nb = Number(String(b).match(/\d+/)?.[0] || 0);
        return na - nb || String(a).localeCompare(String(b), 'es');
      });
    poblarFiltro(controles.ronda, 'todas', 'Todas las rondas', rondas);
  }

  function coincideFiltros(enfrentamiento) {
    const fecha = String(enfrentamiento.fecha_hora || '').slice(0, 10);
    return (!controles.torneo || controles.torneo.value === 'todos' || String(enfrentamiento.id_torneo) === String(controles.torneo.value))
      && (!controles.disciplina || controles.disciplina.value === 'todas' || normalizar(enfrentamiento.disciplina) === normalizar(controles.disciplina.value))
      && (!controles.estado || controles.estado.value === 'todos' || enfrentamiento.estado === controles.estado.value)
      && (!controles.ronda || controles.ronda.value === 'todas' || enfrentamiento.ronda === controles.ronda.value)
      && (!controles.fecha || !controles.fecha.value || fecha === controles.fecha.value);
  }

  function render() {
    const visibles = datos.filter(coincideFiltros);
    lista.innerHTML = visibles.map(function (e) {
      const marcador = e.estado === 'finalizado'
        ? escapar(String(e.puntaje_a) + ' - ' + String(e.puntaje_b))
        : 'VS';
      const participanteA = e.participante_a || 'Participante no disponible';
      const participanteB = e.participante_b || 'Pase automático';
      const tipo = e.tipo_participante === 'equipo' ? 'Equipos' : 'Individual';
      const avatarA = e.tipo_participante === 'individual' && window.ArenaCJDAvatar
        ? window.ArenaCJDAvatar.html(e.id_usuario_a, participanteA, 'avatar-mediano')
        : (e.tipo_participante === 'equipo' && window.ArenaCJDMedia
          ? window.ArenaCJDMedia.html('equipo', e.id_equipo_a, participanteA, {clase: 'media-mediana'})
          : '<span class="avatar-competidor">' + escapar(iniciales(participanteA)) + '</span>');
      const avatarB = e.tipo_participante === 'individual' && e.id_usuario_b && window.ArenaCJDAvatar
        ? window.ArenaCJDAvatar.html(e.id_usuario_b, participanteB, 'avatar-mediano')
        : (e.tipo_participante === 'equipo' && e.id_equipo_b && window.ArenaCJDMedia
          ? window.ArenaCJDMedia.html('equipo', e.id_equipo_b, participanteB, {clase: 'media-mediana'})
          : '<span class="avatar-competidor">' + escapar(iniciales(participanteB)) + '</span>');
      const mediaTorneo = window.ArenaCJDMedia
        ? window.ArenaCJDMedia.html('torneo', e.id_torneo, e.torneo, {clase: 'media-mediana'})
        : '<div class="icono-disciplina icono-voley"><span data-icono="enfrentamientos" aria-hidden="true"></span></div>';
      return '<article class="tarjeta-enfrentamiento" data-torneo="' + Number(e.id_torneo) + '" data-id-enfrentamiento="' + Number(e.id_enfrentamiento) + '">' +
        '<div class="datos-torneo-enfrentamiento">' + mediaTorneo + '<div>' +
        '<h2>' + escapar(e.torneo) + '</h2><p>' + escapar(e.disciplina) + ' · ' + escapar(e.ronda) + ' · ' + escapar(tipo) + '</p>' +
        '<span class="etiqueta-ronda ronda-morada">' + escapar(etiquetaEstado(e.estado)) + '</span></div></div>' +
        '<div class="cruce-enfrentamiento"><div class="competidor-enfrentamiento">' + avatarA + '<span>' + escapar(participanteA) + '</span></div>' +
        '<strong>' + marcador + '</strong>' +
        '<div class="competidor-enfrentamiento competidor-derecha">' + avatarB + '<span>' + escapar(participanteB) + '</span></div></div>' +
        '<div class="datos-fecha-enfrentamiento"><span><span data-icono="calendario" aria-hidden="true"></span> ' + escapar(formatearFecha(e.fecha_hora)) + '</span>' +
        (puedeGestionar(e) && !esPaseAutomatico(e) ? '<button class="boton boton-claro" type="button" data-gestionar-enfrentamiento="' + Number(e.id_enfrentamiento) + '">Gestionar</button>' : '') +
        '</div></article>';
    }).join('');
    if (window.ArenaCJDAvatar) window.ArenaCJDAvatar.activar(lista);

    if (!visibles.length) {
      const idTorneo = controles.torneo && controles.torneo.value !== 'todos' ? controles.torneo.value : '';
      lista.innerHTML = '<div class="sin-enfrentamientos">No hay enfrentamientos que coincidan con los filtros.' +
        (idTorneo && (esAdministrador || esOrganizador) ? ' <a class="boton boton-claro" href="sorteos.php?torneo=' + encodeURIComponent(idTorneo) + '">Ir a Sorteos</a>' : '') + '</div>';
    }
    if (contador) contador.textContent = 'Mostrando ' + visibles.length + (visibles.length === 1 ? ' enfrentamiento' : ' enfrentamientos');
  }

  function mostrarAviso(texto, tipo) {
    if (window.ArenaCJDToast) {
      window.ArenaCJDToast(texto, tipo || 'info');
      return;
    }
    let aviso = document.getElementById('avisoEnfrentamientos');
    if (!aviso) {
      aviso = document.createElement('div');
      aviso.id = 'avisoEnfrentamientos';
      aviso.className = 'aviso-flotante-ah';
      aviso.setAttribute('role', 'status');
      document.body.appendChild(aviso);
    }
    aviso.textContent = texto;
    aviso.dataset.tipo = tipo || 'info';
    aviso.classList.add('visible');
    window.clearTimeout(aviso._temporizador);
    aviso._temporizador = window.setTimeout(function () { aviso.classList.remove('visible'); }, 3200);
  }

  function crearModal() {
    let fondo = document.getElementById('modalGestionEnfrentamiento');
    if (fondo) return fondo;
    fondo = document.createElement('div');
    fondo.id = 'modalGestionEnfrentamiento';
    fondo.className = 'fondo-modal-perfil';
    fondo.hidden = true;
    fondo.innerHTML = '<section class="modal-perfil-usuario" role="dialog" aria-modal="true" aria-labelledby="tituloGestionEnfrentamiento">' +
      '<div class="cabecera-modal-perfil"><div><h2 id="tituloGestionEnfrentamiento">Gestionar enfrentamiento</h2><p id="textoGestionEnfrentamiento"></p></div><button class="cerrar-modal-perfil" type="button" data-cerrar-enfrentamiento aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button></div>' +
      '<form id="formGestionEnfrentamiento" class="form-seguridad-perfil"><input type="hidden" id="idGestionEnfrentamiento">' +
      '<label>Fecha y hora<input class="campo-formulario" id="fechaGestionEnfrentamiento" type="datetime-local"></label>' +
      '<label>Estado<select class="selector-formulario" id="estadoGestionEnfrentamiento"><option value="pendiente">Pendiente</option><option value="programado">Programado</option><option value="en_curso">En curso</option><option value="finalizado">Finalizado</option><option value="cancelado">Cancelado</option></select></label>' +
      '<div class="grid-resultados-gestion" id="bloqueResultadosGestion" hidden><label><span id="etiquetaPuntajeAGestion">Resultado A</span><input class="campo-formulario" id="puntajeAGestion" type="number" min="0" step="1" inputmode="numeric"></label><label><span id="etiquetaPuntajeBGestion">Resultado B</span><input class="campo-formulario" id="puntajeBGestion" type="number" min="0" step="1" inputmode="numeric"></label></div>' +
      '<p id="mensajeGestionEnfrentamiento" class="mensaje-seguridad-perfil" aria-live="polite"></p>' +
      '<div class="acciones-foto-perfil"><button class="boton boton-claro" type="button" data-cerrar-enfrentamiento>Cancelar</button><button class="boton boton-principal" type="submit">Guardar</button></div></form></section>';
    document.body.appendChild(fondo);
    fondo.querySelectorAll('[data-cerrar-enfrentamiento]').forEach(function (boton) {
      boton.addEventListener('click', cerrarModal);
    });
    fondo.addEventListener('click', function (evento) {
      if (evento.target === fondo) cerrarModal();
    });
    fondo.querySelector('#formGestionEnfrentamiento').addEventListener('submit', guardar);
    fondo.querySelector('#estadoGestionEnfrentamiento').addEventListener('change', actualizarCamposGestion);
    return fondo;
  }

  function actualizarCamposGestion() {
    const estado = document.getElementById('estadoGestionEnfrentamiento');
    const fecha = document.getElementById('fechaGestionEnfrentamiento');
    const bloqueResultados = document.getElementById('bloqueResultadosGestion');
    const puntajeA = document.getElementById('puntajeAGestion');
    const puntajeB = document.getElementById('puntajeBGestion');
    const mensaje = document.getElementById('mensajeGestionEnfrentamiento');
    if (!estado || !fecha || !bloqueResultados || !puntajeA || !puntajeB || !mensaje) return;

    const valor = estado.value;
    const finalizado = valor === 'finalizado';
    bloqueResultados.hidden = !finalizado;
    puntajeA.disabled = !finalizado;
    puntajeB.disabled = !finalizado;
    puntajeA.required = finalizado;
    puntajeB.required = finalizado;
    fecha.required = ['programado', 'en_curso', 'finalizado'].includes(valor);

    const textos = {
      pendiente: 'El enfrentamiento está pendiente. Puedes dejarlo sin fecha o programarlo más adelante.',
      programado: 'Programa la fecha y hora del enfrentamiento.',
      en_curso: 'El enfrentamiento está en curso. La fecha y hora son obligatorias.',
      finalizado: 'Ingresa el resultado final del enfrentamiento. Ambos puntajes son obligatorios.',
      cancelado: 'El enfrentamiento quedará cancelado y sin resultado.'
    };
    mensaje.textContent = textos[valor] || '';
    mensaje.className = 'mensaje-seguridad-perfil';
  }

  function cerrarModal() {
    const fondo = document.getElementById('modalGestionEnfrentamiento');
    if (fondo) fondo.hidden = true;
    enfrentamientoGestionActual = null;
    document.body.classList.remove('sin-scroll');
  }

  function abrirGestion(id) {
    const enfrentamiento = datos.find(function (item) { return Number(item.id_enfrentamiento) === Number(id); });
    if (!enfrentamiento || !puedeGestionar(enfrentamiento)) return;
    if (esPaseAutomatico(enfrentamiento)) {
      mostrarAviso('Los pases automáticos son resueltos por el sistema y no se editan manualmente.', 'info');
      return;
    }
    enfrentamientoGestionActual = enfrentamiento;
    const fondo = crearModal();
    document.getElementById('idGestionEnfrentamiento').value = enfrentamiento.id_enfrentamiento;
    document.getElementById('textoGestionEnfrentamiento').textContent = enfrentamiento.participante_a + ' vs ' + enfrentamiento.participante_b + ' · ' + enfrentamiento.ronda;
    document.getElementById('fechaGestionEnfrentamiento').value = enfrentamiento.fecha_hora ? String(enfrentamiento.fecha_hora).replace(' ', 'T').slice(0, 16) : '';
    document.getElementById('estadoGestionEnfrentamiento').value = enfrentamiento.estado;
    document.getElementById('puntajeAGestion').value = enfrentamiento.puntaje_a == null ? '' : enfrentamiento.puntaje_a;
    document.getElementById('puntajeBGestion').value = enfrentamiento.puntaje_b == null ? '' : enfrentamiento.puntaje_b;
    document.getElementById('etiquetaPuntajeAGestion').textContent = enfrentamiento.participante_a || 'Resultado A';
    document.getElementById('etiquetaPuntajeBGestion').textContent = enfrentamiento.participante_b || 'Resultado B';
    actualizarCamposGestion();
    if (enfrentamiento.estado === 'finalizado') {
      const mensaje = document.getElementById('mensajeGestionEnfrentamiento');
      mensaje.textContent += ' Puedes corregirlo mientras no haya generado una ronda posterior.';
    }
    fondo.hidden = false;
    document.body.classList.add('sin-scroll');
  }

  async function guardar(evento) {
    evento.preventDefault();
    const formulario = evento.currentTarget;
    const mensaje = document.getElementById('mensajeGestionEnfrentamiento');
    const boton = formulario.querySelector('[type="submit"]');
    const estadoSeleccionado = document.getElementById('estadoGestionEnfrentamiento').value;
    const fechaSeleccionada = document.getElementById('fechaGestionEnfrentamiento').value;
    const valorPuntajeA = document.getElementById('puntajeAGestion').value;
    const valorPuntajeB = document.getElementById('puntajeBGestion').value;
    if (['programado', 'en_curso', 'finalizado'].includes(estadoSeleccionado) && !fechaSeleccionada) {
      mensaje.textContent = estadoSeleccionado === 'finalizado'
        ? 'Asigna la fecha y hora del enfrentamiento antes de guardar el resultado final.'
        : 'Asigna una fecha y hora para ese estado.';
      mensaje.className = 'mensaje-seguridad-perfil error';
      return;
    }
    if (estadoSeleccionado === 'finalizado') {
      if (valorPuntajeA === '' || valorPuntajeB === '') {
        mensaje.textContent = 'Debes ingresar ambos resultados para finalizar el enfrentamiento.';
        mensaje.className = 'mensaje-seguridad-perfil error';
        return;
      }
      const puntajeA = Number(valorPuntajeA);
      const puntajeB = Number(valorPuntajeB);
      if (!Number.isInteger(puntajeA) || !Number.isInteger(puntajeB) || puntajeA < 0 || puntajeB < 0) {
        mensaje.textContent = 'Los resultados deben ser números enteros mayores o iguales a 0.';
        mensaje.className = 'mensaje-seguridad-perfil error';
        return;
      }
      const tipo = normalizar(enfrentamientoGestionActual && enfrentamientoGestionActual.tipo_torneo);
      if (tipo.includes('elimin') && puntajeA === puntajeB) {
        mensaje.textContent = 'En eliminación directa debe existir un ganador; el resultado final no puede terminar empatado.';
        mensaje.className = 'mensaje-seguridad-perfil error';
        return;
      }
    }
    boton.disabled = true;
    mensaje.textContent = 'Guardando...';
    mensaje.className = 'mensaje-seguridad-perfil';
    try {
      const respuesta = await fetch('api/enfrentamiento_actualizar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify({
          id_enfrentamiento: Number(document.getElementById('idGestionEnfrentamiento').value),
          fecha_hora: document.getElementById('fechaGestionEnfrentamiento').value,
          estado: document.getElementById('estadoGestionEnfrentamiento').value,
          puntaje_a: estadoSeleccionado === 'finalizado' ? valorPuntajeA : '',
          puntaje_b: estadoSeleccionado === 'finalizado' ? valorPuntajeB : ''
        })
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo guardar.');
      mensaje.textContent = resultado.mensaje;
      mensaje.className = 'mensaje-seguridad-perfil exito';
      await cargar();
      mostrarAviso(resultado.mensaje || 'Enfrentamiento actualizado.', 'exito');
      window.dispatchEvent(new CustomEvent('arenacjd:enfrentamientos-actualizados'));
      window.setTimeout(cerrarModal, 650);
    } catch (error) {
      mensaje.textContent = error.message;
      mensaje.className = 'mensaje-seguridad-perfil error';
    } finally {
      boton.disabled = false;
    }
  }

  async function cargar() {
    if (primeraCargaEnfrentamientos && window.ArenaCJDSkeleton) window.ArenaCJDSkeleton.mostrar(lista, 6);
    try {
      const respuesta = await fetch('api/enfrentamientos.php?_=' + Date.now(), {cache: 'no-store'});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron cargar los enfrentamientos.');
      datos = resultado.enfrentamientos || [];
      csrf = resultado.csrf_token || csrf;
      usuarioId = Number(resultado.usuario_id || 0);
      esAdministrador = Boolean(resultado.es_administrador);
      esOrganizador = Boolean(resultado.es_organizador);
      actualizarFiltrosDinamicos();
      const idUrl = !torneoUrlAplicado ? new URLSearchParams(location.search).get('torneo') : null;
      if (idUrl && controles.torneo && Array.from(controles.torneo.options).some(function (opcion) { return opcion.value === idUrl; })) {
        controles.torneo.value = idUrl;
      }
      torneoUrlAplicado = true;
      render();
      const idEnfrentamientoUrl = Number(new URLSearchParams(location.search).get('enfrentamiento') || 0);
      if (idEnfrentamientoUrl) {
        window.setTimeout(function () {
          const tarjeta = lista.querySelector('[data-id-enfrentamiento="' + idEnfrentamientoUrl + '"]');
          if (!tarjeta) return;
          tarjeta.scrollIntoView({behavior:'smooth', block:'center'});
          if (window.ArenaCJDResaltar) window.ArenaCJDResaltar(tarjeta);
        }, 80);
      }
    } catch (error) {
      if (window.ArenaCJDEstadoError) window.ArenaCJDEstadoError(lista, error.message, cargar);
      else lista.innerHTML = '<div class="sin-enfrentamientos">' + escapar(error.message) + '</div>';
      if (contador) contador.textContent = 'Mostrando 0 enfrentamientos';
    } finally {
      if (window.ArenaCJDSkeleton) window.ArenaCJDSkeleton.ocultar(lista);
      primeraCargaEnfrentamientos = false;
    }
  }

  Object.values(controles).forEach(function (control) {
    if (control) control.addEventListener('change', render);
  });
  lista.addEventListener('click', function (evento) {
    const boton = evento.target.closest('[data-gestionar-enfrentamiento]');
    if (boton) abrirGestion(boton.dataset.gestionarEnfrentamiento);
  });
  document.addEventListener('keydown', function (evento) {
    if (evento.key === 'Escape') cerrarModal();
  });

  if (exportar) {
    exportar.addEventListener('click', function () {
      const visibles = datos.filter(coincideFiltros);
      if (!visibles.length) {
        mostrarAviso('No hay enfrentamientos visibles para exportar.', 'info');
        return;
      }
      const filas = [['Torneo', 'Disciplina', 'Ronda', 'Fecha', 'Estado', 'Participante A', 'Participante B', 'Resultado']];
      visibles.forEach(function (e) {
        filas.push([e.torneo, e.disciplina, e.ronda, e.fecha_hora || '', etiquetaEstado(e.estado), e.participante_a, e.participante_b, e.puntaje_a == null ? '' : e.puntaje_a + '-' + e.puntaje_b]);
      });
      const csv = filas.map(function (fila) {
        return fila.map(function (valor) { return '"' + String(valor == null ? '' : valor).replace(/"/g, '""') + '"'; }).join(',');
      }).join('\n');
      const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], {type: 'text/csv;charset=utf-8'}));
      const enlace = document.createElement('a');
      enlace.href = url;
      enlace.download = 'enfrentamientos-arenacjd.csv';
      enlace.click();
      URL.revokeObjectURL(url);
      mostrarAviso('Exportación generada correctamente.', 'exito');
    });
  }

  window.addEventListener('arenacjd:torneos-cargados', function () {
    const idUrl = new URLSearchParams(location.search).get('torneo');
    if (idUrl && controles.torneo && Array.from(controles.torneo.options).some(function (o) { return o.value === idUrl; })) {
      controles.torneo.value = idUrl;
    }
    render();
  });

  cargar();
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('enfrentamientos', function () {
    const modal = document.getElementById('modalGestionEnfrentamiento');
    if (!modal || modal.hidden) return cargar();
  }, 5000, {ejecutarAhora:false});
}());
