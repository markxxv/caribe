const field=id=>document.getElementById(id);
let count=0;

function classifyUrl(value){
  try{
    const url=new URL(value);
    if(url.protocol!=="https:"||!/(^|\.)zonaprop\.com\.ar$/.test(url.hostname))return "unknown";
    return /^\/propiedades\/[^/]+/i.test(url.pathname) ? "detail" : "listing";
  }catch{return "unknown";}
}

function parseSourceUrl(value){
  if(!value)throw Error("Укажите URL страницы Zonaprop.");
  let url;
  try{url=new URL(value);}catch{throw Error("Некорректный URL страницы Zonaprop.");}
  if(url.protocol!=="https:"||!/(^|\.)zonaprop\.com\.ar$/.test(url.hostname))
    throw Error("Ссылка должна вести на zonaprop.com.ar по HTTPS.");
  return url;
}

function parseApiUrl(value){
  if(!value)throw Error("Laravel API endpoint не заполнен. Вставьте адрес /api/realestate/import.");
  let url;
  try{url=new URL(value);}catch{throw Error("Некорректный Laravel API endpoint. Нужен полный адрес https://.../api/realestate/import.");}
  if(url.protocol!=="https:"&&!(url.protocol==="http:"&&["localhost","127.0.0.1"].includes(url.hostname)))
    throw Error("Для Laravel API требуется HTTPS (исключение — localhost).");
  if(url.pathname.replace(/\/+$/,"")!=="/api/realestate/import")
    throw Error("Laravel API endpoint должен заканчиваться на /api/realestate/import.");
  if(url.username||url.password)throw Error("Уберите логин и пароль из адреса API.");
  return url;
}

function updatePageMode(){
  const mode=classifyUrl(field("source").value.trim());
  field("pageType").textContent=mode==="detail" ? "Отдельный объект"
    : mode==="listing" ? "Страница выдачи" : "Вставьте ссылку Zonaprop";
  field("collect").textContent=mode==="detail" ? "Собрать JSON объекта" : "Собрать JSON";
  field("collectSend").textContent=mode==="detail" ? "Импортировать объект в Laravel" : "Собрать и отправить в Laravel";
  field("collect").classList.toggle("primary",mode!=="detail");
  field("collectSend").classList.toggle("primary",mode==="detail");
}
function paint(job,items){
  const names={detecting:"Определение страницы",listing:"Загрузка выдачи",details:"Сбор карточек",upload:"Импорт в Laravel",
    done:"Завершено",canceled:"Остановлено",error:"Ошибка"};
  field("phase").textContent=names[job.phase]||"Готово к запуску";
  field("current").textContent=job.current||job.message||"";
  field("progress").max=Math.max(job.total||1,1);
  field("progress").value=Math.min(job.processed||0,field("progress").max);
  count=items.length;
  field("count").textContent="Собрано: "+count+" · Обработано: "+(job.processed||0)+"/"+
    (job.total||0)+" · Загружено: "+(job.uploaded||0);
  const errors=job.errors||[];
  field("errors").textContent=errors.length?"Ошибок: "+errors.length+" · "+errors.at(-1).message:"";
  for(const id of ["collect","collectSend","upload"])field(id).disabled=Boolean(job.running);
  field("upload").disabled=Boolean(job.running)||!items.some(item=>!item.incomplete);
  field("download").disabled=items.length===0;
  field("cancel").hidden=!job.running;
}
const settingsKeys = {source:"sourceUrl",endpoint:"endpointUrl",token:"apiToken"};

async function refresh(){
  const data=await browser.storage.local.get(["job","items"]);
  paint(data.job||{},data.items||[]);
}

async function initialize(){
  const stored=await browser.storage.local.get([
    "job","items","settings","sourceUrl","endpointUrl","apiToken"
  ]);

  let activeUrl=null;
  try{
    const [tab]=await browser.tabs.query({active:true,currentWindow:true});
    if(tab?.url){
      const u=new URL(tab.url);
      if(u.protocol==="https:" && /(^|\.)zonaprop\.com\.ar$/.test(u.hostname)){
        activeUrl=u.href;
      }
    }
  }catch{
    // In a standalone extension tab, fall back to the most recent source URL.
  }

  field("source").value=activeUrl||stored.sourceUrl||stored.settings?.source||"";
  field("endpoint").value=stored.endpointUrl||stored.settings?.endpoint||"";
  field("token").value=stored.apiToken||"";

  // Save on every input, not only after clicking Start. Closing the popup cannot
  // discard the endpoint or token that was just pasted.
  for(const [id,key] of Object.entries(settingsKeys)){
    field(id).addEventListener("input",()=>{
      browser.storage.local.set({[key]:field(id).value}).catch(console.error);
      if(id==="source")updatePageMode();
    });
  }

  if(activeUrl && activeUrl!==stored.sourceUrl){
    await browser.storage.local.set({sourceUrl:activeUrl});
  }

  updatePageMode();
  paint(stored.job||{},stored.items||[]);
}
async function execute(type,send=false){
  const source=field("source").value.trim();
  const endpoint=field("endpoint").value.trim();
  const token=field("token").value.trim();

  try{
    if(type==="collect")parseSourceUrl(source);

    if(send||type==="upload"){
      // Validate both inputs before the permission prompt. Never construct
      // URL("") or wipe saved credentials when collecting JSON only.
      const u=parseApiUrl(endpoint);
      if(!token){
        field("token").focus();
        throw Error("Введите API токен. Он будет сохранён в Firefox.");
      }
      // Permission must be requested directly from the button click.
      const hostPattern=u.protocol+"//"+u.hostname+"/*";
      if(!await browser.permissions.request({origins:[hostPattern]}))
        throw Error("Firefox не дал расширению доступ к Laravel API.");
    }

    const settings={};
    if(type==="collect")settings.sourceUrl=source;
    if(send||type==="upload"){
      settings.endpointUrl=endpoint;
      settings.apiToken=token;
    }
    if(Object.keys(settings).length)await browser.storage.local.set(settings);

    const response=await browser.runtime.sendMessage({type,url:source,endpoint,token,send});
    if(!response?.ok)throw Error(response?.error||"Ошибка запуска");
    await refresh();
  }catch(e){
    field("phase").textContent="Ошибка запуска";
    field("current").textContent="";
    field("errors").textContent=String(e.message||e);
    field("progress").max=1;
    field("progress").value=0;
    field("count").textContent="Новый запуск не начался · ранее собрано: "+count;
    if(type==="collect" && !source)field("source").focus();
    else if((send||type==="upload")&&!endpoint)field("endpoint").focus();
  }
}
field("collect").onclick=()=>execute("collect");
field("collectSend").onclick=()=>execute("collect",true);
field("upload").onclick=()=>execute("upload");
field("cancel").onclick=()=>browser.runtime.sendMessage({type:"cancel"});
field("panel").onclick=()=>browser.tabs.create({url:browser.runtime.getURL("popup.html"),active:true});
field("download").onclick=async()=>{
  const items=(await browser.storage.local.get("items")).items||[];
  if(!items.length)return;
  const blob=new Blob([JSON.stringify({source_platform:"zonaprop",exported_at:new Date().toISOString(),items},null,2)],{type:"application/json"});
  const url=URL.createObjectURL(blob);
  const a=document.createElement("a");
  a.href=url;a.download="zonaprop-"+new Date().toISOString().slice(0,10)+".json";
  document.body.append(a);a.click();a.remove();
  setTimeout(()=>URL.revokeObjectURL(url),5000);
};
browser.storage.onChanged.addListener((changes,area)=>{
  if(area==="local"&&(changes.job||changes.items))refresh();
});
initialize().catch(error=>{
  field("phase").textContent="Ошибка загрузки настроек";
  field("errors").textContent=String(error.message||error);
});
