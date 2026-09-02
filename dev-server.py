#!/usr/bin/env python3
"""Local dev server that mirrors the .htaccess SPA rewrite, plus live reload.

.htaccess only gets read by Apache, so a plain static server 404s on a
direct visit/refresh of a deep-linked tab (e.g. /experience). This serves
the same directory but falls back to index.html for those routes, keeping
local behavior in sync with the live site.

It also injects a tiny polling script into served HTML pages so the
browser auto-refreshes whenever a watched file changes on disk.

Usage: python3 dev-server.py [port]   (default port 8000)
"""
import http.server
import os
import re
import sys
import threading
import time

PORT = int(sys.argv[1]) if len(sys.argv) > 1 else 8000

# Keep this route list in sync with the RewriteRule in .htaccess.
SPA_ROUTES = re.compile(
    r"^/(bio|experience|education|skills|websites|landing-pages"
    r"|mini-tools|edm-tools|edm-work|site-history|freelance"
    r"|contact|documents)/?$"
)

WATCH_EXTS = {".html", ".css", ".js", ".json"}
IGNORE_DIRS = {".git"}

_lock = threading.Lock()
_last_change = 0.0


def _scan_once():
    latest = 0.0
    for dirpath, dirnames, filenames in os.walk("."):
        dirnames[:] = [d for d in dirnames if d not in IGNORE_DIRS]
        for fn in filenames:
            if os.path.splitext(fn)[1] in WATCH_EXTS:
                try:
                    mtime = os.path.getmtime(os.path.join(dirpath, fn))
                except OSError:
                    continue
                latest = max(latest, mtime)
    global _last_change
    with _lock:
        _last_change = latest


def _watch_loop():
    while True:
        _scan_once()
        time.sleep(1)


RELOAD_SNIPPET = b"""
<script>
(function() {
  var last = null;
  setInterval(function() {
    fetch('/__reload').then(function(r) { return r.text(); }).then(function(t) {
      if (last === null) { last = t; return; }
      if (t !== last) { location.reload(); }
    }).catch(function() {});
  }, 1000);
})();
</script>
</body>"""


class Handler(http.server.SimpleHTTPRequestHandler):
    def end_headers(self):
        self.send_header("Cache-Control", "no-store")
        super().end_headers()

    def translate_path(self, path):
        clean_path = path.split("?", 1)[0].split("#", 1)[0]
        if SPA_ROUTES.match(clean_path):
            fs_path = super().translate_path(clean_path)
            if not os.path.exists(fs_path):
                return super().translate_path("/index.html")
        return super().translate_path(path)

    def do_GET(self):
        clean_path = self.path.split("?", 1)[0].split("#", 1)[0]

        if clean_path == "/__reload":
            with _lock:
                token = str(_last_change).encode()
            self.send_response(200)
            self.send_header("Content-Type", "text/plain")
            self.send_header("Content-Length", str(len(token)))
            self.end_headers()
            self.wfile.write(token)
            return

        fs_path = self.translate_path(self.path)
        if fs_path.endswith(".html") and os.path.isfile(fs_path):
            with open(fs_path, "rb") as f:
                body = f.read()
            body = body.replace(b"</body>", RELOAD_SNIPPET, 1)
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)
            return

        super().do_GET()


if __name__ == "__main__":
    threading.Thread(target=_watch_loop, daemon=True).start()
    with http.server.ThreadingHTTPServer(("", PORT), Handler) as httpd:
        print(f"Serving http://localhost:{PORT}  (Ctrl+C to stop, auto-reloads on save)")
        httpd.serve_forever()
