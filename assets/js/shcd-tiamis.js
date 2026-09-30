(function () {
  'use strict';

  var config = window.TiamisChatConfig || {};
  var storageKey = 'shcd_tiamis_session';
  var oneSignalPromise = null;

  if (config.pwa && config.pwa.enabled && config.pwa.worker && 'serviceWorker' in navigator) {
    window.addEventListener('load', function () { navigator.serviceWorker.register(config.pwa.worker, { scope: '/' }).catch(function () {}); });
  }

  function clientId() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
    return 'tm-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
  }

  function getOneSignal() {
    if (!config.oneSignal || !config.oneSignal.enabled || !config.oneSignal.appId) return Promise.resolve(null);
    if (oneSignalPromise) return oneSignalPromise;
    oneSignalPromise = new Promise(function (resolve) {
      window.OneSignalDeferred = window.OneSignalDeferred || [];
      window.OneSignalDeferred.push(async function (OneSignal) {
        try {
          await OneSignal.init({
            appId: config.oneSignal.appId,
            serviceWorkerPath: config.oneSignal.workerPath,
            serviceWorkerParam: { scope: config.oneSignal.workerScope },
            notificationClickHandlerMatch: 'origin',
            notificationClickHandlerAction: 'focus'
          });
        } catch (e) {
          // If another integration initialized the SDK, login may still be available.
        }
        resolve(OneSignal || null);
      });
    });
    return oneSignalPromise;
  }

  function connectPushIdentity(publicId) {
    if (!publicId || !config.oneSignal || !config.oneSignal.enabled) return;
    getOneSignal().then(function (OneSignal) {
      if (OneSignal && typeof OneSignal.login === 'function') return OneSignal.login(publicId);
      return null;
    }).catch(function () {});
  }

  function jsonFetch(path, options) {
    options = options || {};
    options.headers = options.headers || {};
    options.headers['Content-Type'] = 'application/json';
    var method = String(options.method || 'GET').toUpperCase();
    var url = config.restUrl + path;

    function ajaxFallback(originalError) {
      if (!config.ajaxUrl) throw originalError;
      var cleanPath = String(path || '').split('?')[0].replace(/^\/+|\/+$/g, '');
      var routeMap = {
        'session': 'session',
        'messages': method === 'GET' ? 'messages_get' : 'messages_post',
        'sync': 'sync',
        'reaction': 'reaction',
        'resume': 'resume',
        'conversation/code': 'conversation_code',
        'typing': 'typing',
        'report': 'report',
        'rating': 'rating',
        'conversation/end': 'conversation_end'
      };
      var route = routeMap[cleanPath];
      if (!route) throw originalError;
      var payload = {};
      try { payload = options.body ? JSON.parse(options.body) : {}; } catch (e) {}
      try {
        var query = new URL(url, window.location.href).searchParams;
        query.forEach(function (value, key) { payload[key] = value; });
      } catch (e) {}
      var token = options.headers['X-SHCD-Tiamis-Session'] || localStorage.getItem(storageKey) || '';
      var data = new URLSearchParams();
      data.set('action', 'shcd_tiamis_public');
      data.set('nonce', config.ajaxNonce || '');
      data.set('route', route);
      data.set('http_method', method);
      data.set('token', token);
      data.set('payload', JSON.stringify(payload));
      return fetch(config.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: data.toString()
      }).then(function (response) {
        return response.json().then(function (body) {
          if (!response.ok || !body || !body.success) {
            var detail = body && body.data ? body.data : {};
            var error = new Error(detail.message || config.i18n.networkError);
            error.code = detail.code || 'ajax_error';
            throw error;
          }
          return body.data;
        });
      });
    }

    return fetch(url, options).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok) {
          var error = new Error(data.message || config.i18n.networkError);
          error.code = data.code || '';
          error.status = response.status;
          if (response.status === 404 || error.code === 'rest_no_route') return ajaxFallback(error);
          throw error;
        }
        return data;
      });
    }).catch(function (error) {
      if (error && (error.status === 404 || error.code === 'rest_no_route')) return ajaxFallback(error);
      if (error && error.name === 'TypeError') return ajaxFallback(error);
      throw error;
    });
  }

  function pageMeta() {
    var params = new URLSearchParams(window.location.search);
    return {
      page_url: window.location.href,
      referrer: document.referrer || '',
      utm_source: params.get('utm_source') || '',
      utm_medium: params.get('utm_medium') || '',
      utm_campaign: params.get('utm_campaign') || ''
    };
  }

  function applyRandomAnimation(widget) {
    var animations = ['float', 'pulse', 'bounce', 'shake', 'swing', 'wobble', 'heartbeat', 'tada', 'jelly', 'rotate', 'orbit', 'glow', 'wave', 'pop', 'slide'];
    var selected = widget.getAttribute('data-base-animation') || 'random';
    var toggleButton = widget.querySelector('.shcd-tiamis-toggle');
    animations.forEach(function (name) {
      widget.classList.remove('tiamis-bubble-animation-' + name);
      if (toggleButton) toggleButton.classList.remove('tiamis-active-animation-' + name);
    });
    if (selected === 'random' || widget.getAttribute('data-random-animation') === '1') {
      var randomValue = Date.now() ^ Math.floor((window.performance && performance.now ? performance.now() : Math.random() * 1000000) * 1000);
      if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
        var values = new Uint32Array(2);
        window.crypto.getRandomValues(values);
        randomValue = (values[0] ^ values[1] ^ randomValue) >>> 0;
      } else {
        randomValue = Math.abs(randomValue + Math.floor(Math.random() * 0x7fffffff));
      }
      var previous = '';
      try { previous = sessionStorage.getItem('shcd_tiamis_last_animation') || ''; } catch (e) {}
      selected = animations[randomValue % animations.length];
      if (animations.length > 1 && selected === previous) {
        selected = animations[(animations.indexOf(selected) + 1 + (randomValue % (animations.length - 1))) % animations.length];
      }
      try { sessionStorage.setItem('shcd_tiamis_last_animation', selected); } catch (e) {}
      widget.setAttribute('data-random-animation-resolved', selected);
    }
    if (selected !== 'none' && animations.indexOf(selected) !== -1) {
      widget.classList.add('tiamis-bubble-animation-' + selected);
      if (toggleButton) {
        void toggleButton.offsetWidth;
        toggleButton.classList.add('tiamis-active-animation-' + selected);
      }
    }
    widget.setAttribute('data-active-animation', selected);
  }

  function initWidget(widget) {
    if (!widget || widget.getAttribute('data-tiamis-initialized') === '1') return;
    widget.setAttribute('data-tiamis-initialized', '1');
    applyRandomAnimation(widget);
    var toggle = widget.querySelector('.shcd-tiamis-toggle');
    var close = widget.querySelector('.shcd-tiamis-close');
    var endConversationButton = widget.querySelector('[data-tiamis-end-conversation]');
    var panel = widget.querySelector('.shcd-tiamis-panel');
    var profile = widget.querySelector('[data-tiamis-profile]');
    var body = widget.querySelector('[data-shcd-tiamis-body]');
    var messagesBox = widget.querySelector('[data-tiamis-messages]');
    var form = widget.querySelector('[data-tiamis-form]');
    var input = widget.querySelector('[data-tiamis-input]');
    var notifyButton = widget.querySelector('[data-tiamis-notify]');
    var unread = widget.querySelector('[data-tiamis-unread]');
    var typingIndicator = widget.querySelector('[data-tiamis-typing]');
    var token = localStorage.getItem(storageKey) || '';
    var conversation = null;
    var lastMessageId = 0;
    var rendered = {};
    var unreadCount = 0;
    var isOpen = false;
    var initialized = false;
    var initialLoadComplete = false;
    var consentGranted = !config.consentRequired;
    var typingTimer = 0;
    var typingSentAt = 0;
    var audioContext = null;
    var pollBusy = false;
    var pollTimer = 0;
    var conversationStatus = 'open';
    var ratingSubmitted = false;
    var sending = false;
    var ratingPanel = widget.querySelector('[data-tiamis-rating]');
    var fileInput = widget.querySelector('[data-tiamis-file]');
    var fileState = widget.querySelector('[data-tiamis-file-state]');
    var pendingAttachment = null;
    var socket = null;
    var reconnectTimer = 0;
    var queueKey = 'shcd_tiamis_outbox_' + (token ? token.slice(0, 18) : 'guest');
    var inlineWidget = widget.classList.contains('tiamis-live-chat-inline');
    if (panel) {
      panel.hidden = !inlineWidget;
      panel.setAttribute('aria-hidden', inlineWidget ? 'false' : 'true');
    }

    function readOutbox() {
      if (!config.offlineQueueEnabled) return [];
      try { var parsed = JSON.parse(localStorage.getItem(queueKey) || '[]'); return Array.isArray(parsed) ? parsed.slice(0, 50) : []; } catch (e) { return []; }
    }
    function writeOutbox(items) {
      if (!config.offlineQueueEnabled) return;
      try { localStorage.setItem(queueKey, JSON.stringify(items.slice(-50))); } catch (e) {}
    }
    function queueMessage(payload, temporaryId) {
      var items = readOutbox();
      if (!items.some(function (item) { return item.payload.client_message_id === payload.client_message_id; })) items.push({ payload: payload, temporaryId: temporaryId, attempts: 0, createdAt: Date.now() });
      writeOutbox(items);
    }

    function showProfileGate() {
      widget.classList.add('is-profile-gate');
      profile.hidden = false;
      body.hidden = true;
      form.hidden = true;
    }

    function hideProfileGate() {
      widget.classList.remove('is-profile-gate');
      profile.hidden = true;
      body.hidden = false;
      form.hidden = false;
    }

    function validEmail(value) {
      if (!value) return false;
      var field = widget.querySelector('[data-tiamis-email]');
      if (field && typeof field.checkValidity === 'function') return field.checkValidity();
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function setOpen(open) {
      isOpen = open;
      if (open) panel.hidden = false;
      widget.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      panel.setAttribute('aria-hidden', open ? 'false' : 'true');
      if (!open) panel.hidden = true;
      if (open) {
        unreadCount = 0;
        updateUnread();
        if (!initialized && !token && config.profileGate) {
          showProfileGate();
          var firstField = widget.querySelector('[data-tiamis-name]');
          if (firstField) firstField.focus();
        } else if (!initialized) {
          ensureSession().then(function () {
            if (!profile.hidden) {
              var nameInput = widget.querySelector('[data-tiamis-name]');
              if (nameInput) nameInput.focus();
            } else if (input) {
              input.focus();
            }
          }).catch(function () {
            if (config.profileGate) showProfileGate();
          });
        } else if (input) {
          input.focus();
        }
      } else {
      }
    }

    function animateClose() {
      if (!isOpen) return;
      widget.classList.add('is-closing');
      window.setTimeout(function () {
        widget.classList.remove('is-closing');
        setOpen(false);
      }, 260);
    }

    function showRating() {
      if (!ratingPanel || ratingSubmitted) return false;
      ratingPanel.hidden = false;
      widget.classList.add('is-rating-open');
      return true;
    }

    function hideRating() {
      if (!ratingPanel) return;
      ratingPanel.hidden = true;
      widget.classList.remove('is-rating-open');
    }

    function updateUnread() {
      if (!unread) return;
      unread.textContent = String(unreadCount);
      unread.hidden = unreadCount < 1;
    }

    function profileData() {
      var name = widget.querySelector('[data-tiamis-name]');
      var email = widget.querySelector('[data-tiamis-email]');
      var phone = widget.querySelector('[data-tiamis-phone]');
      var consent = widget.querySelector('[data-tiamis-consent]');
      var honey = widget.querySelector('[data-tiamis-honeypot]');
      return Object.assign(pageMeta(), {
        token: token,
        name: name ? name.value.trim() : '',
        email: email ? email.value.trim() : '',
        phone: phone ? phone.value.trim() : '',
        consent: consent && consent.checked ? 1 : 0,
        website: honey ? honey.value : ''
      });
    }

    function ensureSession(data) {
      return jsonFetch('session', { method: 'POST', body: JSON.stringify(data || Object.assign(pageMeta(), { token: token })) }).then(function (response) {
        token = response.token;
        conversation = response.conversation;
        localStorage.setItem(storageKey, token);
        queueKey = 'shcd_tiamis_outbox_' + token.slice(0, 18);
        connectPushIdentity(conversation.public_id);
        initialized = true;
        consentGranted = !config.consentRequired || !!conversation.has_consent;
        if (consentGranted) window.dispatchEvent(new CustomEvent('shcd-tiamis-consent-granted'));
        var needsProfile = config.profileGate && !conversation.profile_complete;
        if (needsProfile) showProfileGate(); else hideProfileGate();
        if (!needsProfile) { connectRealtime(); flushOutbox(); }
        return needsProfile ? Promise.resolve() : loadMessages();
      });
    }

    function receiptLabel(seen) {
      return seen ? (config.i18n.seen || 'دیده شد') : (config.i18n.delivered || 'تحویل شد');
    }

    function updateSeenReceipts(seenThrough) {
      var through = Number(seenThrough || 0);
      messagesBox.querySelectorAll('.shcd-tiamis-message.is-visitor[data-message-id]').forEach(function (row) {
        var receipt = row.querySelector('[data-tiamis-receipt]');
        if (!receipt) return;
        var seen = Number(row.getAttribute('data-message-id') || 0) <= through;
        receipt.classList.toggle('is-seen', seen);
        receipt.textContent = seen ? '✓✓' : '✓';
        receipt.setAttribute('title', receiptLabel(seen));
        receipt.setAttribute('aria-label', receiptLabel(seen));
      });
    }

    function setOperatorTyping(active, agent) {
      if (!typingIndicator) return;
      typingIndicator.hidden = !active;
      if (active && agent) {
        activateAgentProfile({
          sender: 'admin',
          sender_name: agent.agent_name || '',
          sender_role: agent.agent_role || '',
          sender_photo: agent.agent_photo || '',
          agent_index: agent.agent_index || 0
        });
        var label = typingIndicator.querySelector('b');
        if (label && agent.agent_name) label.textContent = agent.agent_name + (config.i18n.typingSuffix || ' در حال تایپ است…');
      }
      if (active) messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    function sendTyping(active) {
      if (!token || !initialized) return;
      var now = Date.now();
      if (active && now - typingSentAt < 1500) return;
      if (active) typingSentAt = now;
      jsonFetch('typing', {
        method: 'POST',
        headers: { 'X-SHCD-Tiamis-Session': token },
        body: JSON.stringify({ typing: active ? 1 : 0 })
      }).catch(function () {});
    }

    function getAudioContext() {
      if (!config.soundsEnabled) return null;
      if (!audioContext) {
        var AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return null;
        try { audioContext = new AudioContext(); } catch (e) { return null; }
      }
      if (audioContext.state === 'suspended') audioContext.resume().catch(function () {});
      return audioContext;
    }

    function tone(frequency, start, duration, volume) {
      var context = getAudioContext();
      if (!context) return;
      var oscillator = context.createOscillator();
      var gain = context.createGain();
      oscillator.type = 'sine';
      oscillator.frequency.setValueAtTime(frequency, context.currentTime + start);
      gain.gain.setValueAtTime(0.0001, context.currentTime + start);
      gain.gain.exponentialRampToValueAtTime(volume, context.currentTime + start + 0.015);
      gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + start + duration);
      oscillator.connect(gain); gain.connect(context.destination);
      oscillator.start(context.currentTime + start);
      oscillator.stop(context.currentTime + start + duration + 0.03);
    }

    function playChatSound(kind) {
      if (!config.soundsEnabled) return;
      if (kind === 'sent') {
        tone(720, 0, 0.08, 0.045); tone(980, 0.09, 0.11, 0.04);
      } else {
        tone(520, 0, 0.12, 0.05); tone(740, 0.14, 0.12, 0.05); tone(960, 0.29, 0.14, 0.045);
      }
    }

    function activateAgentProfile(message) {
      if (!message || message.sender !== 'admin') return;
      var stack = widget.querySelector('.shcd-tiamis-agent-stack');
      if (!stack) return;
      var profiles = Array.prototype.slice.call(stack.querySelectorAll('[data-tiamis-agent-profile]'));
      var active = profiles.find(function (profile) {
        return String(profile.getAttribute('data-agent-index')) === String(message.agent_index) ||
          String(profile.getAttribute('data-agent-name') || '').trim() === String(message.sender_name || '').trim();
      });
      if (!active && (message.sender_name || message.sender_photo)) {
        active = document.createElement(message.sender_photo ? 'img' : 'i');
        active.setAttribute('data-tiamis-agent-profile', '');
        active.setAttribute('data-agent-index', String(message.agent_index || 0));
        active.setAttribute('data-agent-name', message.sender_name || '');
        if (message.sender_photo) {
          active.src = message.sender_photo;
          active.alt = message.sender_name || '';
        } else {
          active.textContent = String(message.sender_name || config.i18n.support || 'پ').trim().charAt(0);
        }
        stack.appendChild(active);
      }
      stack.querySelectorAll('[data-tiamis-agent-profile]').forEach(function (profile) { profile.classList.remove('is-active'); });
      if (active) {
        active.classList.add('is-active');
        stack.insertBefore(active, stack.firstChild);
        active.title = [message.sender_name || '', message.sender_role || ''].filter(Boolean).join(' — ');
      }
    }

    function renderMessage(message) {
      if (rendered[message.id]) return;
      rendered[message.id] = true;
      var numericMessageId = Number(message.id);
      if (Number.isFinite(numericMessageId)) lastMessageId = Math.max(lastMessageId, numericMessageId);
      var row = document.createElement('div');
      row.className = 'shcd-tiamis-message is-' + message.sender;
      row.setAttribute('data-message-id', String(message.id));
      if (message.pending) row.classList.add('is-pending');
      if (message.sender === 'admin') {
        activateAgentProfile(message);
        var avatar = document.createElement('span');
        avatar.className = 'shcd-tiamis-message-avatar';
        if (message.sender_photo) {
          var avatarImage = document.createElement('img');
          avatarImage.src = message.sender_photo;
          avatarImage.alt = message.sender_name || config.i18n.support;
          avatar.appendChild(avatarImage);
        } else {
          avatar.textContent = String(message.sender_name || config.i18n.support || 'پ').trim().charAt(0);
        }
        row.appendChild(avatar);
      }
      var bubble = document.createElement('div');
      var text = document.createElement('span');
      text.textContent = message.deleted_at ? (config.i18n.deletedMessage || 'این پیام حذف شده است.') : (message.body || '');
      if (message.deleted_at) row.classList.add('is-deleted');
      if (message.edited_at) row.classList.add('is-edited');
      var meta = document.createElement('small');
      var date = new Date(String(message.created_at).replace(' ', 'T'));
      var identity = document.createElement('span');
      identity.textContent = (message.sender === 'visitor' ? config.i18n.you : (message.sender_name || config.i18n.support)) + ' • ' + (isNaN(date.getTime()) ? message.created_at : date.toLocaleTimeString(config.locale || 'fa-IR', { hour: '2-digit', minute: '2-digit' }));
      meta.appendChild(identity);
      if (message.sender === 'visitor') {
        var receipt = document.createElement('i');
        receipt.className = 'shcd-tiamis-receipt' + (message.is_seen ? ' is-seen' : '');
        receipt.setAttribute('data-tiamis-receipt', '');
        receipt.textContent = message.is_seen ? '✓✓' : '✓';
        receipt.setAttribute('title', receiptLabel(!!message.is_seen));
        receipt.setAttribute('aria-label', receiptLabel(!!message.is_seen));
        meta.appendChild(receipt);
      }
      bubble.appendChild(text);
      if (message.attachment && message.attachment.url) {
        var attachment = document.createElement('a');
        attachment.className = 'shcd-tiamis-message-attachment';
        attachment.href = message.attachment.url;
        attachment.target = '_blank'; attachment.rel = 'noopener';
        attachment.textContent = '📎 ' + (message.attachment.name || config.i18n.attachment || 'فایل پیوست');
        bubble.appendChild(attachment);
      }
      if (Array.isArray(message.reactions) && message.reactions.length) {
        var reactions = document.createElement('div'); reactions.className = 'shcd-tiamis-reactions';
        message.reactions.forEach(function (reaction) { var badge = document.createElement('button'); badge.type = 'button'; badge.textContent = reaction.reaction + ' ' + reaction.count; reactions.appendChild(badge); });
        bubble.appendChild(reactions);
      }
      if (!message.pending && !message.deleted_at) {
        var quick = document.createElement('div'); quick.className = 'shcd-tiamis-message-tools';
        ['👍','❤️','🙂'].forEach(function (emoji) {
          var react = document.createElement('button'); react.type = 'button'; react.textContent = emoji; react.title = config.i18n.reaction || 'واکنش';
          react.addEventListener('click', function () { jsonFetch('reaction', { method: 'POST', headers: { 'X-SHCD-Tiamis-Session': token }, body: JSON.stringify({ message_id: message.id, reaction: emoji }) }).then(function (r) { react.classList.add('is-selected'); }).catch(function () {}); });
          quick.appendChild(react);
        });
        bubble.appendChild(quick);
      }
      if ((message.sender === 'admin' || message.sender === 'ai') && !message.pending) {
        var reportButton = document.createElement('button');
        reportButton.type = 'button';
        reportButton.className = 'shcd-tiamis-report';
        reportButton.textContent = '!';
        reportButton.title = config.i18n.report || 'گزارش پیام';
        reportButton.setAttribute('aria-label', config.i18n.report || 'گزارش پیام');
        reportButton.addEventListener('click', function () {
          if (!window.confirm(config.i18n.reportConfirm || 'این پیام گزارش شود؟')) return;
          reportButton.disabled = true;
          jsonFetch('report', { method: 'POST', headers: { 'X-SHCD-Tiamis-Session': token }, body: JSON.stringify({ message_id: message.id, reason: 'inappropriate' }) })
            .then(function () { reportButton.textContent = '✓'; reportButton.title = config.i18n.reported || 'گزارش ثبت شد'; row.classList.add('is-reported'); })
            .catch(function (error) { reportButton.disabled = false; window.alert(error.message || config.i18n.networkError); });
        });
        bubble.appendChild(reportButton);
      }
      bubble.appendChild(meta);
      row.appendChild(bubble);
      messagesBox.appendChild(row);
      messagesBox.scrollTop = messagesBox.scrollHeight;

      if ((message.sender === 'admin' || message.sender === 'ai') && (!isOpen || document.visibilityState !== 'visible')) {
        unreadCount += 1;
        updateUnread();
        if (initialLoadComplete) playChatSound('unread');
      }
      if (initialLoadComplete && (message.sender === 'admin' || message.sender === 'ai')) {
        showBrowserNotification(message.body);
      }
    }

    function loadMessages() {
      if (!token || pollBusy || document.visibilityState === 'hidden') return Promise.resolve();
      pollBusy = true;
      return jsonFetch('sync?after=' + encodeURIComponent(lastMessageId), { headers: { 'X-SHCD-Tiamis-Session': token } }).then(function (response) {
        (response.messages || []).forEach(renderMessage);
        updateSeenReceipts(response.visitor_seen_through);
        setOperatorTyping(!!response.operator_typing, response.operator_typing_agent || {});
        widget.classList.toggle('is-operator-online', !!response.operator_online);
        conversationStatus = response.status || (response.conversation && response.conversation.status) || conversationStatus;
        ratingSubmitted = Number(response.rating || 0) > 0;
        if (conversationStatus === 'closed' && !ratingSubmitted) showRating();
        if (response.conversation && response.conversation.blocked) {
          form.classList.add('is-blocked');
          input.disabled = true;
          input.placeholder = config.i18n.blocked || 'ارسال پیام غیرفعال است.';
        }
        initialLoadComplete = true;
      }).catch(function () {}).finally(function () { pollBusy = false; });
    }

    function submitMessage(event) {
      event.preventDefault();
      if (event.stopImmediatePropagation) event.stopImmediatePropagation();
      var value = input.value.trim();
      var now = Date.now();
      var currentSignature = value + '|' + String(pendingAttachment && pendingAttachment.id ? pendingAttachment.id : 0);
      var previousSignature = form.getAttribute('data-tiamis-last-signature') || '';
      var previousTime = Number(form.getAttribute('data-tiamis-last-submit-at') || 0);
      if (!value || sending || form.getAttribute('data-tiamis-submitting') === '1' || !token || !initialized || !profile.hidden) {
        if (config.profileGate && !profile.hidden) showProfileGate();
        return;
      }
      if (currentSignature === previousSignature && now - previousTime < 1800) return;
      getAudioContext();
      sending = true;
      form.setAttribute('data-tiamis-submitting', '1');
      form.setAttribute('data-tiamis-last-signature', currentSignature);
      form.setAttribute('data-tiamis-last-submit-at', String(now));
      var button = form.querySelector('button[type="submit"]');
      var messageHoneypot = form.querySelector('[name="website"]');
      var messageClientId = form.getAttribute('data-tiamis-pending-client-id') || clientId();
      form.setAttribute('data-tiamis-pending-client-id', messageClientId);
      var payload = Object.assign(profileData(), { message: value, website: messageHoneypot ? messageHoneypot.value : '', client_message_id: messageClientId, attachment_id: pendingAttachment ? pendingAttachment.id : 0 });
      var temporaryId = 'tmp-' + messageClientId;
      var optimistic = { id: temporaryId, client_message_id: messageClientId, sender: 'visitor', sender_name: config.i18n.you, body: value, attachment: pendingAttachment, created_at: new Date().toISOString(), is_seen: false, pending: true };
      input.value = '';
      renderMessage(optimistic);
      playChatSound('sent');
      sendTyping(false);
      window.clearTimeout(typingTimer);
      if (button) button.classList.add('is-sending');
      jsonFetch('messages', { method: 'POST', headers: { 'X-SHCD-Tiamis-Session': token }, body: JSON.stringify(payload) }).then(function (response) {
        var row = messagesBox.querySelector('[data-message-id="' + temporaryId + '"]');
        if (row && response.message) {
          row.setAttribute('data-message-id', String(response.message.id));
          row.classList.remove('is-pending');
          delete rendered[temporaryId];
          rendered[response.message.id] = true;
          lastMessageId = Math.max(lastMessageId, Number(response.message.id || 0));
        } else if (response.message) {
          renderMessage(response.message);
        }
        updateSeenReceipts(0);
        pendingAttachment = null;
        if (fileInput) fileInput.value = '';
        if (fileState) { fileState.hidden = true; fileState.textContent = ''; }
        var queued = readOutbox().filter(function (item) { return item.payload.client_message_id !== messageClientId; }); writeOutbox(queued);
        window.setTimeout(loadMessages, 80);
      }).catch(function (error) {
        var row = messagesBox.querySelector('[data-message-id="' + temporaryId + '"]');
        if (row) { row.classList.add('is-failed'); row.title = config.i18n.offlineRetry || 'پس از برقراری اینترنت دوباره ارسال می‌شود.'; }
        if (config.offlineQueueEnabled) {
          queueMessage(payload, temporaryId);
        } else {
          input.value = value;
          window.alert(error.message || config.i18n.networkError);
        }
      }).finally(function () {
        sending = false;
        form.removeAttribute('data-tiamis-submitting');
        if (form.getAttribute('data-tiamis-pending-client-id') === messageClientId) {
          form.removeAttribute('data-tiamis-pending-client-id');
        }
        if (button) button.classList.remove('is-sending');
        input.focus();
      });
    }

    function flushOutbox() {
      if (!token || !initialized || !navigator.onLine) return Promise.resolve();
      var items = readOutbox();
      if (!items.length) return Promise.resolve();
      var item = items[0]; item.attempts = Number(item.attempts || 0) + 1; writeOutbox(items);
      return jsonFetch('messages', { method: 'POST', headers: { 'X-SHCD-Tiamis-Session': token }, body: JSON.stringify(item.payload) }).then(function (response) {
        var row = messagesBox.querySelector('[data-message-id="' + item.temporaryId + '"]');
        if (row && response.message) { row.setAttribute('data-message-id', String(response.message.id)); row.classList.remove('is-pending','is-failed'); }
        writeOutbox(items.slice(1));
        return flushOutbox();
      }).catch(function () { return null; });
    }

    function connectRealtime() {
      if (!conversation || !conversation.public_id || !config.realtime || config.realtime.mode === 'ajax' || !config.realtime.websocket || !('WebSocket' in window)) return;
      try {
        var url = new URL(config.realtime.websocket, window.location.href);
        url.searchParams.set('room', conversation.public_id);
        url.searchParams.set('token', token);
        socket = new WebSocket(url.toString());
        socket.addEventListener('open', function () { widget.classList.add('is-realtime'); });
        socket.addEventListener('message', function (event) {
          try { var data = JSON.parse(event.data || '{}'); var payload = data.payload || data; if (payload.type === 'message.created' && payload.message) renderMessage(payload.message); if (payload.type === 'typing') setOperatorTyping(!!payload.active, payload.agent || {}); } catch (e) {}
        });
        socket.addEventListener('close', function () { widget.classList.remove('is-realtime'); socket = null; window.clearTimeout(reconnectTimer); reconnectTimer = window.setTimeout(connectRealtime, 2500); });
        socket.addEventListener('error', function () { try { socket.close(); } catch (e) {} });
      } catch (e) {}
    }

    function showBrowserNotification(text) {
      if (config.oneSignal && config.oneSignal.enabled) return;
      if (!config.browserNotifications || !('Notification' in window) || Notification.permission !== 'granted' || document.visibilityState === 'visible') return;
      try {
        var headerImage = widget.querySelector('.shcd-tiamis-header img');
        var notificationOptions = { body: text, tag: 'shcd-tiamis-reply' };
        if (headerImage && headerImage.src) notificationOptions.icon = headerImage.src;
        var notification = new Notification(config.i18n.newReply, notificationOptions);
        notification.onclick = function () { window.focus(); setOpen(true); notification.close(); };
      } catch (e) {}
    }

    widget.addEventListener('pointerdown', getAudioContext, { passive: true });
    widget.addEventListener('keydown', getAudioContext);
    toggle.addEventListener('click', function () { getAudioContext(); setOpen(!isOpen); });
    close.addEventListener('click', function () {
      getAudioContext();
      // Closing the bubble keeps the conversation open and does not request a rating.
      animateClose();
    });
    if (endConversationButton) {
      endConversationButton.addEventListener('click', function () {
        getAudioContext();
        if (!initialized || lastMessageId <= 0) {
          animateClose();
          return;
        }
        endConversationButton.disabled = true;
        jsonFetch('conversation/end', {
          method: 'POST',
          headers: { 'X-SHCD-Tiamis-Session': token },
          body: JSON.stringify({ reason: 'visitor_ended' })
        }).then(function () {
          conversationStatus = 'closed';
          if (!ratingSubmitted) showRating();
        }).catch(function (error) {
          endConversationButton.disabled = false;
          window.alert(error.message || config.i18n.networkError);
        });
      });
    }
    form.addEventListener('submit', submitMessage);
    input.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && !event.shiftKey && !event.isComposing && !event.repeat) { event.preventDefault(); event.stopPropagation(); getAudioContext(); form.requestSubmit(); }
    });
    input.addEventListener('input', function () {
      var active = input.value.trim().length > 0;
      sendTyping(active);
      window.clearTimeout(typingTimer);
      if (active) typingTimer = window.setTimeout(function () { sendTyping(false); }, 2600);
    });
    input.addEventListener('blur', function () {
      window.clearTimeout(typingTimer);
      sendTyping(false);
    });

    if (fileInput) {
      fileInput.addEventListener('change', function () {
        var file = fileInput.files && fileInput.files[0];
        if (!file || !token) return;
        if (fileState) { fileState.hidden = false; fileState.textContent = config.i18n.filePreparing || 'در حال آماده‌سازی فایل…'; }
        var data = new FormData(); data.append('file', file);
        fetch(config.restUrl + 'upload', { method: 'POST', credentials: 'same-origin', headers: { 'X-SHCD-Tiamis-Session': token }, body: data }).then(function (response) { return response.json().then(function (body) { if (!response.ok) throw new Error(body.message || config.i18n.networkError); return body; }); }).then(function (body) {
          pendingAttachment = body.attachment || null;
          if (fileState) fileState.textContent = pendingAttachment ? (config.i18n.fileReady || 'فایل آماده است: ') + pendingAttachment.name : '';
        }).catch(function (error) { fileInput.value = ''; pendingAttachment = null; if (fileState) fileState.textContent = error.message || config.i18n.networkError; });
      });
    }

    if (ratingPanel) {
      ratingPanel.querySelectorAll('[data-rating]').forEach(function (button) {
        button.addEventListener('click', function () {
          var rating = Number(button.getAttribute('data-rating') || 0);
          var label = button.getAttribute('data-label') || button.textContent.trim();
          ratingPanel.classList.add('is-submitting');
          jsonFetch('rating', { method: 'POST', headers: { 'X-SHCD-Tiamis-Session': token }, body: JSON.stringify({ rating: rating, label: label }) })
            .then(function () {
              ratingSubmitted = true;
              ratingPanel.innerHTML = '<div class="shcd-tiamis-rating-thanks">' + (config.i18n.rateThanks || 'ممنون از بازخورد شما 🌷') + '</div>';
              window.setTimeout(function () { hideRating(); animateClose(); }, 900);
            }).catch(function (error) { ratingPanel.classList.remove('is-submitting'); window.alert(error.message || config.i18n.networkError); });
        });
      });
      var later = ratingPanel.querySelector('[data-tiamis-rating-later]');
      if (later) later.addEventListener('click', function () { hideRating(); animateClose(); });
    }

    var profileSubmit = widget.querySelector('[data-tiamis-profile-submit]');
    if (profileSubmit) {
      profileSubmit.addEventListener('click', function () {
        var data = profileData();
        var error = widget.querySelector('[data-tiamis-profile-error]');
        error.textContent = '';
        if (config.profileRequired && !data.name) { error.textContent = config.i18n.nameRequired; return; }
        if (config.collectEmail && !validEmail(data.email)) { error.textContent = config.i18n.emailRequired; return; }
        if (config.collectPhone && !data.phone) { error.textContent = config.i18n.phoneRequired; return; }
        if (config.consentRequired && !data.consent) { error.textContent = config.i18n.consentRequired; return; }
        profileSubmit.disabled = true;
        ensureSession(data).then(function () { hideProfileGate(); input.focus(); }).catch(function (e) { error.textContent = e.message; }).finally(function () { profileSubmit.disabled = false; });
      });
    }

    if (notifyButton && config.oneSignal && config.oneSignal.enabled) {
      getOneSignal().then(function (OneSignal) {
        if (OneSignal && OneSignal.Notifications && OneSignal.Notifications.permission) notifyButton.hidden = true;
      }).catch(function () {});
      notifyButton.addEventListener('click', function () {
        notifyButton.disabled = true;
        ensureSession().then(function () {
          return getOneSignal();
        }).then(async function (OneSignal) {
          if (!OneSignal) throw new Error(config.i18n.networkError);
          if (conversation && conversation.public_id && typeof OneSignal.login === 'function') await OneSignal.login(conversation.public_id);
          if (OneSignal.Notifications && typeof OneSignal.Notifications.requestPermission === 'function') await OneSignal.Notifications.requestPermission();
          if (OneSignal.User && OneSignal.User.PushSubscription && typeof OneSignal.User.PushSubscription.optIn === 'function') await OneSignal.User.PushSubscription.optIn();
          if (OneSignal.Notifications && OneSignal.Notifications.permission) {
            notifyButton.hidden = true;
          }
        }).catch(function () {
          window.alert(config.i18n.notificationDenied || config.i18n.networkError);
        }).finally(function () {
          notifyButton.disabled = false;
        });
      });
    } else if (notifyButton && 'Notification' in window) {
      if (Notification.permission === 'granted') notifyButton.hidden = true;
      notifyButton.addEventListener('click', function () {
        Notification.requestPermission().then(function (permission) {
          if (permission === 'granted') { notifyButton.hidden = true; } else { window.alert(config.i18n.notificationDenied); }
        });
      });
    }

    // Returning visitors are initialized silently so new replies, unread badges and
    // browser notifications can arrive without requiring the panel to be opened first.
    if (token) ensureSession().catch(function () { if (config.profileGate) showProfileGate(); });
    function schedulePoll(delay) {
      window.clearTimeout(pollTimer);
      pollTimer = window.setTimeout(function tick() {
        if (!initialized) { schedulePoll(500); return; }
        loadMessages().finally(function () {
          var next = document.visibilityState === 'hidden' ? Math.max(4500, Number(config.idlePollInterval || 2200) * 2) : (isOpen ? Number(config.pollInterval || 450) : Number(config.idlePollInterval || 2200));
          schedulePoll(next);
        });
      }, typeof delay === 'number' ? delay : 500);
    }
    schedulePoll(180);
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible' && initialized) { loadMessages(); schedulePoll(120); }
      else if (document.visibilityState === 'hidden') sendTyping(false);
    });
    window.addEventListener('online', function () { flushOutbox(); loadMessages(); });
    window.addEventListener('pagehide', function () { window.clearTimeout(pollTimer); window.clearTimeout(reconnectTimer); if (socket) try { socket.close(); } catch (e) {} sendTyping(false); });
  }

  function ready() {
    document.querySelectorAll('[data-shcd-tiamis]').forEach(initWidget);
    document.addEventListener('click', function (event) {
      var trigger = event.target && event.target.closest ? event.target.closest('[data-tiamis-open-chat]') : null;
      if (!trigger) return;
      var widget = document.querySelector('[data-shcd-tiamis]');
      var toggle = widget ? widget.querySelector('.shcd-tiamis-toggle') : null;
      if (toggle) { event.preventDefault(); toggle.click(); }
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
}());
