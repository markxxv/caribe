// Firefox MV3 background script. Browser tabs are used to read Zonaprop.
const API_TIMEOUT_MS = 120000;
const PAGE_TIMEOUT_MS = 45000;
const MAX_UPLOAD_ATTEMPTS = 3;
// Laravel rate limiting: metadata 20/min, photos 120/min.
// Use a conservative pace and honor Retry-After on HTTP 429.
const METADATA_SPACING_MS = 3500;
const PHOTO_SPACING_MS = 850;
const MAX_PHOTO_429_RETRIES = 2;
const DEFAULT_429_DELAY_MS = 15000;
let lastMetadataRequest = 0;
let lastPhotoRequest = 0;
const IMAGE_DOWNLOAD_TIMEOUT_MS = 12000;
const IMAGE_UPLOAD_TIMEOUT_MS = 30000;
const IMAGE_BATCH_LIMIT_MS = 120000;
const MAX_IMAGE_BYTES = 15 * 1024 * 1024;


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

async function paceRequest(type, signal) {
  const now = Date.now();
  const last = type === "metadata" ? lastMetadataRequest : lastPhotoRequest;
  const gap = type === "metadata" ? METADATA_SPACING_MS : PHOTO_SPACING_MS;
  if (last) await delay(Math.max(0, last + gap - now), signal);
  stopped(signal);
  if (type === "metadata") lastMetadataRequest = Date.now();
  else lastPhotoRequest = Date.now();
}

function retryAfterMs(response, attempt = 0) {
  const raw = response.headers?.get("retry-after");
  if (raw != null && raw.trim() !== "") {
    const seconds = Number(raw);
    const wait = Number.isFinite(seconds) && seconds >= 0
      ? seconds * 1000
      : Date.parse(raw) - Date.now();
    if (Number.isFinite(wait) && wait >= 0)
      return Math.min(Math.max(1000, Math.ceil(wait) + 1000), 90000);
  }
  return Math.min(DEFAULT_429_DELAY_MS * (attempt + 1), 60000);
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
  // The complete gallery from posting pictures is authoritative.
  // Card previews must not add unrelated images.
  const sources = detail.photos_complete && detail.photos?.length
    ? detail.photos
    : [...(detail.photos || []), ...(summary.photos || [])];
  for (const photoUrl of sources) {
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
  await paceRequest("metadata", signal);
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

    // 429 can be emitted by Laravel or a reverse proxy, sometimes as HTML.
    if (response.status === 429) {
      const error = Error("HTTP 429: " + (data?.message || "Too Many Attempts"));
      error.retryAfterMs = retryAfterMs(response);
      throw error;
    }

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

async function photoRequest(operation, timeoutMs, signal) {
  stopped(signal);
  const controller = new AbortController();
  let timedOut = false;
  const onCancel = () => controller.abort();
  const timer = setTimeout(() => {
    timedOut = true;
    controller.abort();
  }, timeoutMs);

  signal.addEventListener("abort", onCancel, {once: true});

  try {
    return await operation(controller.signal);
  } catch (error) {
    if (signal.aborted || cancel) throw Error("Импорт остановлен");
    if (timedOut) throw Error("Превышено время ожидания фотографии (" + Math.ceil(timeoutMs / 1000) + " с)");
    throw error;
  } finally {
    clearTimeout(timer);
    signal.removeEventListener("abort", onCancel);
  }
}

function checkedImageUrl(value) {
  const u = new URL(value);
  if (u.protocol !== "https:" || u.hostname !== "imgar.zonapropcdn.com"
      || !u.pathname.startsWith("/avisos/") || u.username || u.password) {
    throw Error("Недопустимый URL фотографии Zonaprop");
  }
  return u;
}

async function transferPhotos(endpoint, token, item, realestateId, existingSources, signal, onProgress) {
  const images = [...new Set(item.photos || [])];
  const known = new Map((existingSources || []).filter(p => p.source_key).map(p => [p.source_key, p.source_url]));
  const warnings = [];
  const uploaded = [];
  const startedAt = Date.now();
  const photoEndpoint = new URL("/api/realestate/import/photo", endpoint).href;

  for (let i = 0; i < images.length; i++) {
    stopped(signal);
    const url = images[i];
    const remaining = IMAGE_BATCH_LIMIT_MS - (Date.now() - startedAt);

    if (remaining <= 0) {
      warnings.push("Лимит времени на фотографии (120 с). Остальные фото пропущены.");
      break;
    }

    try {
      const imageUrl = checkedImageUrl(url);
      const key = imageUrl.pathname.split("/").at(-1);

      if (known.get(key) === url) continue;

      await onProgress(i + 1, images.length, url);

      // Firefox downloads bytes from Zonaprop. Laravel performs no CDN requests.
      const blob = await photoRequest(async requestSignal => {
        const response = await fetch(url, {
          method: "GET",
          signal: requestSignal,
          credentials: "omit",
          referrerPolicy: "no-referrer",
        });

        if (!response.ok) throw Error("CDN HTTP " + response.status);

        const mime = (response.headers.get("content-type") || "").split(";")[0].toLowerCase();
        if (!["image/jpeg", "image/png", "image/webp"].includes(mime))
          throw Error("Неизвестный формат фотографии: " + mime);

        const size = Number(response.headers.get("content-length") || 0);
        if (size > MAX_IMAGE_BYTES) throw Error("Фотография превышает 15 МБ");

        const file = await response.blob();
        if (!file.size || file.size > MAX_IMAGE_BYTES)
          throw Error("Некорректный размер фотографии");

        return {file, mime};
      }, Math.min(IMAGE_DOWNLOAD_TIMEOUT_MS, remaining), signal);

      const form = new FormData();
      const extension = {"image/jpeg": "jpg", "image/png": "png", "image/webp": "webp"}[blob.mime];
      form.append("photo", blob.file, "zonaprop." + extension);
      form.append("source_url", url);
      form.append("realestate_id", String(realestateId));

      let saved;
      for (let attempt = 0; attempt <= MAX_PHOTO_429_RETRIES; attempt++) {
        stopped(signal);
        await paceRequest("photo", signal);
        try {
          saved = await photoRequest(async requestSignal => {
            const response = await fetch(photoEndpoint, {
          method: "POST",
          signal: requestSignal,
          headers: {
            Authorization: "Bearer " + token,
            Accept: "application/json",
          },
          body: form,
        });

            const data = await response.json().catch(() => null);
            if (response.status === 429) {
              const error = Error("HTTP 429: " + (data?.message || "Too Many Attempts"));
              error.retryAfterMs = retryAfterMs(response, attempt);
              throw error;
            }
            if (!data) throw Error("Laravel вернул не-JSON ответ (HTTP " + response.status + ")");
            const details = apiErrors(data);
            if (details) throw Error(details);
            if (!response.ok || !data.id) throw Error(data.message || "HTTP " + response.status);
            return data;
          }, Math.min(IMAGE_UPLOAD_TIMEOUT_MS, Math.max(1, IMAGE_BATCH_LIMIT_MS - (Date.now() - startedAt))), signal);
          break;
        } catch (error) {
          stopped(signal);
          if (error.retryAfterMs === undefined || attempt >= MAX_PHOTO_429_RETRIES) throw error;
          const left = IMAGE_BATCH_LIMIT_MS - (Date.now() - startedAt);
          if (left <= error.retryAfterMs + 1000) {
            throw Error("HTTP 429: лимит времени для фотографий. Повторите импорт позже.");
          }
          await status({message: "Laravel ограничил частоту запросов. Пауза " +
            Math.ceil(error.retryAfterMs / 1000) + " секунд."});
          await delay(error.retryAfterMs, signal);
        }
      }

      if (!saved.skipped) uploaded.push(url);
      known.set(key, url);
    } catch (error) {
      stopped(signal);
      warnings.push("Фото " + (i + 1) + "/" + images.length + ": " + String(error.message || error));
    }
  }

  return {uploaded, warnings};
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
    await status({current: "Импорт объекта: " + item.source_url});

    try {
      let saved = false;
      for (let attempt = 0; attempt < MAX_UPLOAD_ATTEMPTS; attempt++) {
        stopped(signal);
        try {
          const result = await sendItem(endpoint, token, item, signal);
          const propertyResult = result.data.items[0];
          const entry = {
            source_url: item.source_url,
            id: propertyResult.id,
            warnings: propertyResult.warnings || [],
            photos_uploaded: 0,
          };
          completed.push(entry);
          await browser.storage.local.set({results: completed});
          await status({uploaded: completed.length, current: "Сохранён объект: " + item.source_url});

          // Photo errors are not property errors. One inaccessible image
          // never prevents importing other images or the next property.
          try {
            const photos = await transferPhotos(
              endpoint, token, item, entry.id, propertyResult.photo_sources, signal,
              async (number, total, url) => status({
                current: "Фото " + number + "/" + total + ": " + url,
              })
            );
            entry.photos_uploaded = photos.uploaded.length;
            entry.warnings.push(...photos.warnings);
            for (const warning of photos.warnings) {
              errors.push({url: item.source_url, message: warning});
            }
          } catch (error) {
            stopped(signal);
            const warning = "Фото не отправлены: " + String(error.message || error);
            entry.warnings.push(warning);
            errors.push({url: item.source_url, message: warning});
          }
          saved = true;
          break;
        } catch (error) {
          stopped(signal);
          // Only retry explicitly temporary HTTP statuses. Never retry
          // timeouts: the server may have saved the record already.
          if (attempt < MAX_UPLOAD_ATTEMPTS - 1 && /^HTTP (429|503):/.test(error.message)) {
            const wait = error.retryAfterMs ?? (attempt + 1) * 5000;
            await status({current: "Ожидание ответа Laravel · " + Math.ceil(wait / 1000) +
              " с · попытка " + (attempt + 2) + "/" + MAX_UPLOAD_ATTEMPTS});
            await delay(wait, signal);
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
    // Let process() write the final "canceled" state. A concurrent
    // "stopping" storage write could otherwise overwrite it afterwards.
    runController?.abort();
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
