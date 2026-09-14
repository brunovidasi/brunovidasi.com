// ==========================================================================
// Mutable application state.
//
// Kept on a single exported object rather than as loose `let`s so every
// module can both read and write it (ES module bindings are read-only for
// importers) and so every mutation is greppable as `state.<thing> =`.
// ==========================================================================

import { DEFAULT_OPEN_TABS, WEBSITE_STYLE_CATEGORIES } from './config.js';

export const state = {
  /** Tab ids, in bar order. */
  openTabs: DEFAULT_OPEN_TABS.slice(),
  /** Id of the focused tab, or null when every tab is closed. */
  activeId: 'intro',
  /** Folder id -> expanded. */
  openFolders: { about: true },
  /** Tool route that could not be opened yet because the project JSON was still loading. */
  pendingToolRouteId: null,
  /** True when the site was entered on a deep link rather than at the root. */
  enteredViaDeepLink: false
};

/**
 * Tool tabs discovered in the project JSON, keyed by project id.
 * Populated by the card renderers; read by the explorer, terminal and router.
 */
export const TOOL_TAB_REGISTRY = {};

// ---- Website list/detail state -------------------------------------------

/** Category -> id of the project whose detail view is open, or null for the list. */
export const websiteDetailIds = { websites: null, 'web-systems': null };
/** Category -> the loaded project JSON for that category. */
export const websiteItemsByCategory = { websites: [], 'web-systems': [] };
/** Category -> project id the `<h2 class="sect">` was last typed out for. */
export const websiteSectKeys = { websites: '', 'web-systems': '' };
/** Category -> whether the detail view was open on the previous render. */
export const websiteDetailWasOpen = { websites: false, 'web-systems': false };

export function resetWebsiteDetailIds(){
  WEBSITE_STYLE_CATEGORIES.forEach(category => { websiteDetailIds[category] = null; });
}
