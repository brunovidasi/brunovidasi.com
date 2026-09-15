const toggleAllBtn = document.getElementById('toggleAllBtn');
const toolCategories = document.querySelectorAll('.tool-category');

toggleAllBtn.addEventListener('click', () => {
  const expand = toggleAllBtn.textContent === 'Expand all';
  toolCategories.forEach((cat) => { cat.open = expand; });
  toggleAllBtn.textContent = expand ? 'Collapse all' : 'Expand all';
});
