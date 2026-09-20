/* The Sync button.
 *
 * A full sync is minutes of work against a 60-calls-a-minute API, so the server
 * does it in slices: each POST here runs for a few seconds, commits what it
 * managed and answers with where it got to. This keeps asking until the run
 * says it's done, which is also why closing the tab is harmless — the next
 * click (or the nightly cron) resumes the same run.
 */

const card = document.getElementById('syncCard');
if (card) {
  const endpoint = card.dataset.endpoint;
  const csrf = card.dataset.csrf;
  const startButton = document.getElementById('syncStart');
  const status = document.getElementById('syncStatus');
  const bar = document.getElementById('syncBar');
  const log = document.getElementById('syncLog');

  let running = false;

  function setBar(progress) {
    const total = progress.details + progress.pending;
    // Before the detail phase there is no meaningful total, so the bar shows a
    // small amount of movement rather than pretending to know.
    const pct = total > 0 ? Math.round((progress.details / total) * 100) : 6;
    bar.hidden = false;
    bar.firstElementChild.style.width = pct + '%';
  }

  function describe(state) {
    const p = state.progress;
    const parts = [];
    if (p.collection) parts.push(`${p.collection} in collection`);
    if (p.wantlist) parts.push(`${p.wantlist} wanted`);
    if (p.details) parts.push(`${p.details} details`);
    if (p.pending) parts.push(`${p.pending} to go`);
    return parts.join(' · ');
  }

  async function step(runId) {
    const body = new URLSearchParams({ csrf_token: csrf });
    if (runId) body.set('run_id', runId);

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { 'X-Requested-With': 'fetch' },
      body,
    });

    if (!response.ok) {
      throw new Error(`The server answered ${response.status}.`);
    }

    return response.json();
  }

  async function run(runId) {
    running = true;
    startButton.disabled = true;
    startButton.textContent = 'Syncing…';
    log.hidden = false;

    try {
      let state;
      do {
        state = await step(runId);
        runId = state.run_id;

        status.textContent = `${state.message || state.phase} ${describe(state)}`.trim();
        setBar(state.progress);
        log.textContent = state.log || '';
        log.scrollTop = log.scrollHeight;
      } while (!state.done);

      const failed = state.status === 'error';
      status.textContent = failed
        ? `Stopped: ${state.message}`
        : `${state.status === 'partial' ? 'Paused' : 'Done'} — ${describe(state)}`;
      bar.firstElementChild.style.width = failed ? '100%' : '100%';
      // A partial run still has work left; the button invites finishing it.
      startButton.textContent = state.status === 'partial' ? 'Continue sync' : 'Sync again';
    } catch (error) {
      status.textContent = `Sync failed: ${error.message}`;
      startButton.textContent = 'Try again';
    } finally {
      running = false;
      startButton.disabled = false;
    }
  }

  startButton.addEventListener('click', () => {
    if (!running) run(card.dataset.running || null);
  });

  // A run left going by a closed tab carries on being reported here.
  if (card.dataset.running) {
    run(card.dataset.running);
  }
}

const copyButton = document.getElementById('copyCron');
if (copyButton) {
  copyButton.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(document.getElementById('cronUrl').textContent.trim());
      copyButton.textContent = 'Copied';
      setTimeout(() => { copyButton.textContent = 'Copy URL'; }, 2000);
    } catch (error) {
      copyButton.textContent = 'Select it by hand';
    }
  });
}
