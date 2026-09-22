/* Keeps the Era dropdown in step with the Artist one.
 *
 * Every artist's eras are already on the page (a script tag of JSON), because
 * there are four artists with a few dozen eras between them — far cheaper to
 * ship once than to fetch on each change. */

const artistSelect = document.getElementById('artist_id');
const eraSelect = document.getElementById('era_id');
const optionsTag = document.getElementById('eraOptions');

if (artistSelect && eraSelect && optionsTag) {
  const erasByArtist = JSON.parse(optionsTag.textContent || '{}');
  const selectedEra = eraSelect.value;

  artistSelect.addEventListener('change', () => {
    const eras = erasByArtist[artistSelect.value] || [];

    eraSelect.replaceChildren(new Option('— filed automatically —', ''));
    eras.forEach(era => {
      const option = new Option(era.name, era.id);
      // Changing artist and back should not silently drop the era already set.
      option.selected = String(era.id) === selectedEra;
      eraSelect.add(option);
    });
  });
}

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
 * the check on the main form first. */

const itemForm = document.getElementById('itemForm');
const syncForm = document.getElementById('syncForm');
const deleteForm = document.getElementById('deleteForm');

let dirty = false;
if (itemForm) {
  itemForm.addEventListener('input', () => { dirty = true; });
  itemForm.addEventListener('change', () => { dirty = true; });
  itemForm.addEventListener('submit', () => { dirty = false; });
}

if (syncForm) {
  syncForm.addEventListener('submit', event => {
    if (dirty && !confirm('You have unsaved changes on this page, and syncing reloads it. Sync anyway and lose them?')) {
      event.preventDefault();
      return;
    }

    // A sync is one or two calls to Discogs and can take a few seconds.
    const button = syncForm.querySelector('button');
    button.disabled = true;
    button.textContent = 'Syncing…';
  });
}

if (deleteForm) {
  deleteForm.addEventListener('submit', event => {
    if (!confirm('Delete this record and everything you typed about it?')) event.preventDefault();
  });
}

/* The Discs section: a picker for each disc in the sleeve, and only that many.
 *
 * "As Discogs says" shows as many as Discogs' formats give. A disc that isn't
 * shown is disabled, so it isn't sent and its picture and colour go when the
 * form is saved — but it keeps what was picked until then, so going 2 → 1 → 2
 * loses nothing. The colour pickers (below, under Colour / variant) follow the
 * same count. */

const discCount = document.getElementById('disc_count');
const discBox = document.getElementById('discs');
const colourPick = document.getElementById('colourPick');

if (discCount && discBox) {
  const derived = Number(discBox.dataset.derived) || 1;

  const showDiscs = () => {
    const shown = Number(discCount.value) || derived;
    document.querySelectorAll('.disc-art, .colour-disc').forEach(section => {
      const off = Number(section.dataset.disc) >= shown;
      section.hidden = off;
      section.querySelectorAll('input, select').forEach(input => { input.disabled = off; });
    });
    // "Disc 1" only labels a picker when there is another one to tell it from.
    if (colourPick) colourPick.classList.toggle('multi', colourPick.querySelectorAll('.colour-disc:not([hidden])').length > 1);
  };

  discCount.addEventListener('change', showDiscs);
  showDiscs();
}

/* The disc colours. Left on automatic, a disc's swatch follows the Colour /
 * variant text through the same keyword list the site matches it with (sent in
 * the page, so the two can't drift); picking a colour, or one of the chips,
 * stores it on that disc instead, and "Use automatic" takes it back. */

if (colourPick) {
  const palette = JSON.parse(colourPick.dataset.palette || '[]');
  const fallback = colourPick.dataset.fallback;
  const text = document.getElementById('f_vinyl_color');

  const match = value => {
    const lower = value.toLowerCase();
    return palette.find(([word]) => lower.includes(word));
  };

  const renders = [];

  colourPick.querySelectorAll('.colour-disc').forEach(disc => {
    const picker = disc.querySelector('.colour-input');
    const hidden = disc.querySelector('.colour-hex');
    const state = disc.querySelector('.colour-state');
    const clarity = disc.querySelector('.colour-clarity');

    const render = () => {
      const source = (text ? text.value : '').trim() || colourPick.dataset.discogs;
      let line;
      if (hidden.value) {
        picker.value = hidden.value;
        line = 'Picked by hand';
      } else {
        const found = match(source);
        picker.value = found ? found[1] : fallback;
        line = found
          ? `Automatic, from "${found[0]}"`
          : 'Automatic: no colour recognised, so it shows as black';
      }
      // The same words the site reads transparency from (see vinyl_color() in items.php).
      const translucent = clarity.value !== ''
        ? clarity.value === '1'
        : /transl|transp|clear|smoky/i.test(source);
      state.textContent = `${line}, ${translucent ? 'translucent' : 'opaque'}`;
      disc.classList.toggle('manual', Boolean(hidden.value));
    };

    const choose = hex => {
      hidden.value = hex;
      // Setting the value from script fires no event, so the unsaved-changes guard is told.
      hidden.dispatchEvent(new Event('change', { bubbles: true }));
      render();
    };

    picker.addEventListener('input', () => choose(picker.value));
    disc.querySelectorAll('.swatch').forEach(chip => chip.addEventListener('click', () => choose(chip.dataset.hex)));
    disc.querySelector('.colour-reset').addEventListener('click', () => choose(''));
    clarity.addEventListener('change', render);

    renders.push(render);
    render();
  });

  if (text) text.addEventListener('input', () => renders.forEach(render => render()));
}
