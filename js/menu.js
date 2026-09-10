document.addEventListener('DOMContentLoaded', function () {

  const botonMovil = document.querySelector('.boton-menu-movil');
  const navPrincipal = document.getElementById('navegacionPrincipal') || document.querySelector('.navegacion-principal');

  if (botonMovil && navPrincipal) {
    botonMovil.addEventListener('click', function () {
      const abierto = navPrincipal.classList.toggle('abierto');
      botonMovil.classList.toggle('abierto');
      botonMovil.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
  }

  const enlacesNav = document.querySelectorAll('.enlace-navegacion');
  enlacesNav.forEach(function (enlace) {
    enlace.addEventListener('click', function () {
      enlacesNav.forEach(function (e) { e.classList.remove('activo'); });
      enlace.classList.add('activo');
      if (window.innerWidth < 992 && navPrincipal) {
        navPrincipal.classList.remove('abierto');
        if (botonMovil) {
          botonMovil.classList.remove('abierto');
          botonMovil.setAttribute('aria-expanded', 'false');
        }
      }
    });
  });

  const botonesVer = document.querySelectorAll('.ver-contrasena[data-target]');
  botonesVer.forEach(function (btn) {
    btn.addEventListener('click', function () {
      const input = document.getElementById(btn.getAttribute('data-target'));
      if (!input) return;
      const oculto = input.type === 'password';
      input.type = oculto ? 'text' : 'password';
      btn.setAttribute('aria-label', oculto ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  });

  const regPass = document.getElementById('password');
  const reglas = {
    length: function (v) { return v.length >= 8; },
    number: function (v) { return /[0-9]/.test(v); },
    uppercase: function (v) { return /[A-ZÁÉÍÓÚÑ]/.test(v); },
    symbol: function (v) { return /[!@#$%&*]/.test(v); }
  };

  function actualizarReglasContrasena(valor, forzarValidacion) {
    document.querySelectorAll('.regla-registro').forEach(function (item) {
      const regla = item.getAttribute('data-rule');
      const icono = item.querySelector('.icono-regla');
      if (!icono || !reglas[regla]) return;

      const cumple = reglas[regla](valor);
      icono.classList.remove('neutral', 'valida', 'invalida');
      item.classList.remove('regla-valida', 'regla-invalida');

      if (valor.length === 0 && !forzarValidacion) {
        icono.classList.add('neutral');
        icono.innerHTML = window.ArenaCJDIcono('circulo');
        return;
      }

      if (cumple) {
        icono.classList.add('valida');
        icono.innerHTML = window.ArenaCJDIcono('exito');
        item.classList.add('regla-valida');
      } else {
        icono.classList.add('invalida');
        icono.innerHTML = window.ArenaCJDIcono('peligro');
        item.classList.add('regla-invalida');
      }
    });
  }

  if (regPass && document.getElementById('formularioRegistro')) {
    regPass.addEventListener('input', function () {
      actualizarReglasContrasena(regPass.value, false);
    });
  }

 const formularioRegistro = document.getElementById('formularioRegistro');

if (formularioRegistro) {
  const camposRegistro = {
    fullname: document.getElementById('fullname'),
    username: document.getElementById('username'),
    email: document.getElementById('email'),
    password: document.getElementById('password'),
    confirmPassword: document.getElementById('confirmPassword'),
    preguntaRecuperacion: document.getElementById('preguntaRecuperacion'),
    respuestaRecuperacion: document.getElementById('respuestaRecuperacion'),
    terms: document.getElementById('terms')
  };
  let intentoRegistro = false;

  function contenedorErrorRegistro(campo) {
    if (!campo) return null;
    if (campo.id === 'terms') return campo.closest('.recordar') || campo.parentElement;
    return campo.closest('.grupo-formulario') || campo.parentElement;
  }

  function asegurarErrorRegistro(campo) {
    if (!campo) return null;
    const id = 'error-' + campo.id;
    let error = document.getElementById(id);
    if (!error) {
      error = document.createElement('small');
      error.id = id;
      error.className = 'error-campo-ah error-registro-campo';
      error.setAttribute('aria-live', 'polite');
      const contenedor = contenedorErrorRegistro(campo);
      if (contenedor) contenedor.appendChild(error);
    }
    const descrito = (campo.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
    if (!descrito.includes(id)) {
      descrito.push(id);
      campo.setAttribute('aria-describedby', descrito.join(' '));
    }
    return error;
  }

  function marcarRegistro(campo, texto) {
    if (!campo) return false;
    const error = asegurarErrorRegistro(campo);
    const envoltorio = campo.closest('.envoltorio-campo');
    campo.setAttribute('aria-invalid', texto ? 'true' : 'false');
    campo.classList.toggle('campo-con-error', Boolean(texto));
    campo.classList.toggle('campo-con-exito', !texto && campo.value !== '');
    if (envoltorio) envoltorio.classList.toggle('campo-invalido', Boolean(texto));
    if (error) {
      error.textContent = texto || '';
      error.hidden = !texto;
    }
    return !texto;
  }

  function validarRegistroCampo(campo) {
    if (!campo) return true;
    const valor = campo.type === 'checkbox' ? campo.checked : campo.value.trim();
    if (campo.id === 'fullname') return marcarRegistro(campo, valor ? '' : 'Escribe tu nombre completo.');
    if (campo.id === 'username') {
      if (!valor) return marcarRegistro(campo, 'Elige un nombre de usuario.');
      const normalizado = valor.replace(/^@+/, '').toLowerCase();
      return marcarRegistro(campo, /^[a-z0-9._-]{4,24}$/.test(normalizado) ? '' : 'Usa entre 4 y 24 caracteres permitidos.');
    }
    if (campo.id === 'email') {
      if (!valor) return marcarRegistro(campo, 'Escribe tu correo electrónico.');
      return marcarRegistro(campo, /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor) ? '' : 'Escribe un correo válido, por ejemplo nombre@dominio.com.');
    }
    if (campo.id === 'password') {
      if (!valor) return marcarRegistro(campo, 'Crea una contraseña.');
      const valida = Object.keys(reglas).every(function (regla) { return reglas[regla](campo.value); });
      return marcarRegistro(campo, valida ? '' : 'La contraseña todavía no cumple todos los requisitos.');
    }
    if (campo.id === 'confirmPassword') {
      if (!valor) return marcarRegistro(campo, 'Repite la contraseña.');
      return marcarRegistro(campo, campo.value === camposRegistro.password.value ? '' : 'Las contraseñas no coinciden.');
    }
    if (campo.id === 'preguntaRecuperacion') return marcarRegistro(campo, valor ? '' : 'Selecciona una pregunta de recuperación.');
    if (campo.id === 'respuestaRecuperacion') return marcarRegistro(campo, valor ? '' : 'Escribe una respuesta que puedas recordar.');
    if (campo.id === 'terms') return marcarRegistro(campo, campo.checked ? '' : 'Debes aceptar los términos y la política de privacidad.');
    return true;
  }

  function validarRegistroCompleto() {
    let valido = true;
    Object.keys(camposRegistro).forEach(function (clave) {
      if (!validarRegistroCampo(camposRegistro[clave])) valido = false;
    });
    return valido;
  }

  Object.keys(camposRegistro).forEach(function (clave) {
    const campo = camposRegistro[clave];
    if (!campo) return;
    asegurarErrorRegistro(campo);
    const evento = campo.type === 'checkbox' || campo.tagName === 'SELECT' ? 'change' : 'input';
    campo.addEventListener(evento, function () {
      if (campo.id === 'password') actualizarReglasContrasena(campo.value, false);
      if (intentoRegistro || campo.getAttribute('aria-invalid') === 'true') validarRegistroCampo(campo);
      if (campo.id === 'password' && camposRegistro.confirmPassword && camposRegistro.confirmPassword.value) validarRegistroCampo(camposRegistro.confirmPassword);
    });
    campo.addEventListener('blur', function () {
      if (campo.value || intentoRegistro) validarRegistroCampo(campo);
    });
  });

  formularioRegistro.addEventListener('submit', async function (e) {
    e.preventDefault();
    intentoRegistro = true;

    const mensaje = document.getElementById('mensajeRegistro');
    const botonEnviar = formularioRegistro.querySelector('[type="submit"]');
    const nombre = camposRegistro.fullname.value.trim();
    const usuario = camposRegistro.username.value.trim();
    const usuarioNormalizado = usuario.replace(/^@+/, '').toLowerCase();
    const correo = camposRegistro.email.value.trim();
    const pass = camposRegistro.password.value;
    const confirmacion = camposRegistro.confirmPassword.value;

    actualizarReglasContrasena(pass, true);

    if (!validarRegistroCompleto()) {
      mensaje.textContent = 'Revisa los campos marcados antes de continuar.';
      mensaje.className = 'mensaje-registro mensaje-registro-error';
      const primero = formularioRegistro.querySelector('[aria-invalid="true"]');
      if (primero) primero.focus();
      return;
    }

    mensaje.textContent = 'Creando tu cuenta...';
    mensaje.className = 'mensaje-registro';
    if (botonEnviar) botonEnviar.disabled = true;

    try {
      const respuesta = await fetch('api/registrar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          nombre_completo: nombre,
          nombre_usuario: usuarioNormalizado,
          correo: correo,
          contrasena: pass,
          confirmar_contrasena: confirmacion,
          terminos: camposRegistro.terms.checked,
          pregunta_recuperacion: camposRegistro.preguntaRecuperacion.value,
          respuesta_recuperacion: camposRegistro.respuestaRecuperacion.value.trim()
        })
      });

      const resultado = await respuesta.json();

      if (!respuesta.ok || !resultado.exito) {
        mensaje.textContent = resultado.mensaje || 'No se pudo crear la cuenta. Revisa los datos e inténtalo nuevamente.';
        mensaje.className = 'mensaje-registro mensaje-registro-error';
        return;
      }

      mensaje.textContent = '';
      mensaje.className = 'mensaje-registro';
      formularioRegistro.reset();
      intentoRegistro = false;
      Object.keys(camposRegistro).forEach(function (clave) { marcarRegistro(camposRegistro[clave], ''); });
      actualizarReglasContrasena('', false);

      const modalRegistro = document.getElementById('modalRegistroExitoso');
      if (modalRegistro) {
        modalRegistro.classList.add('abierto');
        modalRegistro.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-registro-abierto');
        const contenidoModal = modalRegistro.querySelector('.contenido-modal-registro');
        if (contenidoModal) contenidoModal.focus();
      }
    } catch (error) {
      mensaje.textContent = 'No pudimos conectar con el servidor. Comprueba tu conexión e inténtalo nuevamente.';
      mensaje.className = 'mensaje-registro mensaje-registro-error';
    } finally {
      if (botonEnviar) botonEnviar.disabled = false;
    }
  });
}



  const modalRegistroExitoso = document.getElementById('modalRegistroExitoso');
  if (modalRegistroExitoso) {
    function cerrarModalRegistro() {
      modalRegistroExitoso.classList.remove('abierto');
      modalRegistroExitoso.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-registro-abierto');
    }

    modalRegistroExitoso.querySelectorAll('[data-cerrar-modal-registro]').forEach(function (control) {
      control.addEventListener('click', cerrarModalRegistro);
    });

    document.addEventListener('keydown', function (evento) {
      if (evento.key === 'Escape' && modalRegistroExitoso.classList.contains('abierto')) {
        cerrarModalRegistro();
      }
    });
  }

const formularioSesion = document.querySelector('.formulario-sesion');

if (formularioSesion && !document.getElementById('formularioRegistro')) {
  formularioSesion.addEventListener('input', function (evento) {
    if (evento.target.matches('input')) evento.target.removeAttribute('aria-invalid');
  });
  formularioSesion.addEventListener('submit', async function (e) {
    e.preventDefault();

    const usuarioInput = document.getElementById('usuario');
    const passwordInput = document.getElementById('password');
    const botonEnviar = formularioSesion.querySelector('[type="submit"]');
    const recordarInput = formularioSesion.querySelector('input[name="recordar"]');

    let mensaje = document.getElementById('mensajeLogin');

    if (!mensaje) {
      mensaje = document.createElement('div');
      mensaje.id = 'mensajeLogin';
      mensaje.className = 'mensaje-registro';
      mensaje.setAttribute('role', 'status');
      mensaje.setAttribute('aria-live', 'polite');

      if (botonEnviar) {
        formularioSesion.insertBefore(mensaje, botonEnviar);
      }
    }

    const usuario = usuarioInput.value.trim();
    const password = passwordInput.value;

    if (!usuario || !password) {
      mensaje.textContent = 'Debes completar usuario y contraseña.';
      mensaje.className = 'mensaje-registro mensaje-registro-error';
      usuarioInput.setAttribute('aria-invalid', String(!usuario));
      passwordInput.setAttribute('aria-invalid', String(!password));
      (!usuario ? usuarioInput : passwordInput).focus();
      return;
    }

    mensaje.textContent = 'Iniciando sesión...';
    mensaje.className = 'mensaje-registro';

    if (botonEnviar) {
      botonEnviar.disabled = true;
    }

    try {
      const respuesta = await fetch('api/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          usuario: usuario,
          contrasena: password,
          recordar: Boolean(recordarInput && recordarInput.checked)
        })
      });

      const resultado = await respuesta.json();

      if (!respuesta.ok || !resultado.exito) {
        mensaje.textContent =
          resultado.mensaje || 'No se pudo iniciar sesión.';

        mensaje.className =
          'mensaje-registro mensaje-registro-error';

        if (respuesta.status === 401) {
          formularioSesion.classList.remove('zumbido-login');
          void formularioSesion.offsetWidth;
          formularioSesion.classList.add('zumbido-login');
        }

        return;
      }

      mensaje.textContent = 'Inicio de sesión correcto.';
      mensaje.className =
        'mensaje-registro mensaje-registro-exito';

      sessionStorage.setItem('temaArenaCJDTrasLogin', document.documentElement.dataset.preferenciaTema || 'claro');
      window.location.href = 'panel.php';

    } catch (error) {
      mensaje.textContent = 'No se pudo conectar con el servidor.';
      mensaje.className =
        'mensaje-registro mensaje-registro-error';

    } finally {
      if (botonEnviar) {
        botonEnviar.disabled = false;
      }
    }
  });
}

  const formularioBusqueda = document.querySelector('.caja-busqueda');
  if (formularioBusqueda) {
    formularioBusqueda.addEventListener('submit', function (e) {
      e.preventDefault();
    });
  }

  const menuLateral = document.getElementById('menuLateral');
  const botonMenu = document.getElementById('botonMenu');
  const fondoOscuro = document.getElementById('fondoOscuro');

  function esEscritorio() {
    return window.innerWidth >= 992;
  }

  function sincronizarAccesibilidadMenu() {
    if (!menuLateral) return;
    const abierto = menuEstaAbierto();
    if (!abierto && menuLateral.contains(document.activeElement) && botonMenu) botonMenu.focus();
    menuLateral.inert = !abierto;
    menuLateral.setAttribute('aria-hidden', String(!abierto));
    if (botonMenu) botonMenu.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
  }

  function abrirMenu() {
    if (!menuLateral) return;
    if (esEscritorio()) {
      document.body.classList.remove('menu-colapsado');
      localStorage.setItem('menuColapsado', 'false');
      document.documentElement.classList.add('menu-pre-abierto');
    } else {
      menuLateral.classList.add('abierto');
      if (fondoOscuro) { fondoOscuro.classList.add('visible'); }
      document.body.classList.add('sin-scroll');
    }
    if (botonMenu) {
      botonMenu.classList.add('abierto');
      botonMenu.setAttribute('aria-expanded', 'true');
    }
    sincronizarAccesibilidadMenu();
  }

  function cerrarMenu() {
    if (!menuLateral) return;
    if (esEscritorio()) {
      document.body.classList.add('menu-colapsado');
      localStorage.setItem('menuColapsado', 'true');
      document.documentElement.classList.remove('menu-pre-abierto');
    }
    menuLateral.classList.remove('abierto');
    if (fondoOscuro) { fondoOscuro.classList.remove('visible'); }
    document.body.classList.remove('sin-scroll');
    if (botonMenu) {
      botonMenu.classList.remove('abierto');
      botonMenu.setAttribute('aria-expanded', 'false');
    }
    sincronizarAccesibilidadMenu();
  }

  function menuEstaAbierto() {
    if (esEscritorio()) {
      return !document.body.classList.contains('menu-colapsado');
    }
    return menuLateral && menuLateral.classList.contains('abierto');
  }

  if (botonMenu && menuLateral) {
    botonMenu.addEventListener('click', function () {
      if (menuEstaAbierto()) {
        cerrarMenu();
      } else {
        abrirMenu();
        if (!esEscritorio()) menuLateral.querySelector('a, button')?.focus();
      }
    });
  }

  if (fondoOscuro && menuLateral) {
    fondoOscuro.addEventListener('click', cerrarMenu);
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.querySelector('#fondoModalPerfil:not([hidden]), #menuOpcionesUsuario:not([hidden])')) return;
    if (e.key === 'Escape' && menuEstaAbierto()) {
      cerrarMenu();
      if (botonMenu) botonMenu.focus();
    }
    if (e.key === 'Tab' && menuEstaAbierto() && !esEscritorio() && !document.querySelector('#fondoModalPerfil:not([hidden])')) {
      const controles = Array.from(menuLateral.querySelectorAll('a[href], button:not([disabled]), input:not([disabled])')).filter(el => el.getClientRects().length && !el.closest('[hidden]'));
      const primero = controles[0], ultimo = controles[controles.length - 1];
      if (e.shiftKey && (document.activeElement === primero || !menuLateral.contains(document.activeElement))) {
        e.preventDefault(); ultimo?.focus();
      } else if (!e.shiftKey && (document.activeElement === ultimo || !menuLateral.contains(document.activeElement))) {
        e.preventDefault(); primero?.focus();
      }
    }
  });

  window.addEventListener('resize', function () {
    if (esEscritorio()) {
      if (menuLateral) { menuLateral.classList.remove('abierto'); }
      if (fondoOscuro) { fondoOscuro.classList.remove('visible'); }
      document.body.classList.remove('sin-scroll');
      if (document.body.classList.contains('menu-colapsado')) {
        if (botonMenu) { botonMenu.classList.remove('abierto'); botonMenu.setAttribute('aria-expanded', 'false'); }
      } else {
        if (botonMenu) { botonMenu.classList.add('abierto'); botonMenu.setAttribute('aria-expanded', 'true'); }
      }
    } else {
      document.documentElement.classList.remove('menu-pre-abierto');
      document.body.classList.remove('menu-colapsado');
      if (fondoOscuro) fondoOscuro.classList.remove('visible');
      if (!document.querySelector('#fondoModalPerfil:not([hidden])')) document.body.classList.remove('sin-scroll');
      if (menuLateral) { menuLateral.classList.remove('abierto'); }
      if (botonMenu) { botonMenu.classList.remove('abierto'); botonMenu.setAttribute('aria-expanded', 'false'); }
    }
  });

  window.addEventListener('resize', sincronizarAccesibilidadMenu);

  if (esEscritorio()) {
    const colapsado = localStorage.getItem('menuColapsado') === 'true';
    if (colapsado) {
      cerrarMenu();
    } else {
      abrirMenu();
    }
  } else {
    
    document.documentElement.classList.remove('menu-pre-abierto');
    cerrarMenu();
  }

  const paginaActual = window.location.pathname.split('/').pop() || 'index.php';
  const enlacesMenu = document.querySelectorAll('.enlace-menu');
  enlacesMenu.forEach(function (enlace) {
    const paginaEnlace = enlace.getAttribute('href');
    if (paginaEnlace === paginaActual) {
      enlace.classList.add('activo');
    } else {
      enlace.classList.remove('activo');
    }
  });

  enlacesMenu.forEach(function (enlace) {
    enlace.addEventListener('click', function () {
      if (!esEscritorio()) {
        cerrarMenu();
      }
    });
  });


  const pestanasConfig = document.querySelectorAll('.pestana-config');
  const panelesConfig = document.querySelectorAll('.panel-pestana');
  const nombresPaneles = ['general', 'usuarios', 'seguridad', 'apariencia', 'torneos', 'notificaciones', 'respaldos', 'sistema'];

  if (pestanasConfig.length && panelesConfig.length) {
    pestanasConfig.forEach(function (pestana, i) {
      pestana.addEventListener('click', function () {
        pestanasConfig.forEach(function (t) { t.classList.remove('activo'); });
        pestana.classList.add('activo');
        panelesConfig.forEach(function (p) { p.classList.remove('activo'); });
        const destino = document.querySelector('.panel-pestana[data-panel="' + nombresPaneles[i] + '"]');
        if (destino) { destino.classList.add('activo'); }
      });
    });
  }

  const muestrasTema = document.querySelectorAll('.muestra-tema');
  muestrasTema.forEach(function (muestra) {
    muestra.addEventListener('click', function () {
      muestrasTema.forEach(function (m) { m.classList.remove('activo'); });
      muestra.classList.add('activo');
    });
  });

});
