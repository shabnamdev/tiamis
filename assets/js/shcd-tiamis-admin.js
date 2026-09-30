(function ($) {
  'use strict';

  var config = window.TiamisChatAdmin || {};
  var originalText = new WeakMap();
  var originalAttrs = new WeakMap();
  var pageTimers = [];
  var cachePrefix = 'shcd_tiamis_spa:';
  var activeRequest = null;

  function dictionary(language) {
    return (config.translations && config.translations[language]) || {};
  }

  function canonicalSource(source) {
    if (!source || !config.translations) return source;
    var languages = ['ar', 'en'];
    for (var index = 0; index < languages.length; index += 1) {
      var entries = dictionary(languages[index]);
      var keys = Object.keys(entries);
      for (var keyIndex = 0; keyIndex < keys.length; keyIndex += 1) {
        if (entries[keys[keyIndex]] === source) return keys[keyIndex];
      }
    }
    return source;
  }

  function translate(source, language) {
    var canonical = canonicalSource(source);
    var map = dictionary(language || config.language || 'fa');
    return map[canonical] || canonical;
  }

  function shouldSkipText(node) {
    var parent = node.parentElement;
    if (!parent) return true;
    return !!parent.closest('script,style,code,pre,textarea,.ltr,.tiamis-admin-messages,[data-tiamis-no-translate]');
  }

  function applyLanguage(language) {
    language = ['fa', 'ar', 'en'].indexOf(language) >= 0 ? language : 'fa';
    config.language = language;
    var root = document.querySelector('.tiamis-admin-wrap');
    if (!root) return;

    root.setAttribute('dir', language === 'en' ? 'ltr' : 'rtl');
    root.setAttribute('lang', language);
    root.classList.toggle('is-ltr', language === 'en');

    var brandName = language === 'en' ? 'Tiamis' : 'تیامیس';
    document.querySelectorAll('#toplevel_page_shcd-tiamis .wp-menu-name').forEach(function (element) {
      element.textContent = brandName;
      element.setAttribute('dir', language === 'en' ? 'ltr' : 'rtl');
    });

    var menuMap = {
      '.wp-submenu a[href="admin.php?page=shcd-tiamis"]': 'گفتگوها',
      '.wp-submenu a[href="admin.php?page=shcd-tiamis-tickets"]': 'تیکتینگ هوشمند',
      '.wp-submenu a[href="admin.php?page=shcd-tiamis-analytics"]': 'تحلیل و نقشه کلیک',
      '.wp-submenu a[href="admin.php?page=shcd-tiamis-settings"]': 'تنظیمات'
    };
    Object.keys(menuMap).forEach(function (selector) {
      document.querySelectorAll('#toplevel_page_shcd-tiamis ' + selector).forEach(function (link) {
        link.textContent = translate(menuMap[selector], language);
        link.setAttribute('dir', language === 'en' ? 'ltr' : 'rtl');
      });
    });

    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    var node;
    while ((node = walker.nextNode())) {
      if (shouldSkipText(node)) continue;
      if (!originalText.has(node)) originalText.set(node, node.nodeValue);
      var sourceValue = originalText.get(node);
      var trimmed = sourceValue.trim();
      if (!trimmed) continue;
      var translated = translate(trimmed, language);
      node.nodeValue = sourceValue.replace(trimmed, translated);
    }

    root.querySelectorAll('[placeholder],[aria-label],[title]').forEach(function (element) {
      var stored = originalAttrs.get(element) || {};
      ['placeholder', 'aria-label', 'title'].forEach(function (attribute) {
        if (!element.hasAttribute(attribute)) return;
        if (!Object.prototype.hasOwnProperty.call(stored, attribute)) stored[attribute] = element.getAttribute(attribute);
        element.setAttribute(attribute, translate(stored[attribute], language));
      });
      originalAttrs.set(element, stored);
    });

    config.i18n = config.i18n || {};
    config.i18n.thinking = translate('در حال آماده‌سازی پاسخ پیشنهادی…', language);
    config.i18n.error = translate('ساخت پاسخ پیشنهادی انجام نشد.', language);
    config.i18n.savingLanguage = translate('در حال ذخیره زبان پنل…', language);
    config.i18n.languageSaved = translate('زبان پنل ذخیره شد.', language);
    config.i18n.languageError = translate('ذخیره زبان پنل انجام نشد.', language);
    config.i18n.loading = translate('در حال بارگذاری…', language);
    config.i18n.saved = translate('تغییرات ذخیره شد.', language);
    config.i18n.dbConfirm = translate('برای حذف همه داده‌ها، عبارت RESET را وارد کنید.', language);
    config.i18n.dbDone = translate('عملیات پایگاه داده با موفقیت انجام شد.', language);
    config.i18n.visitorTyping = translate('کاربر در حال تایپ است…', language);
    config.i18n.operatorOnline = translate('اپراتور آنلاین است', language);
    config.i18n.operatorOffline = translate('اپراتور آفلاین است', language);
  }

  function root() {
    return document.querySelector('.tiamis-admin-wrap');
  }

  function setLoading(loading, label) {
    var current = root();
    if (!current) return;
    var loader = current.querySelector('[data-tiamis-loader]');
    if (current.getAttribute('data-tiamis-page') === 'inbox') return;
    current.classList.toggle('is-spa-loading', !!loading);
    if (loader) {
      loader.hidden = !loading;
      var text = loader.querySelector('b');
      if (text) text.textContent = label || config.i18n.loading || 'در حال بارگذاری…';
    }
  }

  function clearPageTimers() {
    pageTimers.forEach(function (timer) { window.clearInterval(timer); window.clearTimeout(timer); });
    pageTimers = [];
  }

  function cacheKey(url) {
    return cachePrefix + url;
  }

  function readCache(url) {
    try {
      var value = JSON.parse(sessionStorage.getItem(cacheKey(url)) || 'null');
      if (!value || !value.html || Date.now() - value.time > Number(config.cacheTtl || 45000)) return null;
      return value.html;
    } catch (e) {
      return null;
    }
  }

  function writeCache(url, html) {
    try {
      sessionStorage.setItem(cacheKey(url), JSON.stringify({ time: Date.now(), html: html }));
    } catch (e) {}
  }

  function invalidateCache() {
    try {
      Object.keys(sessionStorage).forEach(function (key) {
        if (key.indexOf(cachePrefix) === 0) sessionStorage.removeItem(key);
      });
    } catch (e) {}
  }

  function parsePage(html) {
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var nextRoot = doc.querySelector('.tiamis-admin-wrap');
    if (!nextRoot) throw new Error('Invalid Tiamis page');
    return { root: nextRoot, title: doc.title || document.title };
  }

  function syncWpMenu(url) {
    var top = document.getElementById('toplevel_page_shcd-tiamis');
    if (!top) return;
    var parsed;
    try { parsed = new URL(url || window.location.href, window.location.href); } catch (e) { return; }
    var page = parsed.searchParams.get('page') || 'shcd-tiamis';
    var pluginPage = page.indexOf('shcd-tiamis') === 0;
    top.classList.toggle('wp-has-current-submenu', pluginPage);
    top.classList.toggle('wp-not-current-submenu', !pluginPage);
    var parentLink = top.querySelector(':scope > a.wp-has-submenu, :scope > a.menu-top');
    if (parentLink) {
      parentLink.classList.toggle('wp-has-current-submenu', pluginPage);
      parentLink.classList.toggle('wp-not-current-submenu', !pluginPage);
      if (pluginPage) parentLink.setAttribute('aria-current', 'page'); else parentLink.removeAttribute('aria-current');
    }
    var matched = false;
    top.querySelectorAll('.wp-submenu li').forEach(function (item) {
      var link = item.querySelector('a');
      if (!link) return;
      var linkPage = '';
      try { linkPage = new URL(link.href, window.location.href).searchParams.get('page') || ''; } catch (e) {}
      var active = !matched && linkPage === page;
      if (active) matched = true;
      item.classList.toggle('current', active);
      link.classList.toggle('current', active);
      if (active) link.setAttribute('aria-current', 'page'); else link.removeAttribute('aria-current');
    });
    document.body.setAttribute('data-tiamis-admin-page', page);
  }

  function replacePage(html, finalUrl, push) {
    var parsed = parsePage(html);
    var current = root();
    clearPageTimers();
    if (!current) return;
    current.replaceWith(parsed.root);
    document.title = parsed.title;
    if (finalUrl) {
      if (push) history.pushState({ tiamis: true }, '', finalUrl);
      else history.replaceState({ tiamis: true }, '', finalUrl);
    }
    syncWpMenu(finalUrl || window.location.href);
    initPage();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function fetchPage(url, options) {
    options = options || {};
    var absolute = new URL(url, window.location.href).toString();
    var cached = !options.force && !options.method && readCache(absolute);
    if (cached) {
      replacePage(cached, absolute, options.push !== false);
      return Promise.resolve();
    }

    if (activeRequest) activeRequest.abort();
    activeRequest = new AbortController();
    setLoading(true, options.label);
    return fetch(absolute, {
      method: options.method || 'GET',
      body: options.body || null,
      credentials: 'same-origin',
      redirect: 'follow',
      signal: activeRequest.signal,
      headers: { 'X-Tiamis-SPA': '1' }
    }).then(function (response) {
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return response.text().then(function (html) {
        var finalUrl = response.url || absolute;
        if ((options.method || 'GET') === 'GET') writeCache(finalUrl, html);
        else invalidateCache();
        replacePage(html, finalUrl, options.push !== false);
      });
    }).catch(function (error) {
      if (error.name === 'AbortError') return;
      setLoading(false);
      window.location.href = absolute;
    }).finally(function () {
      activeRequest = null;
      setLoading(false);
    });
  }

  function isPluginPageUrl(url) {
    try {
      var parsed = new URL(url, window.location.href);
      if (parsed.origin !== window.location.origin) return false;
      var page = parsed.searchParams.get('page') || '';
      return page.indexOf('shcd-tiamis') === 0;
    } catch (e) {
      return false;
    }
  }

  function updateProviderFields() {
    var provider = $('[data-tiamis-ai-provider]').val() || 'cloudflare';
    $('[data-tiamis-provider-group]').each(function () {
      var supported = String($(this).data('tiamis-provider-group') || '').split(/\s+/);
      $(this).toggleClass('is-visible', supported.indexOf(provider) !== -1);
    });
  }

  function activateTab(name, focus) {
    var page = root();
    if (!page) return;
    var button = page.querySelector('[data-tiamis-tab="' + name + '"]');
    if (!button) name = 'widget';
    page.querySelectorAll('[data-tiamis-tab]').forEach(function (item) {
      var active = item.getAttribute('data-tiamis-tab') === name;
      item.classList.toggle('is-active', active);
      item.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    page.querySelectorAll('[data-tiamis-tab-panel]').forEach(function (panel) {
      var active = panel.getAttribute('data-tiamis-tab-panel') === name;
      panel.hidden = !active;
      panel.classList.toggle('is-active', active);
    });
    var select = page.querySelector('[data-tiamis-tab-select]');
    if (select) select.value = name;
    sessionStorage.setItem('shcd_tiamis_settings_tab', name);
    if (focus) {
      var panel = page.querySelector('[data-tiamis-tab-panel="' + name + '"]');
      if (panel) panel.focus({ preventScroll: true });
    }
  }

  function initTabs() {
    var page = root();
    if (!page || page.getAttribute('data-tiamis-page') !== 'settings') return;
    activateTab(sessionStorage.getItem('shcd_tiamis_settings_tab') || 'widget', false);
  }

  function initSortableTables() {
    document.querySelectorAll('[data-tiamis-sortable] th[data-sort]').forEach(function (header, index) {
      header.setAttribute('tabindex', '0');
      header.setAttribute('role', 'button');
      function sort() {
        var table = header.closest('table');
        var body = table && table.tBodies[0];
        if (!body) return;
        var direction = header.getAttribute('data-direction') === 'asc' ? 'desc' : 'asc';
        table.querySelectorAll('th').forEach(function (item) { item.removeAttribute('data-direction'); });
        header.setAttribute('data-direction', direction);
        var type = header.getAttribute('data-sort');
        Array.prototype.slice.call(body.rows).sort(function (a, b) {
          var av = a.cells[index] ? (a.cells[index].getAttribute('data-value') || a.cells[index].textContent.trim()) : '';
          var bv = b.cells[index] ? (b.cells[index].getAttribute('data-value') || b.cells[index].textContent.trim()) : '';
          if (type === 'number') return direction === 'asc' ? Number(av) - Number(bv) : Number(bv) - Number(av);
          return direction === 'asc' ? av.localeCompare(bv, config.language || 'fa') : bv.localeCompare(av, config.language || 'fa');
        }).forEach(function (row) { body.appendChild(row); });
      }
      header.onclick = sort;
      header.onkeydown = function (event) { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); sort(); } };
    });
  }

  function initPolling() {
    var messages = document.getElementById('tiamis-admin-messages');
    if (messages) messages.scrollTop = messages.scrollHeight;
    var workspace = $('[data-tiamis-admin-workspace]');
    if (!workspace.length || Number(workspace.data('conversation')) < 1) return;

    function poll() {
      if (document.visibilityState === 'hidden') return;
      $.post(config.ajaxUrl, {
        action: 'shcd_tiamis_poll_admin',
        nonce: config.nonce,
        conversation: Number(workspace.data('conversation')),
        after: Number(workspace.attr('data-last-message') || 0)
      }).done(function (response) {
        if (!response || !response.success || !response.data.messages) return;
        response.data.messages.forEach(function (message) {
          var row = $('<div/>', { class: 'tiamis-admin-message is-' + message.sender });
          var inner = $('<div/>');
          $('<span/>').text(message.body).appendTo(inner);
          $('<small/>').text((message.sender_name ? message.sender_name + ' • ' : '') + message.created_at).appendTo(inner);
          row.append(inner).appendTo('#tiamis-admin-messages');
          var incomingId = Number(message.id);
          if (Number.isFinite(incomingId)) workspace.attr('data-last-message', String(incomingId));
        });
        var typing = document.querySelector('[data-tiamis-visitor-typing]');
        if (typing) {
          typing.hidden = !response.data.visitor_typing;
          typing.textContent = config.i18n.visitorTyping || 'کاربر در حال تایپ است…';
        }
        if (response.data.conversation) {
          var title = document.querySelector('.tiamis-contact-identity h2');
          if (title) title.textContent = response.data.conversation.visitor_name || 'بازدیدکننده ناشناس';
          var chips = document.querySelector('.tiamis-contact-chips');
          if (chips) {
            chips.innerHTML = '';
            if (response.data.conversation.visitor_phone) { var phone = document.createElement('a'); phone.href = 'tel:' + response.data.conversation.visitor_phone; phone.textContent = '📞 ' + response.data.conversation.visitor_phone; chips.appendChild(phone); }
            if (response.data.conversation.visitor_email) { var email = document.createElement('a'); email.href = 'mailto:' + response.data.conversation.visitor_email; email.textContent = '✉️ ' + response.data.conversation.visitor_email; chips.appendChild(email); }
          }
        }
        if (response.data.messages.length && messages) messages.scrollTop = messages.scrollHeight;
      });
    }

    poll();
    var timer = window.setInterval(poll, 550);
    pageTimers.push(timer);
  }

  function initOperatorPresence() {
    var page = root();
    if (!page || page.getAttribute('data-tiamis-page') !== 'inbox') return;
    function heartbeat() {
      if (document.visibilityState === 'hidden') return;
      $.post(config.ajaxUrl, { action: 'shcd_tiamis_operator_heartbeat', nonce: config.nonce });
    }
    heartbeat();
    var timer = window.setInterval(heartbeat, 20000);
    pageTimers.push(timer);
  }

  function initAdminTyping() {
    var textarea = document.getElementById('tiamis-reply-text');
    var workspace = document.querySelector('[data-tiamis-admin-workspace]');
    if (!textarea || !workspace) return;
    var conversation = Number(workspace.getAttribute('data-conversation') || 0);
    if (!conversation) return;
    var stopTimer = 0;
    var lastSent = 0;
    function sendTyping(typing) {
      var now = Date.now();
      if (typing && now - lastSent < 1600) return;
      if (typing) lastSent = now;
      $.post(config.ajaxUrl, {
        action: 'shcd_tiamis_admin_typing',
        nonce: config.nonce,
        conversation: conversation,
        typing: typing ? 1 : 0,
        agent_index: agentSelect ? agentSelect.value : 0
      });
    }
    textarea.addEventListener('input', function () {
      sendTyping(textarea.value.trim().length > 0);
      window.clearTimeout(stopTimer);
      stopTimer = window.setTimeout(function () { sendTyping(false); }, 2600);
    });
    textarea.addEventListener('blur', function () { sendTyping(false); });
    textarea.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
        event.preventDefault();
        var replyForm = textarea.closest('form');
        if (replyForm && textarea.value.trim()) replyForm.requestSubmit();
      }
    });
    var form = textarea.closest('form');
    var agentSelect = form && form.querySelector('[data-tiamis-reply-agent]');
    if (agentSelect) {
      try {
        var savedAgent = sessionStorage.getItem('shcd_tiamis_reply_agent');
        if (savedAgent !== null && agentSelect.querySelector('option[value="' + savedAgent + '"]')) agentSelect.value = savedAgent;
      } catch (e) {}
      agentSelect.addEventListener('change', function () {
        try { sessionStorage.setItem('shcd_tiamis_reply_agent', agentSelect.value); } catch (e) {}
      });
    }
    if (form) form.addEventListener('submit', function () { sendTyping(false); });
  }

  function initTelegramMode() {
    var select = document.querySelector('[data-tiamis-telegram-mode]');
    if (!select) return;
    function update() {
      var polling = select.value === 'polling';
      document.querySelectorAll('[data-tiamis-telegram-poll-control]').forEach(function (item) { item.hidden = !polling; });
      document.querySelectorAll('[data-tiamis-telegram-webhook-control]').forEach(function (item) { item.hidden = polling; });
    }
    select.addEventListener('change', update);
    update();
  }

  function initBotTabs() {
    var shell = document.querySelector('[data-tiamis-bot-tabs]');
    if (!shell) return;
    var buttons = Array.prototype.slice.call(shell.querySelectorAll('[data-tiamis-bot-tab]'));
    var panels = Array.prototype.slice.call(shell.querySelectorAll('[data-tiamis-bot-panel]'));
    if (!buttons.length || !panels.length) return;

    function activate(name, persist) {
      if (!shell.querySelector('[data-tiamis-bot-panel="' + name + '"]')) name = 'telegram';
      buttons.forEach(function (button) {
        var active = button.getAttribute('data-tiamis-bot-tab') === name;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-selected', active ? 'true' : 'false');
        button.setAttribute('tabindex', active ? '0' : '-1');
      });
      panels.forEach(function (panel) {
        var active = panel.getAttribute('data-tiamis-bot-panel') === name;
        panel.hidden = !active;
        panel.classList.toggle('is-active', active);
      });
      if (persist) {
        try { sessionStorage.setItem('shcd_tiamis_bot_tab', name); } catch (e) {}
      }
    }

    buttons.forEach(function (button, index) {
      button.addEventListener('click', function () { activate(button.getAttribute('data-tiamis-bot-tab'), true); });
      button.addEventListener('keydown', function (event) {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        var direction = event.key === 'ArrowRight' ? 1 : -1;
        var next = buttons[(index + direction + buttons.length) % buttons.length];
        next.focus();
        activate(next.getAttribute('data-tiamis-bot-tab'), true);
      });
    });

    var saved = 'telegram';
    try { saved = sessionStorage.getItem('shcd_tiamis_bot_tab') || 'telegram'; } catch (e) {}
    activate(saved, false);
  }

  function initAgentRepeater() {
    var shell = document.querySelector('[data-tiamis-agents]');
    if (!shell) return;
    var list = shell.querySelector('[data-tiamis-agents-list]');
    var template = shell.querySelector('template[data-tiamis-agent-template]');
    var add = shell.querySelector('[data-tiamis-agent-add]');
    var maximum = 8;
    if (!list || !template || !add) return;

    function rows() {
      return Array.prototype.slice.call(list.querySelectorAll('[data-tiamis-agent-row]'));
    }

    function updatePreview(row) {
      var input = row.querySelector('[data-tiamis-agent-photo]');
      var preview = row.querySelector('.tiamis-agent-preview');
      if (!input || !preview) return;
      var value = String(input.value || '').trim();
      preview.innerHTML = '';
      if (value) {
        var image = document.createElement('img');
        image.alt = '';
        image.loading = 'lazy';
        image.src = value;
        image.addEventListener('error', function () {
          preview.innerHTML = '<span>👤</span>';
        }, { once: true });
        preview.appendChild(image);
      } else {
        preview.innerHTML = '<span>👤</span>';
      }
    }

    function reindex() {
      var current = rows();
      current.forEach(function (row, index) {
        ['name', 'role', 'photo', 'department', 'languages', 'skills', 'capacity', 'wp_user_id', 'active'].forEach(function (field) {
          var input = row.querySelector('[data-agent-field="' + field + '"]');
          if (!input) return;
          input.setAttribute('name', 'settings[agents][' + index + '][' + field + ']');
          input.setAttribute('data-agent-field', field);
        });
      });
      add.disabled = current.length >= maximum;
      add.setAttribute('aria-disabled', add.disabled ? 'true' : 'false');
      shell.classList.toggle('is-at-limit', current.length >= maximum);
    }

    function bind(row) {
      var remove = row.querySelector('[data-tiamis-agent-remove]');
      var photo = row.querySelector('[data-tiamis-agent-photo]');
      if (remove) {
        remove.addEventListener('click', function () {
          if (rows().length <= 1) {
            row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
            updatePreview(row);
          } else {
            row.remove();
          }
          reindex();
        });
      }
      if (photo) {
        photo.addEventListener('input', function () { updatePreview(row); });
        photo.addEventListener('change', function () { updatePreview(row); });
      }
    }

    rows().forEach(bind);
    add.addEventListener('click', function () {
      if (rows().length >= maximum) return;
      var fragment = template.content.cloneNode(true);
      var row = fragment.querySelector('[data-tiamis-agent-row]');
      list.appendChild(fragment);
      bind(row);
      reindex();
      var first = row.querySelector('input');
      if (first) first.focus();
    });
    reindex();
  }

  function initBubblePreview() {
    var style = document.querySelector('[data-tiamis-bubble-style]');
    var animation = document.querySelector('[data-tiamis-bubble-animation]');
    var preview = document.querySelector('[data-tiamis-bubble-preview]');
    if (!style || !animation || !preview) return;
    var animations = ['float', 'pulse', 'bounce', 'shake', 'swing', 'wobble', 'heartbeat', 'tada', 'jelly', 'rotate', 'orbit', 'glow', 'wave', 'pop', 'slide'];
    function randomAnimation() {
      if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
        var value = new Uint32Array(1);
        window.crypto.getRandomValues(value);
        return animations[value[0] % animations.length];
      }
      return animations[Math.floor(Math.random() * animations.length)];
    }
    function chosenAnimation() {
      if (animation.value === 'random') return randomAnimation();
      return animation.value;
    }
    function update() {
      var selected = chosenAnimation();
      preview.className = 'tiamis-bubble-live-preview tiamis-bubble-style-' + style.value;
      if (selected !== 'none') preview.classList.add('tiamis-bubble-animation-' + selected);
      preview.setAttribute('data-preview-animation', selected);
      preview.setAttribute('title', animation.value === 'random' ? 'با هر بار بارگذاری صفحه، یک حرکت تازه انتخاب می‌شود.' : (selected === 'none' ? 'دکمه بدون حرکت نمایش داده می‌شود.' : 'پیش‌نمایش انیمیشن انتخاب‌شده'));
    }
    style.addEventListener('change', update);
    animation.addEventListener('change', update);
    preview.addEventListener('click', function () { if (animation.value === 'random') update(); });
    update();
  }

  function initFileDrop() {
    var input = document.querySelector('.tiamis-file-drop input[type="file"]');
    if (!input) return;
    input.onchange = function () {
      var label = input.closest('.tiamis-file-drop');
      var text = label && label.querySelector('span');
      if (text && input.files && input.files[0]) text.textContent = input.files[0].name;
    };
  }


  function postAdmin(action, data) {
    var body = new URLSearchParams();
    body.set('action', action);
    body.set('nonce', config.nonce || '');
    Object.keys(data || {}).forEach(function (key) { body.set(key, data[key] == null ? '' : String(data[key])); });
    return fetch(config.ajaxUrl, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    }).then(function (response) {
      return response.json().then(function (payload) {
        if (!response.ok || !payload || !payload.success) {
          var detail = payload && payload.data ? payload.data : {};
          throw new Error(detail.message || config.i18n.error || 'خطایی رخ داد.');
        }
        return payload.data || {};
      });
    });
  }

  function initWorkspaceV2(page) {
    var workspace = page.querySelector('[data-tiamis-admin-workspace]');
    if (!workspace) return;
    var conversationId = Number(workspace.getAttribute('data-conversation') || 0);
    var storageKey = 'tiamis_workspace_tab_' + conversationId;
    var tabs = Array.prototype.slice.call(workspace.querySelectorAll('[data-tiamis-workspace-tab]'));
    var panels = Array.prototype.slice.call(workspace.querySelectorAll('[data-tiamis-workspace-panel]'));

    function activateWorkspaceTab(name) {
      if (!tabs.some(function (tab) { return tab.getAttribute('data-tiamis-workspace-tab') === name; })) name = 'chat';
      tabs.forEach(function (tab) {
        var active = tab.getAttribute('data-tiamis-workspace-tab') === name;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      panels.forEach(function (panel) {
        var active = panel.getAttribute('data-tiamis-workspace-panel') === name;
        panel.classList.toggle('is-active', active);
        panel.hidden = !active;
      });
      try { sessionStorage.setItem(storageKey, name); } catch (e) {}
      if (name === 'chat') {
        var box = workspace.querySelector('#tiamis-admin-messages');
        if (box) box.scrollTop = box.scrollHeight;
      }
    }

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () { activateWorkspaceTab(tab.getAttribute('data-tiamis-workspace-tab')); });
    });
    var stored = 'chat';
    try { stored = sessionStorage.getItem(storageKey) || 'chat'; } catch (e) {}
    activateWorkspaceTab(stored);

    var actionsToggle = workspace.querySelector('[data-tiamis-actions-toggle]');
    var actionsMenu = workspace.querySelector('[data-tiamis-actions-menu]');
    if (actionsToggle && actionsMenu) {
      actionsToggle.addEventListener('click', function (event) {
        event.stopPropagation(); actionsMenu.hidden = !actionsMenu.hidden;
      });
      document.addEventListener('click', function (event) {
        if (!actionsMenu.contains(event.target) && event.target !== actionsToggle) actionsMenu.hidden = true;
      });
    }

    var macro = workspace.querySelector('[data-tiamis-macro]');
    var reply = workspace.querySelector('#tiamis-reply-text');
    if (macro && reply) {
      macro.addEventListener('change', function () {
        if (!macro.value) return;
        reply.value = macro.value;
        reply.dispatchEvent(new Event('input', { bubbles: true }));
        reply.focus();
        macro.value = '';
      });
    }

    var manage = workspace.querySelector('[data-tiamis-conversation-manage]');
    if (manage) manage.addEventListener('submit', function (event) {
      event.preventDefault();
      var button = manage.querySelector('button');
      if (button) { button.disabled = true; button.textContent = 'در حال ذخیره…'; }
      var formData = new FormData(manage);
      var payload = {}; formData.forEach(function (value, key) { payload[key] = value; });
      postAdmin('shcd_tiamis_manage_conversation', payload).then(function (data) {
        toast(data.message || 'تغییرات ذخیره شد.', 'success'); invalidateCache();
      }).catch(function (error) { toast(error.message, 'error'); }).finally(function () {
        if (button) { button.disabled = false; button.textContent = 'ذخیره'; }
      });
    });

    var taskForm = workspace.querySelector('[data-tiamis-task-form]');
    var taskList = workspace.querySelector('[data-tiamis-task-list]');
    if (taskForm && taskList) {
      taskForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var input = taskForm.querySelector('[name="title"]');
        if (!input || !input.value.trim()) return;
        postAdmin('shcd_tiamis_task', { conversation: conversationId, title: input.value.trim() }).then(function (data) {
          var empty = taskList.querySelector('.is-empty'); if (empty) empty.remove();
          var item = document.createElement('li');
          item.innerHTML = '<button type="button" data-task-id="' + data.task_id + '"><i></i><span></span></button>';
          item.querySelector('span').textContent = data.title;
          taskList.appendChild(item); input.value = '';
          bindTaskButton(item.querySelector('button'));
        }).catch(function (error) { toast(error.message, 'error'); });
      });
      function bindTaskButton(button) {
        button.addEventListener('click', function () {
          var item = button.closest('li');
          postAdmin('shcd_tiamis_task', { conversation: conversationId, task_id: button.getAttribute('data-task-id') }).then(function (data) {
            item.classList.toggle('is-done', data.status === 'done');
          }).catch(function (error) { toast(error.message, 'error'); });
        });
      }
      taskList.querySelectorAll('[data-task-id]').forEach(bindTaskButton);
    }

    var tagForm = workspace.querySelector('[data-tiamis-tag-form]');
    var tagList = workspace.querySelector('[data-tiamis-tag-list]');
    function bindTagButton(button) {
      button.addEventListener('click', function () {
        postAdmin('shcd_tiamis_tag', { conversation: conversationId, tag_id: button.getAttribute('data-tag-id') }).then(function () { button.remove(); }).catch(function (error) { toast(error.message, 'error'); });
      });
    }
    if (tagList) tagList.querySelectorAll('[data-tag-id]').forEach(bindTagButton);
    if (tagForm && tagList) tagForm.addEventListener('submit', function (event) {
      event.preventDefault(); var input = tagForm.querySelector('[name="name"]'); if (!input || !input.value.trim()) return;
      postAdmin('shcd_tiamis_tag', { conversation: conversationId, name: input.value.trim() }).then(function (data) {
        var button = document.createElement('button'); button.type = 'button'; button.setAttribute('data-tag-id', data.tag_id); button.textContent = data.name + ' ×'; tagList.appendChild(button); bindTagButton(button); input.value = '';
      }).catch(function (error) { toast(error.message, 'error'); });
    });

    var insightsButton = workspace.querySelector('[data-tiamis-ai-insights-button]');
    var insightsBox = workspace.querySelector('[data-tiamis-ai-insights]');
    if (insightsButton && insightsBox) insightsButton.addEventListener('click', function () {
      insightsButton.disabled = true; insightsButton.textContent = 'در حال تحلیل…';
      postAdmin('shcd_tiamis_ai_insights', { conversation: conversationId }).then(function (data) {
        var result = data.insights || {};
        insightsBox.innerHTML = '';
        ['summary','sentiment','topic','next_action','quality'].forEach(function (key) {
          if (!result[key]) return; var row = document.createElement('div'); row.className = 'tiamis-insight-row'; var b = document.createElement('b'); b.textContent = key; var p = document.createElement('p'); p.textContent = result[key]; row.appendChild(b); row.appendChild(p); insightsBox.appendChild(row);
        });
      }).catch(function (error) { toast(error.message, 'error'); }).finally(function () { insightsButton.disabled = false; insightsButton.textContent = 'تحلیل گفتگو'; });
    });

    var knowledgeButton = workspace.querySelector('[data-tiamis-knowledge-sync]');
    var knowledgeStatus = workspace.querySelector('[data-tiamis-knowledge-status]');
    if (knowledgeButton) knowledgeButton.addEventListener('click', function () {
      knowledgeButton.disabled = true;
      postAdmin('shcd_tiamis_knowledge_sync', {}).then(function (data) { if (knowledgeStatus) knowledgeStatus.textContent = data.message || ''; }).catch(function (error) { if (knowledgeStatus) knowledgeStatus.textContent = error.message; }).finally(function () { knowledgeButton.disabled = false; });
    });

    if (conversationId) {
      var lockTimer = window.setInterval(function () { postAdmin('shcd_tiamis_conversation_lock', { conversation: conversationId, mode: 'acquire' }).catch(function () {}); }, 18000);
      pageTimers.push(lockTimer);
      window.addEventListener('pagehide', function () {
        var data = new URLSearchParams(); data.set('action', 'shcd_tiamis_conversation_lock'); data.set('nonce', config.nonce || ''); data.set('conversation', String(conversationId)); data.set('mode', 'release');
        if (navigator.sendBeacon) navigator.sendBeacon(config.ajaxUrl, data);
      }, { once: true });
    }
  }

  function initPage() {
    syncWpMenu(window.location.href);
    applyLanguage(config.language || 'fa');
    updateProviderFields();
    initTabs();
    initBotTabs();
    initTelegramMode();
    initAgentRepeater();
    initPolling();
    initOperatorPresence();
    initAdminTyping();
    initSortableTables();
    initFileDrop();
    initBubblePreview();
    initWorkspaceV2(root());
    var toastTimer = window.setTimeout(function () {
      var toast = document.querySelector('[data-tiamis-toast]');
      if (toast) toast.classList.add('is-hiding');
    }, 5000);
    pageTimers.push(toastTimer);
  }

  $(document).on('change', '[data-tiamis-ai-provider]', updateProviderFields);

  $(document).on('click', '[data-tiamis-tab]', function () {
    activateTab(this.getAttribute('data-tiamis-tab'), true);
  });

  $(document).on('change', '[data-tiamis-tab-select]', function () {
    activateTab(this.value, false);
  });

  $(document).on('click', '[data-tiamis-toast] button', function () {
    var toast = this.closest('[data-tiamis-toast]');
    if (toast) toast.remove();
  });

  $(document).on('change', '[data-tiamis-language-switch]', function () {
    var select = $(this);
    var language = select.val();
    var status = $('[data-tiamis-language-status]');
    applyLanguage(language);
    status.text(config.i18n.savingLanguage).removeClass('is-error is-success');
    select.prop('disabled', true);
    $.post(config.ajaxUrl, { action: 'shcd_tiamis_set_language', nonce: config.nonce, language: language })
      .done(function (response) {
        if (!response || !response.success) {
          status.text(config.i18n.languageError).addClass('is-error');
          return;
        }
        invalidateCache();
        status.text(config.i18n.languageSaved).addClass('is-success');
      }).fail(function () {
        status.text(config.i18n.languageError).addClass('is-error');
      }).always(function () {
        select.prop('disabled', false);
        var timer = window.setTimeout(function () { status.text('').removeClass('is-error is-success'); }, 2500);
        pageTimers.push(timer);
      });
  });

  $(document).on('click', '[data-tiamis-ai-draft]', function () {
    var button = $(this);
    var textarea = $('#tiamis-reply-text');
    var original = button.text();
    button.prop('disabled', true).text(config.i18n.thinking);
    $.post(config.ajaxUrl, { action: 'shcd_tiamis_ai_draft', nonce: config.nonce, conversation: button.data('conversation') })
      .done(function (response) {
        if (response && response.success && response.data.reply) textarea.val(response.data.reply).trigger('focus');
        else window.alert((response && response.data && response.data.message) || config.i18n.error);
      }).fail(function (xhr) {
        var response = xhr.responseJSON;
        window.alert((response && response.data && response.data.message) || config.i18n.error);
      }).always(function () { button.prop('disabled', false).text(original); });
  });

  $(document).on('click', '[data-tiamis-db-action]', function () {
    var button = $(this);
    var operation = button.attr('data-tiamis-db-action');
    var confirmation = '';
    if (operation === 'purge') {
      confirmation = window.prompt(config.i18n.dbConfirm || 'RESET');
      if (confirmation !== 'RESET') return;
    }
    button.prop('disabled', true);
    setLoading(true, config.i18n.loading);
    $.post(config.ajaxUrl, { action: 'shcd_tiamis_db_action', nonce: config.nonce, operation: operation, confirmation: confirmation })
      .done(function (response) {
        if (!response || !response.success) {
          window.alert((response && response.data && response.data.message) || 'Database error');
          return;
        }
        invalidateCache();
        fetchPage(window.location.href, { force: true, push: false, label: config.i18n.dbDone });
      }).fail(function (xhr) {
        var response = xhr.responseJSON;
        window.alert((response && response.data && response.data.message) || 'Database error');
      }).always(function () {
        button.prop('disabled', false);
        setLoading(false);
      });
  });

  $(document).on('click', '[data-tiamis-refresh]', function () {
    invalidateCache();
    fetchPage(window.location.href, { force: true, push: false });
  });

  document.addEventListener('click', function (event) {
    var confirmTarget = event.target.closest('[data-tiamis-confirm]');
    if (confirmTarget && !window.confirm(confirmTarget.getAttribute('data-tiamis-confirm'))) { event.preventDefault(); return; }
    var link = event.target.closest('a');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target === '_blank' || link.hasAttribute('download')) return;
    // Let WordPress handle its own sidebar navigation so Core can keep the
    // correct parent and submenu item active.
    if (link.closest('#adminmenu')) return;
    var href = link.getAttribute('href') || link.href;
    var explicit = link.hasAttribute('data-tiamis-spa-link');
    if (!explicit && !isPluginPageUrl(href)) return;
    event.preventDefault();
    fetchPage(href, { force: explicit && href.indexOf('admin-post.php') !== -1, push: true });
  }, true);

  function showInlineToast(message, type) {
    var page = root();
    if (!page) return;
    var old = page.querySelector('[data-tiamis-fast-toast]');
    if (old) old.remove();
    var toast = document.createElement('div');
    toast.className = 'tiamis-toast is-' + (type || 'success');
    toast.setAttribute('data-tiamis-fast-toast', '');
    var text = document.createElement('span');
    text.textContent = message || config.i18n.saved;
    var close = document.createElement('button');
    close.type = 'button'; close.textContent = '×'; close.addEventListener('click', function () { toast.remove(); });
    toast.appendChild(text); toast.appendChild(close); page.appendChild(toast);
    window.setTimeout(function () { toast.classList.add('is-hiding'); }, 1800);
    window.setTimeout(function () { if (toast.parentNode) toast.remove(); }, 2300);
  }

  function saveSettingsFast(form) {
    var status = document.querySelector('[data-tiamis-save-status]');
    var submit = form.querySelector('[type="submit"]');
    var originalText = submit ? submit.textContent : '';
    var data = new FormData(form);
    if (status) status.textContent = 'در حال ذخیره…';
    if (submit) {
      submit.disabled = true;
      submit.classList.add('is-saving');
      submit.textContent = 'در حال ذخیره…';
    }
    return fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
      .then(function (response) { return response.json(); })
      .then(function (response) {
        if (!response || !response.success) throw new Error((response && response.data && response.data.message) || config.i18n.error);
        invalidateCache();
        if (status) status.textContent = response.data.message || config.i18n.saved;
        showInlineToast(response.data.message || config.i18n.saved, response.data.notice && response.data.notice.indexOf('error') !== -1 ? 'error' : 'success');
      })
      .catch(function (error) {
        if (status) status.textContent = error.message || config.i18n.error;
        showInlineToast(error.message || config.i18n.error, 'error');
      })
      .finally(function () {
        if (submit) {
          submit.disabled = false;
          submit.classList.remove('is-saving');
          submit.textContent = originalText;
        }
      });
  }

  function appendReplyMessage(message) {
    var messages = document.getElementById('tiamis-admin-messages');
    var workspace = document.querySelector('[data-tiamis-admin-workspace]');
    if (!messages || !message) return;
    var row = document.createElement('div');
    row.className = 'tiamis-admin-message is-' + (message.sender || 'admin');
    var inner = document.createElement('div');
    var body = document.createElement('span');
    body.textContent = message.body || '';
    var meta = document.createElement('small');
    meta.textContent = (message.sender_name ? message.sender_name + ' • ' : '') + (message.created_at || '');
    inner.appendChild(body);
    inner.appendChild(meta);
    row.appendChild(inner);
    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;
    var numericMessageId = Number(message.id);
    if (workspace && Number.isFinite(numericMessageId)) workspace.setAttribute('data-last-message', String(numericMessageId));
  }

  function replyFast(form) {
    var textarea = form.querySelector('textarea[name="message"]');
    var submit = form.querySelector('[type="submit"]');
    var value = textarea ? textarea.value.trim() : '';
    if (!value || form.classList.contains('is-sending')) return Promise.resolve();
    form.classList.add('is-sending');
    var originalText = submit ? submit.textContent : '';
    var data = new FormData(form);
    var selected = form.querySelector('[data-tiamis-reply-agent]');
    var senderName = selected && selected.options[selected.selectedIndex] ? selected.options[selected.selectedIndex].text.split(' — ')[0] : 'کارشناس';
    var temp = { id: 'tmp-' + Date.now(), sender: 'admin', sender_name: senderName, body: value, created_at: new Date().toLocaleString('fa-IR') };
    appendReplyMessage(temp);
    if (textarea) textarea.value = '';
    if (submit) { submit.disabled = true; submit.textContent = 'ارسال شد'; }
    return fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
      .then(function (response) { return response.json(); })
      .then(function (response) {
        if (!response || !response.success) throw new Error((response && response.data && response.data.message) || config.i18n.error);
        var messages = document.getElementById('tiamis-admin-messages');
        var tempRow = messages && messages.lastElementChild;
        if (tempRow && response.data.message && response.data.message.id) tempRow.setAttribute('data-message-id', String(response.data.message.id));
        var workspace = document.querySelector('[data-tiamis-admin-workspace]');
        if (workspace && response.data.message) workspace.setAttribute('data-last-message', String(response.data.message.id));
      })
      .catch(function (error) {
        if (textarea) textarea.value = value;
        showInlineToast(error.message || config.i18n.error, 'error');
      })
      .finally(function () {
        form.classList.remove('is-sending');
        if (submit) { submit.disabled = false; submit.textContent = originalText; }
        if (textarea) textarea.focus();
      });
  }

  document.addEventListener('submit', function (event) {
    var noteForm = event.target.closest('[data-tiamis-note-form]');
    if (noteForm) {
      event.preventDefault();
      var noteField = noteForm.querySelector('textarea[name="note"]');
      var note = noteField ? noteField.value.trim() : '';
      if (!note) return;
      var data = new URLSearchParams();
      data.set('action', 'shcd_tiamis_save_note'); data.set('nonce', config.nonce);
      data.set('conversation', noteForm.querySelector('[name="conversation"]').value); data.set('note', note);
      fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: data.toString() })
        .then(function (r) { return r.json(); }).then(function (r) {
          if (!r || !r.success) throw new Error(r && r.data && r.data.message || 'ذخیره یادداشت انجام نشد.');
          var list = document.querySelector('[data-tiamis-note-list]');
          var empty = list && list.querySelector('.tiamis-empty-note'); if (empty) empty.remove();
          var article = document.createElement('article');
          var strong = document.createElement('strong'); strong.textContent = r.data.author || 'کارشناس';
          var time = document.createElement('time'); time.textContent = r.data.created_at || '';
          var paragraph = document.createElement('p'); paragraph.textContent = r.data.note || note;
          article.appendChild(strong); article.appendChild(time); article.appendChild(paragraph);
          if (list) list.insertBefore(article, list.firstChild);
          if (noteField) noteField.value = '';
          showInlineToast('یادداشت ثبت شد.', 'success');
        }).catch(function (e) { showInlineToast(e.message, 'error'); });
      return;
    }
    var form = event.target.closest('form[data-tiamis-spa-form]');
    if (!form) return;
    event.preventDefault();
    if (form.classList.contains('tiamis-reply-form')) {
      replyFast(form);
      return;
    }
    if (form.classList.contains('tiamis-settings-form')) {
      saveSettingsFast(form);
      return;
    }
    var method = (form.getAttribute('method') || 'GET').toUpperCase();
    var action = form.getAttribute('action') || window.location.href;
    if (method === 'GET') {
      var url = new URL(action, window.location.href);
      new FormData(form).forEach(function (value, key) {
        if (typeof value === 'string') url.searchParams.set(key, value);
      });
      fetchPage(url.toString(), { force: true, push: true });
      return;
    }
    var status = document.querySelector('[data-tiamis-save-status]');
    if (status) status.textContent = config.i18n.loading;
    fetchPage(action, { method: method, body: new FormData(form), force: true, push: false, label: config.i18n.loading })
      .then(function () { if (status) status.textContent = config.i18n.saved; });
  }, true);

  window.addEventListener('popstate', function () {
    if (isPluginPageUrl(window.location.href)) fetchPage(window.location.href, { force: false, push: false });
  });

  $(function () {
    history.replaceState({ tiamis: true }, '', window.location.href);
    initPage();
  });
}(jQuery));
