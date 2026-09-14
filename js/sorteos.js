(function () {
  'use strict';

  const selector = document.getElementById('torneoSorteo');
  if (!selector) return;

  const elementos = {
    icono: document.getElementById('iconoTorneoSorteo'),
    nombre: document.getElementById('nombreTorneoSorteo'),
    detalle: document.getElementById('detalleTorneoSorteo'),
    cantidad: document.getElementById('cantidadSorteo'),
    tipoCantidad: document.getElementById('tipoCantidadSorteo'),
    fecha: document.getElementById('fechaSorteo'),
    opcionAprobados: document.getElementById('opcionAprobados'),
    tituloParticipantes: document.getElementById('tituloParticipantesSorteo'),
    descripcionParticipantes: document.getElementById('descripcionParticipantesSorteo'),
    cabeceraNombre: document.getElementById('cabeceraNombreParticipante'),
    cuerpo: document.getElementById('cuerpoParticipantesSorteo'),
    cruces: document.getElementById('listaEmparejamientosSorteo'),
    cantidadEnfrentamientos: document.getElementById('cantidadEnfrentamientos'),
    cantidadResumen: document.getElementById('cantidadParticipantesResumen'),
    cantidadPases: document.getElementById('cantidadPases'),
    tituloCuadro: document.getElementById('tituloCuadroSorteo'),
    subtituloCuadro: document.getElementById('subtituloCuadroSorteo'),
    cuadro: document.getElementById('cuadroSorteoCompleto'),
    detalles: document.getElementById('vistaCompletaSorteo'),
    generar: document.getElementById('generarSorteo'),
    regenerar: document.getElementById('regenerarSorteo'),
    confirmar: document.getElementById('confirmarSorteo'),
    estado: document.getElementById('estadoSorteo'),
    metodo: document.getElementById('metodoSorteo'),
    permitirPases: document.getElementById('permitirPasesAutomaticos'),
    tituloVistaCompleta: document.getElementById('tituloVistaCompletaSorteo'),
    descripcionVistaCompleta: document.getElementById('descripcionVistaCompletaSorteo'),
    notificacion: document.getElementById('notificacionSorteo'),
    tituloNotificacion: document.getElementById('tituloNotificacionSorteo'),
    mensajeNotificacion: document.getElementById('mensajeNotificacionSorteo'),
    cerrarNotificacion: document.getElementById('cerrarNotificacionSorteo'),
    progresoNotificacion: document.getElementById('progresoNotificacionSorteo'),
    continuar: document.getElementById('continuarEnfrentamientosSorteo'),
    ayudaGenerar: document.getElementById('ayudaGenerarSorteo'),
    avisoResultado: document.getElementById('avisoEstadoResultadoSorteo'),
    resumenGeneracion: document.getElementById('resumenGeneracionSorteo'),
    verCuadro: document.getElementById('verCuadroCompletoSorteo')
  };

  let participantesActuales = [];
  let ordenSorteoActual = [];
  let csrfTokenSorteo = '';
  let usuarioSesionId = 0;
  let rolesSesion = [];
  let temporizadorNotificacionSorteo = 0;
  let temporizadorOcultarNotificacionSorteo = 0;
  let vistaPreviaValida = false;
  let sorteoExistenteCargado = false;
  let sorteoExistenteBloqueado = false;

  try {
    const sesionInicial = JSON.parse(document.body.dataset.sesionArenaCjd || '{}');
    usuarioSesionId = Number(sesionInicial.usuario && sesionInicial.usuario.id || 0);
    rolesSesion = Array.isArray(sesionInicial.usuario && sesionInicial.usuario.roles) ? sesionInicial.usuario.roles : [];
  } catch (error) {
    usuarioSesionId = 0;
    rolesSesion = [];
  }


  async function refrescarCatalogoTorneos() {
    if (!window.ArenaCJDDatos || typeof window.ArenaCJDDatos.cargarTorneos !== 'function') return;
    const valor = selector.value;
    await window.ArenaCJDDatos.cargarTorneos();
    if (valor && Array.from(selector.options).some(function (opcion) { return opcion.value === valor; })) {
      selector.value = valor;
    }
  }

  function torneoDisponibleParaSorteo(torneo) {
    return Boolean(torneo) && (torneo.estadoBD === 'inscripciones' || torneo.estadoBD === 'en_curso');
  }

  function puedeGestionarTorneo(torneo) {
    if (!torneoDisponibleParaSorteo(torneo)) return false;
    if (rolesSesion.includes('administrador')) return true;
    return rolesSesion.includes('organizador') && Number(torneo.idOrganizador) === usuarioSesionId;
  }

  function mensajeMinimoParticipantes(torneo, cantidad) {
    const esEquipo = torneo && torneo.modalidadBD === 'equipo';
    if (esEquipo) {
      return 'Este torneo por equipos tiene ' + cantidad + ' equipo' + (cantidad === 1 ? '' : 's') + ' aprobado' + (cantidad === 1 ? '' : 's') + '. Se necesitan al menos 2 equipos aprobados.';
    }
    return 'Este torneo individual tiene ' + cantidad + ' participante' + (cantidad === 1 ? '' : 's') + ' aprobado' + (cantidad === 1 ? '' : 's') + '. Se necesitan al menos 2 participantes aprobados. Los integrantes de equipos no cuentan como inscripciones individuales.';
  }

  function pluralizarCantidad(cantidad, singular, plural) {
    return cantidad + ' ' + (cantidad === 1 ? singular : plural);
  }

  function requisitosGeneracion(torneo) {
    if (!torneoDisponibleParaSorteo(torneo)) {
      return {
        permitido: false,
        breve: 'Selecciona un torneo disponible para generar el sorteo.',
        completo: 'Selecciona un torneo que esté en Inscripciones o En curso.'
      };
    }

    if (!puedeGestionarTorneo(torneo)) {
      return {
        permitido: false,
        breve: 'No tienes permisos para generar este sorteo.',
        completo: 'No tienes permisos para gestionar el sorteo de este torneo.'
      };
    }

    const cantidad = participantesActuales.length;
    if (cantidad < 2) {
      const faltan = 2 - cantidad;
      const esEquipo = torneo.modalidadBD === 'equipo';
      return {
        permitido: false,
        breve: (faltan === 1 ? 'Falta ' : 'Faltan ') + pluralizarCantidad(faltan, esEquipo ? 'equipo aprobado' : 'participante aprobado', esEquipo ? 'equipos aprobados' : 'participantes aprobados') + '.',
        completo: mensajeMinimoParticipantes(torneo, cantidad)
      };
    }

    if (cantidad % 2 !== 0 && elementos.permitirPases && !elementos.permitirPases.checked) {
      return {
        permitido: false,
        breve: 'Activa los pases automáticos o agrega una inscripción aprobada.',
        completo: 'La cantidad de participantes es impar. Activa los pases automáticos o agrega otra inscripción antes de generar el sorteo.'
      };
    }

    return {permitido: true, breve: '', completo: ''};
  }

  function actualizarAccesoCuadro(habilitado) {
    if (!elementos.verCuadro) return;
    elementos.verCuadro.setAttribute('aria-disabled', habilitado ? 'false' : 'true');
    elementos.verCuadro.classList.toggle('boton-deshabilitado', !habilitado);
  }

  function actualizarDisponibilidadGeneracion(torneo) {
    const requisitos = requisitosGeneracion(torneo);
    const bloqueado = sorteoExistenteBloqueado;
    const mensajeBloqueo = 'El sorteo está bloqueado porque ya existen enfrentamientos disputados o programados.';

    if (elementos.generar) {
      elementos.generar.disabled = !requisitos.permitido || bloqueado;
      elementos.generar.title = bloqueado ? mensajeBloqueo : (requisitos.permitido ? '' : requisitos.breve);
    }

    if (elementos.ayudaGenerar) {
      const mensaje = bloqueado ? mensajeBloqueo : requisitos.breve;
      elementos.ayudaGenerar.textContent = mensaje;
      elementos.ayudaGenerar.hidden = !mensaje;
    }

    if (elementos.regenerar) {
      const puedeRegenerar = requisitos.permitido && !bloqueado && (vistaPreviaValida || sorteoExistenteCargado);
      elementos.regenerar.disabled = !puedeRegenerar;
    }

    return requisitos;
  }

  function descripcionParticipantesActual(torneo, cantidad) {
    const esEquipo = torneo && torneo.modalidadBD === 'equipo';
    if (cantidad < 2) {
      const faltan = 2 - cantidad;
      const aprobados = pluralizarCantidad(cantidad, esEquipo ? 'equipo aprobado' : 'participante aprobado', esEquipo ? 'equipos aprobados' : 'participantes aprobados');
      const faltantes = pluralizarCantidad(faltan, esEquipo ? 'equipo' : 'participante', esEquipo ? 'equipos' : 'participantes');
      return cantidad === 0
        ? 'No hay ' + (esEquipo ? 'equipos' : 'participantes') + ' aprobados. Faltan ' + faltantes + '.'
        : aprobados + '. Falta ' + faltantes + ' para habilitar el sorteo.';
    }
    return pluralizarCantidad(cantidad, esEquipo ? 'equipo aprobado' : 'participante aprobado', esEquipo ? 'equipos aprobados' : 'participantes aprobados') + ' · listo para generar la vista previa.';
  }

  function ocultarNotificacionSorteo() {
    if (!elementos.notificacion) return;
    window.clearTimeout(temporizadorNotificacionSorteo);
    window.clearTimeout(temporizadorOcultarNotificacionSorteo);
    elementos.notificacion.classList.remove('visible');
    temporizadorOcultarNotificacionSorteo = window.setTimeout(function () {
      elementos.notificacion.hidden = true;
    }, 220);
  }

  function mostrarNotificacionSorteo(mensaje, titulo) {
    if (!elementos.notificacion) return;
    window.clearTimeout(temporizadorNotificacionSorteo);
    window.clearTimeout(temporizadorOcultarNotificacionSorteo);
    if (elementos.tituloNotificacion) elementos.tituloNotificacion.textContent = titulo || 'No se puede generar el sorteo';
    if (elementos.mensajeNotificacion) elementos.mensajeNotificacion.textContent = mensaje;
    elementos.notificacion.hidden = false;
    elementos.notificacion.classList.remove('visible');
    if (elementos.progresoNotificacion) {
      elementos.progresoNotificacion.style.animation = 'none';
      void elementos.progresoNotificacion.offsetWidth;
      elementos.progresoNotificacion.style.animation = '';
    }
    window.requestAnimationFrame(function () {
      elementos.notificacion.classList.add('visible');
    });
    temporizadorNotificacionSorteo = window.setTimeout(ocultarNotificacionSorteo, 6500);
  }

  function mostrarProblemaSorteo(mensaje) {
    mostrarNotificacionSorteo(mensaje, 'No se puede generar el sorteo');
    if (elementos.estado) {
      elementos.estado.textContent = 'Requiere atención';
      elementos.estado.classList.add('pendiente');
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

  function obtenerCsrfInicial(){try{const d=JSON.parse(document.body.dataset.sesionArenaCjd||'{}');return d.csrf_token||'';}catch(e){return '';}}
  function iconoDisciplina(disciplina) {
    const iconos = {
      'Vóleibol': window.ArenaCJDIcono('balon'),
      'Ajedrez': window.ArenaCJDIcono('ajedrez'),
      'eSports': window.ArenaCJDIcono('juego'),
      'Tenis de mesa': window.ArenaCJDIcono('raqueta'),
      'Básquetbol': window.ArenaCJDIcono('balon'),
      'Juego de cartas': window.ArenaCJDIcono('cartas')
    };
    return iconos[disciplina] || window.ArenaCJDIcono('torneo');
  }

  function formatearFecha(fecha) {
    if (!fecha) return '-';
    const partes = fecha.split('-');
    return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : fecha;
  }

  function mezclar(lista) {
    const copia = lista.slice();
    for (let i = copia.length - 1; i > 0; i -= 1) {
      const j = Math.floor(Math.random() * (i + 1));
      const temporal = copia[i];
      copia[i] = copia[j];
      copia[j] = temporal;
    }
    return copia;
  }

  function ordenarPorCabezas(lista) {
    const base = lista.slice();
    const orden = [];
    let inicio = 0;
    let fin = base.length - 1;
    while (inicio <= fin) {
      orden.push(base[inicio]);
      if (inicio !== fin) orden.push(base[fin]);
      inicio += 1;
      fin -= 1;
    }
    return orden;
  }

  function ordenarSegunMetodo(lista) {
    const metodo = elementos.metodo ? elementos.metodo.value : 'Aleatorio';
    if (metodo === 'Por cabezas de serie') return ordenarPorCabezas(lista);
    if (metodo === 'Orden actual') return lista.slice();
    return mezclar(lista);
  }

  function crearCruces(lista) {
    const cruces = [];
    for (let i = 0; i < lista.length; i += 2) {
      cruces.push([lista[i], lista[i + 1] || 'Pase automático']);
    }
    return cruces;
  }

  function esPaseAutomatico(participante) {
    return !participante || participante === 'Pase automático' || participante.esPase === true;
  }

  function htmlIdentidadCruce(participante, claseMedia) {
    if (esPaseAutomatico(participante)) {
      return '<span class="identidad-cruce-sorteo pase-automatico"><span class="nombre-cruce-sorteo">Pase automático</span></span>';
    }

    const nombre = participante.nombre || '';
    const media = participante.tipo === 'equipo' && window.ArenaCJDMedia
      ? window.ArenaCJDMedia.html('equipo', participante.id, nombre, {clase: claseMedia || 'media-pequena'})
      : '';
    return '<span class="identidad-cruce-sorteo">' + media + '<span class="nombre-cruce-sorteo">' + escaparHTML(nombre) + '</span></span>';
  }

  function htmlEmparejamiento(cruce, indice) {
    return '<article class="emparejamiento-previo"><div class="competidor-previo"><span class="numero-competidor-previo">' + (indice * 2 + 1) + '</span><strong>' + htmlIdentidadCruce(cruce[0], 'media-pequena') + '</strong></div><b>VS</b><div class="competidor-previo"><span class="numero-competidor-previo">' + (indice * 2 + 2) + '</span><strong>' + htmlIdentidadCruce(cruce[1], 'media-pequena') + '</strong></div></article>';
  }

  function htmlCruceLlave(cruce, indice, estado) {
    return '<article class="cruce-llave"><div><span>' + (indice * 2 + 1) + '</span><strong>' + htmlIdentidadCruce(cruce[0], 'media-pequena') + '</strong></div><div><span>' + (indice * 2 + 2) + '</span><strong>' + htmlIdentidadCruce(cruce[1], 'media-pequena') + '</strong></div><small>' + escaparHTML(estado) + '</small></article>';
  }

  function renderTabla(torneo, participantes) {
    if (!elementos.cuerpo) return;

    elementos.cuerpo.innerHTML = participantes.map(function (participante, indice) {
      const nombre = participante.usuario
        ? participante.nombre + ' (@' + participante.usuario + ')'
        : participante.nombre;
      const avatar = participante.tipo === 'individual' && window.ArenaCJDAvatar
        ? window.ArenaCJDAvatar.html(participante.id, participante.nombre, 'avatar-pequeno')
        : (participante.tipo === 'equipo' && window.ArenaCJDMedia
          ? window.ArenaCJDMedia.html('equipo', participante.id, participante.nombre, {clase: 'media-pequena'})
          : '');
      return '<tr class="fila-participante-destacada"><td>' + (indice + 1) + '</td><td><span class="participante-sorteo-identidad">' + avatar + '<span>' +
        escaparHTML(nombre) +
        '</span></span></td><td>' + escaparHTML(torneo.disciplina) + '</td><td><span class="estado-mini aprobado">Aprobado</span></td></tr>';
    }).join('');
    if (window.ArenaCJDAvatar) window.ArenaCJDAvatar.activar(elementos.cuerpo);
  }

  function limpiarResultado(torneo) {
    ordenSorteoActual = [];
    vistaPreviaValida = false;
    sorteoExistenteCargado = false;
    sorteoExistenteBloqueado = false;
    if (elementos.cruces) {
      elementos.cruces.innerHTML = '<div class="estado-previo-sorteo"><strong>Sin vista previa</strong><span>Los enfrentamientos aparecerán aquí después de generar el sorteo.</span></div>';
    }
    if (elementos.cuadro) elementos.cuadro.innerHTML = '';
    if (elementos.detalles) {
      elementos.detalles.hidden = true;
      elementos.detalles.open = false;
    }
    actualizarAccesoCuadro(false);
    if (elementos.resumenGeneracion) elementos.resumenGeneracion.hidden = true;
    if (elementos.avisoResultado) {
      elementos.avisoResultado.textContent = torneo
        ? 'Todavía no hay una vista previa del sorteo. Genera los cruces cuando se cumplan los requisitos.'
        : 'Todavía no hay una vista previa del sorteo. Selecciona un torneo y genera los cruces cuando se cumplan los requisitos.';
      elementos.avisoResultado.classList.add('aviso-sorteo-previo');
    }
    if (elementos.cantidadEnfrentamientos) elementos.cantidadEnfrentamientos.textContent = '0 enfrentamientos';
    if (elementos.cantidadResumen) {
      const esEquipo = torneo && torneo.modalidadBD === 'equipo';
      elementos.cantidadResumen.textContent = pluralizarCantidad(participantesActuales.length, esEquipo ? 'equipo' : 'participante', esEquipo ? 'equipos' : 'participantes');
    }
    if (elementos.cantidadPases) elementos.cantidadPases.textContent = '0 pases';
    if (elementos.regenerar) elementos.regenerar.disabled = true;
    if (elementos.confirmar) {
      elementos.confirmar.disabled = true;
      elementos.confirmar.textContent = torneo && !puedeGestionarTorneo(torneo) ? 'Solo lectura' : 'Confirmar sorteo';
    }
    if (elementos.continuar) elementos.continuar.hidden = true;
    if (elementos.estado) {
      elementos.estado.textContent = 'Pendiente';
      elementos.estado.classList.add('pendiente');
    }
  }

  function renderPrevia(torneo, lista) {
    vistaPreviaValida = true;
    sorteoExistenteCargado = false;
    sorteoExistenteBloqueado = false;
    const cruces = crearCruces(lista);
    if (elementos.cruces) {
      elementos.cruces.innerHTML = cruces.map(htmlEmparejamiento).join('');
    }
    const cantidadPases = cruces.filter(function (cruce) { return esPaseAutomatico(cruce[1]); }).length;
    if (elementos.cantidadEnfrentamientos) elementos.cantidadEnfrentamientos.textContent = pluralizarCantidad(cruces.length, 'enfrentamiento', 'enfrentamientos');
    if (elementos.cantidadResumen) elementos.cantidadResumen.textContent = pluralizarCantidad(lista.length, torneo.modalidadBD === 'equipo' ? 'equipo' : 'participante', torneo.modalidadBD === 'equipo' ? 'equipos' : 'participantes');
    if (elementos.cantidadPases) elementos.cantidadPases.textContent = pluralizarCantidad(cantidadPases, 'pase', 'pases');
    if (elementos.resumenGeneracion) elementos.resumenGeneracion.hidden = false;
    if (elementos.detalles) elementos.detalles.hidden = false;
    actualizarAccesoCuadro(true);
    if (elementos.avisoResultado) {
      elementos.avisoResultado.textContent = 'Esta es una vista previa. Nada se modifica hasta que confirmes. Al confirmar se creará la primera ronda del torneo con estas inscripciones.';
      elementos.avisoResultado.classList.remove('aviso-sorteo-previo');
    }

    if (!elementos.cuadro) return;
    const esEliminacion = torneo.tipo.toLowerCase().indexOf('elimin') !== -1;
    if (elementos.tituloVistaCompleta) {
      elementos.tituloVistaCompleta.textContent = esEliminacion
        ? 'Cuadro de eliminación directa'
        : 'Emparejamientos de la primera ronda';
    }
    if (elementos.descripcionVistaCompleta) {
      elementos.descripcionVistaCompleta.textContent = esEliminacion
        ? 'Vista previa de los cruces que se crearán al confirmar'
        : torneo.tipo + ' · ronda inicial calculada con las inscripciones aprobadas';
    }

    elementos.cuadro.innerHTML = cruces.map(function (cruce, indice) {
      return htmlCruceLlave(cruce, indice, esEliminacion ? 'Por disputarse' : 'Ronda 1');
    }).join('');
  }

  async function cargarSorteoExistente(torneo) {
    try {
      const respuesta = await fetch('api/enfrentamientos.php?id_torneo=' + encodeURIComponent(torneo.id) + '&_=' + Date.now(), {cache: 'no-store'});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) return;
      const todos = resultado.enfrentamientos || [];
      if (!todos.length) return;

      const primera = todos.filter(function (e) { return Number(e.numero_ronda) === 1; });
      if (!primera.length) return;

      sorteoExistenteCargado = true;
      vistaPreviaValida = false;
      const bloqueado = todos.some(function (e) {
        const dos = e.id_participante_a != null && e.id_participante_b != null;
        return Number(e.numero_ronda) > 1 || e.estado === 'en_curso' ||
          (e.estado === 'finalizado' && dos) || Boolean(e.fecha_hora);
      });

      sorteoExistenteBloqueado = bloqueado;
      const cruces = primera.map(function (e) {
        const tipo = e.tipo_participante === 'equipo' ? 'equipo' : 'individual';
        return [
          e.id_participante_a == null ? {esPase:true} : {id:e.id_participante_a, nombre:e.participante_a || '', tipo:tipo},
          e.id_participante_b == null ? {esPase:true} : {id:e.id_participante_b, nombre:e.participante_b || '', tipo:tipo}
        ];
      });

      if (elementos.resumenGeneracion) elementos.resumenGeneracion.hidden = false;
      if (elementos.detalles) elementos.detalles.hidden = false;
      actualizarAccesoCuadro(true);
      if (elementos.avisoResultado) {
        elementos.avisoResultado.textContent = bloqueado
          ? 'La primera ronda ya está guardada y tiene actividad competitiva. Se muestra en modo de consulta para proteger los resultados.'
          : 'La primera ronda ya está guardada. Puedes revisarla o volver a generar una vista previa mientras el torneo siga sin actividad competitiva.';
        elementos.avisoResultado.classList.remove('aviso-sorteo-previo');
      }

      if (elementos.cruces) {
        elementos.cruces.innerHTML = cruces.map(htmlEmparejamiento).join('');
      }
      if (elementos.cuadro) {
        elementos.cuadro.innerHTML = cruces.map(function (cruce, indice) {
          return htmlCruceLlave(cruce, indice, 'Confirmado');
        }).join('');
      }
      if (elementos.estado) {
        elementos.estado.textContent = bloqueado ? 'En competencia' : 'Confirmado';
        elementos.estado.classList.remove('pendiente');
      }
      if (elementos.cantidadEnfrentamientos) elementos.cantidadEnfrentamientos.textContent = pluralizarCantidad(cruces.length, 'enfrentamiento', 'enfrentamientos');
      if (elementos.cantidadPases) {
        const pasesGuardados = primera.filter(function (e) { return e.id_participante_a == null || e.id_participante_b == null; }).length;
        elementos.cantidadPases.textContent = pluralizarCantidad(pasesGuardados, 'pase', 'pases');
      }
      if (elementos.tituloVistaCompleta) elementos.tituloVistaCompleta.textContent = 'Primera ronda guardada';
      if (elementos.descripcionVistaCompleta) elementos.descripcionVistaCompleta.textContent = torneo.nombre + ' · sorteo confirmado';
      if (elementos.confirmar) {
        elementos.confirmar.disabled = true;
        elementos.confirmar.textContent = puedeGestionarTorneo(torneo) ? 'Sorteo guardado' : 'Solo lectura';
      }
      actualizarDisponibilidadGeneracion(torneo);
      if (bloqueado && elementos.descripcionParticipantes) {
        elementos.descripcionParticipantes.textContent = 'El torneo ya tiene actividad competitiva. La primera ronda está bloqueada para proteger los resultados.';
      }
    } catch (error) {
    }
  }

  async function cargarParticipantes(torneo) {
    participantesActuales = [];
    limpiarResultado(torneo);

    try {
      const respuesta = await fetch('api/torneo_participantes.php?id=' + encodeURIComponent(torneo.id), { cache: 'no-store' });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito || !Array.isArray(resultado.participantes)) {
        throw new Error(resultado.mensaje || 'No se pudieron cargar las inscripciones aprobadas.');
      }
      participantesActuales = resultado.participantes;
    } catch (error) {
      participantesActuales = [];
      if (elementos.cuerpo) {
        elementos.cuerpo.innerHTML = '<tr><td colspan="4"><div data-error-sorteo></div></td></tr>';
        const contenedorError = elementos.cuerpo.querySelector('[data-error-sorteo]');
        if (window.ArenaCJDEstadoError) window.ArenaCJDEstadoError(contenedorError, error.message, function () { return cargarParticipantes(torneo).then(function () { return cargarSorteoExistente(torneo); }); });
        else contenedorError.textContent = error.message;
      }
      if (elementos.descripcionParticipantes) elementos.descripcionParticipantes.textContent = 'No se pudieron sincronizar las inscripciones aprobadas.';
      if (elementos.generar) elementos.generar.disabled = true;
      if (elementos.regenerar) elementos.regenerar.disabled = true;
      if (elementos.ayudaGenerar) {
        elementos.ayudaGenerar.textContent = 'No se pudieron cargar las inscripciones aprobadas.';
        elementos.ayudaGenerar.hidden = false;
      }
      return;
    }

    renderTabla(torneo, participantesActuales);
    const cantidad = participantesActuales.length;
    const esEquipo = torneo.modalidadBD === 'equipo';
    const singularCabecera = esEquipo ? 'Equipo' : 'Participante';

    if (elementos.cantidad) elementos.cantidad.textContent = cantidad;
    if (elementos.tipoCantidad) elementos.tipoCantidad.textContent = cantidad === 1
      ? (esEquipo ? 'Equipo aprobado' : 'Participante aprobado')
      : (esEquipo ? 'Equipos aprobados' : 'Participantes aprobados');
    if (elementos.opcionAprobados) elementos.opcionAprobados.textContent = 'Solo aprobadas (' + cantidad + ')';
    if (elementos.tituloParticipantes) elementos.tituloParticipantes.textContent = (esEquipo ? 'Equipos incluidos (' : 'Participantes incluidos (') + cantidad + ')';
    if (elementos.descripcionParticipantes) elementos.descripcionParticipantes.textContent = descripcionParticipantesActual(torneo, cantidad);
    if (elementos.cabeceraNombre) elementos.cabeceraNombre.textContent = singularCabecera;
    if (elementos.cantidadResumen) elementos.cantidadResumen.textContent = pluralizarCantidad(cantidad, esEquipo ? 'equipo' : 'participante', esEquipo ? 'equipos' : 'participantes');
    actualizarDisponibilidadGeneracion(torneo);
    if (elementos.confirmar) {
      elementos.confirmar.disabled = true;
      elementos.confirmar.textContent = puedeGestionarTorneo(torneo) ? 'Confirmar sorteo' : 'Solo lectura';
      elementos.confirmar.title = puedeGestionarTorneo(torneo) ? 'Primero genera una vista previa válida.' : 'Solo el administrador o el organizador responsable puede guardar este sorteo.';
    }
  }

  async function actualizarTorneo() {
    const torneo = window.ArenaCJDDatos && window.ArenaCJDDatos.obtenerTorneo(selector.value);

    if (torneo && !torneoDisponibleParaSorteo(torneo)) {
      selector.value = '';
    }

    const torneoDisponible = torneoDisponibleParaSorteo(torneo) ? torneo : null;

    if (!torneoDisponible) {
      if (elementos.nombre) elementos.nombre.textContent = 'Selecciona un torneo';
      if (elementos.detalle) elementos.detalle.textContent = 'Selecciona un torneo para cargar sus inscripciones';
      if (elementos.icono) elementos.icono.innerHTML = window.ArenaCJDIcono('torneo');
      if (elementos.fecha) elementos.fecha.textContent = '-';
      if (elementos.cantidad) elementos.cantidad.textContent = '0';
      if (elementos.tipoCantidad) elementos.tipoCantidad.textContent = 'Inscripciones aprobadas';
      if (elementos.opcionAprobados) elementos.opcionAprobados.textContent = 'Solo aprobadas (0)';
      if (elementos.tituloParticipantes) elementos.tituloParticipantes.textContent = 'Participantes incluidos (0)';
      if (elementos.descripcionParticipantes) elementos.descripcionParticipantes.textContent = 'Selecciona un torneo para cargar sus inscripciones reales.';
      if (elementos.cabeceraNombre) elementos.cabeceraNombre.textContent = 'Participante';
      if (elementos.tituloVistaCompleta) elementos.tituloVistaCompleta.textContent = 'Vista completa del sorteo';
      if (elementos.descripcionVistaCompleta) elementos.descripcionVistaCompleta.textContent = 'Selecciona un torneo y genera la primera ronda';
      participantesActuales = [];
      renderTabla({ disciplina: '' }, []);
      limpiarResultado(null);
      actualizarDisponibilidadGeneracion(null);
      return;
    }

    if (elementos.icono) {
      elementos.icono.innerHTML = window.ArenaCJDMedia
        ? window.ArenaCJDMedia.html('torneo', torneoDisponible.id, torneoDisponible.nombre, {clase: 'media-resumen-sorteo'})
        : iconoDisciplina(torneoDisponible.disciplina);
    }
    if (elementos.nombre) elementos.nombre.textContent = torneoDisponible.nombre;
    if (elementos.detalle) elementos.detalle.textContent = torneoDisponible.disciplina + ' · ' + torneoDisponible.modalidad + ' · ' + torneoDisponible.tipo;
    if (elementos.fecha) elementos.fecha.textContent = formatearFecha(torneoDisponible.fechaInicio);
    if (elementos.tituloCuadro) elementos.tituloCuadro.textContent = torneoDisponible.nombre;
    if (elementos.subtituloCuadro) elementos.subtituloCuadro.textContent = torneoDisponible.disciplina + ' · ' + torneoDisponible.tipo;
    if (elementos.detalles) elementos.detalles.open = false;

    await cargarParticipantes(torneoDisponible);
    await cargarSorteoExistente(torneoDisponible);
    actualizarDisponibilidadGeneracion(torneoDisponible);
  }

  function generarSorteo() {
    const torneo = window.ArenaCJDDatos && window.ArenaCJDDatos.obtenerTorneo(selector.value);
    const requisitos = requisitosGeneracion(torneo);
    if (!requisitos.permitido || sorteoExistenteBloqueado) {
      const mensaje = sorteoExistenteBloqueado
        ? 'El sorteo está bloqueado porque ya existen enfrentamientos disputados o programados.'
        : requisitos.completo;
      mostrarProblemaSorteo(mensaje);
      actualizarDisponibilidadGeneracion(torneo);
      return;
    }
    const mezclados = ordenarSegunMetodo(participantesActuales);
    ordenSorteoActual = mezclados.map(function (p) { return Number(p.id); });
    renderPrevia(torneo, mezclados);
    if (elementos.estado) { elementos.estado.textContent = 'Vista previa lista'; elementos.estado.classList.remove('pendiente'); }
    if (elementos.confirmar) {
      const puedeGuardar = puedeGestionarTorneo(torneo) && vistaPreviaValida;
      elementos.confirmar.disabled = !puedeGuardar;
      elementos.confirmar.textContent = puedeGuardar ? 'Confirmar sorteo' : 'Solo lectura';
      elementos.confirmar.title = puedeGuardar ? '' : 'La vista previa debe ser válida antes de confirmar.';
      if (elementos.continuar) elementos.continuar.hidden = true;
    }
    actualizarDisponibilidadGeneracion(torneo);
    if (elementos.generar) {
      elementos.generar.textContent = 'Sorteo generado';
      window.setTimeout(function () { elementos.generar.textContent = 'Generar sorteo'; }, 1200);
    }
  }

  if (elementos.cerrarNotificacion) elementos.cerrarNotificacion.addEventListener('click', ocultarNotificacionSorteo);
  if (elementos.verCuadro) elementos.verCuadro.addEventListener('click', function (evento) {
    if (elementos.verCuadro.getAttribute('aria-disabled') === 'true') evento.preventDefault();
  });
  if (elementos.permitirPases) elementos.permitirPases.addEventListener('change', function () {
    const torneo = window.ArenaCJDDatos && window.ArenaCJDDatos.obtenerTorneo(selector.value);
    const requisitos = requisitosGeneracion(torneo);
    if (vistaPreviaValida && !requisitos.permitido) limpiarResultado(torneo);
    actualizarDisponibilidadGeneracion(torneo);
  });

  selector.addEventListener('change', actualizarTorneo);
  if (elementos.generar) elementos.generar.addEventListener('click', generarSorteo);
  if (elementos.regenerar) elementos.regenerar.addEventListener('click', generarSorteo);
  if (elementos.confirmar) elementos.confirmar.addEventListener('click', async function () {
    const torneo = window.ArenaCJDDatos && window.ArenaCJDDatos.obtenerTorneo(selector.value);
    const requisitos = requisitosGeneracion(torneo);
    if (!vistaPreviaValida || !requisitos.permitido || ordenSorteoActual.length < 2 || sorteoExistenteBloqueado) return;
    const cantidad = participantesActuales.length;
    const esEquipo = torneo.modalidadBD === 'equipo';
    const mensaje = 'Vas a crear la primera ronda de ' + torneo.nombre + ' con ' + pluralizarCantidad(cantidad, esEquipo ? 'equipo' : 'participante', esEquipo ? 'equipos' : 'participantes') + '. La vista previa actual será la que quede confirmada.';
    const confirmado = typeof window.ArenaCJDConfirmar === 'function' ? await window.ArenaCJDConfirmar({titulo:'Confirmar sorteo',mensaje:mensaje,confirmar:'Crear primera ronda'}) : window.confirm(mensaje);
    if (!confirmado) return;
    elementos.confirmar.disabled = true;
    elementos.confirmar.textContent = 'Guardando sorteo...';
    try {
      if (!csrfTokenSorteo) csrfTokenSorteo = obtenerCsrfInicial();
      const respuesta = await fetch('api/sorteo_confirmar.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfTokenSorteo},body:JSON.stringify({id_torneo:Number(torneo.id),orden:ordenSorteoActual})});
      const resultado = await respuesta.json();
      if(!respuesta.ok||!resultado.exito) throw new Error(resultado.mensaje||'No se pudo guardar el sorteo.');
      vistaPreviaValida = false;
      sorteoExistenteCargado = true;
      sorteoExistenteBloqueado = false;
      elementos.confirmar.disabled = true;
      elementos.confirmar.textContent='Sorteo confirmado';
      if(elementos.estado){elementos.estado.textContent='Confirmado';elementos.estado.classList.remove('pendiente');}
      if (elementos.avisoResultado) elementos.avisoResultado.textContent = 'La primera ronda quedó confirmada y guardada. Puedes continuar a enfrentamientos.';
      actualizarDisponibilidadGeneracion(torneo);
      if (elementos.continuar) {
        elementos.continuar.href = 'partidos.php?torneo=' + encodeURIComponent(torneo.id);
        elementos.continuar.hidden = false;
        elementos.continuar.focus();
      }
      if (typeof window.ArenaCJDAvisar === 'function') window.ArenaCJDAvisar('Sorteo confirmado. La primera ronda ya está lista para gestionar.', 'exito');
    } catch(error){
      const requisitosActuales = requisitosGeneracion(torneo);
      elementos.confirmar.disabled = !vistaPreviaValida || !requisitosActuales.permitido;
      elementos.confirmar.textContent='Confirmar sorteo';
      mostrarNotificacionSorteo(error.message || 'No se pudo guardar el sorteo.', 'No se pudo guardar el sorteo');
      if(elementos.estado){elementos.estado.textContent='Requiere atención';elementos.estado.classList.add('pendiente');}
    }
  });

  window.addEventListener('arenacjd:torneos-cargados', function () {
    if (selector.dataset.torneoInicial && Array.from(selector.options).some(function(o){return o.value===selector.dataset.torneoInicial;})) { selector.value=selector.dataset.torneoInicial; delete selector.dataset.torneoInicial; } else if (!selector.value && selector.options.length > 1) { selector.value = selector.options[1].value; }
    actualizarTorneo();
  });

  csrfTokenSorteo = obtenerCsrfInicial();
  const torneoUrl = new URLSearchParams(window.location.search).get('torneo');
  if (torneoUrl) selector.dataset.torneoInicial = torneoUrl;
  refrescarCatalogoTorneos().then(actualizarTorneo);
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('sorteos-catalogo', refrescarCatalogoTorneos, 5000, {ejecutarAhora:false});
}());
