// ==========================================================================
// Static configuration: the "workspace" this site pretends to be.
//
// Nothing here touches the DOM or holds runtime state — it is the data the
// explorer, tabs, router, terminal and search all read from. `files` is the
// one exception to being read-only: tool tabs register themselves into it at
// runtime (see tool-tabs.js), which is why it is a plain mutable object.
// ==========================================================================

export const CAREER_START_YEAR = 2012;
export const yearsExperience = new Date().getFullYear() - CAREER_START_YEAR;

/** Viewport width at or below which the explorer becomes a mobile drawer. */
export const MOBILE_BREAKPOINT = '(max-width: 720px)';

// ---- Files & folders ------------------------------------------------------

export const files = {
  intro:                    { label: 'intro.js',           icon: 'js',   folder: null },
  about:                    { label: 'README.md',          icon: 'info', folder: 'about' },
  experience:               { label: 'experience.js',      icon: 'js',   folder: 'about' },
  education:                { label: 'education.md',       icon: 'md',   folder: 'about' },
  skills:                   { label: 'skills.json',        icon: 'json', folder: 'about' },
  websites:                 { label: 'websites.html',      icon: 'html', folder: 'projects' },
  'web-systems':            { label: 'web-systems.php',    icon: 'php',  folder: 'projects' },
  'landing-pages':          { label: 'landing-pages.html', icon: 'html', folder: 'projects' },
  'edm-tools':              { label: 'eDM-tools.html',     icon: 'html', folder: 'projects' },
  'mini-games':             { label: 'mini-games.html',    icon: 'html', folder: 'projects' },
  'site-history':           { label: 'site-history.html',  icon: 'html', folder: 'projects' },
  'mini-tools-readme':      { label: 'mini-tools.md',      icon: 'md',   folder: 'mini-tools' },
  'mini-tools-dev':         { label: 'dev-utilities.md',   icon: 'md',   folder: 'mini-tools' },
  'mini-tools-media':       { label: 'media-tools.md',     icon: 'md',   folder: 'mini-tools' },
  'mini-tools-converters':  { label: 'converters.md',      icon: 'md',   folder: 'mini-tools' },
  'mini-tools-generators':  { label: 'generators.md',      icon: 'md',   folder: 'mini-tools' },
  'mini-tools-pdf':         { label: 'pdf-tools.md',       icon: 'md',   folder: 'mini-tools' },
  freelance:                { label: 'freelance.css',      icon: 'css',  folder: null },
  contact:                  { label: 'contact.eml',        icon: 'eml',  folder: null },
  documents:                { label: 'documents.pdf',      icon: 'pdf',  folder: null,
                              aliases: ['resume', 'résumé', 'curriculum vitae', 'cv'] }
};

export const folders = {
  about:        { label: 'about/',        children: ['about', 'experience', 'education', 'skills'] },
  projects:     { label: 'projects/',     children: ['websites', 'web-systems', 'landing-pages', 'edm-tools', 'mini-games', 'site-history'] },
  'mini-tools': { label: 'mini-tools/',   children: ['mini-tools-readme', 'mini-tools-dev', 'mini-tools-media', 'mini-tools-converters', 'mini-tools-generators', 'mini-tools-pdf'] },
  'mini-tools-dev':        { label: 'dev-utilities/' },
  'mini-tools-media':      { label: 'media-tools/' },
  'mini-tools-converters': { label: 'converters/' },
  'mini-tools-generators': { label: 'generators/' },
  'mini-tools-pdf':        { label: 'pdf-tools/' }
};

/** Second-level folders whose children are tool tabs rather than static files. */
export const MINI_TOOL_CATEGORY_IDS = new Set([
  'mini-tools-dev', 'mini-tools-media', 'mini-tools-converters', 'mini-tools-generators', 'mini-tools-pdf'
]);

/** File a folder opens when it is expanded (desktop) or `cd`-ed into. */
export const FOLDER_DEFAULT_FILE = {
  about: 'about',
  'mini-tools': 'mini-tools-readme',
  projects: 'websites'
};

/** Top-level explorer order. Also the terminal's root `ls` listing. */
export const rootOrder = ['intro', 'about', 'projects', 'mini-tools', 'freelance', 'documents', 'contact'];

export const DEFAULT_OPEN_TABS = ['intro'];

// ---- Routing --------------------------------------------------------------

/** URLs that point at a folder resolve to its readme. */
export const ROUTE_ALIASES = { 'mini-tools': 'mini-tools-readme' };

/** Projects that live in a panel other than the one named by their category. */
export const PROJECT_TAB_OVERRIDES = {
  'edm-kinetic-modules': 'edm-tools',
  'edm-html-builder': 'edm-tools',
  'mini-tools-personal': 'mini-tools-readme'
};

export const PROJECT_PANEL_OVERRIDES = {
  'edm-html-builder': 'edm-tools',
  'edm-kinetic-modules': 'edm-tools'
};

// ---- Project categories ---------------------------------------------------

/** Categories rendered as browser-chrome cards with a list/detail split view. */
export const WEBSITE_STYLE_CATEGORIES = ['websites', 'web-systems'];

export const WEBSITE_CATEGORY_LABELS = {
  websites: 'websites',
  'web-systems': 'web systems'
};

export const WEBSITE_CATEGORY_TAGS = {
  websites: { plural: 'websites', singular: 'website' },
  'web-systems': { plural: 'web-systems', singular: 'web-system' }
};

/** Categories sorted newest-first and grouped under a year marker. */
export const TIMELINE_CATEGORIES = new Set(['landing-pages', 'site-history', 'websites', 'web-systems']);

/** Which JSON categories feed each project card on the intro panel. */
export const INTRO_PROJECT_GROUPS = {
  websites: ['websites'],
  'web-systems': ['web-systems'],
  'landing-pages': ['landing-pages'],
  'mini-tools-readme': ['mini-tools-dev', 'mini-tools-media', 'mini-tools-converters', 'mini-tools-generators', 'mini-tools-pdf', 'mini-tools-personal'],
  'edm-tools': ['edm-html-builder', 'edm-kinetic-modules', 'edm-tools'],
  'mini-games': ['mini-games']
};

/** Project fields folded into the workspace search index. */
export const PROJECT_TEXT_FIELDS = ['title', 'description', 'role', 'extendedDescription', 'company', 'challenge', 'technique', 'outcome'];

// ---- Content --------------------------------------------------------------

export const CONTACT_EMAIL = 'contact@brunovidasi.com';

export const SOCIAL_LINKS = {
  github: 'https://github.com/brunovidasi',
  linkedin: 'https://www.linkedin.com/in/brunovidasi/?locale=en_US',
  codepen: 'https://codepen.io/brunovidasi'
};

export const bioText = `Hi, I'm Bruno — a Brazilian-born, Sydney-based developer with ${yearsExperience}+ years of experience across full-stack and front-end development. I specialise in building reliable, well-structured systems — from custom PHP/Node back-ends to pixel-perfect front-ends.\n\nI'm 32, originally from Rio de Janeiro, Brazil, and I've called Sydney home since 2017. \nI'm an Australian citizen, fluent in English, Portuguese and Spanish.\n\nRather than a typical portfolio, this website is built like a developer tool — a file explorer, tabs, even a boot-up sequence — so browsing it feels less like reading a resume and more like poking around a codebase, getting a real sense of how I think and build.`;

export const bootLines = [
  '$ initializing brunovida.si...',
  '$ mounting /experience ... ok',
  `$ loading ${yearsExperience}+ years of experience ... ok`,
  '$ compiling creativity.module ... ok',
  '$ launching interface_'
];
