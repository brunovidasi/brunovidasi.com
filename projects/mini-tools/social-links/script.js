/* =====================================================================
   LINKS — edit this list to add, remove or reorder buttons.
   icon  : must match a <symbol id="icon-..."> defined above
   color : any CSS background value (hex, or a gradient string)
           omit it (or use "default") to fall back to the accent colour
===================================================================== */
const DEFAULT_COLOR = 'var(--accent)';

const links = [

  { category: 'Social', name: 'Instagram', url: 'https://instagram.com/brunovidasi', icon: 'instagram', color: 'linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)' },
  { category: 'Social', name: 'Facebook', url: 'https://facebook.com/brunovidasi', icon: 'facebook', color: '#1877F2' },
  { category: 'Social', name: 'TikTok', url: 'https://tiktok.com/@brunovidasi', icon: 'tiktok', color: '#000000' },
  { category: 'Social', name: 'X', url: 'https://x.com/brunovidasi', icon: 'x', color: '#000000' },
  { category: 'Social', name: 'Pinterest', url: 'https://pinterest.com.au/brunovidasi', icon: 'pinterest', color: '#E60023' },
  { category: 'Social', name: 'Youtube', url: 'https://youtube.com/brunices', icon: 'youtube', color: '#FF0000' },

  { category: 'Professional', name: 'Website', url: 'https://brunovida.si/', icon: 'website', color: DEFAULT_COLOR },
  { category: 'Professional', name: 'Resume', url: 'https://brunovida.si/resume/', icon: 'resume', color: DEFAULT_COLOR },
  { category: 'Professional', name: 'Email me', url: 'mailto:hello@example.com', icon: 'email', color: DEFAULT_COLOR },
  { category: 'Professional', name: 'LinkedIn', url: 'https://linkedin.com/in/brunovidasi', icon: 'linkedin', color: '#0A66C2' },
  { category: 'Professional', name: 'GitHub', url: 'https://github.com/brunovidasi', icon: 'github', color: '#181717' },
  { category: 'Professional', name: 'CodePen', url: 'https://codepen.io/brunovidasi', icon: 'codepen', color: '#000000' },

  { category: 'Chat', name: 'Whatsapp', url: 'https://wa.me/61473216970', icon: 'whatsapp', color: '#25D366' },
  { category: 'Chat', name: 'Telegram', url: 'https://t.me/brunovidasi', icon: 'telegram', color: '#26A5E4' },
  { category: 'Chat', name: 'Messenger', url: 'https://messenger.com/t/brunovidasi', icon: 'messenger', color: 'linear-gradient(135deg, #00B2FF, #006AFF, #B620E0)' },


  { category: 'Gaming', name: 'PlayStation', url: 'https://psnprofiles.com/bvidasi', icon: 'playstation', color: '#003791' },
  { category: 'Gaming', name: 'Nintendo Switch', url: 'https://lounge.nintendo.com/friendcode/0941-9916-2497/CwbBTf2PGB', icon: 'nintendoswitch', color: '#E60012' },
  { category: 'Gaming', name: 'Xbox', url: 'https://www.xbox.com/en-AU/play/user?gamerTag=brunovidasi', icon: 'xbox', color: '#107C10' },

  { category: 'Other', name: 'Discogs', url: 'https://www.discogs.com/user/brunovidasi', icon: 'discogs', color: '#000000' },
];

// Render the buttons into the page
const container = document.getElementById('links');

let currentCategory = '';

links.forEach(link => {

  if (link.category !== currentCategory) {
    currentCategory = link.category;

    const header = document.createElement('div');
    header.className = 'category-header';

    const title = document.createElement('h2');
    title.className = 'category-title';
    title.textContent = currentCategory;

    const arrow = document.createElement('span');
    arrow.className = 'category-arrow';
    arrow.textContent = '▼';

    header.appendChild(arrow);
    header.appendChild(title);


    const section = document.createElement('div');
    section.className = 'category-links';

    header.onclick = () => {
      const closed = section.style.display === 'none';
      section.style.display = closed ? 'flex' : 'none';
      arrow.textContent = '▶';
      arrow.textContent = closed ? '▼' : '▶';
    };

    container.appendChild(header);
    container.appendChild(section);
  }

  const a = document.createElement('a');
  a.className = 'link-item';
  a.href = link.url;
  a.style.background = link.color || DEFAULT_COLOR;

  // open real links in a new tab, but not mailto: links
  if (!link.url.startsWith('mailto:')) {
    a.target = '_blank';
    a.rel = 'noopener';
  }

  a.innerHTML = `
    <svg class="icon" aria-hidden="true"><use href="#icon-${link.icon}"></use></svg>
    <span class="label">${link.name}</span>
  `;

  const section = container.lastElementChild;
  section.appendChild(a);
});
