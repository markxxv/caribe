// Firefox background process. All pages are read through browser tabs.
const delay=ms=>new Promise(resolve=>setTimeout(resolve,ms));
let busy=false, cancel=false;

async function status(changes) {
  const previous=(await browser.storage.local.get("job")).job||{};
  await browser.storage.local.set({job:{...previous,...changes}});
}

function canonical(input) {
  const u=new URL(input);
  if(u.protocol!=="https:" || !/(^|\.)zonaprop\.com\.ar$/.test(u.hostname))throw Error("Ссылка должна вести на Zonaprop");
  return u.origin+u.pathname.replace(/\/$/,"");
}

async function scrape(url,mode) {
  const tab=await browser.tabs.create({url,active:false});
  try{
    await new Promise((resolve,reject)=>{
      let done=false;
      function finish(err) {
        if(done)return;
        done=true;
        clearTimeout(timer);
        browser.tabs.onUpdated.removeListener(onUpdate);
        err?reject(err):resolve();
      }
      function onUpdate(id,info){if(id===tab.id&&info.status==="complete")finish();}
      const timer=setTimeout(()=>finish(Error("Не удалось дождаться загрузки страницы")),45000);
      browser.tabs.onUpdated.addListener(onUpdate);
      browser.tabs.get(tab.id).then(t=>{if(t.status==="complete")finish();}).catch(finish);
    });
    await delay(1600);
    const result=await browser.scripting.executeScript({target:{tabId:tab.id},func:scrapeZonaprop,args:[mode]});
    if(!result?.[0]?.result)throw Error("Не удалось разобрать HTML");
    return result[0].result;
  }finally{await browser.tabs.remove(tab.id).catch(()=>{});}
}

function merge(summary,detail){
  const result={...summary};
  for(const [key,value] of Object.entries(detail)){
    if(value===null||value===undefined||value==="")continue;
    if(key==="photos")continue;
    if(key==="features"||key==="amenities")result[key]={...(result[key]||{}),...value};
    else result[key]=value;
  }
  const photos=new Map();
  for(const photoUrl of [...(detail.photos||[]),...(summary.photos||[])]){
    const key=new URL(photoUrl).pathname.split("/").at(-1);
    if(!photos.has(key))photos.set(key,photoUrl);
  }
  result.photos=[...photos.values()].slice(0,40);
  result.incomplete=false;
  return result;
}

async function collect(source){
  await browser.storage.local.set({items:[]});
  await status({running:true,phase:"listing",processed:0,total:0,uploaded:0,errors:[],message:""});
  const page=await scrape(canonical(source),"listing");
  if(!page.items?.length)throw Error("Объекты на этой странице не найдены");
  const gathered=[],errors=[];
  await status({phase:"details",total:page.items.length});
  for(let i=0;i<page.items.length;i++){
    if(cancel)break;
    const item=page.items[i];
    await status({current:item.source_url});
    try{
      gathered.push(merge(item,await scrape(item.source_url,"detail")));
    }catch(e){
      gathered.push({...item,incomplete:true,scrape_error:String(e.message||e)});
      errors.push({url:item.source_url,message:String(e.message||e)});
    }
    await browser.storage.local.set({items:gathered});
    await status({processed:i+1,errors});
    if(!cancel)await delay(1600);
  }
  return gathered;
}

async function upload(endpoint,token){
  const all=(await browser.storage.local.get("items")).items||[];
  const items=all.filter(x=>x.source_url&&x.title&&!x.incomplete);
  if(!items.length)throw Error("Нет готового JSON. Сначала соберите данные");
  if(!/^https:\/\/[^/]+\/api\/realestate\/import\/?$/i.test(endpoint)
    &&!/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?\/api\/realestate\/import\/?$/i.test(endpoint))
    throw Error("Нужен полный URL /api/realestate/import (HTTPS)");
  if(!token)throw Error("Введите токен");
  const completed=[],errors=[];
  await status({running:true,phase:"upload",processed:0,total:items.length,uploaded:0,errors});
  for(let i=0;i<items.length;i++){
    if(cancel)break;
    const item=items[i];
    await status({current:item.source_url});
    try{
      let data;
      for(let tryCount=0;tryCount<3;tryCount++){
        const response=await fetch(endpoint,{
          method:"POST",
          headers:{"Authorization":"Bearer "+token,"Content-Type":"application/json","Accept":"application/json"},
          body:JSON.stringify({items:[item]})
        });
        if([429,503].includes(response.status)&&tryCount<2){await delay((tryCount+1)*5000);continue;}
        data=await response.json().catch(()=>({message:"API вернул не-JSON ответ"}));
        if(!response.ok&&response.status!==207)throw Error(data.message||"HTTP "+response.status);
        if(data.errors?.length)throw Error(data.errors[0].message||"Ошибка записи");
        break;
      }
      completed.push({source_url:item.source_url,id:data.items?.[0]?.id,warnings:data.items?.[0]?.warnings||[]});
    }catch(e){errors.push({url:item.source_url,message:String(e.message||e)});}
    await status({processed:i+1,uploaded:completed.length,errors});
  }
  await browser.storage.local.set({results:completed});
}

async function process(message){
  busy=true;cancel=false;
  try{
    if(message.type==="collect"){
      const items=await collect(message.url);
      if(message.send&&!cancel&&items.some(x=>!x.incomplete))await upload(message.endpoint,message.token);
    }else await upload(message.endpoint,message.token);
    await status({running:false,phase:cancel?"canceled":"done",current:null});
  }catch(e){await status({running:false,phase:"error",message:String(e.message||e),current:null});}
  finally{busy=false;}
}
browser.runtime.onMessage.addListener(message=>{
  if(message?.type==="cancel"){cancel=true;return Promise.resolve({ok:true});}
  if(!["collect","upload"].includes(message?.type))return false;
  if(busy)return Promise.resolve({ok:false,error:"Задача уже выполняется"});
  process(message);
  return Promise.resolve({ok:true});
});
