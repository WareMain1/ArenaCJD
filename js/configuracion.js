(function () {
  'use strict';

  const guardar = document.getElementById('guardarConfiguracion');
  const editarPerfil = document.getElementById('editarPerfilConfiguracion');
  let csrfPreferencias = '';
  let preferenciasCargadas = null;

  function csrfInicial() {
    try {
      const datos = JSON.parse(document.body.dataset.sesionArenaCjd || '{}');
      return datos.csrf_token || '';
    } catch (error) {
      return '';
    }
  }

  function controlesPreferencias() {
    const selectores = [
      '#preferencias-configuracion select',
      '#preferencias-configuracion input[type="checkbox"]',
      '#torneos-configuracion select',
      '#torneos-configuracion input[type="checkbox"]'
    ].join(',');
    return Array.from(document.querySelectorAll(selectores));
  }

  function aplicarPreferencias(preferencias) {
    controlesPreferencias().forEach(function (control) {
      if (!control.id || !Object.prototype.hasOwnProperty.call(preferencias, control.id)) return;
      if (control.type === 'checkbox') control.checked = Boolean(preferencias[control.id]);
      else control.value = String(preferencias[control.id] == null ? '' : preferencias[control.id]);
    });

  }

  async function cargarPreferencias() {
    try {
      const respuesta = await fetch('api/preferencias.php?_=' + Date.now(), {cache: 'no-store'});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron cargar las preferencias.');
      csrfPreferencias = resultado.csrf_token || csrfPreferencias || csrfInicial();
      preferenciasCargadas = resultado.preferencias || {};
      aplicarPreferencias(preferenciasCargadas);
    } catch (error) {
      csrfPreferencias = csrfPreferencias || csrfInicial();
    }
  }

  function datosPreferencias() {
    const datos = {};
    controlesPreferencias().forEach(function (control) {
      if (!control.id) return;
      datos[control.id] = control.type === 'checkbox' ? control.checked : control.value;
    });
    return datos;
  }

  async function guardarPreferencias() {
    if (!guardar) return;
    const textoOriginal = guardar.textContent;
    guardar.disabled = true;
    guardar.textContent = 'Guardando cambios...';

    try {
      const respuesta = await fetch('api/preferencias.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfPreferencias || csrfInicial()
        },
        body: JSON.stringify(datosPreferencias())
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron guardar las preferencias.');
      preferenciasCargadas = resultado.preferencias || {};
      aplicarPreferencias(preferenciasCargadas);
      guardar.innerHTML = window.ArenaCJDIcono('exito') + ' Cambios guardados';
    } catch (error) {
      guardar.textContent = error.message || 'No se pudo guardar';
    } finally {
      window.setTimeout(function () {
        guardar.textContent = textoOriginal;
        guardar.disabled = false;
      }, 1600);
    }
  }

  if (guardar) guardar.addEventListener('click', guardarPreferencias);

  if (editarPerfil) {
    editarPerfil.addEventListener('click', function () {
      const botonPerfil = document.getElementById('abrirPerfilUsuario');
      if (botonPerfil) botonPerfil.click();
    });
  }

  window.addEventListener('arenacjd:torneos-cargados', function () {
    if (preferenciasCargadas) aplicarPreferencias(preferenciasCargadas);
  });

  csrfPreferencias = csrfInicial();
  cargarPreferencias();
}());

(function () {
  'use strict';

  const pestanas = document.querySelectorAll('[data-pestana-usuarios]');
  const paneles = document.querySelectorAll('[data-panel-usuarios]');
  const cuerpoUsuarios = document.getElementById('cuerpoUsuariosActivos');
  const estadoUsuarios = document.getElementById('estadoUsuariosActivos');
  const actualizarUsuarios = document.getElementById('actualizarUsuariosActivos');
  const cantidadActivos = document.getElementById('cantidadUsuariosActivosConfig');
  const modal = document.getElementById('modalGestionUsuario');
  const formulario = document.getElementById('formularioGestionUsuario');
  const campoId = document.getElementById('gestionUsuarioId');
  const campoEstado = document.getElementById('gestionUsuarioEstado');
  const nombreUsuario = document.getElementById('gestionUsuarioNombre');
  const aliasUsuario = document.getElementById('gestionUsuarioAlias');
  const rolesContenedor = document.getElementById('rolesGestionUsuario');
  const mensaje = document.getElementById('mensajeGestionUsuario');

  let usuarios = [];
  let roles = [];
  let csrfToken = '';
  let cargando = false;
  let firmaUsuarios = '';

  function escapar(texto) {
    return String(texto ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatearFecha(valor) {
    if (!valor) return '-';
    const fecha = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(fecha.getTime())) return valor;
    return new Intl.DateTimeFormat('es-UY', {
      day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    }).format(fecha);
  }

  function textoEstado(estado) {
    return ({activo: 'Activo', inactivo: 'Inactivo', bloqueado: 'Bloqueado'})[estado] || estado;
  }

  function claseEstado(estado) {
    if (estado === 'activo') return 'estado-aprobado';
    if (estado === 'bloqueado') return 'estado-rechazado';
    return 'estado-pendiente';
  }

  function firma(datos) {
    return JSON.stringify((datos || []).map(function (u) {
      return [u.id_usuario, u.nombre_completo, u.nombre_usuario, u.correo, u.estado, u.roles];
    }));
  }

  function renderUsuarios() {
    if (!cuerpoUsuarios) return;

    if (!usuarios.length) {
      cuerpoUsuarios.innerHTML = '<tr><td colspan="6"><p class="sin-solicitudes">No hay usuarios registrados para gestionar.</p></td></tr>';
      if (cantidadActivos) cantidadActivos.textContent = '0';
      return;
    }

    const activos = usuarios.filter(function (u) { return u.estado === 'activo'; }).length;
    if (cantidadActivos) cantidadActivos.textContent = String(activos);

    cuerpoUsuarios.innerHTML = usuarios.map(function (usuario) {
      const rolesTexto = (usuario.roles_lista || []).map(function (rol) {
        return rol.charAt(0).toUpperCase() + rol.slice(1);
      }).join(', ') || 'Sin rol';

      return '<tr data-id-usuario="' + Number(usuario.id_usuario) + '">' +
        '<td><div class="usuario-tabla-config"><strong>' + escapar(usuario.nombre_completo) + '</strong><span>@' + escapar(usuario.nombre_usuario) + (usuario.es_sesion_actual ? ' · Tú' : '') + '</span></div></td>' +
        '<td>' + escapar(usuario.correo) + '</td>' +
        '<td><span class="roles-usuario-tabla">' + escapar(rolesTexto) + '</span></td>' +
        '<td><span class="estado-solicitud ' + claseEstado(usuario.estado) + '">' + escapar(textoEstado(usuario.estado)) + '</span></td>' +
        '<td>' + escapar(formatearFecha(usuario.fecha_registro)) + '</td>' +
        '<td><div class="acciones-usuario-activo">' +
        '<button type="button" data-accion-usuario="editar" data-id-usuario="' + Number(usuario.id_usuario) + '">Editar</button>' +
        '<button type="button" class="accion-eliminar-usuario" data-accion-usuario="eliminar" data-id-usuario="' + Number(usuario.id_usuario) + '"' + (usuario.es_sesion_actual ? ' disabled title="No puedes eliminar tu propia sesión"' : '') + '>Eliminar</button>' +
        '</div></td>' +
        '</tr>';
    }).join('');
  }

  async function cargarUsuarios(forzar) {
    if (!cuerpoUsuarios || cargando) return;
    cargando = true;
    if (estadoUsuarios && forzar) estadoUsuarios.textContent = 'Actualizando...';

    try {
      const respuesta = await fetch('api/usuarios_activos.php?_=' + Date.now(), {cache: 'no-store'});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron cargar los usuarios.');

      const nuevaFirma = firma(resultado.usuarios);
      usuarios = resultado.usuarios || [];
      roles = resultado.roles || [];
      csrfToken = resultado.csrf_token || csrfToken;

      if (forzar || nuevaFirma !== firmaUsuarios) {
        firmaUsuarios = nuevaFirma;
        renderUsuarios();
      }

      if (estadoUsuarios) {
        const ahora = new Date();
        estadoUsuarios.textContent = 'Sincronizado ' + ahora.toLocaleTimeString('es-UY', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
      }
    } catch (error) {
      if (estadoUsuarios) estadoUsuarios.textContent = error.message;
      if (!usuarios.length && cuerpoUsuarios) cuerpoUsuarios.innerHTML = '<tr><td colspan="6"><p class="sin-solicitudes">No se pudieron cargar los usuarios.</p></td></tr>';
    } finally {
      cargando = false;
    }
  }

  function abrirModal(usuario) {
    if (!modal || !formulario || !usuario) return;
    campoId.value = String(usuario.id_usuario);
    campoEstado.value = usuario.estado;
    nombreUsuario.textContent = usuario.nombre_completo;
    aliasUsuario.textContent = '@' + usuario.nombre_usuario;
    mensaje.textContent = '';
    mensaje.className = 'mensaje-gestion-usuario';

    rolesContenedor.innerHTML = roles.map(function (rol) {
      const seleccionado = (usuario.roles_lista || []).includes(rol.nombre);
      return '<label class="rol-seleccionable"><input type="checkbox" value="' + escapar(rol.nombre) + '"' + (seleccionado ? ' checked' : '') + '><span><strong>' + escapar(rol.nombre.charAt(0).toUpperCase() + rol.nombre.slice(1)) + '</strong></span></label>';
    }).join('');

    modal.hidden = false;
    document.body.classList.add('sin-scroll');
  }

  function cerrarModal() {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('sin-scroll');
    if (formulario) formulario.reset();
  }

  async function eliminarUsuario(usuario) {
    if (!usuario || usuario.es_sesion_actual) return;
    const confirmar = window.ArenaCJDConfirmar ? await window.ArenaCJDConfirmar({titulo:'Eliminar usuario',mensaje:'¿Eliminar definitivamente a @' + usuario.nombre_usuario + '? Esta acción solo se permitirá si no tiene torneos, equipos o inscripciones relacionadas.',confirmar:'Eliminar usuario'}) : false;
    if (!confirmar) return;

    try {
      const respuesta = await fetch('api/usuario_gestion_eliminar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({id_usuario: Number(usuario.id_usuario)})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo eliminar el usuario.');
      await cargarUsuarios(true);
    } catch (error) {
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar(error.message, 'error'); else console.error(error.message);
    }
  }

  if (cuerpoUsuarios) {
    cuerpoUsuarios.addEventListener('click', function (evento) {
      const boton = evento.target.closest('[data-accion-usuario]');
      if (!boton) return;
      const id = Number(boton.dataset.idUsuario);
      const usuario = usuarios.find(function (item) { return Number(item.id_usuario) === id; });
      if (!usuario) return;
      if (boton.dataset.accionUsuario === 'editar') abrirModal(usuario);
      if (boton.dataset.accionUsuario === 'eliminar') eliminarUsuario(usuario);
    });
  }

  if (formulario) {
    formulario.addEventListener('submit', async function (evento) {
      evento.preventDefault();
      const seleccionados = Array.from(rolesContenedor.querySelectorAll('input[type="checkbox"]:checked')).map(function (input) { return input.value; });
      if (!seleccionados.length) {
        mensaje.textContent = 'Selecciona al menos un rol.';
        mensaje.className = 'mensaje-gestion-usuario error';
        return;
      }

      const botonGuardar = formulario.querySelector('[type="submit"]');
      botonGuardar.disabled = true;
      mensaje.textContent = 'Guardando cambios...';
      mensaje.className = 'mensaje-gestion-usuario';

      try {
        const respuesta = await fetch('api/usuario_gestion_actualizar.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
          body: JSON.stringify({
            id_usuario: Number(campoId.value),
            estado: campoEstado.value,
            roles: seleccionados
          })
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo actualizar el usuario.');
        mensaje.textContent = resultado.mensaje;
        mensaje.className = 'mensaje-gestion-usuario exito';
        await cargarUsuarios(true);
        window.setTimeout(cerrarModal, 500);
      } catch (error) {
        mensaje.textContent = error.message;
        mensaje.className = 'mensaje-gestion-usuario error';
      } finally {
        botonGuardar.disabled = false;
      }
    });
  }

  if (modal) {
    modal.querySelectorAll('[data-cerrar-gestion-usuario]').forEach(function (control) { control.addEventListener('click', cerrarModal); });
    modal.addEventListener('click', function (evento) { if (evento.target === modal) cerrarModal(); });
    document.addEventListener('keydown', function (evento) { if (evento.key === 'Escape' && !modal.hidden) cerrarModal(); });
  }

  if (actualizarUsuarios) actualizarUsuarios.addEventListener('click', function () { cargarUsuarios(true); });

  pestanas.forEach(function (pestana) {
    pestana.addEventListener('click', function () {
      const destino = pestana.dataset.pestanaUsuarios;
      pestanas.forEach(function (item) { item.classList.toggle('activa', item === pestana); });
      paneles.forEach(function (panel) { panel.hidden = panel.dataset.panelUsuarios !== destino; });
      if (destino === 'activos') cargarUsuarios(true);
    });
  });

  function iniciarSincronizacion() {
    if (!cuerpoUsuarios) return;
    cargarUsuarios(true);
    if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('configuracion-usuarios', function () {
      const panelActivo = document.querySelector('[data-panel-usuarios="activos"]');
      if (panelActivo && !panelActivo.hidden && (!modal || modal.hidden)) return cargarUsuarios(false);
    }, 10000, {ejecutarAhora:false});
  }

  iniciarSincronizacion();
}());

(function () {
  'use strict';

  const cuerpo = document.getElementById('cuerpoSolicitudesAcceso');
  if (!cuerpo) return;
  const pestana = document.querySelector('[data-pestana-usuarios="solicitudes"]');
  const token = cuerpo.dataset.csrfToken || '';
  let firma = null;

  function escapar(valor) {
    return String(valor == null ? '' : valor).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function formatearFecha(valor) {
    if (!valor) return '-';
    const fecha = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(fecha.getTime())) return valor;
    return new Intl.DateTimeFormat('es-UY', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}).format(fecha);
  }

  function render(solicitudes) {
    if (pestana) {
      const contador = pestana.querySelector('span');
      if (contador) contador.textContent = String(solicitudes.length);
    }
    if (!solicitudes.length) {
      cuerpo.innerHTML = '<tr><td colspan="6"><p class="sin-solicitudes">No hay solicitudes pendientes.</p></td></tr>';
      return;
    }
    cuerpo.innerHTML = solicitudes.map(function (usuario) {
      return '<tr>' +
        '<td><strong>' + escapar(usuario.nombre) + '</strong></td>' +
        '<td>@' + escapar(usuario.nombre_usuario) + '</td>' +
        '<td>' + escapar(usuario.correo) + '</td>' +
        '<td>' + escapar(formatearFecha(usuario.fecha_registro)) + '</td>' +
        '<td><span class="estado-solicitud estado-pendiente">Pendiente</span></td>' +
        '<td><form method="post" class="acciones-tabla-solicitudes">' +
        '<input type="hidden" name="csrf_token" value="' + escapar(token) + '">' +
        '<input type="hidden" name="id_usuario" value="' + Number(usuario.id) + '">' +
        '<button type="submit" name="accion" value="aprobar">Aprobar</button>' +
        '<button type="submit" name="accion" value="rechazar">Rechazar</button>' +
        '</form></td></tr>';
    }).join('');
  }

  async function sincronizar() {
    try {
      const respuesta = await fetch('api/notificaciones.php?_=' + Date.now(), {cache: 'no-store'});
      if (!respuesta.ok) return;
      const resultado = await respuesta.json();
      if (!resultado.exito || !Array.isArray(resultado.solicitudes)) return;
      const nuevaFirma = JSON.stringify(resultado.solicitudes.map(function (u) { return [u.id, u.nombre, u.nombre_usuario, u.correo, u.fecha_registro]; }));
      if (firma !== nuevaFirma) {
        firma = nuevaFirma;
        render(resultado.solicitudes);
      }
    } catch (error) {
    }
  }

  sincronizar();
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('configuracion-solicitudes', sincronizar, 10000, {ejecutarAhora:false});
}());


(function(){'use strict';const raiz=document.getElementById('estadoTecnicoTiempoReal');if(!raiz)return;const ids={sesion:'tecnicoSesion',csrf:'tecnicoCsrf',hash:'tecnicoHash',mysql:'tecnicoMysql',bd:'tecnicoBaseDatos',enc:'tecnicoEnfrentamientos',php:'tecnicoPhp',servidor:'tecnicoServidor',hora:'tecnicoHora',estado:'tecnicoEstado'};function set(k,v){const e=document.getElementById(ids[k]);if(e)e.textContent=v}async function cargar(){set('estado','Actualizando...');try{const r=await fetch('api/estado_sistema.php?_='+Date.now(),{cache:'no-store'}),d=await r.json();if(!r.ok||!d.exito)throw new Error(d.mensaje||'Sin respuesta');set('sesion',d.sesion);set('csrf',d.csrf?'Activo':'Inactivo');set('hash',d.hash+' · contraseñas protegidas');set('mysql',d.mysql);set('bd',d.usuario_bd+' · '+d.tablas+' tablas');set('enc',String(d.enfrentamientos));set('php',d.php);set('servidor',d.servidor);set('hora','Última comprobación: '+d.hora);set('estado','Operativo')}catch(e){set('estado','No disponible');set('hora',e.message||'No se pudo consultar el servidor')}}cargar();if(window.ArenaCJDSync)window.ArenaCJDSync.registrar('configuracion-estado-tecnico',cargar,5000,{ejecutarAhora:false});}());

(function () {
  'use strict';

  const contenedor = document.getElementById('historialUsuariosConfiguracion');
  const estado = document.getElementById('estadoHistorialUsuarios');
  const actualizar = document.getElementById('actualizarHistorialUsuarios');
  const pestana = document.querySelector('[data-pestana-usuarios="historial"]');
  const filtros = document.getElementById('filtrosHistorialConfiguracion');
  if (!contenedor) return;

  const ordenCategorias = ['sesiones', 'amenazas', 'torneos', 'usuarios', 'participacion', 'competencia', 'catalogos', 'otros'];
  const etiquetasCategorias = {
    sesiones: 'Inicios de sesión',
    amenazas: 'Alertas de seguridad',
    torneos: 'Torneos',
    usuarios: 'Usuarios y accesos',
    participacion: 'Participación',
    competencia: 'Competencia',
    catalogos: 'Catálogos',
    otros: 'Otros eventos'
  };
  const iconosCategorias = {
    sesiones: window.ArenaCJDIcono('derecha'),
    amenazas: window.ArenaCJDIcono('alerta'),
    torneos: window.ArenaCJDIcono('torneo'),
    usuarios: window.ArenaCJDIcono('usuario'),
    participacion: window.ArenaCJDIcono('participantes'),
    competencia: window.ArenaCJDIcono('enfrentamientos'),
    catalogos: window.ArenaCJDIcono('panel'),
    otros: window.ArenaCJDIcono('actividad')
  };

  let cargando = false;
  let firma = '';
  let eventosActuales = [];
  let categoriaActiva = 'todas';

  function escapar(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function fecha(valor) {
    if (!valor) return '-';
    const d = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return valor;
    return new Intl.DateTimeFormat('es-UY', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit', second: '2-digit'
    }).format(d);
  }

  function iconoEvento(evento) {
    if (evento && evento.categoria && iconosCategorias[evento.categoria]) return iconosCategorias[evento.categoria];
    return ({usuario: window.ArenaCJDIcono('usuario'), torneo: window.ArenaCJDIcono('torneo'), enfrentamiento: window.ArenaCJDIcono('enfrentamientos'), equipo: window.ArenaCJDIcono('participantes'), invitacion: window.ArenaCJDIcono('mensaje')})[evento && evento.tipo] || window.ArenaCJDIcono('actividad');
  }

  function itemEvento(evento) {
    const claseAlerta = evento.nivel === 'alerta' ? ' item-historial-alerta' : '';
    return '<article class="item-historial-vivo' + claseAlerta + '">' +
      '<span class="icono-historial-vivo">' + iconoEvento(evento) + '</span>' +
      '<div><strong>' + escapar(evento.titulo) + '</strong><p>' + escapar(evento.detalle) + '</p></div>' +
      '<time>' + escapar(fecha(evento.fecha)) + '</time></article>';
  }

  function actualizarContadores(eventos) {
    const resumen = {todas: eventos.length};
    eventos.forEach(function (evento) {
      const categoria = evento.categoria || 'otros';
      resumen[categoria] = (resumen[categoria] || 0) + 1;
    });
    document.querySelectorAll('[data-contador-historial]').forEach(function (contador) {
      const categoria = contador.dataset.contadorHistorial;
      contador.textContent = String(resumen[categoria] || 0);
    });
  }

  function render(eventos) {
    actualizarContadores(eventos);
    if (!eventos.length) {
      contenedor.innerHTML = '<p class="sin-solicitudes">Todavía no hay actividad registrada.</p>';
      return;
    }

    if (categoriaActiva !== 'todas') {
      const filtrados = eventos.filter(function (evento) { return (evento.categoria || 'otros') === categoriaActiva; });
      if (!filtrados.length) {
        contenedor.innerHTML = '<p class="sin-solicitudes">No hay eventos registrados en “' + escapar(etiquetasCategorias[categoriaActiva] || categoriaActiva) + '”.</p>';
        return;
      }
      contenedor.innerHTML = '<section class="grupo-historial-configuracion grupo-historial-' + escapar(categoriaActiva) + '"><header><div><span class="icono-categoria-historial">' + (iconosCategorias[categoriaActiva] || window.ArenaCJDIcono('actividad')) + '</span><h3>' + escapar(etiquetasCategorias[categoriaActiva] || categoriaActiva) + '</h3></div><strong>' + filtrados.length + '</strong></header><div class="lista-historial-vivo">' + filtrados.map(itemEvento).join('') + '</div></section>';
      return;
    }

    const secciones = ordenCategorias.map(function (categoria) {
      const lista = eventos.filter(function (evento) { return (evento.categoria || 'otros') === categoria; });
      if (!lista.length) return '';
      return '<section class="grupo-historial-configuracion grupo-historial-' + escapar(categoria) + '">' +
        '<header><div><span class="icono-categoria-historial">' + (iconosCategorias[categoria] || window.ArenaCJDIcono('actividad')) + '</span><h3>' + escapar(etiquetasCategorias[categoria] || categoria) + '</h3></div><strong>' + lista.length + '</strong></header>' +
        '<div class="lista-historial-vivo">' + lista.map(itemEvento).join('') + '</div></section>';
    }).join('');
    contenedor.innerHTML = secciones || '<p class="sin-solicitudes">Todavía no hay actividad registrada.</p>';
  }

  function seleccionarCategoria(categoria) {
    categoriaActiva = categoria || 'todas';
    if (filtros) {
      filtros.querySelectorAll('[data-categoria-historial]').forEach(function (boton) {
        const activo = boton.dataset.categoriaHistorial === categoriaActiva;
        boton.classList.toggle('activo', activo);
        boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
      });
    }
    render(eventosActuales);
  }

  async function cargar(forzar) {
    if (cargando) return;
    cargando = true;
    if (actualizar) actualizar.disabled = true;
    if (estado && forzar) estado.textContent = 'Actualizando...';
    try {
      const respuesta = await fetch('api/historial.php?_=' + Date.now(), {cache: 'no-store'});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo cargar el historial.');
      const eventos = Array.isArray(resultado.eventos) ? resultado.eventos : [];
      const nuevaFirma = JSON.stringify(eventos);
      eventosActuales = eventos;
      if (forzar || firma !== nuevaFirma) {
        firma = nuevaFirma;
        render(eventosActuales);
      } else {
        actualizarContadores(eventosActuales);
      }
      if (estado) estado.textContent = 'Sincronizado · ' + new Date().toLocaleTimeString('es-UY');
    } catch (error) {
      if (estado) estado.textContent = error.message;
      if (!firma) {
        if (window.ArenaCJDEstadoError) window.ArenaCJDEstadoError(contenedor, error.message, function () { return cargar(true); });
        else contenedor.innerHTML = '<p class="sin-solicitudes">' + escapar(error.message) + '</p>';
      }
    } finally {
      cargando = false;
      if (actualizar) actualizar.disabled = false;
    }
  }

  if (filtros) {
    filtros.addEventListener('click', function (evento) {
      const boton = evento.target.closest('[data-categoria-historial]');
      if (!boton) return;
      seleccionarCategoria(boton.dataset.categoriaHistorial || 'todas');
    });
  }
  if (actualizar) actualizar.addEventListener('click', function () { cargar(true); });
  if (pestana) pestana.addEventListener('click', function () { cargar(true); });
  if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('configuracion-historial', function () {
    const panel = document.querySelector('[data-panel-usuarios="historial"]');
    if (panel && !panel.hidden) return cargar(false);
  }, 10000, {ejecutarAhora:false});
}());
