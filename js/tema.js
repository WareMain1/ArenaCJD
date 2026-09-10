(function () {
  const claveTema = 'temaArenaCJD';
  const documento = document.documentElement;
  const temasPermitidos = ['claro', 'oscuro', 'sistema'];
  const claveTemaTrasLogin = 'temaArenaCJDTrasLogin';
  const temaTrasLogin = sessionStorage.getItem(claveTemaTrasLogin);
  const conservarTemaTrasLogin = temasPermitidos.includes(temaTrasLogin);
  let preferenciasServidor = null;
  let csrfPreferencias = '';
  let cargaPreferencias = null;
  let temaCambiadoPorUsuario = false;
  let guardadoPendiente = Promise.resolve();

  function sesionActual() {
    try {
      return JSON.parse(document.body && document.body.dataset.sesionArenaCjd || '{}');
    } catch (error) {
      return {};
    }
  }

  function temaDelSistema() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oscuro' : 'claro';
  }

  function resolverTema(valor) {
    return valor === 'sistema' ? temaDelSistema() : valor;
  }

  function aplicarTema(valor) {
    const candidata = valor || localStorage.getItem(claveTema) || 'claro';
    const preferencia = temasPermitidos.includes(candidata) ? candidata : 'claro';
    const temaResuelto = resolverTema(preferencia);
    documento.dataset.tema = temaResuelto;
    documento.dataset.preferenciaTema = preferencia;
    documento.style.colorScheme = temaResuelto === 'oscuro' ? 'dark' : 'light';
    localStorage.setItem(claveTema, preferencia);
    document.querySelectorAll('[data-selector-tema]').forEach(function (selector) {
      selector.value = preferencia;
    });
    document.querySelectorAll('[data-boton-tema]').forEach(function (boton) {
      boton.setAttribute('aria-pressed', String(boton.dataset.botonTema === preferencia));
    });
  }

  async function cargarPreferenciasServidor() {
    const sesion = sesionActual();
    if (!sesion.usuario && document.body.dataset.temaAutenticado !== '1') return null;
    if (preferenciasServidor) return preferenciasServidor;
    if (cargaPreferencias) return cargaPreferencias;
    csrfPreferencias = sesion.csrf_token || '';
    cargaPreferencias = fetch('api/preferencias.php?_=' + Date.now(), { cache: 'no-store' })
      .then(function (respuesta) { return respuesta.json().then(function (datos) { return { respuesta: respuesta, datos: datos }; }); })
      .then(function (resultado) {
        if (!resultado.respuesta.ok || !resultado.datos.exito) throw new Error(resultado.datos.mensaje || 'No se pudieron cargar las preferencias.');
        preferenciasServidor = resultado.datos.preferencias || {};
        csrfPreferencias = resultado.datos.csrf_token || csrfPreferencias;
        if (!temaCambiadoPorUsuario && !conservarTemaTrasLogin && temasPermitidos.includes(preferenciasServidor.tema)) aplicarTema(preferenciasServidor.tema);
        return preferenciasServidor;
      })
      .catch(function () { return null; })
      .finally(function () { cargaPreferencias = null; });
    return cargaPreferencias;
  }

  async function guardarTemaServidor(valor) {
    const preferencias = await cargarPreferenciasServidor();
    if (!preferencias || !csrfPreferencias) return;
    const nuevas = { tema: valor };
    try {
      const respuesta = await fetch('api/preferencias.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfPreferencias },
        body: JSON.stringify(nuevas)
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.exito) throw new Error('No se pudo guardar el tema.');
      preferenciasServidor = resultado.preferencias || nuevas;
      sessionStorage.removeItem(claveTemaTrasLogin);
    } catch (error) {
      if (window.ArenaCJDToast) window.ArenaCJDToast('El tema se aplicó en este dispositivo, pero no se pudo guardar en tu cuenta.', 'error');
    }
  }

  function seleccionarTema(valor) {
    temaCambiadoPorUsuario = true;
    if (sessionStorage.getItem(claveTemaTrasLogin)) sessionStorage.setItem(claveTemaTrasLogin, valor);
    aplicarTema(valor);
    guardadoPendiente = guardadoPendiente.then(function () { return guardarTemaServidor(valor); });
  }

  function crearBotonVolverArriba() {
    document.querySelectorAll('footer #botonVolverArriba, footer .boton-volver-arriba').forEach(function (elemento) { elemento.remove(); });
    const existente = document.getElementById('botonVolverArriba');
    if (existente) {
      if (existente.parentElement !== document.body) document.body.appendChild(existente);
      return;
    }
    const boton = document.createElement('button');
    boton.id = 'botonVolverArriba';
    boton.className = 'boton-volver-arriba';
    boton.type = 'button';
    boton.hidden = true;
    boton.title = 'Volver arriba';
    boton.setAttribute('aria-label', 'Volver al inicio de la página');
    boton.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>';
    document.body.appendChild(boton);
    boton.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
    });
    function actualizarVisibilidad() {
      const visible = window.matchMedia('(min-width: 992px)').matches && window.scrollY > 320;
      boton.hidden = !visible;
      boton.classList.toggle('visible', visible);
      const footer = document.querySelector('footer');
      const espacioFooter = footer ? Math.max(0, window.innerHeight - footer.getBoundingClientRect().top) : 0;
      boton.style.setProperty('--separacion-volver-arriba', Math.max(24, espacioFooter + 16) + 'px');
    }
    window.addEventListener('scroll', actualizarVisibilidad, { passive: true });
    window.addEventListener('resize', actualizarVisibilidad);
    actualizarVisibilidad();
  }

  document.addEventListener('change', function (evento) {
    if (evento.target.matches('[data-selector-tema]')) seleccionarTema(evento.target.value);
  });

  document.addEventListener('click', function (evento) {
    const boton = evento.target.closest('[data-boton-tema]');
    if (boton) seleccionarTema(boton.dataset.botonTema);
  });

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
    if ((localStorage.getItem(claveTema) || 'claro') === 'sistema') aplicarTema('sistema');
  });

  window.addEventListener('storage', function (evento) {
    if (evento.key === claveTema) aplicarTema(evento.newValue);
  });

  aplicarTema(conservarTemaTrasLogin ? temaTrasLogin : undefined);
  if (conservarTemaTrasLogin) {
    guardadoPendiente = guardadoPendiente.then(function () { return guardarTemaServidor(temaTrasLogin); });
  } else {
    cargarPreferenciasServidor();
  }
  crearBotonVolverArriba();
  window.ArenaCJDTema = { aplicar: seleccionarTema };
}());
