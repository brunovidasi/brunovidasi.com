/* The filter bar on the item lists.
 *
 * A choice in any dropdown applies at once; the search applies on Enter, or when
 * its × empties it. An era belongs to an artist, so changing the artist drops
 * the era rather than sending a pair that can't match. */

const filters = document.getElementById('filters');

if (filters) {
  const { artist, era, q } = filters.elements;

  filters.addEventListener('change', event => {
    if (event.target.tagName !== 'SELECT') return;
    if (event.target === artist) era.value = '';
    filters.requestSubmit();
  });

  // Fires when the × is pressed (and on Enter, which submits by itself already).
  q.addEventListener('search', () => {
    if (q.value === '') filters.requestSubmit();
  });
}
