// ==========================================================================
// Icon markup: Seti-style file glyphs for the explorer/tabs, and the inline
// SVGs used on action buttons.
//
// The file glyphs are private-use codepoints (U+E0xx) from the bundled Seti
// icon font, so they only render where that font is loaded — hence the `glyph`
// class. They are written as \u escapes on purpose: as literal characters they
// are invisible in most editors and get silently dropped by tools that strip
// non-printable input.
// ==========================================================================

const ICON_GLYPHS = {
  js:   { char: '\ue051', color: '#cbcb41' },
  md:   { char: '\ue060', color: '#ffb454' },
  json: { char: '\ue055', color: '#cbcb41' },
  info: { char: '\ue04d', color: '#519aba' },
  html: { char: '\ue048', color: '#ffb454' },
  php:  { char: '\ue070', color: '#a074c4' },
  sh:   { char: '\ue089', color: '#8dc149' },
  eml:  { char: '\ue023', color: '#6d8086' },
  pdf:  { char: '\ue06d', color: '#cc3e44' },
  css:  { char: '\ue01d', color: '#519aba' }
};

const FOLDER_COLOR = '#ffb454';
const FOLDER_CLOSED_SVG = '<svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M2 4.5V6H5.58579C5.71839 6 5.84557 5.94732 5.93934 5.85355L7.29289 4.5L5.93934 3.14645C5.84557 3.05268 5.71839 3 5.58579 3H3.5C2.67157 3 2 3.67157 2 4.5ZM1 4.5C1 3.11929 2.11929 2 3.5 2H5.58579C5.98361 2 6.36514 2.15804 6.64645 2.43934L8.20711 4H12.5C13.8807 4 15 5.11929 15 6.5V11.5C15 12.8807 13.8807 14 12.5 14H3.5C2.11929 14 1 12.8807 1 11.5V4.5ZM2 7V11.5C2 12.3284 2.67157 13 3.5 13H12.5C13.3284 13 14 12.3284 14 11.5V6.5C14 5.67157 13.3284 5 12.5 5H8.20711L6.64645 6.56066C6.36514 6.84197 5.98361 7 5.58579 7H2Z"/></svg>';
const FOLDER_OPEN_SVG = '<svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M2 4.5V9.10022L2.92389 7.5C3.45979 6.5718 4.45017 6 5.52196 6L11.9146 6C11.7087 5.4174 11.1531 5 10.5 5H7C6.86739 5 6.74021 4.94732 6.64645 4.85355L4.93934 3.14645C4.84557 3.05268 4.71839 3 4.58579 3H3.5C2.67157 3 2 3.67157 2 4.5ZM7.06895 13.9953C7.04641 13.9984 7.02339 14 7 14H3.5C2.11929 14 1 12.8807 1 11.5V4.5C1 3.11929 2.11929 2 3.5 2H4.58579C4.98361 2 5.36514 2.15804 5.64645 2.43934L7.20711 4H10.5C11.724 4 12.7426 4.87965 12.958 6.04127C14.605 6.34148 15.5443 8.22106 14.6616 9.75L13.0766 12.4953C12.5407 13.4235 11.5503 13.9953 10.4785 13.9953H7.06895ZM5.52196 7C4.80743 7 4.14718 7.3812 3.78991 8L2.20492 10.7453C1.62757 11.7453 2.34926 12.9953 3.50396 12.9953L10.4785 12.9953C11.193 12.9953 11.8533 12.6141 12.2105 11.9953L13.7955 9.25C14.3729 8.25 13.6512 7 12.4965 7L5.52196 7Z"/></svg>';

/** Monospace badge used for tool tabs, which carry an emoji instead of a glyph. */
const TOOL_ICON_STYLE = 'font-family:var(--mono,monospace);font-size:13px;color:#519aba';

export function fileIconHtml(type){
  const glyph = ICON_GLYPHS[type];
  if(!glyph) return '';
  return `<span class="file-icon glyph" style="color:${glyph.color}">${glyph.char}</span>`;
}

export function toolIconHtml(icon){
  return `<span class="file-icon" style="${TOOL_ICON_STYLE}">${icon}</span>`;
}

export function tabIconHtml(file){
  if(file.fileIconType) return fileIconHtml(file.fileIconType);
  if(file.toolIcon) return toolIconHtml(file.toolIcon);
  return fileIconHtml(file.icon);
}

export function folderIconHtml(open){
  return `<span class="file-icon" style="color:${FOLDER_COLOR}">${open ? FOLDER_OPEN_SVG : FOLDER_CLOSED_SVG}</span>`;
}

// ---- Button icons ---------------------------------------------------------

export const ICON_GITHUB_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>';
export const ICON_CODEPEN_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"><path d="M12 2.5 22 9v6l-10 6.5L2 15V9z"/><path d="M12 2.5v6.2M12 22v-6.2M2 9l10 6.2M22 9 12 15.2M2 15l10-6.2M22 15 12 8.8"/></svg>';
export const ICON_LIVE_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>';
export const ICON_EYE_SVG = '<svg class="btn-icon" viewBox="0 0 24 24"><use href="img/icons/sprite.svg#icon-eye"></use></svg>';
export const ICON_EYE_OFF_SVG = '<svg class="btn-icon" viewBox="0 0 24 24"><use href="img/icons/sprite.svg#icon-eye-off"></use></svg>';
export const ICON_PDF_SVG = '<svg class="btn-icon icon-pdf" viewBox="0 0 24 24"><use href="img/icons/sprite.svg#icon-pdf"></use></svg>';
export const ICON_FULLSCREEN_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M21 16v3a2 2 0 0 1-2 2h-3M8 21H5a2 2 0 0 1-2-2v-3"/></svg>';
