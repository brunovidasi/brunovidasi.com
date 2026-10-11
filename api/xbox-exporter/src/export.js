import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const configPath = path.join(root, 'config.json');
const tokenPath = path.join(root, 'tokens.json');
if (!fs.existsSync(configPath)) { console.error('Missing config.json.'); process.exit(1); }
if (!fs.existsSync(tokenPath)) { console.error('No tokens.json. Run `npm run auth` first.'); process.exit(1); }
const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
const tokens = JSON.parse(fs.readFileSync(tokenPath, 'utf8'));
if (!tokens.user_hash || !tokens.xsts_token) { console.error('tokens.json is missing Xbox token values. Run `npm run auth` again.'); process.exit(1); }
const outputDir = path.resolve(config.outputDir || './output');
fs.mkdirSync(outputDir, { recursive: true });
const auth = `XBL3.0 x=${tokens.user_hash};${tokens.xsts_token}`;
const sleep = ms => new Promise(r => setTimeout(r, ms));

async function fetchAndSave(name, url, headers = {}) {
  const result = { exported_at_utc: new Date().toISOString(), endpoint: url, ok: false };
  try {
    const response = await fetch(url, { headers: {
      Authorization: auth,
      Accept: 'application/json',
      'x-xbl-contract-version': '2',
      ...headers
    }});
    result.http_status = response.status;
    const raw = await response.text();
    try { result.data = raw ? JSON.parse(raw) : null; }
    catch { result.response_text = raw.slice(0, 4000); }
    result.ok = response.ok;
    if (!response.ok) result.error = `HTTP ${response.status}`;
  } catch (e) { result.error = e?.message || String(e); }
  fs.writeFileSync(path.join(outputDir, `${name}.json`), JSON.stringify(result, null, 2) + '\n');
  return { file: `${name}.json`, ok: result.ok, http_status: result.http_status ?? null, error: result.error ?? null };
}

const endpoints = [
  ['profile', 'https://profile.xboxlive.com/users/me/profile/settings?settings=Gamertag,GameDisplayPicRaw,GameDisplayName,AccountTier,TenureLevel,Gamerscore'],
  ['presence', 'https://userpresence.xboxlive.com/users/me'],
  ['friends', 'https://peoplehub.xboxlive.com/users/me/people?scopes=All']
];
if (tokens.xuid) {
  endpoints.push(
    ['achievements', `https://achievements.xboxlive.com/users/xuid(${encodeURIComponent(tokens.xuid)})/achievements`],
    ['title_history', `https://titlehistories.xboxlive.com/users/xuid(${encodeURIComponent(tokens.xuid)})/titles`]
  );
} else console.warn('No XUID in tokens.json. Skipping XUID-specific endpoints.');

const results = [];
console.log(`Exporting raw responses to ${outputDir}\n`);
for (const [name, url] of endpoints) {
  console.log(`Fetching ${name} ...`);
  const result = await fetchAndSave(name, url);
  results.push({ name, ...result });
  console.log(result.ok ? `  OK (${result.http_status})` : `  FAILED (${result.http_status ?? 'network'}): ${result.error || 'Unknown error'}`);
  await sleep(Number(config.requestDelayMs || 400));
}
const summary = {
  exported_at_utc: new Date().toISOString(),
  xuid: tokens.xuid ?? null,
  gamertag: tokens.gamertag ?? null,
  successful_requests: results.filter(x => x.ok).length,
  failed_requests: results.filter(x => !x.ok).length,
  results,
  note: 'Experimental exporter. API routes, contracts and access can change; this does not guarantee a complete game library or playtime history.'
};
fs.writeFileSync(path.join(outputDir, 'export_summary.json'), JSON.stringify(summary, null, 2) + '\n');
console.log('\nDone. See output/export_summary.json.');
