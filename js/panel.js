(function () {
  'use strict';

  const fecha = document.getElementById('fechaPanel');
  if (fecha) {
    fecha.textContent = new Intl.DateTimeFormat('es-UY', {
      weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    }).format(new Date());
  }

  function actualizarSaludo() {
    const hora = Number(new Intl.DateTimeFormat('es-UY', {hour:'numeric', hourCycle:'h23', timeZone:'America/Montevideo'}).format(new Date()));
    const saludo = document.getElementById('saludoPanel');
    if (saludo) saludo.textContent = hora >= 6 && hora < 12 ? 'Buenos días' : hora >= 12 && hora < 19 ? 'Buenas tardes' : 'Buenas noches';
  }
  actualizarSaludo();
  document.addEventListener('visibilitychange', actualizarSaludo);
  let cargando = false;

  function escapar(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function setTexto(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.textContent = String(valor == null ? 0 : valor);
  }

  function formatearFechaHora(valor) {
    if (!valor) return {fecha: '-', hora: '-'};
    const d = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return {fecha: valor, hora: '-'};
    return {
      fecha: new Intl.DateTimeFormat('es-UY', {day: '2-digit', month: '2-digit', year: 'numeric'}).format(d),
      hora: new Intl.DateTimeFormat('es-UY', {hour: '2-digit', minute: '2-digit'}).format(d)
    };
  }


  function tiempoRelativo(valor) {
    if (!valor) return '';
    const d = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return '';
    const segundos = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
    if (segundos < 60) return 'ahora';
    const minutos = Math.floor(segundos / 60);
    if (minutos < 60) return 'hace ' + minutos + ' min';
    const horas = Math.floor(minutos / 60);
    if (horas < 24) return 'hace ' + horas + ' h';
    const dias = Math.floor(horas / 24);
    if (dias === 1) return 'ayer';
    if (dias < 30) return 'hace ' + dias + ' días';
    return new Intl.DateTimeFormat('es-UY', {day: '2-digit', month: '2-digit', year: 'numeric'}).format(d);
  }

  function fechaExactaActividad(valor) {
    if (!valor) return '';
    const d = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return String(valor);
    return new Intl.DateTimeFormat('es-UY', {
      day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    }).format(d);
  }

  function iconoActividad(accion) {
    const clave = String(accion || '').toLowerCase();
    if (clave.includes('foto') || clave.includes('perfil')) return {icono: window.ArenaCJDIcono('foto'), tipo: 'perfil'};
    if (clave.includes('invitacion') && (clave.includes('rechaz') || clave.includes('elimin'))) return {icono: window.ArenaCJDIcono('peligro'), tipo: 'peligro'};
    if (clave.includes('invitacion') && (clave.includes('acept') || clave.includes('aprobad'))) return {icono: window.ArenaCJDIcono('exito'), tipo: 'exito'};
    if (clave.includes('invitacion')) return {icono: window.ArenaCJDIcono('mensaje'), tipo: 'mensaje'};
    if ((clave.includes('equipo') || clave.includes('usuario') || clave.includes('disciplina') || clave.includes('torneo') || clave.includes('inscripcion')) && (clave.includes('elimin') || clave.includes('rechaz'))) return {icono: window.ArenaCJDIcono('peligro'), tipo: 'peligro'};
    if (clave.includes('equipo')) return {icono: window.ArenaCJDIcono('participantes'), tipo: 'equipo'};
    if (clave.includes('torneo')) return {icono: window.ArenaCJDIcono('torneo'), tipo: 'torneo'};
    if (clave.includes('sorteo')) return {icono: window.ArenaCJDIcono('sorteo'), tipo: 'sorteo'};
    if (clave.includes('resultado')) return {icono: window.ArenaCJDIcono('resultados'), tipo: 'resultado'};
    if (clave.includes('enfrentamiento')) return {icono: window.ArenaCJDIcono('enfrentamientos'), tipo: 'enfrentamiento'};
    if (clave.includes('disciplina')) return {icono: window.ArenaCJDIcono('medalla'), tipo: 'disciplina'};
    if (clave.includes('inscripcion')) return {icono: window.ArenaCJDIcono('exito'), tipo: 'exito'};
    return {icono: window.ArenaCJDIcono('actualizar'), tipo: 'general'};
  }

  function porcentajes(resumen) {
    const total = Math.max(1, Number(resumen.activos || 0) + Number(resumen.proximos || 0) + Number(resumen.finalizados || 0));
    const activos = Math.round(Number(resumen.activos || 0) * 100 / total);
    const proximos = Math.round(Number(resumen.proximos || 0) * 100 / total);
    const finalizados = Math.max(0, 100 - activos - proximos);
    return {activos: activos, proximos: proximos, finalizados: finalizados};
  }

  function renderProximos(items) {
    const cuerpo = document.getElementById('panelProximosEnfrentamientos');
    if (!cuerpo) return;
    if (!items.length) {
      cuerpo.innerHTML = '<tr><td colspan="6"><strong>No hay enfrentamientos próximos programados.</strong></td></tr>';
      return;
    }
    cuerpo.innerHTML = items.map(function (e) {
      const f = formatearFechaHora(e.fecha_hora);
      const mediaTorneo = window.ArenaCJDMedia ? window.ArenaCJDMedia.html('torneo', e.id_torneo, e.torneo, {clase: 'media-pequena'}) : '';
      return '<tr><td data-label="Torneo"><span class="fila-panel-torneo-media">' + mediaTorneo + '<strong>' + escapar(e.torneo) + '</strong></span></td><td data-label="Disciplina">' + escapar(e.disciplina) + '</td><td data-label="Ronda">' + escapar(e.ronda) + '</td><td data-label="Fecha">' + escapar(f.fecha) + '</td><td data-label="Hora">' + escapar(f.hora) + '</td><td data-label="Acción"><a href="partidos.php?torneo=' + Number(e.id_torneo) + '">Abrir</a></td></tr>';
    }).join('');
  }

  function renderActividad(items) {
    const lista = document.getElementById('panelActividadReciente');
    if (!lista) return;
    if (!items.length) {
      lista.innerHTML = '<li><span class="actividad-icono actividad-icono--general" aria-hidden="true"><span data-icono="actualizar" aria-hidden="true"></span></span><div class="actividad-contenido-panel"><strong>Sin actividad reciente</strong><span>Los cambios relevantes del sistema aparecerán aquí.</span></div></li>';
      return;
    }
    lista.innerHTML = items.map(function (item) {
      const usuario = item.nombre_usuario ? ' · @' + escapar(item.nombre_usuario) : '';
      const icono = iconoActividad(item.accion);
      const nombres = {perfil:'foto',equipo:'participantes',resultado:'resultados',enfrentamiento:'enfrentamientos',disciplina:'torneo',general:'actividad'};
      const dibujo = window.ArenaCJDIcono(nombres[icono.tipo] || icono.tipo);
      const relativo = tiempoRelativo(item.fecha);
      const exacta = fechaExactaActividad(item.fecha);
      const datetime = item.fecha ? String(item.fecha).replace(' ', 'T') : '';
      const tiempo = relativo
        ? '<time class="actividad-tiempo-panel" datetime="' + escapar(datetime) + '" title="' + escapar(exacta) + '">' + escapar(relativo) + '</time>'
        : '';
      return '<li><span class="actividad-icono actividad-icono--' + escapar(icono.tipo) + '" aria-hidden="true">' + dibujo + '</span><div class="actividad-contenido-panel"><strong>' + escapar(item.titulo || 'Actividad') + '</strong><span>' + escapar(item.detalle || '') + usuario + '</span></div>' + tiempo + '</li>';
    }).join('');
  }

  function renderPrioridades(items) {
    const contenedor = document.getElementById('panelPrioridadesTorneos');
    if (!contenedor) return;
    if (!items.length) {
      contenedor.innerHTML = '<div class="estado-vacio-ah"><strong>No hay tareas competitivas pendientes</strong><span>Cuando un torneo necesite configuración, participantes, sorteo o resultados aparecerá aquí.</span><a class="boton boton-claro" href="torneos.php">Revisar torneos</a></div>';
      return;
    }
    contenedor.innerHTML = items.map(function (item) {
      return '<article class="siguiente-accion-panel-ah"><div><strong>' + escapar(item.nombre) + ' · ' + escapar(item.accion) + '</strong><span>' + escapar(item.disciplina) + ' · ' + escapar(item.detalle) + '</span></div><a class="boton boton-principal" href="' + escapar(item.url) + '">Continuar</a></article>';
    }).join('');
  }

  function render(datos) {
    const r = datos.resumen_torneos || {};
    setTexto('panelTorneosActivos', r.activos || 0);
    setTexto('panelTorneosTotal', r.total || 0);
    setTexto('panelParticipantes', r.participantes || 0);
    setTexto('panelEnfrentamientosPendientes', datos.enfrentamientos_pendientes || 0);
    setTexto('panelResultadosRegistrados', datos.resultados_registrados || 0);
    setTexto('panelHoyProgramados', datos.hoy_programados || 0);
    setTexto('panelHoyResultados', datos.hoy_resultados || 0);
    setTexto('panelComienzanHoy', r.comienzan_hoy || 0);
    setTexto('panelEstadoActivos', r.activos || 0);
    setTexto('panelEstadoProximos', r.proximos || 0);
    setTexto('panelEstadoFinalizados', r.finalizados || 0);

    document.querySelectorAll('[data-cantidad-solicitudes]').forEach(function (e) {
      e.textContent = String(datos.solicitudes_pendientes || 0);
    });

    const p = porcentajes(r);
    setTexto('panelPorcentajeActivos', p.activos + '%');
    setTexto('panelPorcentajeProximos', p.proximos + '%');
    setTexto('panelPorcentajeFinalizados', p.finalizados + '%');
    const ba = document.getElementById('panelBarraActivos'); if (ba) ba.style.width = p.activos + '%';
    const bp = document.getElementById('panelBarraProximos'); if (bp) bp.style.width = p.proximos + '%';
    const bf = document.getElementById('panelBarraFinalizados'); if (bf) bf.style.width = p.finalizados + '%';

    renderProximos(datos.proximos_enfrentamientos || []);
    renderActividad(datos.actividad_reciente || []);
    renderPrioridades(datos.prioridades_torneos || []);
  }

  async function cargar() {
    if (cargando) return;
    cargando = true;
    try {
      const respuesta = await fetch('api/panel.php?_=' + Date.now(), {cache: 'no-store'});
      const datos = await respuesta.json();
      if (!respuesta.ok || !datos.exito) throw new Error(datos.mensaje || 'No se pudo actualizar el Panel.');
      render(datos);
    } catch (error) {
      const prioridades = document.getElementById('panelPrioridadesTorneos');
      if (prioridades && window.ArenaCJDEstadoError) window.ArenaCJDEstadoError(prioridades, error.message || 'No se pudo actualizar el panel.', cargar);
      const actividad = document.getElementById('panelActividadReciente');
      if (actividad) actividad.innerHTML = '<li><span class="actividad-icono actividad-icono--peligro" aria-hidden="true"><span data-icono="alerta" aria-hidden="true"></span></span><div class="actividad-contenido-panel"><strong>No se pudo actualizar la actividad</strong><span>Comprueba tu conexión e intenta nuevamente.</span></div></li>';
    } finally {
      cargando = false;
    }
  }

  cargar();
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('panel', cargar, 6000, {ejecutarAhora:false});
}());
