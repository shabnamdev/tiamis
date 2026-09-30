(function () {
  'use strict';
  var config = window.TiamisAdminNotify || {};
  if (!config.ajaxUrl || !config.nonce) return;

  var storageKey = 'shcd_tiamis_admin_last_visitor_message';
  var audioContext = null;
  var initialized = false;

  function unlockAudio() {
    if (!audioContext) {
      var Context = window.AudioContext || window.webkitAudioContext;
      if (Context) {
        try { audioContext = new Context(); } catch (error) {}
      }
    }
    if ('Notification' in window && Notification.permission === 'default') {
      try { Notification.requestPermission().catch(function () {}); } catch (error) {}
    }
  }

  document.addEventListener('pointerdown', unlockAudio, { once: true, passive: true });
  document.addEventListener('keydown', unlockAudio, { once: true });

  function playSound() {
    if (!audioContext) return;
    if (audioContext.state === 'suspended') audioContext.resume().catch(function () {});
    [620, 880].forEach(function (frequency, index) {
      var delay = index * 0.11;
      var oscillator = audioContext.createOscillator();
      var gain = audioContext.createGain();
      oscillator.frequency.value = frequency;
      gain.gain.setValueAtTime(0.0001, audioContext.currentTime + delay);
      gain.gain.exponentialRampToValueAtTime(0.045, audioContext.currentTime + delay + 0.01);
      gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + delay + 0.12);
      oscillator.connect(gain);
      gain.connect(audioContext.destination);
      oscillator.start(audioContext.currentTime + delay);
      oscillator.stop(audioContext.currentTime + delay + 0.15);
    });
  }

  function updateBadge(type, count) {
    count = Math.max(0, Number(count || 0));
    document.querySelectorAll('[data-tiamis-badge="' + type + '"]').forEach(function (badge) {
      var value = badge.querySelector('.pending-count, .plugin-count');
      if (value) value.textContent = String(count);
      badge.classList.toggle('is-zero', count < 1);
      badge.className = badge.className.replace(/\bcount-\d+\b/g, '').trim() + ' count-' + count;
    });
  }

  function updateBadges(counts) {
    counts = counts || {};
    updateBadge('chat', counts.chat);
    updateBadge('total', counts.total);
  }

  function toast(message, title, url) {
    var item = document.createElement('a');
    item.className = 'tiamis-global-chat-notice is-chat';
    item.href = url || '#';
    item.innerHTML = '<span aria-hidden="true">💬</span><div><b></b><small></small></div>';
    item.querySelector('b').textContent = title;
    item.querySelector('small').textContent = message;
    document.body.appendChild(item);
    window.setTimeout(function () { item.classList.add('is-visible'); }, 20);
    window.setTimeout(function () { item.classList.remove('is-visible'); }, 7000);
    window.setTimeout(function () { if (item.parentNode) item.remove(); }, 7500);
  }

  function notify(message) {
    var name = message.visitor_name || 'بازدیدکننده';
    var title = config.title || 'پیام تازه در تیامیس';
    var text = String(config.body || 'یک پیام تازه از %s دریافت شد.').replace('%s', name);
    var target = message.url || config.inboxUrl || '#';
    toast(text, title, target);
    playSound();
    if ('Notification' in window && Notification.permission === 'granted') {
      try {
        var notification = new Notification(title, {
          body: name + ': ' + message.body,
          tag: 'tiamis-admin-chat-' + message.id,
          renotify: true
        });
        notification.onclick = function () {
          window.focus();
          window.location.href = target;
          notification.close();
        };
      } catch (error) {}
    }
  }

  function poll() {
    var last = Number(localStorage.getItem(storageKey) || 0);
    var data = new URLSearchParams();
    data.set('action', 'shcd_tiamis_poll_notifications');
    data.set('nonce', config.nonce);
    data.set('after', String(last));
    fetch(config.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: data.toString()
    }).then(function (response) {
      return response.json();
    }).then(function (response) {
      if (!response || !response.success) return;
      updateBadges(response.data.counts || {});
      var latest = Number(response.data.latest_id || 0);
      if (!initialized && !last) {
        initialized = true;
        var count = Number((response.data.counts || {}).chat || 0);
        if (count > 0) {
          toast(String(config.unreadChatSummary || '%s گفتگوی خوانده‌نشده در صندوق تیامیس دارید.').replace('%s', count), config.title || 'پیام‌های تیامیس', config.inboxUrl || '#');
        }
        if (latest) localStorage.setItem(storageKey, String(latest));
        return;
      }
      initialized = true;
      (response.data.messages || []).forEach(notify);
      if (latest) localStorage.setItem(storageKey, String(latest));
    }).catch(function () {});
  }

  poll();
  window.setInterval(poll, 2500);
}());
