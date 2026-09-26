/* What the record and listing edit pages share: the tracklist starting from
 * Discogs' list, and a guard on the Sync and Delete buttons, which are forms of
 * their own and reload the page. */

const copyTracks = document.getElementById('copyDiscogsTracks');
const tracklistBox = document.getElementById('o_tracklist');

if (copyTracks && tracklistBox) {
  copyTracks.addEventListener('click', () => {
    if (tracklistBox.value.trim() && !confirm("Replace what's in the tracklist box with Discogs' list?")) return;
    tracklistBox.value = tracklistBox.dataset.discogs;
    tracklistBox.focus();
  });
}

const saveButton = document.querySelector('.page-head button[form]');
const editForm = saveButton && document.getElementById(saveButton.getAttribute('form'));
const syncForm = document.getElementById('syncForm');
const deleteForm = document.getElementById('deleteForm');

let dirty = false;
if (editForm) {
  editForm.addEventListener('input', () => { dirty = true; });
  editForm.addEventListener('change', () => { dirty = true; });
  editForm.addEventListener('submit', () => { dirty = false; });
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
    if (!confirm(deleteForm.dataset.confirm)) event.preventDefault();
  });
}
