/* ===================================================================
   Smart Music Practice Tracking System — shared front-end behaviour
   =================================================================== */

// ---- Modal helpers -------------------------------------------------
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
}
document.addEventListener('click', function (e) {
  if (e.target.classList && e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(function (m) {
      m.classList.remove('open');
    });
  }
});

// ---- Confirm-before-submit for destructive actions -----------------
function confirmDelete(message) {
  return window.confirm(message || 'Are you sure? This action cannot be undone.');
}

// ---- Show/hide password toggles -------------------------------------
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const input = document.getElementById(btn.dataset.target);
      if (!input) return;
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.textContent = showing ? '👁️' : '🙈';
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
  });
});

// ---- Close sidebar on nav-link click (mobile) -----------------------
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.nav-link').forEach(function (link) {
    link.addEventListener('click', function () {
      const sb = document.getElementById('sidebar');
      if (sb && window.innerWidth <= 900) sb.classList.remove('open');
    });
  });
});

// =====================================================================
// Practice Timer widget (student dashboard)
// Persists elapsed seconds in sessionStorage so an accidental refresh
// doesn't lose progress. Also records the student's microphone for the
// full duration of the timer (unlimited length) and auto-attaches the
// recording to the "Log Session" form when the student is done.
// =====================================================================
(function () {
  const timerDisplay = document.getElementById('timerDisplay');
  if (!timerDisplay) return; // timer not on this page

  const startBtn = document.getElementById('timerStart');
  const pauseBtn = document.getElementById('timerPause');
  const resetBtn = document.getElementById('timerReset');
  const logBtn   = document.getElementById('timerLog');
  const eqBars   = document.getElementById('timerEq');
  const recStatus = document.getElementById('recStatus');

  let seconds = parseInt(sessionStorage.getItem('pt_timer_seconds') || '0', 10);
  let running = false;
  let interval = null;

  function format(s) {
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;
    return [h, m, sec].map(function (v) { return String(v).padStart(2, '0'); }).join(':');
  }

  function render() {
    timerDisplay.textContent = format(seconds);
    sessionStorage.setItem('pt_timer_seconds', seconds);
  }

  function tick() {
    seconds += 1;
    render();
  }

  // --- Microphone recording (unlimited duration) ---
  let mediaRecorder = null;
  let mediaStream = null;
  let recordedChunks = [];

  function setRecPill(state, text) {
    if (!recStatus) return;
    recStatus.style.display = 'inline-flex';
    recStatus.className = 'rec-pill ' + state;
    recStatus.innerHTML = (state === 'recording' ? '<span class="rec-dot"></span> ' : '') + text;
  }

  async function startRecording() {
    try {
      mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
      recordedChunks = [];
      const preferredType = 'audio/webm';
      const options = (window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(preferredType))
        ? { mimeType: preferredType } : undefined;
      mediaRecorder = options ? new MediaRecorder(mediaStream, options) : new MediaRecorder(mediaStream);
      mediaRecorder.addEventListener('dataavailable', function (e) {
        if (e.data && e.data.size > 0) recordedChunks.push(e.data);
      });
      mediaRecorder.start();
      setRecPill('recording', 'Recording your practice…');
    } catch (err) {
      setRecPill('warning', '⚠️ Mic access unavailable — timer will run without recording.');
    }
  }

  function pauseRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
      mediaRecorder.pause();
      setRecPill('warning', 'Recording paused');
    }
  }

  function resumeRecording() {
    if (mediaRecorder && mediaRecorder.state === 'paused') {
      mediaRecorder.resume();
      setRecPill('recording', 'Recording your practice…');
    }
  }

  function stopStream() {
    if (mediaStream) {
      mediaStream.getTracks().forEach(function (t) { t.stop(); });
      mediaStream = null;
    }
  }

  function discardRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      try { mediaRecorder.stop(); } catch (e) {}
    }
    stopStream();
    recordedChunks = [];
    mediaRecorder = null;
    if (recStatus) recStatus.style.display = 'none';
  }

  /** Stops recording (if any) and hands the resulting audio Blob to `callback`. */
  function stopAndAttach(callback) {
    if (!mediaRecorder || mediaRecorder.state === 'inactive') {
      callback(null);
      return;
    }
    mediaRecorder.addEventListener('stop', function onStop() {
      const blob = new Blob(recordedChunks, { type: 'audio/webm' });
      stopStream();
      callback(blob.size > 0 ? blob : null);
    }, { once: true });
    mediaRecorder.stop();
  }

  startBtn && startBtn.addEventListener('click', function () {
    if (running) return;
    running = true;
    interval = setInterval(tick, 1000);
    if (eqBars) eqBars.classList.remove('is-paused');
    startBtn.disabled = true;
    pauseBtn.disabled = false;
    if (!mediaRecorder) {
      startRecording();
    } else {
      resumeRecording();
    }
  });

  pauseBtn && pauseBtn.addEventListener('click', function () {
    running = false;
    clearInterval(interval);
    if (eqBars) eqBars.classList.add('is-paused');
    startBtn.disabled = false;
    pauseBtn.disabled = true;
    pauseRecording();
  });

  resetBtn && resetBtn.addEventListener('click', function () {
    running = false;
    clearInterval(interval);
    seconds = 0;
    render();
    if (eqBars) eqBars.classList.add('is-paused');
    startBtn.disabled = false;
    pauseBtn.disabled = true;
    discardRecording();
  });

  logBtn && logBtn.addEventListener('click', function () {
    running = false;
    clearInterval(interval);
    const minutes = Math.max(1, Math.round(seconds / 60));
    const minutesInput = document.getElementById('duration_minutes');
    if (minutesInput) minutesInput.value = minutes;

    if (recStatus) setRecPill('warning', 'Finishing recording…');

    stopAndAttach(function (blob) {
      if (blob) {
        const file = new File([blob], 'practice_recording_' + Date.now() + '.webm', { type: 'audio/webm' });
        const fileInput = document.getElementById('practice_video_input');
        if (fileInput && window.DataTransfer) {
          const dt = new DataTransfer();
          dt.items.add(file);
          fileInput.files = dt.files;
        }
        const preview = document.getElementById('recPreview');
        if (preview) {
          preview.style.display = 'block';
          const audioEl = preview.querySelector('audio');
          if (audioEl) audioEl.src = URL.createObjectURL(blob);
        }
        if (recStatus) recStatus.style.display = 'none';
      } else if (recStatus) {
        recStatus.style.display = 'none';
      }
      openModal('addSessionModal');
    });
  });

  // initial paint
  pauseBtn && (pauseBtn.disabled = true);
  render();
})();
