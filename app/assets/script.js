const appList = document.getElementById('appList');

const apps = {
  'app-1': { ready: false },
  'app-2': { ready: false },
  'app-3': { ready: false }
};

appList.addEventListener('click', (e) => {
  const card = e.target.closest('.app-card');
  if (!card) return;

  e.preventDefault();

  const id = card.dataset.app;
  const app = apps[id];

  if (app && app.ready) {
    window.location.href = `/${id}`;
    return;
  }

  card.classList.add('shake');
  setTimeout(() => card.classList.remove('shake'), 300);
});

const toggleAllBtn = document.getElementById('toggleAllBtn');
const toolCategories = document.querySelectorAll('.tool-category');

toggleAllBtn.addEventListener('click', () => {
  const expand = toggleAllBtn.textContent === 'Expand all';
  toolCategories.forEach((cat) => { cat.open = expand; });
  toggleAllBtn.textContent = expand ? 'Collapse all' : 'Expand all';
});
