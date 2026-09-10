(function () {
  'use strict';

  let csrfSesion = '';
  let nombreUsuarioOriginal = '';
  let nombreUsuarioVerificado = '';
  let idUsuarioNotificaciones = 0;
  let ultimoResultadoNotificaciones = null;

  const enlacesMenu = [
    ['panel.php', 'panel', 'Panel', 'todos', 'Resumen'],
    ['torneos.php', 'torneo', 'Torneos', 'todos', 'Competencia'],
    ['participantes.php', 'participantes', 'Participantes', 'todos', 'Competencia'],
    ['sorteos.php', 'sorteo', 'Sorteos', 'gestion', 'Competencia'],
    ['partidos.php', 'enfrentamientos', 'Enfrentamientos', 'todos', 'Seguimiento'],
    ['resultados.php', 'resultados', 'Resultados', 'todos', 'Seguimiento'],
    ['clasificacion.php', 'clasificacion', 'Clasificación', 'todos', 'Seguimiento'],
    ['mi-actividad.php', 'actividad', 'Mi actividad', 'todos', 'Seguimiento'],
    ['calendario.php', 'calendario', 'Calendario', 'todos', 'Seguimiento'],
    ['disciplinas.php', 'torneo', 'Disciplinas', 'admin', 'Administración'],
    ['configuracion.php', 'configuracion', 'Configuración', 'admin', 'Administración']
  ];

  function escaparHtml(valor) {
    const elemento = document.createElement('div');
    elemento.textContent = String(valor ?? '');
    return elemento.innerHTML;
  }

  function inicialesAvatar(nombre) {
    return String(nombre || '?').trim().split(/\s+/).slice(0, 2).map(function (parte) {
      return parte.charAt(0);
    }).join('').toUpperCase() || '?';
  }

  window.ArenaCJDAvatar = {
    html: function (idUsuario, nombre, claseExtra) {
      const id = Number(idUsuario || 0);
      const nombreSeguro = escaparHtml(nombre || 'Usuario');
      const iniciales = escaparHtml(inicialesAvatar(nombre));
      const clases = 'avatar-perfil-dinamico' + (claseExtra ? ' ' + escaparHtml(claseExtra) : '');
      if (!id) {
        return '<span class="' + clases + ' sin-foto"><span class="avatar-perfil-iniciales">' + iniciales + '</span></span>';
      }
      return '<span class="' + clases + '"><span class="avatar-perfil-iniciales">' + iniciales + '</span><img src="api/foto_perfil.php?id_usuario=' + encodeURIComponent(id) + '" alt="Foto de perfil de ' + nombreSeguro + '" loading="lazy"></span>';
    },
    activar: function (raiz) {
      const contenedor = raiz && raiz.querySelectorAll ? raiz : document;
      contenedor.querySelectorAll('.avatar-perfil-dinamico img:not([data-avatar-listo])').forEach(function (imagen) {
        imagen.dataset.avatarListo = '1';
        const avatar = imagen.closest('.avatar-perfil-dinamico');
        function listo() { if (avatar) avatar.classList.add('con-foto'); }
        function error() { if (avatar) avatar.classList.remove('con-foto'); imagen.hidden = true; }
        imagen.addEventListener('load', listo, {once: true});
        imagen.addEventListener('error', error, {once: true});
        if (imagen.complete) {
          if (imagen.naturalWidth > 0) listo(); else error();
        }
      });
    }
  };

  function obtenerSesionInicial() {
    const cuerpo = document.body;
    if (!cuerpo || !cuerpo.dataset.sesionArenaCjd) return null;

    try {
      const sesion = JSON.parse(cuerpo.dataset.sesionArenaCjd);
      return sesion && sesion.usuario ? sesion : null;
    } catch (error) {
      console.error('No se pudieron leer los datos iniciales de la sesión.');
      return null;
    }
  }

  function crearEnlacesMenu(sesionInicial) {
    const rolesIniciales = sesionInicial && sesionInicial.usuario && Array.isArray(sesionInicial.usuario.roles)
      ? sesionInicial.usuario.roles
      : [];
    const esAdministradorInicial = rolesIniciales.includes('administrador');
    const puedeGestionarInicial = esAdministradorInicial || rolesIniciales.includes('organizador');
    const visibles = enlacesMenu.filter(function (enlace) {
      if (enlace[3] === 'admin') return esAdministradorInicial;
      if (enlace[3] === 'gestion') return puedeGestionarInicial;
      return true;
    });
    let grupoAnterior = '';

    return visibles.map(function (enlace) {
      const grupo = enlace[4] || '';
      const cabeceraGrupo = grupo !== grupoAnterior
        ? '<li class="grupo-menu-etiqueta" aria-hidden="true">' + escaparHtml(grupo) + '</li>'
        : '';
      grupoAnterior = grupo;
      const badgeActividad = enlace[0] === 'mi-actividad.php'
        ? '<span class="contador-menu-actividad" id="contadorMenuMiActividad" hidden>0</span>'
        : '';

      return cabeceraGrupo + '<li>' +
        '<a href="' + enlace[0] + '" class="enlace-menu">' +
        '<span class="icono-menu icono-imagen-ui">' + window.ArenaCJDIcono(enlace[1]) + '</span>' +
        '<span>' + enlace[2] + '</span>' + badgeActividad +
        '</a>' +
        '</li>';
    }).join('');
  }

  function crearMarcaArenaCJD() {
    return [
      '<div class="marca-menu marca-menu-arenacjd">',
      '  <div class="escudo-marca" aria-hidden="true">',
      '    <svg viewBox="0 0 64 72" width="54" height="60" fill="none">',
      '      <path d="M32 4 57 13v19c0 17-10 29-25 36C17 61 7 49 7 32V13L32 4Z" stroke="currentColor" stroke-width="4"/>',
      '      <path d="M23 20h18v8c0 7-4 12-9 12s-9-5-9-12v-8Z" fill="currentColor"/>',
      '      <path d="M20 22h-5v4c0 5 4 8 8 8M44 22h5v4c0 5-4 8-8 8M32 40v8M25 50h14" style="stroke:var(--blanco)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>',
      '    </svg>',
      '  </div>',
      '  <div class="texto-marca">',
      '    <span class="titulo-menu">ArenaCJD</span>',
      '    <span class="subtitulo-menu">Sistema de Gestión<br>Deportiva Modular</span>',
      '  </div>',
      '</div>'
    ].join('');
  }

  function crearTarjetaUsuario(sesionInicial) {
    const usuarioInicial = sesionInicial && sesionInicial.usuario ? sesionInicial.usuario : null;
    const nombreInicial = usuarioInicial ? escaparHtml(usuarioInicial.nombre || '') : '';
    const aliasInicial = usuarioInicial ? '@' + escaparHtml(usuarioInicial.nombre_usuario || '') : '';
    const fotoInicial = usuarioInicial && usuarioInicial.tiene_foto_perfil && usuarioInicial.foto_perfil_url
      ? escaparHtml(usuarioInicial.foto_perfil_url)
      : 'imagenes/avatar-usuario-predeterminado.png';
    const atributoOcultoUsuario = usuarioInicial ? '' : ' hidden';

    return [
      '<div class="zona-usuario-menu" id="zonaUsuarioMenu"' + atributoOcultoUsuario + '>',
      '  <button class="boton-usuario-menu" id="botonUsuarioMenu" type="button" aria-expanded="false" aria-controls="menuOpcionesUsuario">',
      '    <span class="avatar-usuario-menu" aria-hidden="true">',
      '      <img class="foto-usuario-menu" id="fotoUsuarioMenu" src="' + fotoInicial + '" alt="">',
      '      <span class="estado-usuario-conectado"></span>',
      '    </span>',
      '    <span class="datos-usuario-menu">',
      '      <span class="nombre-usuario-menu" id="nombreUsuarioMenu">' + nombreInicial + '</span>',
      '      <span class="alias-usuario-menu" id="aliasUsuarioMenu">' + aliasInicial + '</span>',
      '    </span>',
      '    <span class="flecha-usuario-menu" aria-hidden="true">' + window.ArenaCJDIcono('abajo') + '</span>',
      '  </button>',
      '  <div class="menu-opciones-usuario" id="menuOpcionesUsuario" hidden>',
      '    <button class="opcion-usuario" id="abrirPerfilUsuario" type="button">' + window.ArenaCJDIcono('usuario') + '<span>Perfil</span></button>',
      '    <a class="opcion-usuario opcion-cerrar-sesion" href="api/logout.php">' + window.ArenaCJDIcono('salir') + '<span>Cerrar sesión</span></a>',
      '  </div>',
      '</div>',
      '<div class="fondo-modal-perfil" id="fondoModalPerfil" hidden>',
      '  <section class="modal-perfil-usuario" role="dialog" aria-modal="true" aria-labelledby="tituloModalPerfil">',
      '    <div class="cabecera-modal-perfil"><div><h2 id="tituloModalPerfil">Perfil del usuario</h2><p>Información de la cuenta autenticada</p></div><button class="cerrar-modal-perfil" id="cerrarModalPerfil" type="button" aria-label="Cerrar perfil" title="Cerrar">' + window.ArenaCJDIcono('peligro') + '</button></div>',
      '    <div class="resumen-perfil-modal"><span class="avatar-perfil-modal" aria-hidden="true"><img class="foto-perfil-modal" id="fotoPerfilModal" src="' + fotoInicial + '" alt=""></span><div><strong id="nombrePerfilUsuario">' + nombreInicial + '</strong><span id="aliasPerfilUsuario">' + aliasInicial + '</span></div></div>',
      '    <div class="bloque-foto-perfil">',
      '      <div><strong>Foto de perfil</strong><p>JPG, PNG o WebP · máximo 3 MB.</p></div>',
      '      <input id="inputFotoPerfil" type="file" accept="image/jpeg,image/png,image/webp" hidden>',
      '      <div class="acciones-foto-perfil"><button class="boton boton-principal" id="botonCambiarFotoPerfil" type="button">Subir foto</button><button class="boton boton-secundario" id="botonEliminarFotoPerfil" type="button" hidden>Quitar foto</button></div>',
      '      <p class="mensaje-foto-perfil" id="mensajeFotoPerfil" aria-live="polite"></p>',
      '    </div>',
      '    <div class="bloque-usuario-perfil">',
      '      <div><strong>Nombre de usuario</strong><p>El símbolo @ se mantiene fijo. Verifica la disponibilidad antes de guardar.</p></div>',
      '      <label class="campo-nombre-usuario-perfil" for="inputNombreUsuarioPerfil"><span aria-hidden="true">@</span><input id="inputNombreUsuarioPerfil" aria-label="Nombre de usuario" type="text" minlength="4" maxlength="24" autocomplete="username" spellcheck="false" aria-describedby="mensajeNombreUsuarioPerfil"></label>',
      '      <div class="acciones-nombre-usuario-perfil"><button class="boton boton-secundario" id="botonVerificarUsuarioPerfil" type="button">Verificar disponibilidad</button><button class="boton boton-principal" id="botonGuardarUsuarioPerfil" type="button" disabled>Guardar cambio</button></div>',
      '      <p class="mensaje-nombre-usuario-perfil" id="mensajeNombreUsuarioPerfil" aria-live="polite"></p>',
      '    </div>',
      '    <div class="bloque-tema-perfil">',
      '      <div><strong>Apariencia</strong><p>Elige Claro, Oscuro o la preferencia del Sistema. La preferencia se aplica en toda ArenaCJD.</p></div>',
      '      <div class="opciones-tema-perfil" role="group" aria-label="Tema visual">',
      '        <button class="boton-tema-perfil" type="button" data-boton-tema="claro" aria-pressed="false"><span class="icono-tema-perfil" aria-hidden="true">' + window.ArenaCJDIcono('sol') + '</span><span><strong>Claro</strong><small>Fondo luminoso</small></span></button>',
      '        <button class="boton-tema-perfil" type="button" data-boton-tema="oscuro" aria-pressed="false"><span class="icono-tema-perfil" aria-hidden="true">' + window.ArenaCJDIcono('luna') + '</span><span><strong>Oscuro</strong><small>Contraste suave</small></span></button>',
      '        <button class="boton-tema-perfil" type="button" data-boton-tema="sistema" aria-pressed="false"><span class="icono-tema-perfil" aria-hidden="true">' + window.ArenaCJDIcono('sistema') + '</span><span><strong>Sistema</strong><small>Según el dispositivo</small></span></button>',
      '      </div>',
      '    </div>',
      '    <div class="bloque-seguridad-perfil">',
      '      <div><strong>Seguridad de la cuenta</strong><p>Cambia tu contraseña o actualiza la pregunta de recuperación.</p></div>',
      '      <details class="detalle-seguridad-perfil"><summary>Cambiar contraseña</summary><form id="formCambiarContrasenaPerfil" class="form-seguridad-perfil"><input class="campo-formulario" id="contrasenaActualPerfil" aria-label="Contraseña actual" type="password" placeholder="Contraseña actual" autocomplete="current-password" required><input class="campo-formulario" id="contrasenaNuevaPerfil" aria-label="Nueva contraseña" type="password" placeholder="Nueva contraseña" autocomplete="new-password" required><input class="campo-formulario" id="contrasenaConfirmarPerfil" aria-label="Confirmar nueva contraseña" type="password" placeholder="Confirmar nueva contraseña" autocomplete="new-password" required><button class="boton boton-principal" type="submit">Guardar contraseña</button><p id="mensajeContrasenaPerfil" class="mensaje-seguridad-perfil"></p></form></details>',
      '      <details class="detalle-seguridad-perfil"><summary>Pregunta de recuperación</summary><form id="formRecuperacionPerfil" class="form-seguridad-perfil"><input class="campo-formulario" id="recuperacionActualPerfil" aria-label="Contraseña actual para recuperación" type="password" placeholder="Contraseña actual" required><select class="selector-formulario" id="preguntaPerfil" aria-label="Pregunta de recuperación" required><option value="">Seleccionar pregunta</option><option>¿Cuál era el nombre de tu primera mascota?</option><option>¿Cuál es tu comida favorita?</option><option>¿Cuál fue tu primer videojuego?</option><option>¿Cuál es el apodo de un amigo de la infancia?</option><option>¿Cuál es tu película favorita?</option></select><input class="campo-formulario" id="respuestaPerfil" aria-label="Respuesta de recuperación" type="text" maxlength="100" placeholder="Respuesta" autocomplete="off" required><button class="boton boton-secundario" type="submit">Guardar recuperación</button><p id="mensajeRecuperacionPerfil" class="mensaje-seguridad-perfil"></p></form></details>',
      '    </div>',
      '    <dl class="datos-perfil-modal"><div><dt>Rol</dt><dd id="rolPerfilUsuario"></dd></div><div><dt>Correo</dt><dd id="correoPerfilUsuario"></dd></div><div><dt>Estado</dt><dd><span class="estado-cuenta-activa" id="estadoPerfilUsuario"></span></dd></div></dl>',
      '  </section>',
      '</div>'
    ].join('');
  }

  function crearMenuLateral(sesionInicial) {
    return [
      crearMarcaArenaCJD(),
      '<nav class="navegacion-menu" aria-label="Menú principal"><ul>',
      crearEnlacesMenu(sesionInicial),
      '</ul></nav>',
      crearTarjetaUsuario(sesionInicial)
    ].join('');
  }

  function renderizarMenuLateral(sesionInicial) {
    document.querySelectorAll('[data-componente="menu-lateral"]').forEach(function (menu) {
      menu.innerHTML = crearMenuLateral(sesionInicial);
      menu.dataset.menuListo = 'true';
      const modalPerfil = menu.querySelector('#fondoModalPerfil');

      if (modalPerfil && !document.body.querySelector(':scope > #fondoModalPerfil')) {
        document.body.appendChild(modalPerfil);
      }
    });
  }

  function configurarMenuUsuario() {
    const boton = document.getElementById('botonUsuarioMenu');
    const menu = document.getElementById('menuOpcionesUsuario');
    const abrirPerfil = document.getElementById('abrirPerfilUsuario');
    const modalPerfil = document.getElementById('fondoModalPerfil');
    const cerrarPerfil = document.getElementById('cerrarModalPerfil');
    let origenPerfil = null;
    let fondoInerte = [];

    if (!boton || !menu || boton.dataset.configurado === 'true') return;
    boton.dataset.configurado = 'true';

    function cerrarMenuUsuario() {
      menu.hidden = true;
      boton.setAttribute('aria-expanded', 'false');
    }

    function abrirMenuUsuario() {
      menu.hidden = false;
      boton.setAttribute('aria-expanded', 'true');
    }

    function cerrarModalPerfil() {
      if (!modalPerfil || modalPerfil.hidden) return;
      modalPerfil.hidden = true;
      fondoInerte.forEach(function (elemento) { elemento.inert = false; });
      fondoInerte = [];
      document.body.classList.toggle('sin-scroll', window.innerWidth < 992 && Boolean(document.querySelector('.menu-lateral.abierto')));
      const retorno = origenPerfil && origenPerfil.isConnected && !origenPerfil.closest('[inert]') ? origenPerfil : (boton.closest('[inert]') ? document.getElementById('botonMenu') : boton);
      if (retorno) retorno.focus();
    }

    boton.addEventListener('click', function (evento) {
      evento.stopPropagation();
      if (menu.hidden) abrirMenuUsuario();
      else cerrarMenuUsuario();
    });

    menu.addEventListener('click', function (evento) {
      evento.stopPropagation();
    });

    if (abrirPerfil && modalPerfil) {
      abrirPerfil.addEventListener('click', function () {
        origenPerfil = document.activeElement === abrirPerfil ? boton : document.activeElement;
        cerrarMenuUsuario();
        modalPerfil.hidden = false;
        fondoInerte = Array.from(document.body.children).filter(function (elemento) { return elemento !== modalPerfil && !elemento.inert && !['SCRIPT','STYLE','LINK'].includes(elemento.tagName); });
        fondoInerte.forEach(function (elemento) { elemento.inert = true; });
        document.body.classList.add('sin-scroll');
        const cerrar = document.getElementById('cerrarModalPerfil');
        if (cerrar) cerrar.focus();
      });
    }

    if (cerrarPerfil) {
      cerrarPerfil.addEventListener('click', cerrarModalPerfil);
    }

    if (modalPerfil) {
      modalPerfil.addEventListener('click', function (evento) {
        if (evento.target === modalPerfil) cerrarModalPerfil();
      });
    }

    document.addEventListener('click', function (evento) {
      if (!menu.hidden && !boton.contains(evento.target) && !menu.contains(evento.target)) {
        cerrarMenuUsuario();
      }
    });

    document.addEventListener('keydown', function (evento) {
      if (evento.key !== 'Escape') return;
      if (!menu.hidden || (modalPerfil && !modalPerfil.hidden)) {
        evento.preventDefault();
        evento.stopImmediatePropagation();
      }
      if (!menu.hidden) { cerrarMenuUsuario(); boton.focus(); }
      if (modalPerfil && !modalPerfil.hidden) cerrarModalPerfil();
    });
  }

  function mostrarFotoPerfil(tieneFoto, url) {
    const avatarEstandar = 'imagenes/avatar-usuario-predeterminado.png';
    const fotoMenu = document.getElementById('fotoUsuarioMenu');
    const fotoModal = document.getElementById('fotoPerfilModal');
    const fotoConfiguracion = document.getElementById('fotoPerfilConfiguracion');
    const botonCambiar = document.getElementById('botonCambiarFotoPerfil');
    const botonEliminar = document.getElementById('botonEliminarFotoPerfil');
    const fuente = tieneFoto && url ? url : avatarEstandar;

    [fotoMenu, fotoModal, fotoConfiguracion].forEach(function (imagen) {
      if (!imagen) return;

      imagen.onerror = function () {
        if (!this.src.endsWith('/imagenes/avatar-usuario-predeterminado.png') &&
            !this.src.endsWith('imagenes/avatar-usuario-predeterminado.png')) {
          this.src = avatarEstandar;
        }
      };

      imagen.src = fuente;
      imagen.hidden = false;
    });

    if (botonCambiar) {
      botonCambiar.textContent = tieneFoto ? 'Cambiar foto' : 'Subir foto';
    }

    if (botonEliminar) {
      botonEliminar.hidden = !tieneFoto;
    }
  }

  function mostrarMensajeFotoPerfil(texto, tipo) {
    const mensaje = document.getElementById('mensajeFotoPerfil');
    if (!mensaje) return;

    mensaje.textContent = texto || '';
    mensaje.className = 'mensaje-foto-perfil' + (tipo ? ' mensaje-foto-' + tipo : '');
  }

  function configurarFotoPerfil() {
    const input = document.getElementById('inputFotoPerfil');
    const botonCambiar = document.getElementById('botonCambiarFotoPerfil');
    const botonEliminar = document.getElementById('botonEliminarFotoPerfil');

    if (!input || !botonCambiar || !botonEliminar || botonCambiar.dataset.configurado === 'true') {
      return;
    }

    botonCambiar.dataset.configurado = 'true';

    botonCambiar.addEventListener('click', function () {
      input.value = '';
      input.click();
    });

    input.addEventListener('change', async function () {
      const archivo = input.files && input.files[0];
      if (!archivo) return;

      const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

      if (!tiposPermitidos.includes(archivo.type)) {
        mostrarMensajeFotoPerfil('Solo se permiten imágenes JPG, PNG o WebP.', 'error');
        return;
      }

      if (archivo.size > 3 * 1024 * 1024) {
        mostrarMensajeFotoPerfil('La imagen debe pesar como máximo 3 MB.', 'error');
        return;
      }

      const datos = new FormData();
      datos.append('foto', archivo);
      botonCambiar.disabled = true;
      botonEliminar.disabled = true;
      mostrarMensajeFotoPerfil('Subiendo foto...', 'info');

      try {
        const respuesta = await fetch('api/subir_foto_perfil.php', {
          method: 'POST',
          headers: {
            'X-CSRF-Token': csrfSesion
          },
          body: datos
        });

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
          mostrarMensajeFotoPerfil(resultado.mensaje || 'No se pudo subir la foto.', 'error');
          return;
        }

        mostrarFotoPerfil(true, resultado.foto_perfil_url);
        mostrarMensajeFotoPerfil(resultado.mensaje || 'Foto de perfil actualizada.', 'exito');
      } catch (error) {
        mostrarMensajeFotoPerfil('No se pudo conectar con el servidor.', 'error');
      } finally {
        botonCambiar.disabled = false;
        botonEliminar.disabled = false;
      }
    });

    botonEliminar.addEventListener('click', async function () {
      botonCambiar.disabled = true;
      botonEliminar.disabled = true;
      mostrarMensajeFotoPerfil('Eliminando foto...', 'info');

      try {
        const respuesta = await fetch('api/eliminar_foto_perfil.php', {
          method: 'POST',
          headers: {
            'X-CSRF-Token': csrfSesion
          }
        });

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
          mostrarMensajeFotoPerfil(resultado.mensaje || 'No se pudo eliminar la foto.', 'error');
          return;
        }

        mostrarFotoPerfil(false, null);
        mostrarMensajeFotoPerfil(resultado.mensaje || 'Foto de perfil eliminada.', 'exito');
      } catch (error) {
        mostrarMensajeFotoPerfil('No se pudo conectar con el servidor.', 'error');
      } finally {
        botonCambiar.disabled = false;
        botonEliminar.disabled = false;
      }
    });
  }


  function normalizarNombreUsuario(valor) {
    return String(valor || '')
      .trim()
      .toLowerCase()
      .replace(/^@+/, '');
  }

  function nombreUsuarioTieneFormatoValido(valor) {
    return /^[a-z0-9._-]{4,24}$/.test(valor);
  }

  function mostrarMensajeNombreUsuario(texto, tipo) {
    const mensaje = document.getElementById('mensajeNombreUsuarioPerfil');
    if (!mensaje) return;

    mensaje.textContent = texto || '';
    mensaje.className = 'mensaje-nombre-usuario-perfil' +
      (tipo ? ' mensaje-usuario-' + tipo : '');
  }

  function actualizarAliasInterfaz(nombreUsuario) {
    const aliasMenu = document.getElementById('aliasUsuarioMenu');
    const aliasPerfil = document.getElementById('aliasPerfilUsuario');
    const aliasConfiguracion = document.getElementById('aliasAdministradorConfiguracion');

    if (aliasMenu) aliasMenu.textContent = '@' + nombreUsuario;
    if (aliasPerfil) aliasPerfil.textContent = '@' + nombreUsuario;
    if (aliasConfiguracion) aliasConfiguracion.textContent = '@' + nombreUsuario;
  }

  function configurarNombreUsuarioPerfil(nombreActual) {
    const input = document.getElementById('inputNombreUsuarioPerfil');
    const botonVerificar = document.getElementById('botonVerificarUsuarioPerfil');
    const botonGuardar = document.getElementById('botonGuardarUsuarioPerfil');

    if (!input || !botonVerificar || !botonGuardar) return;

    nombreUsuarioOriginal = normalizarNombreUsuario(nombreActual);
    nombreUsuarioVerificado = '';
    input.value = nombreUsuarioOriginal;
    botonGuardar.disabled = true;
    mostrarMensajeNombreUsuario('Este es tu nombre de usuario actual.', 'info');

    if (input.dataset.configurado === 'true') {
      return;
    }

    input.dataset.configurado = 'true';

    input.addEventListener('input', function () {
      const normalizado = normalizarNombreUsuario(input.value);

      if (input.value !== normalizado) {
        input.value = normalizado;
      }

      nombreUsuarioVerificado = '';
      botonGuardar.disabled = true;

      if (normalizado === nombreUsuarioOriginal) {
        mostrarMensajeNombreUsuario('Este es tu nombre de usuario actual.', 'info');
        return;
      }

      if (!nombreUsuarioTieneFormatoValido(normalizado)) {
        mostrarMensajeNombreUsuario(
          'Usa entre 4 y 24 caracteres: letras, números, punto, guion o guion bajo.',
          'error'
        );
        return;
      }

      mostrarMensajeNombreUsuario('Verifica si el nombre está disponible.', 'info');
    });

    botonVerificar.addEventListener('click', async function () {
      const nombreUsuario = normalizarNombreUsuario(input.value);
      input.value = nombreUsuario;
      nombreUsuarioVerificado = '';
      botonGuardar.disabled = true;

      if (!nombreUsuarioTieneFormatoValido(nombreUsuario)) {
        mostrarMensajeNombreUsuario(
          'Usa entre 4 y 24 caracteres: letras, números, punto, guion o guion bajo.',
          'error'
        );
        return;
      }

      botonVerificar.disabled = true;
      mostrarMensajeNombreUsuario('Verificando disponibilidad...', 'info');

      try {
        const respuesta = await fetch(
          'api/verificar_nombre_usuario.php?nombre_usuario=' + encodeURIComponent(nombreUsuario),
          { cache: 'no-store' }
        );
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
          mostrarMensajeNombreUsuario(
            resultado.mensaje || 'No se pudo verificar el nombre de usuario.',
            'error'
          );
          return;
        }

        if (resultado.actual) {
          mostrarMensajeNombreUsuario(resultado.mensaje, 'info');
          return;
        }

        if (!resultado.disponible) {
          mostrarMensajeNombreUsuario(resultado.mensaje, 'error');
          return;
        }

        nombreUsuarioVerificado = resultado.nombre_usuario;
        botonGuardar.disabled = false;
        mostrarMensajeNombreUsuario(resultado.mensaje, 'exito');
      } catch (error) {
        mostrarMensajeNombreUsuario('No se pudo conectar con el servidor.', 'error');
      } finally {
        botonVerificar.disabled = false;
      }
    });

    botonGuardar.addEventListener('click', async function () {
      const nombreUsuario = normalizarNombreUsuario(input.value);

      if (nombreUsuario === nombreUsuarioOriginal) {
        botonGuardar.disabled = true;
        mostrarMensajeNombreUsuario('Este es tu nombre de usuario actual.', 'info');
        return;
      }

      if (nombreUsuarioVerificado !== nombreUsuario) {
        botonGuardar.disabled = true;
        mostrarMensajeNombreUsuario('Primero verifica que el nombre esté disponible.', 'error');
        return;
      }

      botonGuardar.disabled = true;
      botonVerificar.disabled = true;
      input.disabled = true;
      mostrarMensajeNombreUsuario('Guardando cambio...', 'info');

      try {
        const respuesta = await fetch('api/actualizar_nombre_usuario.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfSesion
          },
          body: JSON.stringify({
            nombre_usuario: nombreUsuario
          })
        });
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
          nombreUsuarioVerificado = '';
          mostrarMensajeNombreUsuario(
            resultado.mensaje || 'No se pudo actualizar el nombre de usuario.',
            'error'
          );
          return;
        }

        nombreUsuarioOriginal = resultado.nombre_usuario;
        nombreUsuarioVerificado = '';
        input.value = nombreUsuarioOriginal;
        actualizarAliasInterfaz(nombreUsuarioOriginal);
        mostrarMensajeNombreUsuario(
          resultado.mensaje + ' La próxima vez inicia sesión con @' + nombreUsuarioOriginal + '.',
          'exito'
        );
      } catch (error) {
        nombreUsuarioVerificado = '';
        mostrarMensajeNombreUsuario('No se pudo conectar con el servidor.', 'error');
      } finally {
        input.disabled = false;
        botonVerificar.disabled = false;
        botonGuardar.disabled = true;
      }
    });
  }


  function configurarSeguridadPerfil() {
    const formContrasena = document.getElementById('formCambiarContrasenaPerfil');
    const formRecuperacion = document.getElementById('formRecuperacionPerfil');
    if (formContrasena && formContrasena.dataset.configurado !== 'true') {
      formContrasena.dataset.configurado = 'true';
      formContrasena.addEventListener('submit', async function (evento) {
        evento.preventDefault();
        const mensaje = document.getElementById('mensajeContrasenaPerfil');
        const boton = formContrasena.querySelector('[type="submit"]');
        boton.disabled = true; mensaje.textContent = 'Actualizando contraseña...'; mensaje.className = 'mensaje-seguridad-perfil';
        try {
          const respuesta = await fetch('api/cambiar_contrasena.php', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':csrfSesion}, body:JSON.stringify({actual:document.getElementById('contrasenaActualPerfil').value,nueva:document.getElementById('contrasenaNuevaPerfil').value,confirmar:document.getElementById('contrasenaConfirmarPerfil').value})});
          const resultado = await respuesta.json(); if(!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo cambiar la contraseña.');
          mensaje.textContent = resultado.mensaje; mensaje.className = 'mensaje-seguridad-perfil exito'; formContrasena.reset();
        } catch (error) { mensaje.textContent = error.message; mensaje.className = 'mensaje-seguridad-perfil error'; }
        finally { boton.disabled = false; }
      });
    }
    if (formRecuperacion && formRecuperacion.dataset.configurado !== 'true') {
      formRecuperacion.dataset.configurado = 'true';
      formRecuperacion.addEventListener('submit', async function (evento) {
        evento.preventDefault(); const mensaje=document.getElementById('mensajeRecuperacionPerfil'); const boton=formRecuperacion.querySelector('[type="submit"]'); boton.disabled=true; mensaje.textContent='Guardando recuperación...'; mensaje.className='mensaje-seguridad-perfil';
        try { const respuesta=await fetch('api/configurar_recuperacion.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfSesion},body:JSON.stringify({contrasena_actual:document.getElementById('recuperacionActualPerfil').value,pregunta:document.getElementById('preguntaPerfil').value,respuesta:document.getElementById('respuestaPerfil').value})}); const resultado=await respuesta.json(); if(!respuesta.ok||!resultado.exito)throw new Error(resultado.mensaje||'No se pudo guardar la recuperación.'); mensaje.textContent=resultado.mensaje; mensaje.className='mensaje-seguridad-perfil exito'; formRecuperacion.reset(); }
        catch(error){mensaje.textContent=error.message;mensaje.className='mensaje-seguridad-perfil error';}finally{boton.disabled=false;}
      });
    }
  }

  function crearPanelNotificaciones() {
    let panel = document.getElementById('panelNotificaciones');
    if (panel) return panel;

    panel = document.createElement('aside');
    panel.id = 'panelNotificaciones';
    panel.className = 'panel-notificaciones';
    panel.setAttribute('aria-label', 'Notificaciones de ArenaCJD');
    panel.setAttribute('aria-hidden', 'true');
    panel.innerHTML = [
      '<div class="cabecera-panel-notificaciones">',
      '  <div><strong>Notificaciones</strong><span>Invitaciones y actividad de tu cuenta</span></div>',
      '  <button id="cerrarPanelNotificaciones" type="button" aria-label="Cerrar notificaciones"><span data-icono="peligro" aria-hidden="true"></span></button>',
      '</div>',
      '<section id="seccionInvitacionesNotificaciones">',
      '  <div class="titulo-grupo-notificaciones"><strong>Invitaciones a torneos</strong><span id="cantidadInvitacionesPanel">0</span></div>',
      '  <div id="listaInvitacionesNotificaciones"><p class="sin-solicitudes">Cargando invitaciones...</p></div>',
      '  <a class="enlace-configuracion-notificaciones" href="participantes.php#mis-invitaciones">Ver Mis invitaciones</a>',
      '  <div class="titulo-grupo-notificaciones"><strong>Invitaciones a equipos</strong><span id="cantidadInvitacionesEquipoPanel">0</span></div>',
      '  <div id="listaInvitacionesEquipoNotificaciones"><p class="sin-solicitudes">Cargando invitaciones a equipos...</p></div>',
      '  <a class="enlace-configuracion-notificaciones" href="participantes.php#mis-invitaciones">Ver Mis invitaciones</a>',
      '  <div class="titulo-grupo-notificaciones"><strong>Torneos que requieren atención</strong><span id="cantidadAvisosTorneoPanel">0</span></div>',
      '  <div id="listaAvisosTorneoNotificaciones"><p class="sin-solicitudes">Comprobando próximos torneos...</p></div>',
      '  <div class="titulo-grupo-notificaciones"><strong>Mi actividad</strong><span id="cantidadActividadPanel">0</span></div>',
      '  <div id="listaActividadNotificaciones"><p class="sin-solicitudes">Comprobando tu actividad...</p></div>',
      '  <a class="enlace-configuracion-notificaciones" href="mi-actividad.php">Ver Mi actividad</a>',
      '</section>',
      '<section id="seccionSolicitudesNotificaciones" hidden>',
      '  <div class="titulo-grupo-notificaciones"><strong>Solicitudes pendientes</strong><span id="cantidadPanelPendientes">0</span></div>',
      '  <div id="listaNotificacionesPendientes"><p class="sin-solicitudes">Cargando solicitudes...</p></div>',
      '  <a class="enlace-configuracion-notificaciones" href="configuracion.php#usuarios-configuracion">Gestionar usuarios</a>',
      '</section>',
      '<section id="seccionUltimaGestion" hidden>',
      '  <div class="titulo-grupo-notificaciones"><strong>Última gestión</strong><span>Estado actualizado</span></div>',
      '  <div class="notificacion-gestion" id="ultimaGestionNotificaciones"></div>',
      '</section>'
    ].join('');

    document.body.appendChild(panel);
    const cerrar = panel.querySelector('#cerrarPanelNotificaciones');
    if (cerrar) cerrar.addEventListener('click', cerrarPanelNotificaciones);

    panel.addEventListener('click', function (evento) {
      const botonEquipo = evento.target.closest('[data-responder-invitacion-equipo]');
      if (botonEquipo) {
        responderInvitacionEquipoNotificacion(
          Number(botonEquipo.dataset.idInvitacionEquipo),
          botonEquipo.dataset.responderInvitacionEquipo
        );
        return;
      }
      const boton = evento.target.closest('[data-responder-invitacion]');
      if (!boton) return;
      responderInvitacionNotificacion(
        Number(boton.dataset.idInvitacion),
        boton.dataset.responderInvitacion
      );
    });

    return panel;
  }

  function abrirPanelNotificaciones() {
    const panel = crearPanelNotificaciones();
    const boton = document.getElementById('botonNotificaciones');
    panel.classList.add('panel-notificaciones-visible');
    panel.setAttribute('aria-hidden', 'false');
    if (boton) boton.setAttribute('aria-expanded', 'true');

    if (ultimoResultadoNotificaciones) {
      marcarNotificacionesComoLeidas(ultimoResultadoNotificaciones);
    } else {
      actualizarBadgeNotificaciones(0);
    }

    cargarNotificaciones(false).then(function (resultado) {
      if (resultado) marcarNotificacionesComoLeidas(resultado);
    });
  }

  function cerrarPanelNotificaciones() {
    const panel = document.getElementById('panelNotificaciones');
    const boton = document.getElementById('botonNotificaciones');
    if (panel) {
      panel.classList.remove('panel-notificaciones-visible');
      panel.setAttribute('aria-hidden', 'true');
    }
    if (boton) boton.setAttribute('aria-expanded', 'false');
  }

  function normalizarTipoToast(tipo) {
    const valor = String(tipo || 'info').toLowerCase();
    if (valor === 'exito' || valor === 'aprobado' || valor === 'success') return 'exito';
    if (valor === 'error' || valor === 'rechazo' || valor === 'danger') return 'error';
    if (valor === 'advertencia' || valor === 'pendiente' || valor === 'warning') return 'advertencia';
    return 'info';
  }

  function obtenerConfiguracionToast(tipo) {
    const configuraciones = {
      exito: {icono:window.ArenaCJDIcono('exito'), duracion:3800},
      error: {icono:window.ArenaCJDIcono('peligro'), duracion:8000},
      advertencia: {icono:window.ArenaCJDIcono('alerta'), duracion:6000},
      info: {icono:window.ArenaCJDIcono('info'), duracion:4200}
    };
    return configuraciones[tipo] || configuraciones.info;
  }

  function obtenerContenedorToasts() {
    let contenedor = document.getElementById('contenedorToastsArenaCJD');
    if (contenedor) return contenedor;

    contenedor = document.createElement('div');
    contenedor.id = 'contenedorToastsArenaCJD';
    contenedor.className = 'contenedor-toasts-ah';
    contenedor.setAttribute('aria-label', 'Notificaciones');
    document.body.appendChild(contenedor);
    return contenedor;
  }

  function cerrarToast(toast) {
    if (!toast || toast.dataset.cerrando === 'true') return;
    toast.dataset.cerrando = 'true';
    if (toast._temporizadorArenaCJD) window.clearTimeout(toast._temporizadorArenaCJD);
    toast.classList.add('toast-ah-saliendo');
    window.setTimeout(function () {
      if (toast.parentNode) toast.remove();
    }, 180);
  }

  function mostrarAvisoNotificacion(texto, tipo, opciones) {
    const mensaje = String(texto || '').trim();
    if (!mensaje) return null;

    const tipoNormalizado = normalizarTipoToast(tipo);
    const configuracion = obtenerConfiguracionToast(tipoNormalizado);
    const config = opciones || {};
    const duracion = Number.isFinite(Number(config.duracion)) ? Math.max(0, Number(config.duracion)) : configuracion.duracion;
    const contenedor = obtenerContenedorToasts();
    const existentes = Array.from(contenedor.querySelectorAll('.toast-ah'));

    while (existentes.length >= 4) {
      const primero = existentes.shift();
      if (primero) primero.remove();
    }

    const toast = document.createElement('div');
    toast.className = 'toast-ah toast-ah-' + tipoNormalizado;
    toast.setAttribute('role', tipoNormalizado === 'error' ? 'alert' : 'status');
    toast.setAttribute('aria-live', tipoNormalizado === 'error' ? 'assertive' : 'polite');
    toast.setAttribute('aria-atomic', 'true');

    const icono = document.createElement('span');
    icono.className = 'toast-ah-icono';
    icono.setAttribute('aria-hidden', 'true');
    icono.innerHTML = configuracion.icono;

    const contenido = document.createElement('div');
    contenido.className = 'toast-ah-contenido';

    if (config.titulo) {
      const tituloToast = document.createElement('strong');
      tituloToast.className = 'toast-ah-titulo';
      tituloToast.textContent = String(config.titulo);
      contenido.appendChild(tituloToast);
    }

    const textoToast = document.createElement('p');
    textoToast.className = 'toast-ah-texto';
    textoToast.textContent = mensaje;
    contenido.appendChild(textoToast);

    if (config.accion && typeof config.accion === 'object' && typeof config.accion.ejecutar === 'function') {
      const accionToast = document.createElement('button');
      accionToast.className = 'toast-ah-accion';
      accionToast.type = 'button';
      accionToast.textContent = String(config.accion.texto || 'Deshacer');
      accionToast.addEventListener('click', async function () {
        accionToast.disabled = true;
        try {
          await config.accion.ejecutar();
          cerrarToast(toast);
        } catch (error) {
          accionToast.disabled = false;
          mostrarAvisoNotificacion(error && error.message ? error.message : 'No se pudo completar la acción.', 'error');
        }
      });
      contenido.appendChild(accionToast);
    }

    const cerrar = document.createElement('button');
    cerrar.className = 'toast-ah-cerrar';
    cerrar.type = 'button';
    cerrar.setAttribute('aria-label', 'Cerrar notificación');
    cerrar.innerHTML = window.ArenaCJDIcono('peligro');
    cerrar.addEventListener('click', function () { cerrarToast(toast); });

    toast.appendChild(icono);
    toast.appendChild(contenido);
    toast.appendChild(cerrar);
    contenedor.appendChild(toast);

    function iniciarTemporizador() {
      if (duracion <= 0 || toast.dataset.cerrando === 'true') return;
      if (toast._temporizadorArenaCJD) window.clearTimeout(toast._temporizadorArenaCJD);
      toast._temporizadorArenaCJD = window.setTimeout(function () { cerrarToast(toast); }, duracion);
    }

    function pausarTemporizador() {
      if (!toast._temporizadorArenaCJD) return;
      window.clearTimeout(toast._temporizadorArenaCJD);
      toast._temporizadorArenaCJD = null;
    }

    toast.addEventListener('mouseenter', pausarTemporizador);
    toast.addEventListener('mouseleave', iniciarTemporizador);
    toast.addEventListener('focusin', pausarTemporizador);
    toast.addEventListener('focusout', iniciarTemporizador);
    iniciarTemporizador();
    return toast;
  }

  window.ArenaCJDToast = function (texto, tipo, opciones) {
    return mostrarAvisoNotificacion(texto, tipo, opciones);
  };

  window.ArenaCJDAvisar = function (texto, tipo, opciones) {
    return mostrarAvisoNotificacion(texto, tipo, opciones);
  };

  function renderizarInvitacionesNotificaciones(invitaciones) {
    const lista = document.getElementById('listaInvitacionesNotificaciones');
    const cantidad = document.getElementById('cantidadInvitacionesPanel');
    if (cantidad) cantidad.textContent = invitaciones.length;
    if (!lista) return;

    if (!invitaciones.length) {
      lista.innerHTML = '<p class="sin-solicitudes">No tienes invitaciones pendientes.</p>';
      return;
    }

    lista.innerHTML = invitaciones.slice(0, 6).map(function (invitacion) {
      return '<article class="solicitud-notificacion invitacion-notificacion">' +
        '<div><strong>' + escaparHtml(invitacion.torneo) + '</strong>' +
        '<span>' + escaparHtml(invitacion.disciplina) + ' · invitó @' + escaparHtml(invitacion.invitador_usuario) + '</span></div>' +
        '<div class="acciones-solicitud-notificacion">' +
        '<button type="button" data-responder-invitacion="aceptada" data-id-invitacion="' + Number(invitacion.id_invitacion) + '">Aceptar</button>' +
        '<button type="button" data-responder-invitacion="rechazada" data-id-invitacion="' + Number(invitacion.id_invitacion) + '">Rechazar</button>' +
        '<a class="enlace-configuracion-notificaciones" href="torneos.php?detalle=' + Number(invitacion.id_torneo) + '">Ver torneo</a>' +
        '</div></article>';
    }).join('');
  }

  function renderizarInvitacionesEquipoNotificaciones(invitaciones) {
    const lista = document.getElementById('listaInvitacionesEquipoNotificaciones');
    const cantidad = document.getElementById('cantidadInvitacionesEquipoPanel');
    if (cantidad) cantidad.textContent = invitaciones.length;
    if (!lista) return;

    if (!invitaciones.length) {
      lista.innerHTML = '<p class="sin-solicitudes">No tienes invitaciones pendientes a equipos.</p>';
      return;
    }

    lista.innerHTML = invitaciones.slice(0, 6).map(function (invitacion) {
      return '<article class="solicitud-notificacion invitacion-notificacion">' +
        '<div><strong>' + escaparHtml(invitacion.equipo) + '</strong>' +
        '<span>Te invitó @' + escaparHtml(invitacion.invitador_usuario) + ' para formar parte del equipo</span></div>' +
        '<div class="acciones-solicitud-notificacion">' +
        '<button type="button" data-responder-invitacion-equipo="aceptada" data-id-invitacion-equipo="' + Number(invitacion.id_invitacion_equipo) + '">Aceptar</button>' +
        '<button type="button" data-responder-invitacion-equipo="rechazada" data-id-invitacion-equipo="' + Number(invitacion.id_invitacion_equipo) + '">Rechazar</button>' +
        '</div></article>';
    }).join('');
  }

  function renderizarAvisosTorneoNotificaciones(avisos) {
    const lista = document.getElementById('listaAvisosTorneoNotificaciones');
    const cantidad = document.getElementById('cantidadAvisosTorneoPanel');
    if (!lista) return;
    if (cantidad) cantidad.textContent = avisos.length;

    if (!avisos.length) {
      lista.innerHTML = '<p class="sin-solicitudes">No hay torneos que comiencen dentro de las próximas 24 horas.</p>';
      return;
    }

    lista.innerHTML = avisos.slice(0, 6).map(function (aviso) {
      const inicio = aviso.fecha_hora ? new Date(String(aviso.fecha_hora).replace(' ', 'T')) : null;
      const fecha = inicio && !Number.isNaN(inicio.getTime())
        ? inicio.toLocaleString('es-UY', {day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit'})
        : '';
      const estado = aviso.tipo === 'iniciado' ? 'Ya comenzó' : 'Comienza pronto';
      return '<article class="solicitud-notificacion invitacion-notificacion">' +
        '<div><strong>' + escaparHtml(aviso.torneo) + '</strong>' +
        '<span>' + escaparHtml(aviso.disciplina) + ' · ' + escaparHtml(estado) + (fecha ? ' · ' + escaparHtml(fecha) : '') + '</span></div>' +
        '<a class="enlace-configuracion-notificaciones" href="' + escaparHtml(aviso.url || 'torneos.php') + '">Ver torneo</a>' +
        '</article>';
    }).join('');
  }

  function renderizarActividadNotificaciones(actividades) {
    const lista = document.getElementById('listaActividadNotificaciones');
    const cantidad = document.getElementById('cantidadActividadPanel');
    const elementos = Array.isArray(actividades) ? actividades : [];
    if (cantidad) cantidad.textContent = elementos.length;
    if (!lista) return;

    if (!elementos.length) {
      lista.innerHTML = '<p class="sin-solicitudes">No tienes actividad nueva que requiera atención.</p>';
      return;
    }

    lista.innerHTML = elementos.slice(0, 6).map(function (actividad) {
      return '<article class="solicitud-notificacion invitacion-notificacion">' +
        '<div><strong>' + escaparHtml(actividad.titulo || 'Actividad relacionada contigo') + '</strong>' +
        '<span>' + escaparHtml(actividad.detalle || '') + '</span></div>' +
        '<a class="enlace-configuracion-notificaciones" href="' + escaparHtml(actividad.url || 'mi-actividad.php') + '">Ver</a>' +
        '</article>';
    }).join('');
  }

  function renderizarPendientesNotificaciones(solicitudes, esAdministrador) {
    const seccion = document.getElementById('seccionSolicitudesNotificaciones');
    const lista = document.getElementById('listaNotificacionesPendientes');
    const cantidad = document.getElementById('cantidadPanelPendientes');
    if (seccion) seccion.hidden = !esAdministrador;
    if (cantidad) cantidad.textContent = solicitudes.length;
    if (!lista || !esAdministrador) return;

    if (!solicitudes.length) {
      lista.innerHTML = '<p class="sin-solicitudes">No hay usuarios esperando aprobación.</p>';
      return;
    }

    lista.innerHTML = solicitudes.slice(0, 6).map(function (solicitud) {
      return '<article class="solicitud-notificacion"><div><strong>' + escaparHtml(solicitud.nombre) + '</strong><span>@' + escaparHtml(solicitud.nombre_usuario) + ' · ' + escaparHtml(solicitud.correo) + '</span></div><span class="estado-solicitud estado-pendiente">Pendiente de aprobación</span></article>';
    }).join('');
  }

  function renderizarUltimaGestion(gestion) {
    const seccion = document.getElementById('seccionUltimaGestion');
    const contenido = document.getElementById('ultimaGestionNotificaciones');
    if (!seccion || !contenido) return;

    if (!gestion || !gestion.mensaje) {
      seccion.hidden = true;
      contenido.innerHTML = '';
      return;
    }

    seccion.hidden = false;
    contenido.className = 'notificacion-gestion notificacion-gestion-' + escaparHtml(gestion.tipo || 'info');
    contenido.innerHTML = '<strong>' + (gestion.tipo === 'aprobado' ? 'Usuario aprobado' : 'Gestión realizada') + '</strong><span>' + escaparHtml(gestion.mensaje) + '</span>';
  }

  async function responderInvitacionNotificacion(idInvitacion, respuestaInvitacion) {
    if (!idInvitacion || !['aceptada', 'rechazada'].includes(respuestaInvitacion)) return;
    const panel = crearPanelNotificaciones();
    panel.querySelectorAll('[data-responder-invitacion]').forEach(function (boton) { boton.disabled = true; });
    try {
      const respuesta = await fetch('api/invitacion_responder.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfSesion},
        body: JSON.stringify({id_invitacion: idInvitacion, respuesta: respuestaInvitacion})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo responder la invitación.');
      mostrarAvisoNotificacion(resultado.mensaje, respuestaInvitacion === 'aceptada' ? 'aprobado' : 'info');
      await cargarNotificaciones(false);
      window.dispatchEvent(new CustomEvent('arenacjd:invitaciones-actualizadas'));
    } catch (error) {
      mostrarAvisoNotificacion(error.message || 'No se pudo responder la invitación.', 'error');
    } finally {
      panel.querySelectorAll('[data-responder-invitacion]').forEach(function (boton) { boton.disabled = false; });
    }
  }

  async function responderInvitacionEquipoNotificacion(idInvitacion, respuestaInvitacion) {
    if (!idInvitacion || !['aceptada', 'rechazada'].includes(respuestaInvitacion)) return;
    const panel = crearPanelNotificaciones();
    panel.querySelectorAll('[data-responder-invitacion-equipo]').forEach(function (boton) { boton.disabled = true; });
    try {
      const respuesta = await fetch('api/invitacion_equipo_responder.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json', 'X-CSRF-Token': csrfSesion},
        body: JSON.stringify({id_invitacion_equipo: idInvitacion, respuesta: respuestaInvitacion})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo responder la invitación al equipo.');
      mostrarAvisoNotificacion(resultado.mensaje, respuestaInvitacion === 'aceptada' ? 'aprobado' : 'info');
      await cargarNotificaciones(false);
      window.dispatchEvent(new CustomEvent('arenacjd:invitaciones-actualizadas'));
    } catch (error) {
      mostrarAvisoNotificacion(error.message || 'No se pudo responder la invitación al equipo.', 'error');
    } finally {
      panel.querySelectorAll('[data-responder-invitacion-equipo]').forEach(function (boton) { boton.disabled = false; });
    }
  }

  function claveLecturaNotificaciones() {
    return 'arenaCJD-notificaciones-leidas-v1-' + String(idUsuarioNotificaciones || 'anonimo');
  }

  function firmasNotificaciones(resultado) {
    if (!resultado) return [];
    const firmas = [];

    (resultado.invitaciones || []).forEach(function (invitacion) {
      firmas.push('invitacion:' + Number(invitacion.id_invitacion));
    });

    (resultado.invitaciones_equipo || []).forEach(function (invitacion) {
      firmas.push('invitacion-equipo:' + Number(invitacion.id_invitacion_equipo));
    });

    (resultado.avisos_torneo || []).forEach(function (aviso) {
      firmas.push('torneo:' + String(aviso.tipo || '') + ':' + Number(aviso.id_torneo) + ':' + String(aviso.fecha_hora || ''));
    });

    (resultado.firmas_actividad_usuario || []).forEach(function (firma) {
      if (firma) firmas.push(String(firma));
    });

    if (resultado.es_administrador) {
      (resultado.solicitudes || []).forEach(function (solicitud) {
        firmas.push('solicitud:' + Number(solicitud.id));
      });
    }

    return Array.from(new Set(firmas));
  }

  function firmasActividadUsuario(resultado) {
    if (!resultado || !Array.isArray(resultado.firmas_actividad_usuario)) return [];
    return Array.from(new Set(resultado.firmas_actividad_usuario.map(function (firma) { return String(firma || ''); }).filter(Boolean)));
  }

  function cantidadActividadNoLeida(resultado) {
    const vistas = leerFirmasVistas();
    return firmasActividadUsuario(resultado).filter(function (firma) { return !vistas.has(firma); }).length;
  }

  function esPaginaMiActividad() {
    const archivo = String(window.location.pathname || '').split('/').pop();
    return archivo === 'mi-actividad.php';
  }

  function leerFirmasVistas() {
    try {
      const guardado = localStorage.getItem(claveLecturaNotificaciones());
      const datos = guardado ? JSON.parse(guardado) : [];
      return new Set(Array.isArray(datos) ? datos : []);
    } catch (error) {
      return new Set();
    }
  }

  function guardarFirmasVistas(firmas) {
    try {
      const actuales = leerFirmasVistas();
      firmas.forEach(function (firma) { actuales.add(firma); });
      localStorage.setItem(claveLecturaNotificaciones(), JSON.stringify(Array.from(actuales).slice(-500)));
    } catch (error) {
      console.error('No se pudo guardar el estado de lectura de las notificaciones.');
    }
  }

  function cantidadNotificacionesNoLeidas(resultado) {
    const vistas = leerFirmasVistas();
    return firmasNotificaciones(resultado).filter(function (firma) { return !vistas.has(firma); }).length;
  }

  function marcarNotificacionesComoLeidas(resultado) {
    if (!resultado) return;
    guardarFirmasVistas(firmasNotificaciones(resultado));
    actualizarBadgeNotificaciones(0);
    actualizarBadgeMiActividad(0);
  }

  function marcarActividadComoLeida(resultado) {
    if (!resultado) return;
    guardarFirmasVistas(firmasActividadUsuario(resultado));
    actualizarBadgeMiActividad(0);
    actualizarBadgeNotificaciones(cantidadNotificacionesNoLeidas(resultado));
  }

  function actualizarBadgeMiActividad(cantidad) {
    const contador = document.getElementById('contadorMenuMiActividad');
    if (!contador) return;
    const total = Math.max(0, Number(cantidad || 0));
    contador.textContent = total > 99 ? '99+' : String(total);
    contador.hidden = total === 0;
    contador.setAttribute('aria-label', total > 0
      ? total + (total === 1 ? ' elemento relacionado contigo en Mi actividad' : ' elementos relacionados contigo en Mi actividad')
      : '');
  }

  function actualizarBadgeNotificaciones(cantidad) {
    const contador = document.getElementById('contadorNotificaciones');
    const boton = document.getElementById('botonNotificaciones');
    const total = Math.max(0, Number(cantidad || 0));
    if (contador) {
      contador.textContent = total > 99 ? '99+' : String(total);
      contador.hidden = total === 0;
      contador.setAttribute('aria-hidden', total === 0 ? 'true' : 'false');
    }
    if (boton) {
      boton.setAttribute('aria-label', total > 0
        ? 'Abrir notificaciones, ' + total + (total === 1 ? ' sin leer' : ' sin leer')
        : 'Abrir notificaciones');
      boton.classList.toggle('tiene-notificaciones', total > 0);
    }
  }

  async function cargarNotificaciones(mostrarAvisos) {
    const boton = document.getElementById('botonNotificaciones');
    if (!boton) return;

    try {
      const respuesta = await fetch('api/notificaciones.php?_=' + Date.now(), { cache: 'no-store' });
      if (!respuesta.ok) return;
      const resultado = await respuesta.json();
      if (!resultado.exito) return;
      csrfSesion = resultado.csrf_token || csrfSesion;

      ultimoResultadoNotificaciones = resultado;
      if (esPaginaMiActividad()) {
        marcarActividadComoLeida(resultado);
      } else {
        actualizarBadgeMiActividad(cantidadActividadNoLeida(resultado));
      }
      const cantidadNoLeida = cantidadNotificacionesNoLeidas(resultado);
      const panelAbierto = Boolean(document.getElementById('panelNotificaciones') && document.getElementById('panelNotificaciones').classList.contains('panel-notificaciones-visible'));
      if (panelAbierto) {
        marcarNotificacionesComoLeidas(resultado);
      } else {
        actualizarBadgeNotificaciones(cantidadNoLeida);
      }

      crearPanelNotificaciones();
      renderizarInvitacionesNotificaciones(resultado.invitaciones || []);
      renderizarInvitacionesEquipoNotificaciones(resultado.invitaciones_equipo || []);
      renderizarAvisosTorneoNotificaciones(resultado.avisos_torneo || []);
      renderizarActividadNotificaciones(resultado.actividad_usuario || []);
      renderizarPendientesNotificaciones(resultado.solicitudes || [], Boolean(resultado.es_administrador));
      renderizarUltimaGestion(resultado.ultima_gestion || null);

      if (mostrarAvisos) {
        const invitaciones = Number(resultado.cantidad_invitaciones || 0);
        const claveInvitaciones = 'arenaCJD-invitaciones-vistas';
        const invitacionesAnteriores = sessionStorage.getItem(claveInvitaciones);
        if (invitaciones > 0 && invitacionesAnteriores !== String(invitaciones)) {
          mostrarAvisoNotificacion(invitaciones === 1 ? 'Tienes 1 invitación pendiente.' : 'Tienes ' + invitaciones + ' invitaciones pendientes.', 'pendiente');
        }
        sessionStorage.setItem(claveInvitaciones, String(invitaciones));

        const avisosTorneo = Array.isArray(resultado.avisos_torneo) ? resultado.avisos_torneo : [];
        const firmaAvisos = avisosTorneo.map(function (aviso) { return aviso.tipo + ':' + aviso.id_torneo; }).join('|');
        const claveAvisos = 'arenaCJD-avisos-torneo-vistos';
        const firmaAnterior = sessionStorage.getItem(claveAvisos);
        if (firmaAvisos && firmaAvisos !== firmaAnterior) {
          const iniciados = avisosTorneo.filter(function (aviso) { return aviso.tipo === 'iniciado'; }).length;
          mostrarAvisoNotificacion(iniciados > 0
            ? (iniciados === 1 ? 'Un torneo relacionado contigo acaba de comenzar.' : iniciados + ' torneos relacionados contigo acaban de comenzar.')
            : (avisosTorneo.length === 1 ? 'Tienes un torneo que comienza dentro de las próximas 24 horas.' : 'Tienes ' + avisosTorneo.length + ' torneos que comienzan dentro de las próximas 24 horas.'), 'pendiente');
        }
        sessionStorage.setItem(claveAvisos, firmaAvisos);

        if (resultado.es_administrador) {
          const pendientes = Number(resultado.cantidad_pendientes || 0);
          const clavePendientes = 'arenaCJD-pendientes-admin-vistos';
          const cantidadAnterior = sessionStorage.getItem(clavePendientes);
          if (pendientes > 0 && cantidadAnterior !== String(pendientes)) {
            mostrarAvisoNotificacion(pendientes === 1 ? 'Hay 1 usuario esperando aprobación.' : 'Hay ' + pendientes + ' usuarios esperando aprobación.', 'pendiente');
          }
          sessionStorage.setItem(clavePendientes, String(pendientes));
        }
      }
      return resultado;
    } catch (error) {
      console.error('No se pudieron actualizar las notificaciones.');
      return null;
    }
  }

  window.ArenaCJDActualizarNotificaciones = function (mostrarAvisos) {
    return cargarNotificaciones(Boolean(mostrarAvisos));
  };

  window.ArenaCJDMarcarActividadVista = function () {
    if (!ultimoResultadoNotificaciones) return cargarNotificaciones(false);
    marcarActividadComoLeida(ultimoResultadoNotificaciones);
    return Promise.resolve(ultimoResultadoNotificaciones);
  };

  if (!window.__arenaCjdEscuchaInvitacionesNotificaciones) {
    window.__arenaCjdEscuchaInvitacionesNotificaciones = true;
    window.addEventListener('arenacjd:invitaciones-actualizadas', function () {
      cargarNotificaciones(false);
    });
    window.addEventListener('focus', function () {
      cargarNotificaciones(false);
    });
  }

  function configurarBotonNotificaciones() {
    const boton = document.getElementById('botonNotificaciones');
    if (!boton) return;

    boton.hidden = false;
    boton.removeAttribute('data-solo-admin');
    if (boton.dataset.configurado === 'true') return;
    boton.dataset.configurado = 'true';

    boton.addEventListener('click', function () {
      const panel = document.getElementById('panelNotificaciones');
      const abierto = panel && panel.classList.contains('panel-notificaciones-visible');
      if (abierto) cerrarPanelNotificaciones();
      else abrirPanelNotificaciones();
    });

    document.addEventListener('keydown', function (evento) {
      if (evento.key === 'Escape') cerrarPanelNotificaciones();
    });

    cargarNotificaciones(true);
    if (window.ArenaCJDSync) window.ArenaCJDSync.registrar('notificaciones', function () { return cargarNotificaciones(true); }, 5000, {ejecutarAhora:false});
  }

  function aplicarUsuarioSesion(resultado) {
    if (!resultado || !resultado.usuario) return;

    const usuario = resultado.usuario;
    csrfSesion = resultado.csrf_token || csrfSesion;
    idUsuarioNotificaciones = Number(usuario.id_usuario || usuario.id || 0);
    const esAdministrador = Array.isArray(usuario.roles) && usuario.roles.includes('administrador');
    const esOrganizador = Array.isArray(usuario.roles) && usuario.roles.includes('organizador');
    const puedeGestionar = esAdministrador || esOrganizador;

    document.querySelectorAll('[data-solo-admin]').forEach(function (elemento) {
      elemento.hidden = !esAdministrador;
    });
    document.querySelectorAll('[data-solo-gestion]').forEach(function (elemento) {
      elemento.hidden = !puedeGestionar;
    });

    const zonaUsuario = document.getElementById('zonaUsuarioMenu');
    const nombreMenu = document.getElementById('nombreUsuarioMenu');
    const aliasMenu = document.getElementById('aliasUsuarioMenu');
    const nombrePerfil = document.getElementById('nombrePerfilUsuario');
    const aliasPerfil = document.getElementById('aliasPerfilUsuario');
    const rolPerfil = document.getElementById('rolPerfilUsuario');
    const correoPerfil = document.getElementById('correoPerfilUsuario');
    const estadoPerfil = document.getElementById('estadoPerfilUsuario');

    if (nombreMenu) nombreMenu.textContent = usuario.nombre;
    if (aliasMenu) aliasMenu.textContent = '@' + usuario.nombre_usuario;
    if (nombrePerfil) nombrePerfil.textContent = usuario.nombre;
    if (aliasPerfil) aliasPerfil.textContent = '@' + usuario.nombre_usuario;
    if (correoPerfil) correoPerfil.textContent = usuario.correo;

    if (rolPerfil) {
      const roles = usuario.roles.map(function (rol) {
        return rol.charAt(0).toUpperCase() + rol.slice(1);
      });
      rolPerfil.textContent = roles.length > 0 ? roles.join(', ') : 'Sin rol';
    }

    if (estadoPerfil) {
      const estados = {
        activo: 'Cuenta activa',
        pendiente: 'Cuenta pendiente',
        inactivo: 'Cuenta inactiva',
        bloqueado: 'Cuenta bloqueada'
      };
      estadoPerfil.textContent = estados[usuario.estado] || usuario.estado;
    }

    mostrarFotoPerfil(Boolean(usuario.tiene_foto_perfil), usuario.foto_perfil_url || null);
    configurarFotoPerfil();
    configurarNombreUsuarioPerfil(usuario.nombre_usuario);
    configurarSeguridadPerfil();
    if (zonaUsuario) zonaUsuario.hidden = false;
    configurarBotonNotificaciones();
  }

  async function cargarUsuarioSesion() {
    try {
      const respuesta = await fetch('api/sesion.php', { cache: 'no-store' });
      if (!respuesta.ok) return;
      const resultado = await respuesta.json();
      if (!resultado.exito || !resultado.usuario) return;

      aplicarUsuarioSesion(resultado);
    } catch (error) {
      console.error('No se pudo actualizar el usuario de la sesión.');
    }
  }

  const sesionInicial = obtenerSesionInicial();
  renderizarMenuLateral(sesionInicial);
  configurarMenuUsuario();

  if (sesionInicial) {
    aplicarUsuarioSesion(sesionInicial);
  }

  cargarUsuarioSesion();
}());


(function () {
  'use strict';

  window.ArenaCJDEstadoError = function (contenedor, mensaje, reintentar) {
    if (!contenedor) return;
    contenedor.hidden = false;
    contenedor.innerHTML = '';

    const bloque = document.createElement('div');
    bloque.className = 'estado-error-ah';
    const texto = document.createElement('p');
    texto.textContent = mensaje || 'No se pudieron cargar los datos.';
    bloque.appendChild(texto);

    if (typeof reintentar === 'function') {
      const boton = document.createElement('button');
      boton.type = 'button';
      boton.className = 'boton boton-claro';
      boton.textContent = 'Reintentar';
      boton.addEventListener('click', function () {
        boton.disabled = true;
        boton.textContent = 'Reintentando...';
        Promise.resolve(reintentar()).finally(function () {
          if (boton.isConnected) {
            boton.disabled = false;
            boton.textContent = 'Reintentar';
          }
        });
      });
      bloque.appendChild(boton);
    }

    contenedor.appendChild(bloque);
  };
}());

(function () {
  'use strict';

  let modal = null;
  let resolver = null;
  let ultimoFoco = null;

  function cerrar(resultado) {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('sin-scroll');
    if (ultimoFoco && typeof ultimoFoco.focus === 'function') ultimoFoco.focus();
    const completar = resolver;
    resolver = null;
    ultimoFoco = null;
    if (completar) completar(Boolean(resultado));
  }

  function crear() {
    if (modal) return modal;
    modal = document.createElement('div');
    modal.className = 'fondo-confirmacion-ah';
    modal.id = 'confirmacionArenaCJD';
    modal.hidden = true;
    modal.innerHTML = '<section class="modal-confirmacion-ah" role="alertdialog" aria-modal="true" aria-labelledby="tituloConfirmacionArenaCJD" aria-describedby="mensajeConfirmacionArenaCJD"><button class="cerrar-confirmacion-ah" type="button" data-confirmacion-ah="cancelar" aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button><span class="icono-confirmacion-ah" aria-hidden="true"><span data-icono="alerta" aria-hidden="true"></span></span><h2 id="tituloConfirmacionArenaCJD">Confirmar acción</h2><p id="mensajeConfirmacionArenaCJD"></p><div class="acciones-confirmacion-ah"><button class="boton boton-claro" type="button" data-confirmacion-ah="cancelar">Cancelar</button><button class="boton boton-principal boton-peligro-ah" type="button" data-confirmacion-ah="aceptar">Confirmar</button></div></section>';
    document.body.appendChild(modal);
    modal.addEventListener('click', function (evento) {
      const accion = evento.target.closest('[data-confirmacion-ah]');
      if (accion) cerrar(accion.dataset.confirmacionAh === 'aceptar');
      else if (evento.target === modal) cerrar(false);
    });
    document.addEventListener('keydown', function (evento) {
      if (evento.key === 'Escape' && modal && !modal.hidden) cerrar(false);
    });
    return modal;
  }

  window.ArenaCJDConfirmar = function (opciones) {
    const datos = typeof opciones === 'string' ? {mensaje: opciones} : (opciones || {});
    const elemento = crear();
    if (resolver) cerrar(false);
    ultimoFoco = document.activeElement;
    elemento.querySelector('#tituloConfirmacionArenaCJD').textContent = datos.titulo || 'Confirmar acción';
    elemento.querySelector('#mensajeConfirmacionArenaCJD').textContent = datos.mensaje || '¿Deseas continuar?';
    elemento.querySelector('.icono-confirmacion-ah').hidden = datos.mostrarIcono === false;
    const aceptar = elemento.querySelector('[data-confirmacion-ah="aceptar"]');
    aceptar.textContent = datos.confirmar || 'Confirmar';
    elemento.hidden = false;
    document.body.classList.add('sin-scroll');
    window.setTimeout(function () { aceptar.focus(); }, 0);
    return new Promise(function (resolve) { resolver = resolve; });
  };
}());

(function () {
  'use strict';
  function esc(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }
  function iniciales(nombre) {
    const partes = String(nombre || '').trim().split(/\s+/).filter(Boolean).slice(0, 2);
    return (partes.map(function (p) { return p.charAt(0); }).join('') || '•').toUpperCase();
  }
  function url(tipo, id, publico) {
    const numero = Number(id || 0);
    if (!numero) return '';
    if (publico) return tipo === 'equipo'
      ? 'api/publico/imagen_equipo.php?id_equipo=' + numero
      : 'api/publico/imagen_torneo.php?id_torneo=' + numero;
    return tipo === 'equipo'
      ? 'api/imagen_equipo.php?id_equipo=' + numero
      : 'api/imagen_torneo.php?id_torneo=' + numero;
  }
  function html(tipo, id, nombre, opciones) {
    const datos = opciones || {};
    const clase = 'media-entidad media-' + tipo + (datos.clase ? ' ' + esc(datos.clase) : '');
    const fallback = tipo === 'torneo' ? window.ArenaCJDIcono('torneo') : iniciales(nombre);
    const src = url(tipo, id, Boolean(datos.publico));
    return '<span class="' + clase + '" title="' + esc(nombre || '') + '">' +
      '<span class="media-entidad-fallback" aria-hidden="true">' + (tipo === 'torneo' ? fallback : esc(fallback)) + '</span>' +
      (src ? '<img src="' + esc(src) + '" alt="' + esc((tipo === 'torneo' ? 'Imagen del torneo ' : 'Imagen del equipo ') + (nombre || '')) + '" loading="lazy" onerror="this.hidden=true">' : '') +
      '</span>';
  }
  window.ArenaCJDMedia = { html: html, url: url, iniciales: iniciales };
}());

(function () {
  'use strict';

  function modalVisible() {
    const candidatos = Array.from(document.querySelectorAll('[role="dialog"][aria-modal="true"], [role="alertdialog"][aria-modal="true"]'));
    return candidatos.reverse().find(function (elemento) {
      const estilo = window.getComputedStyle(elemento);
      const ocultoAtributo = elemento.closest('[hidden]');
      const contenedorOculto = elemento.closest('[aria-hidden="true"]');
      return !ocultoAtributo && !contenedorOculto && estilo.display !== 'none' && estilo.visibility !== 'hidden';
    }) || null;
  }

  document.addEventListener('keydown', function (evento) {
    if (evento.key !== 'Tab') return;
    const dialogo = modalVisible();
    if (!dialogo) return;
    const focos = Array.from(dialogo.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),summary,[tabindex]:not([tabindex="-1"])')).filter(function (elemento) {
      const detalleCerrado = elemento.closest('details:not([open])');
      return !elemento.closest('[hidden], [inert]') && (!detalleCerrado || elemento === detalleCerrado.querySelector('summary')) && elemento.getClientRects().length > 0 && window.getComputedStyle(elemento).visibility !== 'hidden';
    });
    if (!focos.length) {
      evento.preventDefault();
      dialogo.setAttribute('tabindex', '-1');
      dialogo.focus();
      return;
    }
    const primero = focos[0];
    const ultimo = focos[focos.length - 1];
    if (evento.shiftKey && document.activeElement === primero) {
      evento.preventDefault();
      ultimo.focus();
    } else if (!evento.shiftKey && document.activeElement === ultimo) {
      evento.preventDefault();
      primero.focus();
    } else if (!dialogo.contains(document.activeElement)) {
      evento.preventDefault();
      primero.focus();
    }
  });
}());
