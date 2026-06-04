// ===== State =====
let currentMode = 'fast';
let settings = { apiUrl: 'https://trastly.org', apiKey: '', cleanup: true, translit: true, separator: '_' };
let history = [];
let presets = [];
let lastFinalUrl = '';
let lastGeneratedId = null;

// ===== Transliteration =====
const CYR = 'абвгдеёжзийклмнопрстуфхцчшщъьыэюя';
const LAT = ['a','b','v','g','d','e','yo','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','kh','ts','ch','sh','shch','','','y','e','yu','ya'];
const TRANSLIT_MAP = {};
for (let i = 0; i < CYR.length; i++) {
  TRANSLIT_MAP[CYR[i]] = LAT[i];
  TRANSLIT_MAP[CYR[i].toUpperCase()] = LAT[i];
}

function transliterate(str) {
  return str.split('').map(c => TRANSLIT_MAP[c] !== undefined ? TRANSLIT_MAP[c] : c).join('');
}

function normalizeValue(val) {
  if (!val || !settings.cleanup) return val;
  if (val.includes('{') || val.includes('}')) return val;
  if (settings.translit) val = transliterate(val);
  val = val.toLowerCase();
  val = val.replace(/\s+/g, settings.separator);
  val = val.replace(/[^a-z0-9_\-.]/g, '');
  return val;
}

function getCleanupHint(original) {
  if (!original || !settings.cleanup) return '';
  const cleaned = normalizeValue(original);
  if (cleaned === original) return '';
  return original + ' → ' + cleaned;
}

// ===== URL Building =====
function buildUtmQueryString(params) {
  // Manual build to preserve macros ({keyword}, {{campaign.name}}) without encoding
  return Object.entries(params)
    .map(([k, v]) => k + '=' + v)
    .join('&');
}

function buildUtmUrl() {
  const rawUrl = $('target-url').value.trim();
  if (!rawUrl) return '';
  const fields = { utm_source: 'utm-source', utm_medium: 'utm-medium', utm_campaign: 'utm-campaign', utm_content: 'utm-content', utm_term: 'utm-term' };
  const params = {};
  for (const [param, id] of Object.entries(fields)) {
    let v = $(id).value.trim();
    if (!v) continue;
    v = normalizeValue(v);
    params[param] = v;
  }
  const qs = buildUtmQueryString(params);
  if (!qs) return rawUrl;

  // Handle fragment (#) correctly: UTM params go before fragment
  const hashIdx = rawUrl.indexOf('#');
  let base = rawUrl;
  let fragment = '';
  if (hashIdx !== -1) {
    base = rawUrl.substring(0, hashIdx);
    fragment = rawUrl.substring(hashIdx);
  }
  return base + (base.includes('?') ? '&' : '?') + qs + fragment;
}

// ===== Quality Score =====
function calculateQualityScore() {
  let score = 0;
  const warnings = [];
  const url = $('target-url').value.trim();
  try { new URL(url); score += 20; } catch { warnings.push('Invalid URL'); }
  if ($('utm-source').value.trim()) score += 20; else warnings.push('Missing utm_source');
  if ($('utm-medium').value.trim()) score += 20; else warnings.push('Missing utm_medium');
  if ($('utm-campaign').value.trim()) score += 20; else warnings.push('Missing utm_campaign');
  const content = $('utm-content').value.trim();
  const term = $('utm-term').value.trim();
  if (!content && !term) score += 10;
  else if (content && term) score += 10;
  try {
    const host = new URL(url).hostname.replace('www.', '');
    const src = $('utm-source').value.trim().toLowerCase();
    if (src && !src.includes(host)) score += 10;
    else if (src) warnings.push('utm_source looks like your own domain');
  } catch {}
  return { score: Math.min(100, score), warnings };
}

// ===== Validation =====
function validate() {
  const errs = [];
  const url = $('target-url').value.trim();
  if (!url) errs.push('Target URL is required');
  else { try { new URL(url); } catch { errs.push('Target URL is not valid'); } }
  if (!$('utm-source').value.trim()) errs.push('utm_source is required');
  if (!$('utm-medium').value.trim()) errs.push('utm_medium is required');
  if (!$('utm-campaign').value.trim()) errs.push('utm_campaign is required');
  return errs;
}

// ===== UI Helpers =====
function $(id) { return document.getElementById(id); }

function showToast(msg) {
  const t = $('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 1500);
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => showToast('Copied!')).catch(() => {
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast('Copied!');
  });
}

// ===== Update Preview =====
function updatePreview() {
  lastFinalUrl = buildUtmUrl();
  $('preview-url').textContent = lastFinalUrl || '—';

  // Quality
  const q = calculateQualityScore();
  $('quality-fill').style.width = q.score + '%';
  $('quality-fill').style.background = q.score >= 80 ? '#22c55e' : q.score >= 50 ? '#fbbf24' : '#ef4444';
  $('quality-text').textContent = 'UTM Quality: ' + q.score + '%';

  // Source warning
  try {
    const host = new URL($('target-url').value).hostname.replace('www.', '');
    const src = $('utm-source').value.trim().toLowerCase();
    $('source-warning').style.display = (src && src.includes(host)) ? 'block' : 'none';
  } catch { $('source-warning').style.display = 'none'; }

  // Cleanup hints
  ['source', 'medium', 'campaign', 'content', 'term'].forEach(f => {
    const hint = $(f + '-hint');
    if (hint) hint.textContent = getCleanupHint(document.getElementById('utm-' + f).value.trim());
  });

  // Show copy formats if URL exists
  $('copy-formats').style.display = lastFinalUrl ? 'flex' : 'none';
}

// ===== Current Tab =====
async function getCurrentTab() {
  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (tab) {
      if (tab.url && !tab.url.startsWith('chrome://')) {
        $('target-url').value = tab.url;
        try {
          const u = new URL(tab.url);
          $('page-domain').textContent = u.hostname;
        } catch {}
      }
      if (tab.title) {
        $('page-title-text').textContent = tab.title;
      }
    }
  } catch {}
  updatePreview();
}

// ===== Shorten =====
async function shortenWithTrastly(url) {
  if (!settings.apiKey) throw new Error('Please add your Trastly API Key in Settings.');
  if (!settings.apiUrl) throw new Error('Please set the API Endpoint in Settings.');
  const endpoint = settings.apiUrl.replace(/\/+$/, '');
  const res = await fetch(endpoint + '/api/ext/shorten', {
    method: 'POST',
    headers: { 'Authorization': 'Bearer ' + settings.apiKey, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ url })
  });
  if (!res.ok) {
    const body = await res.text();
    throw new Error('API error: ' + res.status + ' ' + body.substring(0, 100));
  }
  const data = await res.json();
  if (data.short_url) return data.short_url;
  if (data.data && data.data.short_url) return data.data.short_url;
  if (typeof data === 'string') return data;
  throw new Error('Unexpected API response');
}

// ===== Storage =====
async function loadStorage() {
  return new Promise(resolve => {
    chrome.storage.local.get(['settings', 'history', 'presets'], result => {
      if (result.settings) settings = { ...settings, ...result.settings };
      if (result.history) history = result.history;
      if (result.presets) presets = result.presets;
      resolve();
    });
  });
}

function saveStorage() {
  chrome.storage.local.set({ settings, history, presets });
}

function saveToHistory(entry) {
  history.unshift(entry);
  if (history.length > 20) history = history.slice(0, 20);
  saveStorage();
  renderHistory();
}

// ===== Render =====
function renderHistory() {
  const list = $('history-list');
  if (!history.length) { list.innerHTML = '<div style="font-size:11px;color:rgba(255,255,255,0.3);padding:4px;">No recent links.</div>'; return; }
  list.innerHTML = history.map((h, i) => `
    <div class="history-item">
      <div class="history-info">
        <div class="history-url">${escHtml(h.shortUrl || h.finalUrl)}</div>
        <div class="history-meta">${escHtml(h.source || '')}/${escHtml(h.medium || '')} · ${escHtml(h.campaign || '')} · ${escHtml(h.date || '')}</div>
      </div>
      <div class="history-actions">
        <button class="btn-tiny" data-action="copy-history" data-idx="${i}">Copy</button>
        <button class="btn-tiny" data-action="reuse-history" data-idx="${i}">Reuse</button>
        <button class="btn-tiny" data-action="delete-history" data-idx="${i}" style="color:#fca5a5;">&times;</button>
      </div>
    </div>
  `).join('');
}

function renderPresets() {
  const list = $('presets-list');
  if (!presets.length) { list.innerHTML = '<div style="font-size:11px;color:rgba(255,255,255,0.3);padding:4px;">No presets.</div>'; return; }
  list.innerHTML = presets.map((p, i) => `
    <div class="preset-chip" data-idx="${i}">
      ${escHtml(p.name)}
      <span class="preset-del" data-del-idx="${i}">&times;</span>
    </div>
  `).join('');
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

function applySettings() {
  $('set-api-url').value = settings.apiUrl || '';
  $('set-api-key').value = settings.apiKey || '';
  $('set-cleanup').checked = settings.cleanup !== false;
  $('set-translit').checked = settings.translit !== false;
  $('set-separator').value = settings.separator || '_';
}

function setMode(mode) {
  currentMode = mode;
  document.querySelectorAll('.mode-btn').forEach(b => b.classList.toggle('active', b.dataset.mode === mode));
  $('advanced-fields').style.display = mode === 'advanced' ? 'block' : 'none';
  $('templates-section').style.display = 'block';
  $('presets-section').style.display = mode === 'advanced' ? 'block' : 'none';
}

// ===== Init =====
document.addEventListener('DOMContentLoaded', async () => {
  await loadStorage();
  applySettings();
  renderHistory();
  renderPresets();
  setMode(currentMode);
  getCurrentTab();

  // Input listeners for live preview
  ['target-url', 'utm-source', 'utm-medium', 'utm-campaign', 'utm-content', 'utm-term'].forEach(id => {
    $(id)?.addEventListener('input', updatePreview);
  });

  // Mode switch
  document.querySelectorAll('.mode-btn').forEach(btn => {
    btn.addEventListener('click', () => setMode(btn.dataset.mode));
  });

  // Channel templates
  document.querySelectorAll('#channel-tags .tag').forEach(tag => {
    tag.addEventListener('click', () => {
      document.querySelectorAll('#channel-tags .tag').forEach(t => t.classList.remove('active'));
      tag.classList.add('active');
      $('utm-source').value = tag.dataset.source;
      $('utm-medium').value = tag.dataset.medium;
      updatePreview();
    });
  });

  // Quick tags
  document.querySelectorAll('.qtag').forEach(btn => {
    btn.addEventListener('click', () => {
      $(btn.dataset.field).value = btn.dataset.value;
      updatePreview();
    });
  });

  // Macro dropdowns
  document.querySelectorAll('.macro-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const menu = btn.nextElementSibling;
      document.querySelectorAll('.macro-menu').forEach(m => { if (m !== menu) m.classList.remove('open'); });
      menu.classList.toggle('open');
    });
  });
  document.querySelectorAll('.macro-item').forEach(item => {
    item.addEventListener('click', () => {
      const menu = item.closest('.macro-menu');
      const fieldId = menu.id.replace('-macro-menu', '');
      const field = $('utm-' + fieldId);
      if (field) {
        const start = field.selectionStart || field.value.length;
        const before = field.value.substring(0, start);
        const after = field.value.substring(field.selectionEnd || start);
        field.value = before + item.dataset.value + after;
        updatePreview();
      }
      menu.classList.remove('open');
    });
  });
  document.addEventListener('click', () => document.querySelectorAll('.macro-menu').forEach(m => m.classList.remove('open')));

  // Settings checkboxes
  $('set-cleanup').addEventListener('change', function() { settings.cleanup = this.checked; saveStorage(); updatePreview(); });
  $('set-translit').addEventListener('change', function() { settings.translit = this.checked; saveStorage(); updatePreview(); });
  $('set-separator').addEventListener('change', function() { settings.separator = this.value; saveStorage(); updatePreview(); });

  // Copy UTM URL
  $('btn-copy-utm').addEventListener('click', () => {
    const errs = validate();
    if (errs.length) { $('errors').innerHTML = errs.join('<br>'); $('errors').style.display = 'block'; return; }
    $('errors').style.display = 'none';
    const finalUrl = buildUtmUrl();
    copyToClipboard(finalUrl);
    saveToHistory({
      url: $('target-url').value, source: normalizeValue($('utm-source').value),
      medium: normalizeValue($('utm-medium').value), campaign: normalizeValue($('utm-campaign').value),
      finalUrl, shortUrl: null, date: new Date().toLocaleDateString()
    });
  });

  // Copy formats
  $('copy-markdown').addEventListener('click', () => copyToClipboard('[Open link](' + lastFinalUrl + ')'));
  $('copy-html').addEventListener('click', () => copyToClipboard('<a href="' + lastFinalUrl + '">Open link</a>'));
  $('copy-telegram').addEventListener('click', () => copyToClipboard('Link: ' + lastFinalUrl));

  // Shorten
  $('btn-shorten').addEventListener('click', async () => {
    const errs = validate();
    if (errs.length) { $('errors').innerHTML = errs.join('<br>'); $('errors').style.display = 'block'; return; }
    $('errors').style.display = 'none';
    const finalUrl = buildUtmUrl();
    const btn = $('btn-shorten');
    btn.disabled = true; btn.textContent = 'Shortening...';
    try {
      const shortUrl = await shortenWithTrastly(finalUrl);
      $('short-url-value').value = shortUrl;
      $('short-result').style.display = 'block';
      saveToHistory({
        url: $('target-url').value, source: normalizeValue($('utm-source').value),
        medium: normalizeValue($('utm-medium').value), campaign: normalizeValue($('utm-campaign').value),
        finalUrl, shortUrl, date: new Date().toLocaleDateString()
      });
    } catch (err) {
      $('errors').innerHTML = escHtml(err.message);
      $('errors').style.display = 'block';
    }
    btn.disabled = false; btn.textContent = 'Shorten with Trastly';
  });

  // Copy short URL
  $('btn-copy-short').addEventListener('click', () => copyToClipboard($('short-url-value').value));

  // Save settings
  $('btn-save-settings').addEventListener('click', () => {
    settings.apiUrl = $('set-api-url').value.trim() || 'https://trastly.org';
    settings.apiKey = $('set-api-key').value.trim();
    saveStorage();
    showToast('Settings saved');
  });

  // Toggle sections
  $('btn-toggle-settings').addEventListener('click', () => {
    const s = $('settings-section');
    s.style.display = s.style.display === 'none' ? 'block' : 'none';
  });
  $('btn-toggle-presets').addEventListener('click', () => {
    const s = $('presets-section');
    s.style.display = s.style.display === 'none' ? 'block' : 'none';
  });

  // Save preset
  $('btn-save-preset').addEventListener('click', () => {
    const name = prompt('Preset name:');
    if (!name) return;
    presets.push({
      name,
      source: $('utm-source').value, medium: $('utm-medium').value,
      campaign: $('utm-campaign').value, content: $('utm-content').value, term: $('utm-term').value
    });
    saveStorage();
    renderPresets();
    showToast('Preset saved');
  });

  // Preset clicks (delegation)
  $('presets-list').addEventListener('click', (e) => {
    const del = e.target.closest('.preset-del');
    if (del) { presets.splice(parseInt(del.dataset.delIdx), 1); saveStorage(); renderPresets(); return; }
    const chip = e.target.closest('.preset-chip');
    if (chip) {
      const p = presets[parseInt(chip.dataset.idx)];
      if (p) {
        $('utm-source').value = p.source || '';
        $('utm-medium').value = p.medium || '';
        $('utm-campaign').value = p.campaign || '';
        $('utm-content').value = p.content || '';
        $('utm-term').value = p.term || '';
        updatePreview();
      }
    }
  });

  // History clicks (delegation)
  $('history-list').addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const idx = parseInt(btn.dataset.idx);
    const h = history[idx];
    if (!h) return;
    if (btn.dataset.action === 'copy-history') copyToClipboard(h.shortUrl || h.finalUrl);
    if (btn.dataset.action === 'delete-history') {
      history.splice(idx, 1);
      saveStorage();
      renderHistory();
      return;
    }
    if (btn.dataset.action === 'reuse-history') {
      $('target-url').value = h.url || '';
      $('utm-source').value = h.source || '';
      $('utm-medium').value = h.medium || '';
      $('utm-campaign').value = h.campaign || '';
      updatePreview();
      window.scrollTo(0, 0);
    }
  });

  // Clear history
  $('btn-clear-history').addEventListener('click', () => {
    history = [];
    saveStorage();
    renderHistory();
    showToast('History cleared');
  });
});
