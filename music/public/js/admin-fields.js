/* The All column on the drawer-fields grid: ticked when every format shows the
 * fact, half-ticked when only some do, and a click sets the whole row. It has
 * no name, so it is never submitted. */

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
