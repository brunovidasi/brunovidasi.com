/* The All column on the drawer-fields grid.
 *
 * Each row's All box mirrors the row: ticked when every format that has the fact
 * shows it, half-ticked when only some do. Clicking it sets the whole row. The
 * All boxes have no name, so they're never submitted — the per-format boxes are
 * the setting; this is only a shortcut for ticking them. */

document.querySelectorAll('.matrix tbody tr').forEach(row => {
  const all = row.querySelector('.all-toggle');
  const boxes = [...row.querySelectorAll('input[name^="show["]')];
  if (!all || !boxes.length) return;

  const refresh = () => {
    const ticked = boxes.filter(box => box.checked).length;
    all.checked = ticked === boxes.length;
    all.indeterminate = ticked > 0 && ticked < boxes.length;
  };

  all.addEventListener('change', () => {
    boxes.forEach(box => { box.checked = all.checked; });
    refresh();
  });
  boxes.forEach(box => box.addEventListener('change', refresh));

  refresh();
});
