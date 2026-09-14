// ==========================================================================
// The contact panel: the scrambled email reveal and the contact form.
//
// The address is never in the markup as plain text — it sits in a data
// attribute and is decoded character by character on hover or on first visit,
// which keeps naive address scrapers from lifting it straight out of the HTML.
// ==========================================================================

import { CONTACT_EMAIL } from './config.js';
import { registerActions } from './actions.js';

const SCRAMBLE_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%&*+=?';
/** Characters of lead-in scramble before the first real letter settles. */
const SCRAMBLE_LEAD = 8;

let scrambleTimer = null;
let revealedOnce = false;

function scrambleReveal(el, stepMs = 28){
  const target = el.dataset.email || el.textContent;
  clearInterval(scrambleTimer);

  let step = 0;
  const totalSteps = target.length + SCRAMBLE_LEAD;

  scrambleTimer = setInterval(()=>{
    step++;
    const revealCount = Math.max(0, step - SCRAMBLE_LEAD);
    let out = '';
    for(let i = 0; i < target.length; i++){
      out += i < revealCount ? target[i] : SCRAMBLE_CHARS[Math.floor(Math.random() * SCRAMBLE_CHARS.length)];
    }
    el.textContent = out;
    if(step >= totalSteps){
      clearInterval(scrambleTimer);
      el.textContent = target;
    }
  }, stepMs);
}

/** Runs the reveal the first time the contact panel is opened. */
export function startEmailReveal(){
  const emailTextEl = document.getElementById('emailText');
  if(revealedOnce || !emailTextEl) return;
  revealedOnce = true;
  scrambleReveal(emailTextEl);
}

function copyEmail(){
  navigator.clipboard.writeText(CONTACT_EMAIL).then(()=>{
    const btn = document.getElementById('emailCopyBtn');
    const label = btn.querySelector('.copy-btn-label');
    btn.classList.add('copied');
    label.textContent = 'Copied!';
    clearTimeout(btn._copyResetTimer);
    btn._copyResetTimer = setTimeout(()=>{
      btn.classList.remove('copied');
      label.textContent = 'Copy';
    }, 1500);
  });
}

// ---- Form -----------------------------------------------------------------

const FORM_ENDPOINT = 'https://api.web3forms.com/submit';
const ERROR_MESSAGE = 'Something went wrong. Please try again or email me directly.';

function showFormError(){
  const fb = document.getElementById('contactFormFeedback');
  fb.textContent = ERROR_MESSAGE;
  fb.className = 'form-fb err';
  fb.style.display = 'inline';
  setTimeout(()=> { fb.style.display = 'none'; }, 4000);
}

function submitContactForm(e){
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('contactSubmitBtn');
  const originalBtnText = btn.textContent;

  btn.disabled = true;
  btn.textContent = 'Sending…';

  fetch(FORM_ENDPOINT, {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body: new FormData(form)
  })
    .then(res => res.json())
    .then(json => {
      if(!json.success){
        showFormError();
        return;
      }
      form.reset();
      form.hidden = true;
      document.getElementById('contactFormSuccess').hidden = false;
    })
    .catch(showFormError)
    .finally(()=>{
      btn.disabled = false;
      btn.textContent = originalBtnText;
    });
}

// ---- Wiring ---------------------------------------------------------------

export function initContact(){
  registerActions({ 'copy-email': copyEmail });

  const emailRowEl = document.getElementById('emailRow');
  const emailTextEl = document.getElementById('emailText');
  if(emailRowEl && emailTextEl){
    emailRowEl.addEventListener('mouseenter', ()=> scrambleReveal(emailTextEl));
  }

  document.getElementById('contactForm').addEventListener('submit', submitContactForm);
  document.getElementById('contactFormReset').addEventListener('click', (e)=>{
    e.preventDefault();
    document.getElementById('contactFormSuccess').hidden = true;
    document.getElementById('contactForm').hidden = false;
  });
}
