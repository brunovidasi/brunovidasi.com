/* The record edit page: the Era menu following the Artist one, and the discs'
 * pictures and colours. */

const artistSelect = document.getElementById('artist_id');
const eraSelect = document.getElementById('era_id');
const eraOptions = document.getElementById('eraOptions');

if (artistSelect && eraSelect && eraOptions) {
  const erasByArtist = JSON.parse(eraOptions.textContent || '{}');
  const selectedEra = eraSelect.value;

  artistSelect.addEventListener('change', () => {
    const eras = erasByArtist[artistSelect.value] || [];

    eraSelect.replaceChildren(new Option('— filed automatically —', ''));
    eras.forEach(era => {
      const option = new Option(era.name, era.id);
      // Changing the artist and back keeps the era that was set.
      option.selected = String(era.id) === selectedEra;
      eraSelect.add(option);
    });
  });
}

/* One picker per disc in the sleeve, and only that many. A disc that isn't
 * shown is disabled, so it isn't sent, but keeps its choices until the form is
 * saved: going 2 → 1 → 2 loses nothing. */

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
    // "Disc 1" only labels a colour picker when there is another to tell it from.
    if (colourPick) colourPick.classList.toggle('multi', colourPick.querySelectorAll('.colour-disc:not([hidden])').length > 1);
  };

  discCount.addEventListener('change', showDiscs);
  showDiscs();
}

/* On automatic, a disc's swatch follows the Colour / variant text through the
 * site's own keyword list (sent in the page); a picked colour is stored on
 * that disc instead, and "Use automatic" takes it back. */

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
      // The same words the site reads transparency from (vinyl_color() in includes/discs.php).
      const translucent = clarity.value !== ''
        ? clarity.value === '1'
        : /transl|transp|clear|smoky/i.test(source);
      state.textContent = `${line}, ${translucent ? 'translucent' : 'opaque'}`;
      disc.classList.toggle('manual', Boolean(hidden.value));
    };

    const choose = hex => {
      hidden.value = hex;
      // A value set from script fires no event; tell the unsaved-changes guard.
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
