<?php
/* FITTRACK Global AI Copilot - reusable UI. Existing page logic is untouched. */
?>
<style>
  #fittrack-ai-launcher {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 99990;
    border: 0;
    border-radius: 999px;
    padding: 12px 16px;
    background: #12c7a0;
    color: #061412;
    font: 800 12px Inter, Arial, sans-serif;
    box-shadow: 0 10px 30px rgba(0, 0, 0, .28);
    cursor: pointer
  }

  #fittrack-ai-panel {
    position: fixed;
    right: 22px;
    bottom: 78px;
    width: 360px;
    max-width: calc(100vw - 28px);
    height: 520px;
    max-height: calc(100vh - 110px);
    z-index: 99991;
    background: #081517;
    color: #dce8e6;
    border: 1px solid rgba(255, 255, 255, .1);
    border-radius: 16px;
    box-shadow: 0 22px 60px rgba(0, 0, 0, .42);
    display: none;
    overflow: hidden;
    font-family: Inter, Arial, sans-serif
  }

  #fittrack-ai-panel.open {
    display: flex;
    flex-direction: column
  }

  .ft-ai-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 15px;
    border-bottom: 1px solid rgba(255, 255, 255, .08);
    background: #0b1c1e
  }

  .ft-ai-title {
    font-size: 13px;
    font-weight: 800
  }

  .ft-ai-sub {
    font-size: 9px;
    color: #78908d;
    margin-top: 3px
  }

  .ft-ai-close {
    border: 0;
    background: transparent;
    color: #9bb0ad;
    font-size: 18px;
    cursor: pointer
  }

  .ft-ai-messages {
    flex: 1;
    overflow: auto;
    padding: 14px
  }

  .ft-ai-msg {
    max-width: 88%;
    padding: 10px 11px;
    border-radius: 11px;
    margin: 0 0 9px;
    font-size: 11px;
    line-height: 1.55;
    white-space: pre-wrap
  }

  .ft-ai-msg.user {
    margin-left: auto;
    background: #12c7a0;
    color: #061412
  }

  .ft-ai-msg.bot {
    background: rgba(255, 255, 255, .055);
    color: #dce8e6
  }

  .ft-ai-status {
    padding: 0 14px 7px;
    color: #78908d;
    font-size: 9px
  }

  .ft-ai-compose {
    display: flex;
    gap: 7px;
    padding: 10px;
    border-top: 1px solid rgba(255, 255, 255, .08);
    background: #071214
  }

  .ft-ai-input {
    flex: 1;
    resize: none;
    height: 42px;
    border: 1px solid rgba(255, 255, 255, .1);
    border-radius: 9px;
    background: #0d2022;
    color: #fff;
    padding: 9px;
    font: 11px Inter, Arial, sans-serif;
    outline: none
  }

  .ft-ai-send {
    border: 0;
    border-radius: 9px;
    padding: 0 13px;
    background: #12c7a0;
    color: #061412;
    font: 800 11px Inter, Arial, sans-serif;
    cursor: pointer
  }

  .ft-ai-send:disabled {
    opacity: .5;
    cursor: wait
  }

  @media(max-width:520px) {
    #fittrack-ai-launcher {
      right: 12px;
      bottom: 12px
    }

    #fittrack-ai-panel {
      right: 12px;
      bottom: 65px;
      width: calc(100vw - 24px)
    }
  }
</style>
<button id="fittrack-ai-launcher" type="button">✦ FITTRACK AI</button>
<div id="fittrack-ai-panel" aria-hidden="true">
  <div class="ft-ai-head">
    <div>
      <div class="ft-ai-title">FITTRACK AI Copilot</div>
      <div class="ft-ai-sub">Role-aware gym assistant</div>
    </div><button class="ft-ai-close" id="fittrack-ai-close" type="button">×</button>
  </div>
  <div class="ft-ai-messages" id="fittrack-ai-messages">
    <div class="ft-ai-msg bot">Hi! I’m FITTRACK AI. Ask me about the data available to your role.</div>
  </div>
  <div class="ft-ai-status" id="fittrack-ai-status"></div>
  <div class="ft-ai-compose"><textarea id="fittrack-ai-input" class="ft-ai-input" placeholder="Ask FITTRACK AI..." rows="1"></textarea><button id="fittrack-ai-send" class="ft-ai-send" type="button">Send</button></div>
</div>
<script>
  (function() {
    const launcher = document.getElementById('fittrack-ai-launcher'),
      panel = document.getElementById('fittrack-ai-panel'),
      close = document.getElementById('fittrack-ai-close'),
      input = document.getElementById('fittrack-ai-input'),
      send = document.getElementById('fittrack-ai-send'),
      messages = document.getElementById('fittrack-ai-messages'),
      status = document.getElementById('fittrack-ai-status');
    if (!launcher || !panel) return;
    const page = location.pathname.split('/').pop() || 'unknown';
    const storageKey = 'fittrack_ai_history_v1';
    let history = [];
    try {
      const saved = JSON.parse(sessionStorage.getItem(storageKey) || '[]');
      if (Array.isArray(saved)) history = saved.slice(-10);
    } catch (e) {}

    function add(text, who) {
      const el = document.createElement('div');
      el.className = 'ft-ai-msg ' + who;
      el.textContent = text;
      messages.appendChild(el);
      messages.scrollTop = messages.scrollHeight;
    }

    function toggle() {
      panel.classList.toggle('open');
      panel.setAttribute('aria-hidden', panel.classList.contains('open') ? 'false' : 'true');
      if (panel.classList.contains('open')) input.focus();
    }
    launcher.addEventListener('click', toggle);
    close.addEventListener('click', toggle);
    async function ask() {
      const q = input.value.trim();
      if (!q) return;
      add(q, 'user');
      history.push({
        role: 'user',
        content: q
      });
      history = history.slice(-10);
      try {
        sessionStorage.setItem(storageKey, JSON.stringify(history));
      } catch (e) {}
      input.value = '';
      send.disabled = true;
      status.textContent = 'Thinking…';
      try {
        const r = await fetch('../data_science/api/ai_copilot.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          credentials: 'same-origin',
          body: JSON.stringify({
            question: q,
            page: page,
            history: history.slice(-8)
          })
        });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'AI request failed.');
        add(d.answer, 'bot');
        history.push({
          role: 'assistant',
          content: d.answer
        });
        history = history.slice(-10);
        try {
          sessionStorage.setItem(storageKey, JSON.stringify(history));
        } catch (e) {}
      } catch (e) {
        add(e.message || 'Unable to contact FITTRACK AI.', 'bot');
      } finally {
        status.textContent = '';
        send.disabled = false;
        input.focus();
      }
    }
    send.addEventListener('click', ask);
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        ask();
      }
    });
  })();
</script>