/* The short admin tables, sorted and searched in the page rather than on the
 * server. A box with data-table-search="tableId" filters that table's rows. */
document.querySelectorAll('[data-table-search]').forEach(input => {
  const table = document.getElementById(input.dataset.tableSearch);
  if (!table || !table.tBodies[0]) return;
  const rows = [...table.tBodies[0].rows];

  input.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    rows.forEach(row => { row.hidden = q !== '' && !row.textContent.toLowerCase().includes(q); });
  });
});

/* A heading marked data-sort sorts its column, ascending then descending; a
 * cell's data-sort-value is used where what it shows isn't what it sorts by.
 * Blank cells go last either way. */
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

    // The label goes into a span, so the heading can take the keyboard like the server-sorted links.
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
