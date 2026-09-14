// ==========================================================================
// Routing.
//
// In production each tab is a real path (`/experience`) served by the same
// index.html, so links are shareable and crawlable. On localhost that would
// need a rewrite rule, so DEV mode switches the same ids to hash routes.
// ==========================================================================

import { files, ROUTE_ALIASES, WEBSITE_STYLE_CATEGORIES, DEFAULT_OPEN_TABS } from './config.js';
import { state, websiteDetailIds, resetWebsiteDetailIds } from './state.js';
import { renderTabs } from './tabs.js';
import { renderExplorer } from './explorer.js';
import { showActivePanel } from './panels.js';
import { tryOpenToolTabRoute } from './tool-tabs.js';

export const isLocalhost = ['localhost', '127.0.0.1', ''].includes(location.hostname);

/** Hash routing + no boot animation. Opt-in, localhost only. */
export function isDevMode(){
  return isLocalhost && localStorage.getItem('devMode') === 'true';
}

// ---- Query flags ----------------------------------------------------------

function queryFlag(name){
  return new URLSearchParams(location.search).get(name);
}

/** `?fs=1` — open with the explorer hidden. */
export function wantsFullscreenView(){
  return queryFlag('fs') === '1';
}

/** `?all=1` — open every tool in the deep-linked tool's category. */
export function wantsAllToolsInCategory(){
  return queryFlag('all') === '1';
}

/** `?p=<id>` — the website/web-system whose detail view should be open. */
export function readWebsiteDetailFromUrl(){
  return queryFlag('p');
}

function stripWebsiteDetailParam(search){
  if(!search || !search.includes('p=')) return search || '';
  const params = new URLSearchParams(search);
  params.delete('p');
  const str = params.toString();
  return str ? '?' + str : '';
}

// ---- Document title -------------------------------------------------------

// Every route otherwise shares the same static <title>, so same-origin tabs
// (e.g. several mini-tools opened via the app page's "Open" links) are
// indistinguishable in Safari's tab switcher. Give each active tab/tool its
// own title so they can actually be told apart.
const BASE_DOCUMENT_TITLE = document.title;

export function updateDocumentTitle(){
  const label = state.activeId && files[state.activeId] && files[state.activeId].label;
  document.title = label ? `Bruno Vieira • ${label}` : BASE_DOCUMENT_TITLE;
}

// ---- Reading & writing the URL -------------------------------------------

export function currentRouteId(){
  return isDevMode()
    ? decodeURIComponent(location.hash.replace(/^#/, ''))
    : decodeURIComponent(location.pathname.replace(/^\/+|\/+$/g, ''));
}

/** Applies a route id to the workspace state. Returns false for unknown ids. */
export function applyPath(id){
  id = ROUTE_ALIASES[id] || id;
  if(!id || !files[id]) return false;
  if(files[id].folder) state.openFolders[files[id].folder] = true;
  if(!state.openTabs.includes(id)) state.openTabs.push(id);
  state.activeId = id;
  return true;
}

export function updatePath(id){
  const search = stripWebsiteDetailParam(location.search);
  if(isDevMode()){
    const newHash = id ? '#' + id : '';
    if(location.hash !== newHash || location.search !== search){
      history.pushState(null, '', location.pathname + search + newHash);
    }
    return;
  }
  const newPath = id ? '/' + id : '/';
  if(location.pathname !== newPath || location.search !== search){
    history.pushState(null, '', newPath + search);
  }
}

/** Absolute URL for a tab, for "Open in New Window/Tab". */
export function urlForTabId(id){
  const path = isDevMode() ? (location.pathname + '#' + id) : ('/' + id);
  return location.origin + path;
}

/** Pushes a website detail view onto the history stack as `?p=<id>`. */
export function pushWebsiteDetailPath(category, id){
  const params = new URLSearchParams(location.search);
  params.set('p', id);
  const search = '?' + params.toString();
  const path = isDevMode() ? location.pathname : '/' + category;
  const hash = isDevMode() ? (location.hash || '#' + category) : '';
  history.pushState(null, '', path + search + hash);
}

// ---- Init & history navigation -------------------------------------------

/**
 * Resolves the entry URL before the first render. A route the workspace does
 * not know may still be a tool tab, which cannot exist until the project JSON
 * has loaded — it is parked on `state.pendingToolRouteId` for projects.js.
 */
export function initRoute(){
  const initialRouteId = currentRouteId();
  state.enteredViaDeepLink = applyPath(initialRouteId);
  if(WEBSITE_STYLE_CATEGORIES.includes(state.activeId)){
    websiteDetailIds[state.activeId] = readWebsiteDetailFromUrl();
  }
  state.pendingToolRouteId = state.enteredViaDeepLink ? null : (initialRouteId || null);
}

export function initRouter(){
  function handleRouteChange(){
    const id = currentRouteId();
    if(!id){
      state.activeId = 'intro';
      state.openTabs = DEFAULT_OPEN_TABS.slice();
      resetWebsiteDetailIds();
      renderTabs();
      renderExplorer();
      showActivePanel();
      return;
    }
    if(applyPath(id) || tryOpenToolTabRoute(id)){
      resetWebsiteDetailIds();
      if(WEBSITE_STYLE_CATEGORIES.includes(state.activeId)){
        websiteDetailIds[state.activeId] = readWebsiteDetailFromUrl();
      }
      renderTabs();
      renderExplorer();
      showActivePanel();
    }
  }

  window.addEventListener('popstate', handleRouteChange);
  window.addEventListener('hashchange', handleRouteChange);
}
