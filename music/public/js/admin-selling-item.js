/* Starts the tracklist box from Discogs' list, for correcting one title rather
 * than retyping the lot. Asks first if the box already has something in it. */

const copyTracks = document.getElementById('copyDiscogsTracks');
const tracklistBox = document.getElementById('o_tracklist');

if (copyTracks && tracklistBox) {
  copyTracks.addEventListener('click', () => {
    if (tracklistBox.value.trim() && !confirm("Replace what's in the tracklist box with Discogs' list?")) return;
    tracklistBox.value = tracklistBox.dataset.discogs;
    tracklistBox.focus();
  });
}

/* The header buttons. Sync and Delete are forms of their own, so both reload
 * the page: a sync that ate half-typed edits would be a nasty surprise, hence
 * the check on the main form first. Same shape as admin-item.js. */

const sellingForm = document.getElementById('sellingForm');
const syncForm = document.getElementById('syncForm');
const deleteForm = document.getElementById('deleteForm');

let dirty = false;
if (sellingForm) {
  sellingForm.addEventListener('input', () => { dirty = true; });
  sellingForm.addEventListener('change', () => { dirty = true; });
  sellingForm.addEventListener('submit', () => { dirty = false; });
}

if (syncForm) {
  syncForm.addEventListener('submit', event => {
    if (dirty && !confirm('You have unsaved changes on this page, and syncing reloads it. Sync anyway and lose them?')) {
      event.preventDefault();
      return;
    }

    const button = syncForm.querySelector('button');
    button.disabled = true;
    button.textContent = 'Syncing…';
  });
}

if (deleteForm) {
  deleteForm.addEventListener('submit', event => {
    if (!confirm('Delete this listing and everything typed about it?')) event.preventDefault();
  });
}
