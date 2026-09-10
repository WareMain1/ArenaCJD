const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
process.env.TZ = 'America/Montevideo';

async function probarTema(temaLogin = null) {
  const almacen = new Map([['temaArenaCJD', 'claro']]);
  const solicitudes = [];
  const servidor = { tema:temaLogin === 'claro' ? 'oscuro' : 'claro', elementosPagina:'10', torneoPredeterminado:'' };
  const raiz = {dataset:{},style:{}};
  const documento = {documentElement:raiz,body:{dataset:{sesionArenaCjd:JSON.stringify({usuario:{id:1},csrf_token:'prueba'})},classList:{contains:()=>true}},querySelectorAll:()=>[],addEventListener:()=>{}};
  const ventana = {matchMedia:()=>({matches:false,addEventListener:()=>{}}),addEventListener:()=>{}};
  const temporal = new Map(temaLogin ? [['temaArenaCJDTrasLogin',temaLogin]] : []);
  documento.getElementById = () => ({parentElement:documento.body});
  vm.runInNewContext(fs.readFileSync('js/tema.js','utf8'), {
    window:ventana,document:documento,localStorage:{getItem:k=>almacen.get(k),setItem:(k,v)=>almacen.set(k,v)},
    sessionStorage:{getItem:k=>temporal.get(k),setItem:(k,v)=>temporal.set(k,v),removeItem:k=>temporal.delete(k)},
    fetch:async (url,opciones)=>{
      if(opciones.method==='POST') {
        const datos=JSON.parse(opciones.body); solicitudes.push(datos); Object.assign(servidor,datos);
      }
      return {ok:true,json:async()=>({exito:true,preferencias:{...servidor},csrf_token:'prueba'})};
    }
  });
  for(let i=0;i<8;i++) await new Promise(resolve=>setImmediate(resolve));
  if (temaLogin) {
    assert.equal(raiz.dataset.preferenciaTema,temaLogin);
    assert.equal(servidor.tema,temaLogin);
    assert.equal(servidor.elementosPagina,'10');
    assert.equal(temporal.has('temaArenaCJDTrasLogin'),false);
    assert.deepEqual(solicitudes,[{tema:temaLogin}]);
    solicitudes.length=0;
  }
  // Otra pantalla guarda paginación después de la carga inicial del tema.
  servidor.elementosPagina='20';
  ventana.ArenaCJDTema.aplicar('oscuro');
  ventana.ArenaCJDTema.aplicar('sistema');
  ventana.ArenaCJDTema.aplicar('claro');
  for(let i=0;i<30;i++) await new Promise(resolve=>setImmediate(resolve));
  assert.equal(servidor.elementosPagina,'20');
  assert.deepEqual(solicitudes,[{tema:'oscuro'},{tema:'sistema'},{tema:'claro'}]);
  assert.equal(servidor.tema,'claro');
  assert.equal(raiz.dataset.preferenciaTema,'claro');
}

function probarFechas() {
  const fuente=fs.readFileSync('js/area-publica.js','utf8');
  const funcion=fuente.slice(fuente.indexOf('  function fecha('),fuente.indexOf('  function etiquetaEstado('));
  const contexto={Date,Intl};vm.createContext(contexto);vm.runInContext(funcion,contexto);
  for(const [entrada,esperada] of [['2026-08-31','31 ago. 2026'],['2024-02-29','29 feb. 2024'],['2026-01-01','1 ene. 2026']]) {
    assert.equal(contexto.fecha(entrada,false),esperada);
  }
  assert.equal(contexto.fecha('',false),'Sin fecha');
  assert.equal(contexto.fecha('sin-fecha',false),'sin-fecha');
  assert.match(contexto.fecha('2026-08-31 21:00:00',true),/^31 ago\. 2026/);
}

(async()=>{probarFechas();await probarTema();for(const tema of ['claro','oscuro','sistema']) await probarTema(tema);console.log('Fechas, tema tras login (claro/oscuro/sistema), cambios rápidos y aislamiento de preferencias: correctos.');})().catch(e=>{console.error(e);process.exitCode=1;});
