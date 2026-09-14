(function () {
  'use strict';

  const body = document.body;
  if (!body) return;

  let sesion = null;
  try {
    sesion = body.dataset.sesionArenaCjd ? JSON.parse(body.dataset.sesionArenaCjd) : null;
  } catch (error) {
    sesion = null;
  }

  const usuario = sesion && sesion.usuario ? sesion.usuario : null;
  const idUsuario = usuario ? Number(usuario.id || 0) : 0;
  const roles = usuario && Array.isArray(usuario.roles) ? usuario.roles : [];
  const esAdministrador = roles.includes('administrador');
  const esOrganizador = roles.includes('organizador');
  const puedeGestionar = esAdministrador || esOrganizador;
  const pagina = window.location.pathname.split('/').pop() || 'panel.php';
  const claveUsuario = String(idUsuario || 'anonimo');
  const claveRecientes = 'arenaCJD-recientes-v2-' + claveUsuario;
  const claveFiltros = 'arenaCJD-filtros-v2-' + claveUsuario + '-' + pagina;
  let ultimoFocoPanel = null;
  let temporizadorBusqueda = 0;

  function escapar(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function leerJsonLocal(clave, defecto) {
    try {
      const valor = JSON.parse(localStorage.getItem(clave) || 'null');
      return valor == null ? defecto : valor;
    } catch (error) {
      return defecto;
    }
  }

  function guardarJsonLocal(clave, valor) {
    try {
      localStorage.setItem(clave, JSON.stringify(valor));
      return true;
    } catch (error) {
      return false;
    }
  }

  function listaRecientes() {
    const items = leerJsonLocal(claveRecientes, []);
    return Array.isArray(items) ? items.slice(0, 8) : [];
  }

  function registrarReciente(item) {
    if (!item || !item.url || !item.titulo) return;
    const actuales = listaRecientes().filter(function (actual) {
      return String(actual.url) !== String(item.url);
    });
    actuales.unshift({
      url: String(item.url),
      titulo: String(item.titulo),
      tipo: String(item.tipo || 'Acceso'),
      momento: Date.now()
    });
    guardarJsonLocal(claveRecientes, actuales.slice(0, 8));
    renderizarAccesosPanel();
  }

  function insertarSaltoContenido() {
    const main = document.querySelector('main');
    if (!main || document.querySelector('.salto-contenido-ah')) return;
    if (!main.id) main.id = 'contenidoPrincipalArenaCJD';
    const enlace = document.createElement('a');
    enlace.className = 'salto-contenido-ah';
    enlace.href = '#' + main.id;
    enlace.textContent = 'Saltar al contenido';
    body.prepend(enlace);
  }

  function iconoBusqueda() { return window.ArenaCJDIcono('buscar'); }

  function iconoAyuda() { return window.ArenaCJDIcono('ayuda'); }

  function crearFondoPanel() {
    let fondo = document.getElementById('fondoExperienciaArenaCJD');
    if (fondo) return fondo;
    fondo = document.createElement('div');
    fondo.id = 'fondoExperienciaArenaCJD';
    fondo.className = 'fondo-panel-experiencia-ah';
    fondo.hidden = true;
    fondo.addEventListener('click', cerrarPanelesExperiencia);
    body.appendChild(fondo);
    return fondo;
  }

  function crearBusquedaGlobal() {
    if (!usuario || document.getElementById('panelBusquedaGlobalArenaCJD')) return;
    const acciones = document.querySelector('.acciones-app');
    if (!acciones) return;

    const boton = document.createElement('button');
    boton.id = 'botonBusquedaGlobalArenaCJD';
    boton.className = 'boton-experiencia-ah';
    boton.type = 'button';
    boton.setAttribute('aria-label', 'Buscar en ArenaCJD');
    boton.setAttribute('aria-expanded', 'false');
    boton.setAttribute('aria-controls', 'panelBusquedaGlobalArenaCJD');
    boton.innerHTML = iconoBusqueda();
    acciones.insertBefore(boton, acciones.firstChild);

    const panel = document.createElement('section');
    panel.id = 'panelBusquedaGlobalArenaCJD';
    panel.className = 'panel-busqueda-global-ah';
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-labelledby', 'tituloBusquedaGlobalArenaCJD');
    panel.innerHTML = '<header class="cabecera-busqueda-global-ah"><div><strong id="tituloBusquedaGlobalArenaCJD">Buscar en ArenaCJD</strong><span>Torneos, equipos, personas y accesos frecuentes</span></div><button class="cerrar-panel-experiencia-ah" type="button" data-cerrar-busqueda-global aria-label="Cerrar búsqueda"><span data-icono="peligro" aria-hidden="true"></span></button></header>' +
      '<label class="campo-busqueda-global-ah"><span class="sr-only">Buscar</span>' + iconoBusqueda() + '<input id="inputBusquedaGlobalArenaCJD" type="search" autocomplete="off" placeholder="Escribe al menos 2 caracteres"><kbd class="atajo-busqueda-ah">Ctrl K</kbd></label>' +
      '<div class="busqueda-reciente-ah" id="recientesBusquedaGlobalArenaCJD"></div>' +
      '<div class="resultados-busqueda-global-ah" id="resultadosBusquedaGlobalArenaCJD"></div>';
    body.appendChild(panel);
    crearFondoPanel();

    boton.addEventListener('click', abrirBusquedaGlobal);
    panel.querySelector('[data-cerrar-busqueda-global]').addEventListener('click', cerrarPanelesExperiencia);
    const input = panel.querySelector('#inputBusquedaGlobalArenaCJD');
    input.addEventListener('input', function () {
      window.clearTimeout(temporizadorBusqueda);
      panel.querySelector('#resultadosBusquedaGlobalArenaCJD').replaceChildren();
      if (input.value.trim().length < 2) { ejecutarBusquedaGlobal(input.value); return; }
      temporizadorBusqueda = window.setTimeout(function () { ejecutarBusquedaGlobal(input.value); }, 220);
    });
    panel.addEventListener('click', function (evento) {
      const enlace = evento.target.closest('[data-resultado-global]');
      if (!enlace) return;
      registrarReciente({url: enlace.getAttribute('href'), titulo: enlace.dataset.titulo, tipo: enlace.dataset.tipo});
    });
    renderizarRecientesBusqueda();
  }

  function renderizarRecientesBusqueda() {
    const contenedor = document.getElementById('recientesBusquedaGlobalArenaCJD');
    if (!contenedor) return;
    const recientes = listaRecientes().slice(0, 5);
    if (!recientes.length) {
      contenedor.innerHTML = '';
      return;
    }
    contenedor.innerHTML = '<strong>Accesos recientes</strong><div class="chips-recientes-ah">' + recientes.map(function (item) {
      return '<a class="chip-reciente-ah" href="' + escapar(item.url) + '"><span aria-hidden="true"><span data-icono="derecha" aria-hidden="true"></span></span>' + escapar(item.titulo) + '</a>';
    }).join('') + '</div>';
  }

  function abrirBusquedaGlobal() {
    cerrarPanelAyuda();
    const panel = document.getElementById('panelBusquedaGlobalArenaCJD');
    const boton = document.getElementById('botonBusquedaGlobalArenaCJD');
    const fondo = crearFondoPanel();
    if (!panel) return;
    ultimoFocoPanel = document.activeElement;
    panel.hidden = false;
    fondo.hidden = false;
    if (boton) boton.setAttribute('aria-expanded', 'true');
    renderizarRecientesBusqueda();
    window.setTimeout(function () {
      const input = document.getElementById('inputBusquedaGlobalArenaCJD');
      if (input) input.focus();
    }, 0);
  }

  function iconoTipoResultado(tipo) { return window.ArenaCJDIcono(({torneo:'torneo',equipo:'participantes',usuario:'usuario',disciplina:'medalla'})[tipo] || 'buscar'); }

  async function ejecutarBusquedaGlobal(texto) {
    const resultados = document.getElementById('resultadosBusquedaGlobalArenaCJD');
    if (!resultados) return;
    const q = String(texto || '').trim();
    if (q.length < 2) {
      resultados.innerHTML = q.length === 1 ? '<div class="estado-busqueda-global-ah" role="status">Escribe un carácter más para buscar.</div>' : '';
      return;
    }
    resultados.innerHTML = '<div class="estado-busqueda-global-ah">Buscando…</div>';
    try {
      const respuesta = await fetch('api/busqueda_global.php?q=' + encodeURIComponent(q) + '&_=' + Date.now(), {cache: 'no-store'});
      const datos = await respuesta.json();
      if (document.getElementById('inputBusquedaGlobalArenaCJD').value.trim() !== q) return;
      if (!respuesta.ok || !datos.exito) throw new Error(datos.mensaje || 'No se pudo completar la búsqueda.');
      const grupos = Array.isArray(datos.grupos) ? datos.grupos : [];
      if (!grupos.length) {
        resultados.innerHTML = '<div class="estado-busqueda-global-ah"><strong>Sin coincidencias</strong><br>Prueba con otro nombre o término.</div>';
        return;
      }
      resultados.innerHTML = grupos.map(function (grupo) {
        return '<section class="grupo-resultado-global-ah"><strong>' + escapar(grupo.titulo) + '</strong>' + (grupo.items || []).map(function (item) {
          return '<a class="resultado-global-ah" data-resultado-global data-titulo="' + escapar(item.titulo) + '" data-tipo="' + escapar(item.tipo) + '" href="' + escapar(item.url) + '"><span class="icono-resultado-global-ah" aria-hidden="true">' + iconoTipoResultado(item.tipo) + '</span><div><strong>' + escapar(item.titulo) + '</strong><span>' + escapar(item.detalle || '') + '</span></div></a>';
        }).join('') + '</section>';
      }).join('');
    } catch (error) {
      if (document.getElementById('inputBusquedaGlobalArenaCJD').value.trim() !== q) return;
      resultados.innerHTML = '<div class="estado-busqueda-global-ah"><strong>No pudimos buscar ahora</strong><br>' + escapar(error.message || 'Revisa tu conexión e intenta nuevamente.') + '</div>';
    }
  }

  function ayudaSegunPagina() {
    const guias = {
      'panel.php': [
        ['Empieza por el resumen', 'Revisa las cifras y los torneos disponibles. El panel resume la información accesible para tu cuenta.'],
        ['Continúa una tarea', 'Abre el torneo que te interesa y consulta su siguiente paso antes de cambiar de módulo.'],
        ['Interpreta el progreso', 'Resultados completados indica cuántos enfrentamientos están finalizados sobre los generados. Si aún no hay cruces, muestra 0 %.'],
        ['Si faltan torneos', 'Comprueba tu rol y las asignaciones. Un Organizador solo gestiona los torneos que tiene asignados.']
      ],
      'torneos.php': [
        ['Encuentra un torneo', ['Busca por nombre y combina categoría, disciplina, estado y fechas.', 'Pulsa Aplicar filtros. Usa Limpiar para volver a ver el conjunto disponible.']],
        ['Consulta su información', 'Detalles muestra categoría, formato, organizador, fechas y cupo. Más opciones reúne accesos a las etapas del torneo.'],
        ['Borrador y publicación', 'Borrador permite preparar el torneo antes de abrir inscripciones. La visibilidad pública indica si los visitantes pueden consultarlo.'],
        ...(puedeGestionar ? [['Edita con cuidado', ['Abre Administrar y revisa los campos disponibles.', 'Selecciona primero la disciplina: Categoría solo ofrece sus asociaciones permitidas.', 'Pulsa Guardar cambios para aplicar lo editado. Si sales sin guardar, Cancelar te devuelve al formulario.']], ['Sin categorías disponibles', 'El Administrador debe configurar las asociaciones en Disciplinas. No se crean categorías desde el formulario del torneo.']] : []),
        ...(esAdministrador ? [['Crea un torneo', 'Usa Nuevo torneo, completa los datos obligatorios y asigna un organizador. Revisa participantes antes de pasar a Sorteos.']] : [])
      ],
      'participantes.php': [
        ['Elige el contexto', 'Selecciona el torneo antes de revisar sus participantes. Una inscripción pertenece a un torneo concreto; un equipo permanente puede utilizarse en distintas competencias.'],
        ['Consulta el estado', 'Revisa si una inscripción está pendiente, aprobada o rechazada. Estar invitado no equivale a tener una inscripción aprobada.'],
        ['Gestiona invitaciones', 'Consulta Mis invitaciones y revisa el torneo o equipo de origen antes de responder.'],
        ...(puedeGestionar ? [['Prepara el sorteo', 'Revisa las inscripciones del torneo que gestionas y resuelve las pendientes. Comprueba los participantes aprobados antes de generar cruces.']] : []),
        ['Si no encuentras a alguien', 'Comprueba el torneo, la modalidad individual o por equipos y los filtros activos antes de volver a buscar.']
      ],
      'sorteos.php': [
        ['Selecciona el torneo', 'Elige Torneo y disciplina para cargar sus inscripciones reales y el formato competitivo.'],
        ...(puedeGestionar ? [['Genera una vista previa', ['Revisa Participantes incluidos y el método disponible.', 'Pulsa Generar sorteo y comprueba los cruces de la vista previa.', 'Si necesitas otra distribución, usa Volver a generar antes de confirmar.']], ['Confirma cuando esté revisado', 'Confirmar sorteo guarda la estructura competitiva. Después consulta los cruces en Enfrentamientos.']] : [['Consulta el cuadro', 'Selecciona un torneo disponible para revisar su estructura y el avance de las rondas.']]),
        ['Pase automático', 'Un participante puede avanzar sin rival cuando el formato necesita completar una llave. No es un enfrentamiento jugado.'],
        ['Si no puedes generar', 'Revisa las inscripciones aprobadas, el estado del torneo y si ya existe una estructura. No todos los formatos o estados permiten regenerar cruces.']
      ],
      'partidos.php': [
        ['Localiza un enfrentamiento', ['Selecciona el torneo para acotar la lista.', 'Combina disciplina, estado, ronda y fecha para encontrar el cruce que buscas.']],
        ['Revisa antes de actuar', 'Comprueba los rivales, la ronda y el estado del enfrentamiento antes de abrir sus acciones.'],
        ...(puedeGestionar ? [['Programa los cruces', 'En los enfrentamientos que puedas gestionar, utiliza la opción de edición para revisar fecha y hora. Guarda la programación para que aparezca en Calendario.']] : []),
        ['Consulta los marcadores', 'Ve a Resultados para consultar los marcadores registrados y a Clasificación para ver su efecto en el torneo.'],
        ['Si la lista está vacía', 'Prueba sin filtros. Los cruces aparecen después de confirmar el sorteo; un torneo sin cruces todavía no tiene enfrentamientos que mostrar.']
      ],
      'resultados.php': [
        ['Encuentra el cruce', 'Filtra por torneo y por las opciones disponibles. Confirma los participantes y la ronda antes de consultar un marcador.'],
        ...(puedeGestionar ? [['Registra el resultado', ['Abre la acción de resultado del enfrentamiento que gestionas.', 'Completa los campos solicitados y revisa que el marcador corresponda a los rivales correctos.', 'Guarda y comprueba el estado que devuelve la aplicación.']], ['Revisa las restricciones', 'Las acciones disponibles dependen del estado del enfrentamiento y del torneo. Un torneo cerrado puede impedir modificaciones.']] : [['Consulta los resultados', 'Puedes revisar los marcadores disponibles para tu cuenta. La edición corresponde al Administrador o al Organizador responsable.']]),
        ['Comprueba la clasificación', 'Los resultados registrados alimentan la clasificación y el historial. Consulta Clasificación después de guardar un resultado.'],
        ['Si no aparecen resultados', 'Revisa el torneo y los filtros. Puede haber enfrentamientos pendientes que aún no tengan marcador registrado.']
      ],
      'clasificacion.php': [
        ['Carga la clasificación', ['Selecciona un torneo.', 'Revisa las estadísticas y la tabla que corresponden a su formato competitivo.', 'Utiliza Estado, Ronda o Buscar participante para acotar lo que ves.']],
        ['Interpreta la tabla', 'La clasificación se calcula con los resultados registrados. Las posiciones y el avance dependen del formato del torneo; no se editan directamente aquí.'],
        ['Consulta a un participante', 'Abre el detalle disponible en la tabla para revisar sus últimos enfrentamientos y continuar a Ver enfrentamientos.'],
        ['Resultados completados', 'Este porcentaje refleja enfrentamientos finalizados, no la cantidad de personas inscritas.'],
        ['Sin datos o exportación desactivada', 'Selecciona un torneo y comprueba que tenga datos disponibles. Si falta un marcador, revísalo en Resultados.']
      ],
      'calendario.php': [
        ['Elige qué quieres ver', ['Selecciona torneo, disciplina o estado.', 'Si necesitas un período concreto, completa Fecha desde y Fecha hasta.', 'Pulsa Aplicar filtros; usa Limpiar para retirar la selección.']],
        ['Muévete por las fechas', 'Usa las flechas para cambiar de mes y Hoy para regresar al mes actual. Un filtro de fechas puede seguir limitando los eventos visibles.'],
        ['Cambia de vista', 'Vista mensual ayuda a ubicar fechas. Vista de lista facilita leer los eventos en orden y resulta útil en pantallas pequeñas.'],
        ['Qué aparece aquí', 'El calendario reúne inicios y finales de torneos y enfrentamientos con fecha programada. Revisa también Eventos destacados y Próximos eventos.'],
        ['Si falta un enfrentamiento', 'Comprueba los filtros y el mes mostrado. Un cruce sin fecha programada no puede ubicarse en el calendario.'],
        ['Exporta la agenda', 'Usa Exportar calendario y revisa las opciones ofrecidas para obtener los eventos que necesitas.']
      ],
      'mi-actividad.php': [
        ['Tu actividad personal', 'Esta página reúne tus participaciones, equipos e invitaciones. No es el listado de todos los torneos que administras.'],
        ['Revisa lo próximo', 'Próximos enfrentamientos incluye los cruces en los que participas personalmente o mediante tu equipo.'],
        ['Atiende las invitaciones', 'Consulta Invitaciones y abre la gestión correspondiente para revisar y responder cada solicitud.'],
        ['Sigue tus inscripciones', 'Consulta Torneos e inscripciones para revisar el estado de tu participación y Últimos resultados para tus marcadores recientes.'],
        ['Si no hay actividad', 'Una cuenta sin inscripciones, equipos o invitaciones puede mostrar estas secciones vacías. Gestionar un torneo no te inscribe como participante.']
      ],
      'disciplinas.php': [
        ['Configura el catálogo', 'Revisa las disciplinas existentes antes de crear otra. El estado activo determina su disponibilidad en torneos nuevos.'],
        ['Asocia categorías y formatos', ['Abre la edición de una disciplina.', 'Selecciona las categorías y los tipos de torneo permitidos.', 'Guarda y comprueba las asociaciones antes de configurar un torneo.']],
        ['Gestiona Categorías', 'Abre Categorías para crear o renombrar una categoría y consultar las disciplinas y torneos que la utilizan. Renombrarla cambia su nombre donde se muestra.'],
        ['Eliminación segura', 'Una categoría asociada a una disciplina o utilizada por un torneo no puede eliminarse. Revisa el motivo indicado junto a la categoría.'],
        ['Conserva el historial', 'Una disciplina inactiva conserva sus competencias existentes. Revisa el uso antes de eliminar o cambiar elementos del catálogo.']
      ],
      'configuracion.php': [
        ['Personaliza la apariencia', 'Selecciona Claro, Oscuro o Sistema. El cambio de tema se aplica al elegirlo; Sistema sigue la preferencia del dispositivo.'],
        ['Guarda tus preferencias', 'Revisa Elementos por página y Torneo predeterminado. Pulsa Guardar configuración para aplicar estas preferencias.'],
        ['Actualiza tu perfil', 'Usa Editar perfil y revisa los campos del formulario antes de guardar.'],
        ...(esAdministrador ? [['Administra accesos', 'En Solicitudes de acceso revisa cada petición antes de aprobar o rechazar. En Usuarios registrados consulta la cuenta y sus roles antes de modificarla.'], ['Consulta la auditoría', 'Abre Historial y filtra por tipo de actividad. Revisa responsable, acción, resultado y fecha para entender qué ocurrió.']] : [])
      ]
    };
    const instrucciones = guias[pagina] || [['Navega por la aplicación', 'Usa el menú para abrir los módulos disponibles para tu cuenta.']];
    return instrucciones.concat([
      ['Tu acceso', esAdministrador ? 'Tienes acceso de Administrador. Las acciones también dependen del estado de cada torneo y de los datos asociados.' : esOrganizador ? 'Como Organizador gestionas los torneos que tienes asignados. Los catálogos globales y la asignación de roles corresponden al Administrador.' : 'Tu cuenta permite consultar la información disponible y gestionar tu participación. Las acciones administrativas no están habilitadas.'],
      ['Accesos rápidos', 'Usa la lupa de la cabecera o Ctrl K para buscar torneos, equipos y personas. Cierra esta guía con la X o con Escape para continuar.']
    ]);
  }
  function crearPanelAyuda() {
    if (!usuario || document.getElementById('panelAyudaArenaCJD')) return;
    const acciones = document.querySelector('.acciones-app');
    if (!acciones) return;
    const boton = document.createElement('button');
    boton.id = 'botonAyudaArenaCJD';
    boton.className = 'boton-experiencia-ah';
    boton.type = 'button';
    boton.setAttribute('aria-label', 'Ayuda contextual');
    boton.setAttribute('aria-expanded', 'false');
    boton.setAttribute('aria-controls', 'panelAyudaArenaCJD');
    boton.innerHTML = iconoAyuda();
    const notificaciones = document.getElementById('botonNotificaciones');
    if (notificaciones) acciones.insertBefore(boton, notificaciones); else acciones.appendChild(boton);

    const panel = document.createElement('aside');
    panel.id = 'panelAyudaArenaCJD';
    panel.className = 'panel-ayuda-ah';
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-labelledby', 'tituloAyudaArenaCJD');
    panel.innerHTML = '<header class="cabecera-ayuda-ah"><div><strong id="tituloAyudaArenaCJD">Ayuda contextual</strong><span>Guía de uso: ' + escapar(document.querySelector('main h1')?.textContent.trim() || 'esta página') + '</span></div><button class="cerrar-panel-experiencia-ah" type="button" data-cerrar-ayuda aria-label="Cerrar ayuda"><span data-icono="peligro" aria-hidden="true"></span></button></header><div class="contenido-ayuda-ah">' + ayudaSegunPagina().map(function (item) {
      return '<article class="tarjeta-ayuda-ah"><strong>' + escapar(item[0]) + '</strong>' + (Array.isArray(item[1]) ? '<ol>' + item[1].map(function (paso) { return '<li>' + escapar(paso) + '</li>'; }).join('') + '</ol>' : '<p>' + escapar(item[1]) + '</p>') + '</article>';
    }).join('') + '<a class="enlace-ayuda-ah" href="mi-actividad.php"><span>Ir a Mi actividad</span><span aria-hidden="true"><span data-icono="derecha" aria-hidden="true"></span></span></a></div>';
    body.appendChild(panel);
    crearFondoPanel();
    boton.addEventListener('click', function () {
      if (panel.hidden) abrirPanelAyuda(); else cerrarPanelAyuda();
    });
    panel.querySelector('[data-cerrar-ayuda]').addEventListener('click', cerrarPanelesExperiencia);
  }

  function abrirPanelAyuda() {
    const panel = document.getElementById('panelAyudaArenaCJD');
    const boton = document.getElementById('botonAyudaArenaCJD');
    const fondo = crearFondoPanel();
    if (!panel) return;
    ultimoFocoPanel = document.activeElement;
    cerrarBusquedaGlobal();
    panel.hidden = false;
    fondo.hidden = false;
    if (boton) boton.setAttribute('aria-expanded', 'true');
    window.setTimeout(function () {
      const cerrar = panel.querySelector('[data-cerrar-ayuda]');
      if (cerrar) cerrar.focus();
    }, 0);
  }

  function cerrarPanelAyuda() {
    const panel = document.getElementById('panelAyudaArenaCJD');
    const boton = document.getElementById('botonAyudaArenaCJD');
    if (panel) panel.hidden = true;
    if (boton) boton.setAttribute('aria-expanded', 'false');
  }

  function cerrarBusquedaGlobal() {
    const panel = document.getElementById('panelBusquedaGlobalArenaCJD');
    const boton = document.getElementById('botonBusquedaGlobalArenaCJD');
    if (panel) panel.hidden = true;
    if (boton) boton.setAttribute('aria-expanded', 'false');
  }

  function cerrarPanelesExperiencia() {
    const panelBusqueda = document.getElementById('panelBusquedaGlobalArenaCJD');
    const panelAyuda = document.getElementById('panelAyudaArenaCJD');
    const habiaPanel = (panelBusqueda && !panelBusqueda.hidden) || (panelAyuda && !panelAyuda.hidden);
    cerrarBusquedaGlobal();
    cerrarPanelAyuda();
    const fondo = document.getElementById('fondoExperienciaArenaCJD');
    if (fondo) fondo.hidden = true;
    if (habiaPanel && ultimoFocoPanel && typeof ultimoFocoPanel.focus === 'function') ultimoFocoPanel.focus();
  }

  function atraparFocoPanel(evento, panel) {
    if (evento.key !== 'Tab' || !panel || panel.hidden) return;
    const elementos = Array.from(panel.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])')).filter(function (elemento) {
      return elemento.offsetParent !== null;
    });
    if (!elementos.length) return;
    const primero = elementos[0];
    const ultimo = elementos[elementos.length - 1];
    if (evento.shiftKey && document.activeElement === primero) {
      evento.preventDefault();
      ultimo.focus();
    } else if (!evento.shiftKey && document.activeElement === ultimo) {
      evento.preventDefault();
      primero.focus();
    }
  }

  function activarAtajos() {
    document.addEventListener('keydown', function (evento) {
      const teclaK = evento.key && evento.key.toLowerCase() === 'k';
      if (teclaK && (evento.ctrlKey || evento.metaKey) && usuario) {
        evento.preventDefault();
        abrirBusquedaGlobal();
      }
      const busqueda = document.getElementById('panelBusquedaGlobalArenaCJD');
      const ayuda = document.getElementById('panelAyudaArenaCJD');
      if (busqueda && !busqueda.hidden) atraparFocoPanel(evento, busqueda);
      else if (ayuda && !ayuda.hidden) atraparFocoPanel(evento, ayuda);
      if (evento.key === 'Escape') cerrarPanelesExperiencia();
    });
  }

  function crearBannerConexion() {
    let banner = document.getElementById('bannerConexionArenaCJD');
    if (banner) return banner;
    banner = document.createElement('div');
    banner.id = 'bannerConexionArenaCJD';
    banner.className = 'banner-conexion-ah';
    banner.hidden = navigator.onLine;
    banner.setAttribute('role', 'status');
    banner.setAttribute('aria-live', 'polite');
    banner.innerHTML = '<span aria-hidden="true"><span data-icono="alerta" aria-hidden="true"></span></span><span>Sin conexión. Mantendremos la información visible y podrás reintentar cuando vuelva la red.</span><button type="button">Reintentar</button>';
    banner.querySelector('button').addEventListener('click', function () { window.location.reload(); });
    body.appendChild(banner);
    window.addEventListener('offline', function () {
      banner.hidden = false;
    });
    window.addEventListener('online', function () {
      banner.hidden = true;
      if (window.ArenaCJDAvisar) window.ArenaCJDAvisar('Conexión restablecida. Puedes continuar trabajando.', 'exito');
    });
    return banner;
  }

  function obtenerIdTorneoContextual() {
    const params = new URLSearchParams(window.location.search);
    const valor = params.get('torneo');
    return /^\d+$/.test(String(valor || '')) ? Number(valor) : 0;
  }

  function siguienteAccionTorneo(torneo) {
    const estado = String(torneo.estado || '');
    const inscritos = Number(torneo.cantidad_inscritos || 0);
    const total = Number(torneo.total_enfrentamientos || 0);
    const finalizados = Number(torneo.enfrentamientos_finalizados || 0);
    const id = Number(torneo.id_torneo || 0);
    if (estado === 'borrador') return {texto: 'Revisar configuración', url: 'torneos.php?detalle=' + id};
    if (estado === 'inscripciones' && inscritos < 2) return {texto: 'Agregar participantes', url: 'participantes.php?torneo=' + id + '#registrar-participante'};
    if (estado === 'inscripciones' && total === 0) return {texto: 'Generar sorteo', url: 'sorteos.php?torneo=' + id};
    if (estado === 'en_curso' && total > finalizados) return {texto: 'Cargar resultados', url: 'resultados.php?torneo=' + id};
    if (total > 0 && finalizados >= total) return {texto: 'Ver clasificación', url: 'clasificacion.php?torneo=' + id};
    return {texto: 'Ver enfrentamientos', url: 'partidos.php?torneo=' + id};
  }

  async function crearBarraContextoTorneo() {
    if (!usuario || pagina === 'torneos.php') return;
    const idTorneo = obtenerIdTorneoContextual();
    const main = document.querySelector('main');
    if (!idTorneo || !main || main.querySelector('.barra-contexto-torneo-ah')) return;
    try {
      const respuesta = await fetch('api/torneo_detalle.php?id=' + encodeURIComponent(idTorneo) + '&_=' + Date.now(), {cache: 'no-store'});
      const datos = await respuesta.json();
      if (!respuesta.ok || !datos.exito || !datos.torneo) return;
      const torneo = datos.torneo;
      const accion = siguienteAccionTorneo(torneo);
      const rutas = [
        ['participantes.php', 'Participantes'],
        ['sorteos.php', 'Sorteo'],
        ['partidos.php', 'Enfrentamientos'],
        ['resultados.php', 'Resultados'],
        ['clasificacion.php', 'Clasificación'],
        ['calendario.php', 'Calendario']
      ];
      const barra = document.createElement('nav');
      barra.className = 'barra-contexto-torneo-ah';
      barra.setAttribute('aria-label', 'Gestión del torneo ' + torneo.nombre);
      barra.innerHTML = '<div class="identidad-contexto-torneo-ah"><span>Gestionando torneo</span><strong>' + escapar(torneo.nombre) + '</strong></div><div class="navegacion-contexto-torneo-ah"><a href="torneos.php?detalle=' + Number(torneo.id_torneo) + '">Resumen</a>' + rutas.map(function (ruta) {
        return '<a href="' + ruta[0] + '?torneo=' + Number(torneo.id_torneo) + '"' + (pagina === ruta[0] ? ' aria-current="page"' : '') + '>' + escapar(ruta[1]) + '</a>';
      }).join('') + '</div>' + (datos.puede_gestionar ? '<div class="accion-contexto-torneo-ah"><a class="boton boton-principal" href="' + escapar(accion.url) + '">' + escapar(accion.texto) + '</a></div>' : '');
      const referencia = main.firstElementChild;
      if (referencia) main.insertBefore(barra, referencia); else main.appendChild(barra);
      registrarReciente({url: 'torneos.php?detalle=' + Number(torneo.id_torneo), titulo: torneo.nombre, tipo: 'Torneo'});
    } catch (error) {
    }
  }

  function renderizarAccesosPanel() {
    const contenedor = document.getElementById('accesosRecientesArenaCJD');
    if (!contenedor) return;
    const recientes = listaRecientes().slice(0, 4);
    if (!recientes.length) {
      contenedor.innerHTML = '<div class="estado-vacio-recientes-ah">Tus últimos accesos aparecerán aquí.</div>';
      return;
    }
    contenedor.innerHTML = '<div class="lista-accesos-recientes-ah">' + recientes.map(function (item) {
      return '<a class="acceso-reciente-ah" href="' + escapar(item.url) + '"><div><strong>' + escapar(item.titulo) + '</strong><span>' + escapar(item.tipo || 'Acceso') + '</span></div></a>';
    }).join('') + '</div>';
  }

  function crearAccesosPanel() {
    if (pagina !== 'panel.php') return;
    const main = document.querySelector('main');
    if (!main || document.getElementById('accesosRecientesArenaCJD')) return;
    const referencia = main.querySelector('.proximos-panel') || main.querySelector('.distribucion-panel');
    const seccion = document.createElement('section');
    seccion.className = 'tarjeta-ah seccion-panel';
    seccion.innerHTML = '<div class="cabecera-bloque-panel"><div><h2>Continuar donde estabas</h2><p>Tus accesos recientes para continuar donde estabas.</p></div></div><div class="panel-recientes-ah" id="accesosRecientesArenaCJD"></div>';
    if (referencia) main.insertBefore(seccion, referencia); else main.appendChild(seccion);
    renderizarAccesosPanel();
  }

  function crearOnboarding() {
    if (pagina !== 'panel.php' || !usuario || puedeGestionar) return;
    const clave = 'arenaCJD-onboarding-oculto-v2-' + claveUsuario + '-' + (puedeGestionar ? 'gestion' : 'participante');
    if (localStorage.getItem(clave) === '1') return;
    const bienvenida = document.querySelector('.bienvenida-panel');
    if (!bienvenida) return;
    const pasosGestion = [
      ['1', 'Crear o revisar torneo', 'torneos.php'],
      ['2', 'Registrar participantes', 'participantes.php'],
      ['3', 'Generar el sorteo', 'sorteos.php'],
      ['4', 'Publicar resultados', 'resultados.php']
    ];
    const pasosParticipante = [
      ['1', 'Explorar torneos', 'torneos.php'],
      ['2', 'Gestionar mis equipos', 'participantes.php'],
      ['3', 'Ver próximo partido', 'mi-actividad.php'],
      ['4', 'Consultar clasificación', 'clasificacion.php']
    ];
    const pasos = puedeGestionar ? pasosGestion : pasosParticipante;
    const seccion = document.createElement('section');
    seccion.className = 'onboarding-panel-ah';
    seccion.innerHTML = '<div class="cabecera-onboarding-ah"><div><h2>' + (puedeGestionar ? 'Primer recorrido recomendado' : 'Empieza por aquí') + '</h2><p>' + (puedeGestionar ? 'Una guía corta para avanzar por el flujo principal de ArenaCJD.' : 'Accesos clave para seguir tu participación sin buscar entre módulos.') + '</p></div><button class="cerrar-onboarding-ah" type="button" aria-label="Ocultar guía"><span data-icono="peligro" aria-hidden="true"></span></button></div><div class="pasos-onboarding-ah">' + pasos.map(function (paso) {
      return '<a class="paso-onboarding-ah" href="' + paso[2] + '"><b>' + paso[0] + '</b><span>' + escapar(paso[1]) + '</span></a>';
    }).join('') + '</div>';
    bienvenida.insertAdjacentElement('afterend', seccion);
    seccion.querySelector('.cerrar-onboarding-ah').addEventListener('click', function () {
      localStorage.setItem(clave, '1');
      seccion.remove();
    });
  }

  function contenedoresFiltros() {
    return Array.from(document.querySelectorAll('.tarjeta-filtros,.barra-filtros-calendario,.filtros-participantes-compactos,.barra-filtros-enfrentamientos,.barra-filtros-resultados,.barra-filtros-clasificacion,.barra-disciplinas'));
  }

  function controlesPersistibles() {
    const controles = [];
    contenedoresFiltros().forEach(function (contenedor) {
      contenedor.querySelectorAll('input[id],select[id]').forEach(function (control) {
        if (control.type === 'file' || control.type === 'hidden' || control.type === 'password') return;
        if (!controles.includes(control)) controles.push(control);
      });
    });
    return controles;
  }

  function guardarFiltrosPersistentes() {
    const datos = {};
    controlesPersistibles().forEach(function (control) {
      if (control.type === 'checkbox' || control.type === 'radio') datos[control.id] = Boolean(control.checked);
      else datos[control.id] = control.value;
    });
    if (Object.keys(datos).length) guardarJsonLocal(claveFiltros, datos);
  }

  function restaurarFiltrosPersistentes() {
    const datos = leerJsonLocal(claveFiltros, null);
    if (!datos || typeof datos !== 'object') return;
    const parametros = new URLSearchParams(window.location.search);
    let cambios = 0;
    controlesPersistibles().forEach(function (control) {
      if (!Object.prototype.hasOwnProperty.call(datos, control.id)) return;
      if (parametros.has('torneo') && /torneo/i.test(control.id)) return;
      if (parametros.has('buscar') && /buscar/i.test(control.id)) return;
      const valor = datos[control.id];
      if (control.type === 'checkbox' || control.type === 'radio') {
        if (control.checked !== Boolean(valor)) {
          control.checked = Boolean(valor);
          cambios += 1;
        }
      } else if (Array.from(control.options || []).some(function (opcion) { return String(opcion.value) === String(valor); }) || control.tagName === 'INPUT') {
        if (String(control.value) !== String(valor)) {
          control.value = valor;
          cambios += 1;
        }
      }
    });
    if (cambios) {
      controlesPersistibles().forEach(function (control) {
        control.dispatchEvent(new Event(control.tagName === 'INPUT' ? 'input' : 'change', {bubbles: true}));
      });
      const primerResumen = document.querySelector('.resumen-filtros-ah,.estado-filtros-publicos');
      if (primerResumen && !primerResumen.querySelector('.estado-filtros-persistentes-ah')) {
        const estado = document.createElement('span');
        estado.className = 'estado-filtros-persistentes-ah';
        estado.textContent = 'Filtros restaurados';
        primerResumen.appendChild(estado);
      }
    }
  }

  function activarFiltrosPersistentes() {
    const controles = controlesPersistibles();
    if (!controles.length) return;
    controles.forEach(function (control) {
      control.addEventListener(control.tagName === 'INPUT' ? 'input' : 'change', guardarFiltrosPersistentes);
    });
    window.addEventListener('pagehide', guardarFiltrosPersistentes);
    window.setTimeout(restaurarFiltrosPersistentes, 450);
    window.setTimeout(restaurarFiltrosPersistentes, 1200);
  }

  function aplicarBusquedaDesdeUrl() {
    const valor = new URLSearchParams(window.location.search).get('buscar');
    if (!valor) return;
    const candidatos = ['buscarDisciplina', 'buscarEquipo', 'buscarTorneo'];
    window.setTimeout(function () {
      const control = candidatos.map(function (id) { return document.getElementById(id); }).find(Boolean);
      if (!control) return;
      control.value = valor;
      control.dispatchEvent(new Event('input', {bubbles: true}));
      control.focus();
    }, 700);
  }

  function activarSeguimientoRecientes() {
    document.addEventListener('click', function (evento) {
      const enlace = evento.target.closest('a[href*="torneo="],a[href*="detalle="]');
      if (!enlace) return;
      const texto = enlace.closest('.tarjeta-torneo') && enlace.closest('.tarjeta-torneo').querySelector('.nombre-torneo');
      if (texto) registrarReciente({url: enlace.getAttribute('href'), titulo: texto.textContent.trim(), tipo: 'Torneo'});
    });
  }

  window.ArenaCJDSkeleton = {
    mostrar: function (contenedor, cantidad) {
      if (!contenedor) return;
      const total = Math.max(1, Math.min(Number(cantidad || 3), 8));
      contenedor.innerHTML = Array.from({length: total}, function () {
        return '<div class="skeleton-ah skeleton-tarjeta-ah" aria-hidden="true"></div>';
      }).join('');
      contenedor.setAttribute('aria-busy', 'true');
    },
    ocultar: function (contenedor) {
      if (contenedor) contenedor.removeAttribute('aria-busy');
    }
  };

  window.ArenaCJDGuardado = function (elemento, estado, texto) {
    if (!elemento) return;
    elemento.classList.add('estado-guardado-ah');
    elemento.dataset.estado = estado || 'guardado';
    elemento.textContent = texto || (estado === 'pendiente' ? 'Cambios sin guardar' : (estado === 'error' ? 'No se pudo guardar' : 'Guardado'));
  };

  window.ArenaCJDResaltar = function (elemento) {
    if (!elemento) return;
    elemento.classList.remove('resaltado-actualizacion-ah');
    void elemento.offsetWidth;
    elemento.classList.add('resaltado-actualizacion-ah');
    window.setTimeout(function () { elemento.classList.remove('resaltado-actualizacion-ah'); }, 1900);
  };
window.ArenaCJDRecientes = {
    lista: listaRecientes,
    registrar: registrarReciente
  };

  insertarSaltoContenido();
  crearBusquedaGlobal();
  crearPanelAyuda();
  activarAtajos();
  crearBannerConexion();
  crearBarraContextoTorneo();
  crearOnboarding();
  crearAccesosPanel();
  activarFiltrosPersistentes();
  aplicarBusquedaDesdeUrl();
  activarSeguimientoRecientes();
}());
