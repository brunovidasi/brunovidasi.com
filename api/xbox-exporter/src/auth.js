import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import { randomBytes } from 'node:crypto';
import { live, xnet } from '@xboxreplay/xboxlive-auth';

const root = process.cwd();
const configPath = path.join(root, 'config.json');
if (!fs.existsSync(configPath)) {
  console.error('Missing config.json. Copy config.example.json to config.json first.');
  process.exit(1);
}
const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
if (!config.clientId || config.clientId.includes('PASTE_')) {
  console.error('Set clientId in config.json first. See README.md for the important OAuth limitations.');
  process.exit(1);
}
const redirect = new URL(config.redirectUri);
const port = Number(config.port || redirect.port || 3000);
if (redirect.hostname !== 'localhost' || redirect.pathname !== '/callback') {
  console.error('redirectUri must use localhost and the /callback path.');
  process.exit(1);
}
const state = randomBytes(24).toString('hex');
const authorizeUrl = live.getAuthorizeUrl({
  clientId: config.clientId,
  scope: 'XboxLive.signin XboxLive.offline_access',
  responseType: 'code',
  redirectUri: config.redirectUri,
  state
});

console.log('\nOpen this Microsoft sign-in URL in your browser and complete MFA there:\n');
console.log(authorizeUrl + '\n');
console.log(`Waiting for callback at http://localhost:${port}/callback. Keep this Terminal window open.\n`);

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://localhost:${port}`);
  if (url.pathname !== '/callback') {
    res.writeHead(404); res.end('Not found'); return;
  }
  const oauthError = url.searchParams.get('error');
  const code = url.searchParams.get('code');
  if (oauthError) {
    console.error('Microsoft OAuth error:', oauthError, url.searchParams.get('error_description') || '');
    res.writeHead(400); res.end('Microsoft sign-in failed. Return to Terminal for details.');
    server.close(() => process.exit(1)); return;
  }
  if (!code || url.searchParams.get('state') !== state) {
    res.writeHead(400); res.end('Missing code or OAuth state mismatch.');
    console.error('Callback code/state validation failed.');
    server.close(() => process.exit(1)); return;
  }
  try {
    // The library may use default OAuth client settings. Custom app exchange may fail;
    // see README.md. No Microsoft password is requested or stored by this script.
    const oauth = await live.exchangeCodeForAccessToken(code);
    if (!oauth?.access_token) throw new Error('No OAuth access_token returned by the library.');
    const user = await xnet.exchangeRpsTicketForUserToken(oauth.access_token, 'd');
    const xsts = await xnet.exchangeTokenForXSTSToken(user.Token, {
      XSTSRelyingParty: 'http://xboxlive.com',
      sandboxId: 'RETAIL',
      optionalDisplayClaims: ['gtg', 'xid', 'mgt']
    });
    const claims = xsts?.DisplayClaims?.xui?.[0] || {};
    if (!claims.uhs || !xsts.Token) throw new Error('Xbox XSTS response did not contain expected user hash/token.');
    const saved = {
      saved_at_utc: new Date().toISOString(),
      expires_in: oauth.expires_in ?? null,
      refresh_token: oauth.refresh_token ?? null,
      user_hash: claims.uhs,
      xsts_token: xsts.Token,
      xuid: claims.xid ?? null,
      gamertag: claims.gtg ?? null
    };
    fs.writeFileSync(path.join(root, 'tokens.json'), JSON.stringify(saved, null, 2) + '\n', { mode: 0o600 });
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end('<h2>Sign-in callback received</h2><p>Return to Terminal. You may close this tab.</p>');
    console.log('Xbox token saved to tokens.json. Treat this file like a password.');
    server.close(() => process.exit(0));
  } catch (error) {
    console.error('Token exchange failed:', error?.message || error);
    res.writeHead(500); res.end('Sign-in callback received, but Xbox token exchange failed. Check Terminal and README.md.');
    server.close(() => process.exit(1));
  }
});
server.listen(port, 'localhost');
