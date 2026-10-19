// Deploy-time asset build. Run by the deploy workflow, never locally: the
// repo itself stays build-free, served as plain ES modules and one stylesheet.
//
// What it does to the checkout before it is uploaded:
//   - bundles js/main.js and everything it imports into one minified file,
//     so the browser makes one request instead of walking ~26 imports
//   - minifies css/style.css
//   - names both after a hash of their contents and points index.html at them,
//     so .htaccess can cache them for a year: a changed file gets a new name
//
// The source files are left in place; only index.html stops referring to them.
//
// Needs esbuild: npm install --no-save --no-package-lock esbuild@0.28.1

import { build, transform } from 'esbuild';
import { createHash } from 'node:crypto';
import { readFile, writeFile } from 'node:fs/promises';

// It rewrites index.html in place, which would dirty a local checkout.
if(!process.env.CI) throw new Error('build-assets.mjs only runs in CI (it rewrites index.html); set CI=1 to force it in a throwaway copy.');

const hashOf = text => createHash('sha256').update(text).digest('hex').slice(0, 10);

const bundle = await build({
  entryPoints: ['js/main.js'],
  bundle: true,
  format: 'esm',
  minify: true,
  legalComments: 'none',
  write: false
});
const js = bundle.outputFiles[0].text;

const { code: css } = await transform(await readFile('css/style.css', 'utf8'), {
  loader: 'css',
  minify: true
});

// Same directories as the sources, so the CSS's relative font URLs still resolve.
const jsPath = `js/main.${hashOf(js)}.js`;
const cssPath = `css/style.${hashOf(css)}.css`;
await writeFile(jsPath, js);
await writeFile(cssPath, css);

let html = await readFile('index.html', 'utf8');
for(const [from, to] of [['src="js/main.js"', `src="${jsPath}"`], ['href="css/style.css"', `href="${cssPath}"`]]){
  if(!html.includes(from)) throw new Error(`index.html no longer contains ${from}`);
  html = html.replace(from, to);
}
await writeFile('index.html', html);

console.log(`${jsPath} (${js.length} bytes), ${cssPath} (${css.length} bytes)`);
