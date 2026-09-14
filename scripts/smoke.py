import argparse, os, subprocess, sys, urllib.error, urllib.parse, urllib.request

p = argparse.ArgumentParser()
p.add_argument("--base-url", default=os.environ.get("BYL_BASE_URL"))
p.add_argument("--product-path", default=os.environ.get("BYL_SMOKE_PRODUCT_PATH", ""))
p.add_argument("--local-tls-insecure", action="store_true")
a = p.parse_args()
if not a.base_url: raise SystemExit("Set --base-url or BYL_BASE_URL")
base = a.base_url.rstrip("/"); host = urllib.parse.urlparse(base).hostname or ""
allowed_insecure = {"localnew.beyourlover.com", "localdev.beyourlover.com", "localhost", "127.0.0.1"}
if a.local_tls_insecure and host not in allowed_insecure: raise SystemExit("--local-tls-insecure is restricted to approved local hosts")
paths = ["/", "/shop/", "/cart/", "/my-account/", "/wp-json/", "/robots.txt"]
if a.product_path: paths.append("/" + a.product_path.strip("/"))

def fetch(url):
    if a.local_tls_insecure:
        curl = "curl.exe" if os.name == "nt" else "curl"
        r = subprocess.run([curl, "-k", "-sS", "-L", "-w", "\n__BYL_META__%{http_code}\t%{url_effective}", url], capture_output=True, encoding="utf-8", errors="replace", timeout=25)
        if r.returncode: raise RuntimeError(r.stderr.strip() or f"curl exit {r.returncode}")
        body, meta = r.stdout.rsplit("\n__BYL_META__", 1); status, final = meta.split("\t", 1)
        return int(status), body, final.strip()
    req = urllib.request.Request(url, headers={"User-Agent": "BYL-V3-Smoke/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=20) as r: return r.status, r.read(2000000).decode("utf-8","replace"), r.geturl()
    except urllib.error.HTTPError as e: return e.code, e.read(2000000).decode("utf-8","replace"), e.geturl()

fail = []
for path in paths:
    url = base + path
    try: status, body, final = fetch(url)
    except Exception as e: fail.append(f"{url}: {e}"); continue
    print(f"{status} {url} -> {final}")
    if status >= 500: fail.append(f"{url}: server error {status}")
if fail:
    print("SMOKE FAIL"); [print("-", x) for x in fail]; sys.exit(1)
print("SMOKE PASS")
