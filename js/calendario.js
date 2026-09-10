(function () {
  'use strict';

  const el = {
    torneo: document.getElementById('filtroTorneoCalendario'),
    disciplina: document.getElementById('filtroDisciplinaCalendario'),
    estado: document.getElementById('filtroEstadoCalendario'),
    desde: document.getElementById('filtroDesdeCalendario'),
    hasta: document.getElementById('filtroHastaCalendario'),
    aplicar: document.getElementById('aplicarFiltrosCalendario'),
    limpiar: document.getElementById('limpiarFiltrosCalendario'),
    resumen: document.getElementById('resumenCalendario'),
    tituloMes: document.getElementById('tituloMesCalendario'),
    cuadricula: document.getElementById('cuadriculaMesCalendario'),
    vistaMes: document.getElementById('vistaMesCalendario'),
    vistaLista: document.getElementById('vistaListaCalendario'),
    botonMes: document.getElementById('verMesCalendario'),
    botonLista: document.getElementById('verListaCalendario'),
    anterior: document.getElementById('mesAnteriorCalendario'),
    siguiente: document.getElementById('mesSiguienteCalendario'),
    hoy: document.getElementById('irHoyCalendario'),
    destacados: document.getElementById('eventosDestacadosCalendario'),
    proximos: document.getElementById('listaProximosCalendario'),
    contador: document.getElementById('contadorProximosCalendario'),
    mostrarTodos: document.getElementById('mostrarTodosCalendario'),
    vacio: document.getElementById('mensajeCalendarioVacio'),
    paginacion: document.getElementById('paginacionCalendario'),
    exportar: document.getElementById('exportarCalendario')
  };
  if (!el.cuadricula) return;

  let torneos = [];
  let eventos = [];
  let filtrados = [];
  let fechaVisible = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
  let pagina = 1;
  let porPagina = 10;
  let torneoPredeterminadoCalendario = '';
  let preferenciaTorneoAplicada = false;
  let cargando = false;
  let primeraCargaCalendario = true;

  function esc(v) {
    return String(v == null ? '' : v).replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }

  function fechaLocal(valor) {
    if (!valor) return null;
    const d = new Date(String(valor).replace(' ', 'T'));
    return Number.isNaN(d.getTime()) ? null : d;
  }

  function ymd(valor) {
    return String(valor || '').slice(0, 10);
  }

  function fechaTexto(valor, incluirHora) {
    const d = fechaLocal(valor);
    if (!d) return '-';
    return new Intl.DateTimeFormat('es-UY', incluirHora
      ? {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}
      : {day:'2-digit',month:'2-digit',year:'numeric'}).format(d);
  }

  function estadoTexto(v) {
    return {
      borrador:'Borrador', inscripciones:'Inscripciones', en_curso:'En curso',
      finalizado:'Finalizado', cancelado:'Cancelado', programado:'Programado'
    }[v] || v;
  }

  function claseEstadoCalendario(estado) {
    if (estado === 'finalizado') return 'finalizado';
    if (estado === 'cancelado') return 'suspendido';
    if (estado === 'programado' || estado === 'en_curso') return 'confirmado';
    return 'pendiente';
  }

  function tipoTexto(e) {
    return e.tipo === 'enfrentamiento' ? e.titulo
      : (e.tipo === 'inicio_torneo' ? 'Inicio del torneo' : 'Finalización del torneo');
  }

  function poblar(select, baseValor, baseTexto, valores) {
    if (!select) return;
    const anterior = select.value;
    const unicos = Array.from(new Set(valores.filter(Boolean))).sort(function(a,b){return String(a).localeCompare(String(b),'es');});
    select.innerHTML = '<option value="' + esc(baseValor) + '">' + esc(baseTexto) + '</option>' +
      unicos.map(function(v){return '<option value="' + esc(v) + '">' + esc(estadoTexto(v)) + '</option>';}).join('');
    if (Array.from(select.options).some(function(o){return o.value === anterior;})) select.value = anterior;
  }

  function poblarFiltros() {
    if (el.torneo) {
      const anterior = el.torneo.value;
      el.torneo.innerHTML = '<option value="todos">Todos los torneos</option>' +
        torneos.map(function(t){return '<option value="' + Number(t.id_torneo) + '">' + esc(t.nombre) + '</option>';}).join('');
      if (Array.from(el.torneo.options).some(function(o){return o.value === anterior;})) el.torneo.value = anterior;
    }
    poblar(el.disciplina, 'todas', 'Todas las disciplinas', torneos.map(function(t){return t.disciplina;}));
    poblar(el.estado, 'todos', 'Todos los estados', eventos.map(function(e){return e.estado;}));
  }

  function aplicar() {
    const desde = el.desde && el.desde.value;
    const hasta = el.hasta && el.hasta.value;
    filtrados = eventos.filter(function(e) {
      const f = ymd(e.fecha_hora);
      return (!el.torneo || el.torneo.value === 'todos' || String(e.id_torneo) === String(el.torneo.value))
        && (!el.disciplina || el.disciplina.value === 'todas' || e.disciplina === el.disciplina.value)
        && (!el.estado || el.estado.value === 'todos' || e.estado === el.estado.value)
        && (!desde || f >= desde)
        && (!hasta || f <= hasta);
    });
    pagina = 1;
    renderTodo();
  }

  function limpiar() {
    if (el.torneo) el.torneo.value = 'todos';
    if (el.disciplina) el.disciplina.value = 'todas';
    if (el.estado) el.estado.value = 'todos';
    if (el.desde) el.desde.value = '';
    if (el.hasta) el.hasta.value = '';
    const h = new Date();
    fechaVisible = new Date(h.getFullYear(),h.getMonth(),1);
    filtrados = eventos.slice();
    pagina = 1;
    renderTodo();
  }

  function resumen() {
    const encuentros = filtrados.filter(function(e){return e.tipo === 'enfrentamiento';});
    const resultados = encuentros.filter(function(e){return e.estado === 'finalizado';});
    const torneosUnicos = new Set(filtrados.map(function(e){return e.id_torneo;}));
    const proximos = filtrados.filter(function(e){const f=fechaLocal(e.fecha_hora); return f && f >= new Date() && e.estado !== 'cancelado';});
    const items = [
      [window.ArenaCJDIcono('torneo'), torneosUnicos.size, 'Torneos con eventos'],
      [window.ArenaCJDIcono('enfrentamientos'), encuentros.length, 'Enfrentamientos programados'],
      [window.ArenaCJDIcono('exito'), resultados.length, 'Resultados en calendario'],
      [window.ArenaCJDIcono('reloj'), proximos.length, 'Eventos próximos']
    ];
    el.resumen.innerHTML = items.map(function(i){
      return '<article class="tarjeta-resumen-calendario"><span class="icono-resumen-calendario">'+i[0]+'</span><div class="datos-resumen-calendario"><strong>'+i[1]+'</strong><span>'+esc(i[2])+'</span></div></article>';
    }).join('');
  }

  function renderMes() {
    el.tituloMes.textContent = new Intl.DateTimeFormat('es-UY',{month:'long',year:'numeric'}).format(fechaVisible);
    const anio=fechaVisible.getFullYear(), mes=fechaVisible.getMonth();
    const primero=new Date(anio,mes,1);
    const desf=(primero.getDay()+6)%7;
    const inicio=new Date(anio,mes,1-desf);
    const hoy=new Date();
    const hoyYmd=hoy.getFullYear()+'-'+String(hoy.getMonth()+1).padStart(2,'0')+'-'+String(hoy.getDate()).padStart(2,'0');
    let html='';
    for(let i=0;i<42;i++){
      const d=new Date(inicio); d.setDate(inicio.getDate()+i);
      const s=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
      const ev=filtrados.filter(function(e){return ymd(e.fecha_hora)===s;});
      const clases=['dia-calendario'];
      if(d.getMonth()!==mes)clases.push('fuera-mes');
      if(s===hoyYmd)clases.push('seleccionado');
      html+='<button class="'+clases.join(' ')+'" type="button" data-fecha="'+s+'" aria-label="'+ev.length+' eventos el '+s+'"><span class="numero-dia-calendario">'+d.getDate()+'</span><span class="indicadores-dia-calendario">'+
        ev.slice(0,4).map(function(e){return '<span class="punto-evento-calendario '+claseEstadoCalendario(e.estado)+'" title="'+esc(tipoTexto(e))+' · '+esc(estadoTexto(e.estado))+'"></span>';}).join('')+
        '</span></button>';
    }
    el.cuadricula.innerHTML=html;
    el.cuadricula.querySelectorAll('[data-fecha]').forEach(function(b){
      b.addEventListener('click',function(){
        el.desde.value=b.dataset.fecha; el.hasta.value=b.dataset.fecha; aplicar();
        if(el.botonLista)el.botonLista.click();
      });
    });
  }

  function tarjeta(e) {
    const d=fechaLocal(e.fecha_hora);
    const dia=d?d.getDate():'-';
    const mes=d?d.toLocaleDateString('es-UY',{month:'short'}).replace('.',''):'-';
    const href=e.tipo==='enfrentamiento'?'partidos.php?torneo='+Number(e.id_torneo)+'&enfrentamiento='+Number(e.id_enfrentamiento||0):'torneos.php?detalle='+Number(e.id_torneo);
    const marcador=e.tipo==='enfrentamiento' && e.estado==='finalizado' && e.puntaje_a!=null
      ? ' · '+e.puntaje_a+' - '+e.puntaje_b : '';
    const mediaTorneo=window.ArenaCJDMedia?window.ArenaCJDMedia.html('torneo',e.id_torneo,e.torneo,{clase:'media-pequena'}):'';
    return '<article class="tarjeta-enfrentamiento-calendario"><div class="fecha-enfrentamiento-calendario"><strong>'+dia+'</strong><span>'+esc(mes)+'</span></div>'+
      '<div class="datos-enfrentamiento-calendario"><div class="linea-superior-enfrentamiento"><span class="titulo-torneo-calendario-media">'+mediaTorneo+'<strong>'+esc(e.torneo)+'</strong></span><span class="estado-calendario '+claseEstadoCalendario(e.estado)+'">'+esc(estadoTexto(e.estado))+'</span></div>'+
      '<span class="disciplina-enfrentamiento-calendario">'+esc(e.disciplina)+' · '+esc(tipoTexto(e))+'</span>'+
      '<div class="detalles-enfrentamiento-calendario"><span class="ubicacion-enfrentamiento-calendario">'+esc(e.detalle||'')+esc(marcador)+' · '+esc(fechaTexto(e.fecha_hora,e.tipo==='enfrentamiento'))+'</span></div>'+
      '<a class="boton-enlace-calendario" href="'+href+'">Abrir</a></div></article>';
  }

  function renderLista() {
    el.vistaLista.innerHTML=filtrados.slice().sort(function(a,b){return String(a.fecha_hora).localeCompare(String(b.fecha_hora));})
      .map(tarjeta).join('') || '<div class="mensaje-calendario-vacio">No hay eventos para los filtros seleccionados.</div>';
  }

  function renderProximos() {
    const ahora=new Date();
    const proximos=filtrados.filter(function(e){const d=fechaLocal(e.fecha_hora); return d && d>=ahora && e.estado!=='cancelado';})
      .sort(function(a,b){return String(a.fecha_hora).localeCompare(String(b.fecha_hora));});
    const paginas=Math.max(1,Math.ceil(proximos.length/porPagina));
    if(pagina>paginas)pagina=paginas;
    const muestra=proximos.slice((pagina-1)*porPagina,pagina*porPagina);
    el.proximos.innerHTML=muestra.map(tarjeta).join('');
    el.contador.textContent=proximos.length+(proximos.length===1?' programado':' programados');
    el.vacio.hidden=proximos.length>0;
    el.proximos.hidden=proximos.length===0;
    el.paginacion.innerHTML=proximos.length<=porPagina?'':Array.from({length:paginas},function(_,i){
      return '<button class="boton-pagina-calendario'+(i+1===pagina?' activo':'')+'" type="button" data-pagina="'+(i+1)+'">'+(i+1)+'</button>';
    }).join('');
    el.paginacion.querySelectorAll('[data-pagina]').forEach(function(b){b.addEventListener('click',function(){pagina=Number(b.dataset.pagina);renderProximos();});});
  }

  function renderDestacados() {
    const ahora=new Date();
    const lista=filtrados.filter(function(e){const d=fechaLocal(e.fecha_hora);return d && d>=ahora && e.estado!=='cancelado';})
      .sort(function(a,b){return String(a.fecha_hora).localeCompare(String(b.fecha_hora));}).slice(0,6);
    el.destacados.innerHTML=lista.map(function(e){
      const d=fechaLocal(e.fecha_hora);
      const mediaTorneo=window.ArenaCJDMedia?window.ArenaCJDMedia.html('torneo',e.id_torneo,e.torneo,{clase:'media-pequena'}):'';
      return '<article class="evento-destacado-calendario"><div class="fecha-evento-destacado"><strong>'+d.getDate()+'</strong><span>'+esc(d.toLocaleDateString('es-UY',{month:'short'}).replace('.',''))+'</span></div>'+
        '<div class="media-evento-destacado-calendario">'+mediaTorneo+'</div><div class="datos-evento-destacado"><strong>'+esc(tipoTexto(e))+'</strong><span>'+esc(e.torneo)+' · '+esc(e.detalle||e.disciplina)+'</span></div></article>';
    }).join('') || '<div class="mensaje-calendario-vacio">No hay próximos eventos registrados.</div>';
  }

  function renderTodo(){resumen();renderMes();renderLista();renderProximos();renderDestacados();}

  function exportar() {
    const filas=[['Tipo','Torneo','Disciplina','Estado','Fecha y hora','Detalle']].concat(filtrados.map(function(e){
      return [tipoTexto(e),e.torneo,e.disciplina,estadoTexto(e.estado),e.fecha_hora,e.detalle||''];
    }));
    const csv=filas.map(function(f){return f.map(function(v){return '"'+String(v==null?'':v).replace(/"/g,'""')+'"';}).join(',');}).join('\n');
    const url=URL.createObjectURL(new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8'}));
    const a=document.createElement('a');a.href=url;a.download='calendario-arenacjd.csv';document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(url);
  }

  async function cargarPreferenciaPaginacion() {
    try {
      const r = await fetch('api/preferencias.php?_=' + Date.now(), {cache:'no-store'});
      const d = await r.json();
      const cantidad = Number(d && d.preferencias && d.preferencias.elementosPagina);
      if (r.ok && d.exito && d.preferencias) {
        if ([10,20,50].includes(cantidad)) porPagina = cantidad;
        torneoPredeterminadoCalendario = String(d.preferencias.torneoPredeterminado || '');
      }
    } catch (error) {
    }
  }

  async function cargar() {
    if(cargando)return;
    cargando=true;
    if(primeraCargaCalendario&&window.ArenaCJDSkeleton){
      if(el.proximos)window.ArenaCJDSkeleton.mostrar(el.proximos,4);
      if(el.destacados)window.ArenaCJDSkeleton.mostrar(el.destacados,2);
    }
    try{
      const r=await fetch('api/calendario.php?_='+Date.now(),{cache:'no-store'});
      const d=await r.json();
      if(!r.ok||!d.exito)throw new Error(d.mensaje||'No se pudo sincronizar el calendario.');
      torneos=d.datos.torneos||[]; eventos=d.datos.eventos||[];
      poblarFiltros();
      if (!preferenciaTorneoAplicada) {
        preferenciaTorneoAplicada = true;
        const torneoUrl = new URLSearchParams(window.location.search).get('torneo');
        if (!torneoUrl && torneoPredeterminadoCalendario && el.torneo) {
          const existe = Array.from(el.torneo.options).some(function (opcion) {
            return opcion.value === torneoPredeterminadoCalendario;
          });
          if (existe && (el.torneo.value === 'todos' || el.torneo.value === '')) {
            el.torneo.value = torneoPredeterminadoCalendario;
          }
        }
      }
      aplicar();
    }catch(error){
      filtrados=[]; renderTodo();
      if(window.ArenaCJDEstadoError)window.ArenaCJDEstadoError(el.vacio,error.message,cargar);
      else{el.vacio.hidden=false;el.vacio.textContent=error.message;}
    }finally{
      if(window.ArenaCJDSkeleton){
        if(el.proximos)window.ArenaCJDSkeleton.ocultar(el.proximos);
        if(el.destacados)window.ArenaCJDSkeleton.ocultar(el.destacados);
      }
      primeraCargaCalendario=false;
      cargando=false;
    }
  }

  if(el.aplicar)el.aplicar.addEventListener('click',aplicar);
  if(el.limpiar)el.limpiar.addEventListener('click',limpiar);
  if(el.botonMes)el.botonMes.addEventListener('click',function(){el.botonMes.classList.add('activo');el.botonLista.classList.remove('activo');el.vistaMes.hidden=false;el.vistaLista.hidden=true;});
  if(el.botonLista)el.botonLista.addEventListener('click',function(){el.botonLista.classList.add('activo');el.botonMes.classList.remove('activo');el.vistaLista.hidden=false;el.vistaMes.hidden=true;});
  if(el.anterior)el.anterior.addEventListener('click',function(){fechaVisible.setMonth(fechaVisible.getMonth()-1);renderMes();});
  if(el.siguiente)el.siguiente.addEventListener('click',function(){fechaVisible.setMonth(fechaVisible.getMonth()+1);renderMes();});
  if(el.hoy)el.hoy.addEventListener('click',function(){const h=new Date();fechaVisible=new Date(h.getFullYear(),h.getMonth(),1);renderMes();});
  if(el.mostrarTodos)el.mostrarTodos.addEventListener('click',limpiar);
  if(el.exportar)el.exportar.addEventListener('click',exportar);

  cargarPreferenciaPaginacion().then(cargar);
  if(window.ArenaCJDSync)window.ArenaCJDSync.registrar('calendario',cargar,8000,{ejecutarAhora:false});
}());
