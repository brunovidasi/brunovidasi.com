/* The filter bar: a choice in a dropdown applies at once, the search on Enter
 * or when its × empties it. Changing the artist drops the era, which was theirs. */

const filters = document.getElementById('filters');

if (filters) {
  const { artist, era, q } = filters.elements;

  filters.addEventListener('change', event => {
    if (event.target.tagName !== 'SELECT') return;
    if (event.target === artist) era.value = '';
    filters.requestSubmit();
  });

  // Fired by the ×, and by Enter, which submits by itself anyway.
  q.addEventListener('search', () => {
    if (q.value === '') filters.requestSubmit();
  });
}
