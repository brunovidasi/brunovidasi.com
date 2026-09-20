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
