// Firefox MV3 background script. Browser tabs are used to read Zonaprop.
const API_TIMEOUT_MS = 120000;
const PAGE_TIMEOUT_MS = 45000;
const MAX_UPLOAD_ATTEMPTS = 3;

let busy = false;
let cancel = false;
let runController = null;

async function status(changes) {
  const previous = (await browser.storage.local.get("job")).job || {};
  await browser.storage.local.set({job: {...previous, ...changes}});
}

// MV3 background scripts can be suspended and restarted. A stored "running"
// status is not proof that the old job still exists.
const ready = browser.storage.local.get("job").then(async ({job}) => {
  if (job?.running) {
    await browser.storage.local.set({
      job: {...job, running: false, phase: "interrupted", current: null,
        message: "Предыдущая задача прервалась при перезапуске расширения. Собранный JSON сохранён."}
    });
  }
}).catch(error => console.error("Importer recovery failed", error));

function stopped(signal) {
  if (signal.aborted || cancel) throw Error("Импорт остановлен");
}

function delay(ms, signal) {
  return new Promise((resolve, reject) => {
    if (signal.aborted) return reject(Error("Импорт остановлен"));
    const timer = setTimeout(() => finish(), ms);
    function abort() { finish(Error("Импорт остановлен")); }
    function finish(error) {
      clearTimeout(timer);
      signal.removeEventListener("abort", abort);
      error ? reject(error) : resolve();
    }
    signal.addEventListener("abort", abort, {once: true});
  });
}

function withCancellation(promise, signal) {
  return new Promise((resolve, reject) => {
    if (signal.aborted) return reject(Error("Импорт остановлен"));
    const abort = () => finish(Error("Импорт остановлен"));
    function finish(error, value) {
      signal.removeEventListener("abort", abort);
      error ? reject(error) : resolve(value);
    }
    signal.addEventListener("abort", abort, {once: true});
    Promise.resolve(promise).then(value => finish(null, value), error => finish(error));
  });
}

function canonical(input) {
  const u = new URL(input);
  if (u.protocol !== "https:" || !/(^|\.)zonaprop\.com\.ar$/.test(u.hostname))
    throw Error("Ссылка должна вести на Zonaprop");
  return u.origin + u.pathname.replace(/\/$/, "");
}

async function scrape(url, mode, signal) {
  stopped(signal);
  const tab = await browser.tabs.create({url, active: false});
  try {
    stopped(signal);
    await new Promise((resolve, reject) => {
      let done = false;
      const timer = setTimeout(() => finish(Error("Страница Zonaprop не загрузилась за 45 секунд")), PAGE_TIMEOUT_MS);
      function finish(error) {
        if (done) return;
        done = true;
        clearTimeout(timer);
        browser.tabs.onUpdated.removeListener(onUpdate);
        signal.removeEventListener("abort", onAbort);
        error ? reject(error) : resolve();
      }
      function onUpdate(id, info) {
        if (id === tab.id && info.status === "complete") finish();
      }
      function onAbort() { finish(Error("Импорт остановлен")); }

      browser.tabs.onUpdated.addListener(onUpdate);
      signal.addEventListener("abort", onAbort, {once: true});
      if (signal.aborted) return onAbort();

      browser.tabs.get(tab.id)
        .then(t => { if (t.status === "complete") finish(); })
        .catch(finish);
    });

    await delay(1600, signal);
    const result = await withCancellation(
      browser.scripting.executeScript({target: {tabId: tab.id}, func: scrapeZonaprop, args: [mode]}),
      signal
    );
    stopped(signal);
    if (!result?.[0]?.result) throw Error("Не удалось разобрать HTML Zonaprop");
    return result[0].result;
  } finally {
    await browser.tabs.remove(tab.id).catch(() => {});
  }
}

function merge(summary, detail) {
  const result = {...summary};
  for (const [key, value] of Object.entries(detail)) {
    if (value === null || value === undefined || value === "") continue;
    if (key === "photos") continue;
    if (key === "features" || key === "amenities") result[key] = {...(result[key] || {}), ...value};
    else result[key] = value;
  }
  const photos = new Map();
  for (const photoUrl of [...(detail.photos || []), ...(summary.photos || [])]) {
    const key = new URL(photoUrl).pathname.split("/").at(-1);
    if (!photos.has(key)) photos.set(key, photoUrl);
  }
  result.photos = [...photos.values()].slice(0, 40);
  result.incomplete = false;
  return result;
}

async function collect(source, signal) {
  await browser.storage.local.set({items: [], results: []});
  await status({running: true, phase: "detecting", processed: 0, total: 0,
    uploaded: 0, errors: [], message: "", current: null});

  const first = await scrape(canonical(source), "auto", signal);
  stopped(signal);

  if (first.mode === "detail") {
    const item = first.data;
    if (!item?.source_url || !item.title) throw Error("Не удалось извлечь данные объявления");
    const items = [{...item, incomplete: false}];
    await browser.storage.local.set({items});
    await status({phase: "details", processed: 1, total: 1, current: null});
    return items;
  }

  if (first.mode !== "listing" || !first.data?.items?.length)
    throw Error("На странице выдачи объявления не найдены");

  const gathered = [], errors = [];
  await status({phase: "details", total: first.data.items.length, processed: 0});

  for (let i = 0; i < first.data.items.length; i++) {
    stopped(signal);
    const item = first.data.items[i];
    await status({current: item.source_url});

    try {
      gathered.push(merge(item, await scrape(item.source_url, "detail", signal)));
    } catch (error) {
      stopped(signal);
      gathered.push({...item, incomplete: true, scrape_error: String(error.message || error)});
      errors.push({url: item.source_url, message: String(error.message || error)});
    }

    await browser.storage.local.set({items: gathered});
    await status({processed: i + 1, errors});
    if (i + 1 < first.data.items.length) await delay(1600, signal);
  }
  return gathered;
}

function apiErrors(data) {
  if (Array.isArray(data?.errors) && data.errors.length)
    return data.errors.map(error => error.message || String(error)).join("; ");

  if (data?.errors && typeof data.errors === "object")
    return Object.entries(data.errors)
      .map(([field, messages]) => field + ": " + (Array.isArray(messages) ? messages.join(", ") : String(messages)))
      .join("; ");

  return null;
}

async function sendItem(endpoint, token, item, signal) {
  stopped(signal);
  const controller = new AbortController();
  let timeout = false;
  const onStop = () => controller.abort();
  signal.addEventListener("abort", onStop, {once: true});

  const timer = setTimeout(() => {
    timeout = true;
    controller.abort();
  }, API_TIMEOUT_MS);

  try {
    const response = await fetch(endpoint, {
      method: "POST",
      signal: controller.signal,
      headers: {
        Authorization: "Bearer " + token,
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({items: [item]}),
    });

    const data = await response.json().catch(() => null);
    stopped(signal);

    if (!data) throw Error("HTTP " + response.status + ": Laravel вернул не-JSON ответ");
    const details = apiErrors(data);
    if (details) throw Error("HTTP " + response.status + ": " + details);
    if (!response.ok && response.status !== 207)
      throw Error("HTTP " + response.status + ": " + (data.message || response.statusText));
    if (!Array.isArray(data.items) || !data.items[0]?.id)
      throw Error("HTTP " + response.status + ": Laravel не подтвердил сохранение объекта");

    return {data, status: response.status};
  } catch (error) {
    if (signal.aborted || cancel) throw Error("Импорт остановлен");
    if (timeout) throw Error("Laravel не ответил за 120 секунд. Проверьте /log-viewer; операция на сервере могла завершиться.");
    throw error;
  } finally {
    clearTimeout(timer);
    signal.removeEventListener("abort", onStop);
  }
}

async function upload(endpoint, token, signal) {
  const all = (await browser.storage.local.get("items")).items || [];
  const items = all.filter(item => item.source_url && item.title && !item.incomplete);

  if (!items.length) throw Error("Нет готового JSON. Сначала соберите данные");
  if (!/^https:\/\/[^/]+\/api\/realestate\/import\/?$/i.test(endpoint)
    && !/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?\/api\/realestate\/import\/?$/i.test(endpoint))
    throw Error("Нужен полный URL /api/realestate/import (HTTPS)");
  if (!token) throw Error("Введите API токен");

  const completed = [], errors = [];
  await status({running: true, phase: "upload", processed: 0, total: items.length,
    uploaded: 0, errors, current: null, message: ""});

  for (let i = 0; i < items.length; i++) {
    stopped(signal);
    const item = items[i];
    await status({current: item.source_url});

    try {
      let saved = false;
      for (let attempt = 0; attempt < MAX_UPLOAD_ATTEMPTS; attempt++) {
        stopped(signal);
        try {
          const result = await sendItem(endpoint, token, item, signal);
          completed.push({
            source_url: item.source_url,
            id: result.data.items[0].id,
            warnings: result.data.items[0].warnings || [],
          });
          saved = true;
          break;
        } catch (error) {
          stopped(signal);
          // Only retry explicitly temporary HTTP statuses. Never retry
          // timeouts: the server may have saved the record already.
          if (attempt < MAX_UPLOAD_ATTEMPTS - 1 && /^HTTP (429|503):/.test(error.message)) {
            await status({message: "Повторная попытка " + (attempt + 2) + " для " + item.source_url});
            await delay((attempt + 1) * 5000, signal);
            continue;
          }
          throw error;
        }
      }
      if (!saved) throw Error("Laravel не подтвердил импорт");
    } catch (error) {
      stopped(signal);
      errors.push({url: item.source_url, message: String(error.message || error)});
    }

    await browser.storage.local.set({results: completed});
    await status({processed: i + 1, uploaded: completed.length, errors, message: ""});
  }

  return {uploaded: completed.length, failed: errors.length};
}

async function process(message) {
  busy = true;
  cancel = false;
  const controller = new AbortController();
  runController = controller;

  try {
    await ready;
    stopped(controller.signal);

    let result = {failed: 0};
    if (message.type === "collect") {
      const items = await collect(message.url, controller.signal);
      stopped(controller.signal);
      if (message.send && items.some(item => !item.incomplete)) {
        result = await upload(message.endpoint, message.token, controller.signal);
      }
    } else {
      result = await upload(message.endpoint, message.token, controller.signal);
    }

    stopped(controller.signal);
    await status({running: false,
      phase: result.failed > 0 ? "done_with_errors" : "done", current: null, message: ""});
  } catch (error) {
    await status({
      running: false,
      phase: cancel || controller.signal.aborted ? "canceled" : "error",
      message: cancel || controller.signal.aborted ? "Остановлено пользователем" : String(error.message || error),
      current: null,
    });
  } finally {
    runController = null;
    busy = false;
  }
}

browser.runtime.onMessage.addListener(message => {
  if (message?.type === "cancel") {
    cancel = true;
    runController?.abort();
    if (busy) {
      status({phase: "stopping", message: "Остановка текущего запроса…"}).catch(console.error);
    }
    return Promise.resolve({ok: true});
  }

  if (!["collect", "upload"].includes(message?.type)) return false;
  if (busy) return Promise.resolve({ok: false, error: "Задача уже выполняется"});

  process(message).catch(error => {
    console.error("Importer fatal error", error);
    busy = false;
    runController = null;
    status({running: false, phase: "error", current: null,
      message: String(error.message || error)}).catch(console.error);
  });

  return Promise.resolve({ok: true});
});
