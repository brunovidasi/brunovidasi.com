/* Click-to-sort headings for the short tables (the wantlist page).
 *
 * The collection list is paginated, so it sorts on the server; these tables are
 * a few dozen rows and are sorted where they are. A heading marked `data-sort`
 * sorts its column: ascending, then descending. A cell can carry
 * `data-sort-value` where what it shows isn't what it should sort by (a year that
 * should sort by the full date). Blank cells go last either way. */

/* A text box that filters a table's rows live, no reload — opt in with
 * data-table-search="tableId" on the input. Used where the list is short
 * enough not to need the collection's server-side search (the selling list). */
document.querySelectorAll('[data-table-search]').forEach(input => {
  const table = document.getElementById(input.dataset.tableSearch);
  if (!table || !table.tBodies[0]) return;
  const rows = [...table.tBodies[0].rows];

  input.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    rows.forEach(row => { row.hidden = q !== '' && !row.textContent.toLowerCase().includes(q); });
  });
});

document.querySelectorAll('table[data-sortable]').forEach(table => {
  const body = table.tBodies[0];
  const headings = [...table.tHead.rows[0].cells];

  const cellValue = cell => {
    const value = cell.dataset.sortValue ?? cell.textContent;
    const text = value.replace(/\s+/g, ' ').trim();
    return text === '—' ? '' : text.toLowerCase();
  };

  const sortBy = (index, direction) => {
    const rows = [...body.rows];
    rows.sort((a, b) => {
      const x = cellValue(a.cells[index]);
      const y = cellValue(b.cells[index]);
      if ((x === '') !== (y === '')) return x === '' ? 1 : -1;
      return direction * x.localeCompare(y, undefined, { numeric: true });
    });
    rows.forEach(row => body.append(row));

    headings.forEach((th, i) => {
      if (!('sort' in th.dataset)) return;
      const active = i === index;
      th.classList.toggle('sorted', active);
      th.setAttribute('aria-sort', active ? (direction === 1 ? 'ascending' : 'descending') : 'none');
      th.classList.toggle('desc', active && direction === -1);
    });
  };

  headings.forEach((th, index) => {
    if (!('sort' in th.dataset)) return;

    // The label moves into a span so the heading can be a keyboard target with
    // the same look as the server-sorted links.
    const label = document.createElement('span');
    label.className = 'sort-label';
    label.tabIndex = 0;
    label.setAttribute('role', 'button');
    label.append(th.textContent.trim());
    const arrow = document.createElement('i');
    arrow.className = 'sort-arrow';
    arrow.setAttribute('aria-hidden', 'true');
    label.append(arrow);
    th.replaceChildren(label);
    th.classList.add('sortable');

    const toggle = () => sortBy(index, th.classList.contains('sorted') && th.getAttribute('aria-sort') === 'ascending' ? -1 : 1);
    label.addEventListener('click', toggle);
    label.addEventListener('keydown', event => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggle();
      }
    });
  });
});
