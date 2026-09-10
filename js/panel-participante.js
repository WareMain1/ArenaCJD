(function () {
  'use strict';

  const fecha = document.getElementById('fechaPanel');
  if (fecha) {
    fecha.textContent = new Intl.DateTimeFormat('es-UY', {
      weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    }).format(new Date());
  }

  function escapar(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function textoEstado(valor) {
    const mapa = {
      borrador: 'Borrador',
      inscripciones: 'Inscripciones',
      en_curso: 'En curso',
      finalizado: 'Finalizado',
      pendiente: 'Pendiente',
      aprobada: 'Aprobada',
      rechazada: 'Rechazada',
      programado: 'Programado'
    };
    return mapa[String(valor || '')] || String(valor || '');
  }

  function formatearFecha(valor) {
    if (!valor) return 'Sin fecha programada';
    const objeto = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(objeto.getTime())) return String(valor);
    return objeto.toLocaleString('es-UY', {dateStyle: 'short', timeStyle: 'short'});
  }

  function setNumero(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.textContent = String(valor || 0);
  }

  function renderAlertas(datos) {
    const bloque = document.getElementById('panelPersonalAlertasBloque');
    const lista = document.getElementById('panelPersonalAlertas');
    const contador = document.getElementById('panelPersonalAlertasCantidad');
    if (!bloque || !lista) return;

    const alertas = Array.isArray(datos.alertas) ? datos.alertas : [];
    if (!alertas.length) {
      bloque.hidden = true;
      lista.innerHTML = '';
      if (contador) contador.textContent = '0';
      return;
    }

    bloque.hidden = false;
    if (contador) contador.textContent = String(Number(datos.cantidad_alertas || alertas.length));
    lista.innerHTML = alertas.slice(0, 4).map(function (alerta) {
      return '<article class="alerta-panel-participante">' +
        '<div><strong>' + escapar(alerta.titulo || 'Actividad pendiente') + '</strong>' +
        '<span>' + escapar(alerta.detalle || '') + '</span></div>' +
        (alerta.url ? '<a class="boton boton-claro" href="' + escapar(alerta.url) + '">Ver</a>' : '') +
        '</article>';
    }).join('');
  }

  function renderInscripciones(inscripciones) {
    const contenedor = document.getElementById('panelPersonalInscripciones');
    if (!contenedor) return;

    const activas = inscripciones.filter(function (inscripcion) {
      return inscripcion.estado !== 'rechazada' &&
        inscripcion.estado_torneo !== 'finalizado' &&
        inscripcion.estado_torneo !== 'cancelado';
    }).slice(0, 4);

    if (!activas.length) {
      contenedor.innerHTML = '<div><span class="indicador-jornada indicador-morado"></span><div><strong>Sin inscripciones activas</strong><span>Tus participaciones aparecerán aquí.</span></div><a href="participantes.php">Ver</a></div>';
      return;
    }

    contenedor.innerHTML = activas.map(function (inscripcion) {
      const modalidad = inscripcion.modalidad === 'equipo'
        ? 'Equipo ' + escapar(inscripcion.equipo || '')
        : 'Individual';
      const mediaTorneo = window.ArenaCJDMedia ? window.ArenaCJDMedia.html('torneo', inscripcion.id_torneo, inscripcion.torneo, {clase: 'media-pequena'}) : '<span class="indicador-jornada indicador-morado"></span>';
      return '<div>' + mediaTorneo +
        '<div><strong>' + escapar(inscripcion.torneo) + '</strong><span>' + modalidad + ' · ' + escapar(textoEstado(inscripcion.estado)) + ' · ' + escapar(textoEstado(inscripcion.estado_torneo)) + '</span></div>' +
        '<a href="torneos.php?detalle=' + Number(inscripcion.id_torneo || 0) + '">Abrir</a></div>';
    }).join('');
  }

  function renderProximos(proximos) {
    const contenedor = document.getElementById('panelPersonalListaProximos');
    if (!contenedor) return;

    const lista = proximos.slice(0, 5);
    if (!lista.length) {
      contenedor.innerHTML = '<p class="mensaje-panel-personal">No tienes enfrentamientos próximos programados.</p>';
      return;
    }

    contenedor.innerHTML = lista.map(function (partido) {
      const mediaTorneo = window.ArenaCJDMedia ? window.ArenaCJDMedia.html('torneo', partido.id_torneo, partido.torneo, {clase: 'media-pequena'}) : '';
      return '<article class="proximo-panel-participante">' + mediaTorneo +
        '<div><strong>' + escapar(partido.participante_a || 'Por definir') + ' vs ' + escapar(partido.participante_b || 'Por definir') + '</strong>' +
        '<span>' + escapar(partido.torneo) + ' · ' + escapar(partido.ronda || 'Ronda') + '</span></div>' +
        '<div class="meta-proximo-panel"><span>' + escapar(textoEstado(partido.estado)) + '</span><time>' + escapar(formatearFecha(partido.fecha_hora)) + '</time></div>' +
        '<a class="boton boton-claro" href="partidos.php?torneo=' + Number(partido.id_torneo || 0) + '">Ver</a>' +
        '</article>';
    }).join('');
  }

  function renderLoProximo(datos) {
    const bloque = document.getElementById('panelLoProximoParticipante');
    const titulo = document.getElementById('panelLoProximoTitulo');
    const detalle = document.getElementById('panelLoProximoDetalle');
    const accion = document.getElementById('panelLoProximoAccion');
    if (!bloque || !titulo || !detalle || !accion) return;
    const alertas = Array.isArray(datos.alertas) ? datos.alertas : [];
    const proximos = Array.isArray(datos.proximos_enfrentamientos) ? datos.proximos_enfrentamientos : [];
    if (alertas.length) {
      const alerta = alertas[0];
      titulo.textContent = alerta.titulo || 'Tienes una acción pendiente';
      detalle.textContent = alerta.detalle || 'Revisa tu actividad para continuar.';
      accion.href = alerta.url || 'mi-actividad.php';
      accion.textContent = 'Revisar';
      bloque.hidden = false;
      return;
    }
    if (proximos.length) {
      const partido = proximos[0];
      titulo.textContent = (partido.participante_a || 'Por definir') + ' vs ' + (partido.participante_b || 'Por definir');
      detalle.textContent = (partido.torneo || 'Torneo') + ' · ' + (partido.ronda || 'Ronda') + ' · ' + formatearFecha(partido.fecha_hora);
      accion.href = 'partidos.php?torneo=' + Number(partido.id_torneo || 0);
      accion.textContent = 'Ver enfrentamiento';
      bloque.hidden = false;
      return;
    }
    titulo.textContent = 'Explora torneos disponibles';
    detalle.textContent = 'No tienes acciones pendientes ni enfrentamientos próximos.';
    accion.href = 'torneos.php';
    accion.textContent = 'Ver torneos';
    bloque.hidden = false;
  }

  function render(datos) {
    const inscripciones = Array.isArray(datos.inscripciones) ? datos.inscripciones : [];
    const proximos = Array.isArray(datos.proximos_enfrentamientos) ? datos.proximos_enfrentamientos : [];
    const equipos = Array.isArray(datos.equipos) ? datos.equipos : [];
    const invitaciones = Array.isArray(datos.invitaciones) ? datos.invitaciones : [];

    const torneosActivos = new Set(inscripciones.filter(function (inscripcion) {
      return inscripcion.estado === 'aprobada' &&
        inscripcion.estado_torneo !== 'finalizado' &&
        inscripcion.estado_torneo !== 'cancelado';
    }).map(function (inscripcion) { return String(inscripcion.id_torneo); }));

    const invitacionesPendientes = invitaciones.filter(function (invitacion) {
      return invitacion.estado === 'pendiente';
    });

    setNumero('panelPersonalTorneos', torneosActivos.size);
    setNumero('panelPersonalProximos', proximos.length);
    setNumero('panelPersonalEquipos', equipos.length);
    setNumero('panelPersonalInvitaciones', invitacionesPendientes.length);

    renderLoProximo(datos);
    renderAlertas(datos);
    renderInscripciones(inscripciones);
    renderProximos(proximos);
  }

  let primeraCargaPanelParticipante = true;

  async function cargar() {
    const proximos = document.getElementById('panelPersonalListaProximos');
    if (primeraCargaPanelParticipante && proximos && window.ArenaCJDSkeleton) window.ArenaCJDSkeleton.mostrar(proximos, 3);
    try {
      const respuesta = await fetch('api/mi_actividad.php?_=' + Date.now(), {cache: 'no-store'});
      const datos = await respuesta.json();
      if (!respuesta.ok || !datos.exito) throw new Error(datos.mensaje || 'No se pudo cargar tu Panel.');
      render(datos);
    } catch (error) {
      const contenedor = document.getElementById('panelPersonalListaProximos');
      if (contenedor && window.ArenaCJDEstadoError) window.ArenaCJDEstadoError(contenedor, error.message || 'No se pudo actualizar tu información personal.', cargar);
      else if (contenedor) contenedor.innerHTML = '<p class="mensaje-panel-personal">No se pudo actualizar tu información personal.</p>';
      const bloque = document.getElementById('panelLoProximoParticipante');
      if (bloque) bloque.hidden = true;
    } finally {
      if (proximos && window.ArenaCJDSkeleton) window.ArenaCJDSkeleton.ocultar(proximos);
      primeraCargaPanelParticipante = false;
    }
  }

  cargar();
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('panel-participante', cargar, 10000, {ejecutarAhora: false});
}());
