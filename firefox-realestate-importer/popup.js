const field=id=>document.getElementById(id);
let count=0;
function paint(job,items){
  const names={listing:"Загрузка выдачи",details:"Сбор карточек",upload:"Импорт в Laravel",
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
    });
  }

  if(activeUrl && activeUrl!==stored.sourceUrl){
    await browser.storage.local.set({sourceUrl:activeUrl});
  }

  paint(stored.job||{},stored.items||[]);
}
async function execute(type,send=false){
  try{
    const source=field("source").value.trim(),endpoint=field("endpoint").value.trim(),token=field("token").value.trim();
    if(type==="collect"){
      const u=new URL(source);
      if(u.protocol!=="https:"||!/(^|\.)zonaprop\.com\.ar$/.test(u.hostname))throw Error("Нужна ссылка Zonaprop");
    }
    if(send||type==="upload"){
      const u=new URL(endpoint);
      if(u.protocol!=="https:"&&!["localhost","127.0.0.1"].includes(u.hostname))throw Error("Для API требуется HTTPS");
      // Requested directly on the user click; only this endpoint host is granted.
      const hostPattern=u.protocol+"//"+u.hostname+"/*";
      if(!await browser.permissions.request({origins:[hostPattern]}))throw Error("Нет разрешения для API");
      if(!token)throw Error("Введите API токен");
    }
    await browser.storage.local.set({sourceUrl:source,endpointUrl:endpoint,apiToken:token});
    const response=await browser.runtime.sendMessage({type,url:source,endpoint,token,send});
    if(!response?.ok)throw Error(response?.error||"Ошибка запуска");
    await refresh();
  }catch(e){field("phase").textContent="Ошибка запуска";field("errors").textContent=String(e.message||e);}
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
