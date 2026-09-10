(function () {
  'use strict';

  const estado = document.getElementById('estadoMiActividad');
  const errorEstado = document.getElementById('errorMiActividad');

  function escapar(valor) {
    return String(valor ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function fecha(valor) {
    if (!valor) return 'Sin fecha programada';
    const objeto = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(objeto.getTime())) return valor;
    return objeto.toLocaleString('es-UY', {dateStyle: 'short', timeStyle: 'short'});
  }

  function etiquetaEstado(valor) {
    const mapa = {
      borrador: 'Borrador',
      inscripciones: 'Inscripciones',
      en_curso: 'En curso',
      finalizado: 'Finalizado',
      cancelado: 'Cancelado',
      pendiente: 'Pendiente',
      aprobada: 'Aprobada',
      rechazada: 'Rechazada',
      programado: 'Programado'
    };
    return mapa[String(valor || '')] || String(valor || '');
  }

  function renderAlertas(datos) {
    const contenedor = document.getElementById('alertaMiActividad');
    const lista = document.getElementById('listaAlertasMiActividad');
    const cantidad = document.getElementById('cantidadAlertasMiActividad');
    const alertas = Array.isArray(datos.alertas) ? datos.alertas : [];
    if (!contenedor || !lista) return;

    if (!alertas.length) {
      contenedor.hidden = true;
      lista.innerHTML = '';
      if (cantidad) cantidad.textContent = '0';
      return;
    }

    contenedor.hidden = false;
    if (cantidad) cantidad.textContent = String(Number(datos.cantidad_alertas || alertas.length));
    lista.innerHTML = alertas.map(function (alerta) {
      const url = alerta.url ? '<a href="' + escapar(alerta.url) + '">Ver</a>' : '';
      return '<article class="item-alerta-mi-actividad alerta-' + escapar(alerta.tipo || 'actividad') + '">' +
        '<div><strong>' + escapar(alerta.titulo || 'Actividad relacionada contigo') + '</strong><span>' + escapar(alerta.detalle || '') + '</span></div>' +
        url +
        '</article>';
    }).join('');
  }

  function icono(tipo) {
    const iconos = {
      calendario: '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>',
      equipos: '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
      invitaciones: '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>',
      inscripciones: '<svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"></rect><path d="M9 4.5h6M8 9h8M8 13h8M8 17h5"></path></svg>',
      resultados: '<svg viewBox="0 0 24 24"><path d="M8 4h8v5a4 4 0 0 1-8 0V4Z"></path><path d="M8 6H4v2a4 4 0 0 0 4 4M16 6h4v2a4 4 0 0 1-4 4M12 13v4M8 21h8M9 17h6"></path></svg>'
    };
    return iconos[tipo] || iconos.calendario;
  }

  function item(titulo, descripcion, metas, avatar) {
    return '<article class="item-actividad-usuario">' + (avatar || '') + '<div class="contenido-item-actividad"><strong>' + escapar(titulo) + '</strong><span>' + escapar(descripcion) + '</span><div class="meta-actividad-usuario">' + metas.map(function (meta) { return '<b>' + escapar(meta) + '</b>'; }).join('') + '</div></div></article>';
  }

  function mediaEntidad(tipo, id, nombre, clase) {
    if (!window.ArenaCJDMedia || !id) return '';
    return window.ArenaCJDMedia.html(tipo, id, nombre || '', {clase: clase || 'media-mediana'});
  }

  function mediaParticipacion(inscripcion, avatarUsuario) {
    const torneo = mediaEntidad('torneo', inscripcion.id_torneo, inscripcion.torneo, 'media-mediana');
    if (inscripcion.modalidad === 'equipo') {
      const equipo = mediaEntidad('equipo', inscripcion.id_equipo, inscripcion.equipo, 'media-pequena');
      return '<span class="media-apilada-actividad">' + torneo + equipo + '</span>';
    }
    return '<span class="media-apilada-actividad">' + torneo + (avatarUsuario || '') + '</span>';
  }

  function vacio(tipo, titulo, descripcion, enlace, textoEnlace) {
    return '<div class="estado-vacio-actividad"><span class="icono-vacio-actividad" aria-hidden="true">' + icono(tipo) + '</span><div><strong>' + escapar(titulo) + '</strong><p>' + escapar(descripcion) + '</p></div>' + (enlace ? '<a href="' + escapar(enlace) + '">' + escapar(textoEnlace) + '</a>' : '') + '</div>';
  }

  function renderLista(id, contenido, contenidoVacio) {
    const contenedor = document.getElementById(id);
    if (!contenedor) return;
    contenedor.innerHTML = contenido || contenidoVacio;
  }

  function escribirNumero(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.textContent = String(valor);
  }

  function actualizarResumen(datos) {
    const equipos = Array.isArray(datos.equipos) ? datos.equipos : [];
    const invitaciones = Array.isArray(datos.invitaciones) ? datos.invitaciones : [];
    const inscripciones = Array.isArray(datos.inscripciones) ? datos.inscripciones : [];
    const proximos = Array.isArray(datos.proximos_enfrentamientos) ? datos.proximos_enfrentamientos : [];
    const resultados = Array.isArray(datos.ultimos_resultados) ? datos.ultimos_resultados : [];
    const invitacionesPendientes = invitaciones.filter(function (invitacion) { return invitacion.estado === 'pendiente'; });
    const inscripcionesActivas = inscripciones.filter(function (inscripcion) {
      return (inscripcion.estado === 'pendiente' || inscripcion.estado === 'aprobada') &&
        inscripcion.estado_torneo !== 'finalizado' && inscripcion.estado_torneo !== 'cancelado';
    });

    escribirNumero('resumenEquiposActividad', equipos.length);
    escribirNumero('resumenInvitacionesActividad', invitacionesPendientes.length);
    escribirNumero('resumenInscripcionesActividad', inscripcionesActivas.length);
    escribirNumero('resumenProximosActividad', proximos.length);
    escribirNumero('badgeEquiposActividad', equipos.length);
    escribirNumero('badgeInvitacionesActividad', invitacionesPendientes.length);
    escribirNumero('badgeInscripcionesActividad', inscripcionesActivas.length);
    escribirNumero('badgeProximosActividad', proximos.length);
    escribirNumero('badgeResultadosActividad', resultados.length);

    const detalleProximo = document.getElementById('resumenProximosDetalle');
    if (detalleProximo) {
      detalleProximo.textContent = proximos.length ? fecha(proximos[0].fecha_hora) : 'Sin compromisos programados';
    }
  }

  function render(datos) {
    actualizarResumen(datos);
    renderAlertas(datos);
    const avatarUsuario = window.ArenaCJDAvatar
      ? window.ArenaCJDAvatar.html(datos.usuario_id, datos.usuario_nombre || 'Usuario', 'avatar-mediano')
      : '';

    renderLista('miActividadEquipos', (datos.equipos || []).map(function (equipo) {
      return item(equipo.nombre, 'Responsable @' + equipo.responsable_usuario, [equipo.estado, equipo.integrantes + ' integrantes'], mediaEntidad('equipo', equipo.id_equipo, equipo.nombre, 'media-mediana'));
    }).join(''), vacio('equipos', 'No perteneces a ningún equipo todavía.', 'Cuando administres o aceptes una invitación a un equipo aparecerá aquí.', 'participantes.php', 'Administrar equipos'));

    renderLista('miActividadInvitaciones', (datos.invitaciones || []).map(function (invitacion) {
      if (invitacion.clase_invitacion === 'equipo_membresia') {
        return item(invitacion.equipo, 'Invitación para unirte al equipo de @' + invitacion.invitador_usuario, [invitacion.estado, 'Equipo permanente'], mediaEntidad('equipo', invitacion.id_equipo, invitacion.equipo, 'media-mediana'));
      }
      const mediaInvitacion = invitacion.tipo === 'equipo' && invitacion.id_equipo
        ? '<span class="media-apilada-actividad">' + mediaEntidad('torneo', invitacion.id_torneo, invitacion.torneo, 'media-mediana') + mediaEntidad('equipo', invitacion.id_equipo, invitacion.equipo, 'media-pequena') + '</span>'
        : mediaEntidad('torneo', invitacion.id_torneo, invitacion.torneo, 'media-mediana');
      return item(invitacion.torneo, invitacion.tipo === 'equipo' ? ('Equipo ' + (invitacion.equipo || '')) : ('Invitación de @' + invitacion.invitador_usuario), [invitacion.estado, invitacion.disciplina], mediaInvitacion);
    }).join(''), vacio('invitaciones', 'No tienes invitaciones registradas.', 'Las solicitudes para unirte a equipos o torneos aparecerán en este espacio.', 'participantes.php#mis-invitaciones', 'Abrir invitaciones'));

    renderLista('miActividadInscripciones', (datos.inscripciones || []).map(function (inscripcion) {
      const esEquipo = inscripcion.modalidad === 'equipo';
      let descripcion = esEquipo ? ('Equipo ' + (inscripcion.equipo || '')) : 'Participación individual';
      if (inscripcion.estado === 'aprobada' && inscripcion.estado_torneo === 'en_curso') {
        descripcion += ' · Participando actualmente';
      } else if (inscripcion.estado === 'aprobada' && inscripcion.estado_torneo === 'inscripciones') {
        descripcion += ' · Inscripción confirmada';
      }
      return item(inscripcion.torneo, descripcion, [
        etiquetaEstado(inscripcion.estado),
        etiquetaEstado(inscripcion.estado_torneo),
        inscripcion.disciplina,
        inscripcion.categoria
      ], mediaParticipacion(inscripcion, avatarUsuario));
    }).join(''), vacio('inscripciones', 'No tienes inscripciones registradas.', 'Tus participaciones individuales y por equipos aparecerán aquí apenas te inscribas o aceptes una invitación.', 'participantes.php', 'Ver participantes'));

    renderLista('miActividadProximos', (datos.proximos_enfrentamientos || []).map(function (partido) {
      const mediaPartido = partido.tipo_participante === 'equipo'
        ? mediaEntidad('equipo', partido.id_equipo_a || partido.id_equipo_b, partido.participante_a || partido.participante_b, 'media-mediana')
        : avatarUsuario;
      return item((partido.participante_a || 'Por definir') + ' vs ' + (partido.participante_b || 'Por definir'), partido.torneo + ' · ' + partido.ronda, [partido.disciplina, partido.estado, fecha(partido.fecha_hora)], '<span class="media-apilada-actividad">' + mediaEntidad('torneo', partido.id_torneo, partido.torneo, 'media-mediana') + mediaPartido + '</span>');
    }).join(''), vacio('calendario', 'No tienes enfrentamientos próximos.', 'Cuando tengas partidos programados aparecerán aquí con su fecha y estado.', 'partidos.php', 'Ver enfrentamientos'));

    renderLista('miActividadResultados', (datos.ultimos_resultados || []).map(function (partido) {
      const mediaResultado = partido.tipo_participante === 'equipo'
        ? mediaEntidad('equipo', partido.id_equipo_a || partido.id_equipo_b, partido.participante_a || partido.participante_b, 'media-mediana')
        : avatarUsuario;
      return item((partido.participante_a || 'Participante') + ' ' + partido.puntaje_a + ' - ' + partido.puntaje_b + ' ' + (partido.participante_b || 'Participante'), partido.torneo + ' · ' + partido.ronda, [partido.disciplina, fecha(partido.fecha_hora)], '<span class="media-apilada-actividad">' + mediaEntidad('torneo', partido.id_torneo, partido.torneo, 'media-mediana') + mediaResultado + '</span>');
    }).join(''), vacio('resultados', 'Todavía no tienes resultados registrados.', 'Tus resultados recientes se mostrarán aquí cuando finalicen tus enfrentamientos.', 'resultados.php', 'Ver resultados'));
    if (window.ArenaCJDAvatar) window.ArenaCJDAvatar.activar(document.querySelector('.actividad-usuario-panel'));
  }

  async function cargar() {
    try {
      const respuesta = await fetch('api/mi_actividad.php?_=' + Date.now(), {cache: 'no-store'});
      const datos = await respuesta.json();
      if (!respuesta.ok || !datos.exito) throw new Error(datos.mensaje || 'No se pudo cargar la actividad.');
      render(datos);
      if (window.ArenaCJDMarcarActividadVista) window.ArenaCJDMarcarActividadVista();
      if (errorEstado) {
        errorEstado.hidden = true;
        errorEstado.innerHTML = '';
      }
      if (estado) {
        const actualizado = datos.actualizado_en ? new Date(datos.actualizado_en) : new Date();
        estado.textContent = 'Actualizado ' + actualizado.toLocaleTimeString('es-UY', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
      }
    } catch (error) {
      if (estado) estado.textContent = 'Error al sincronizar';
      if (errorEstado && window.ArenaCJDEstadoError) window.ArenaCJDEstadoError(errorEstado, error.message, cargar);
    }
  }

  cargar();
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('mi-actividad', cargar, 10000, {ejecutarAhora: false});
}());
