import argparse, os, sys, urllib.request, urllib.error
p=argparse.ArgumentParser(); p.add_argument("--base-url",default=os.environ.get("BYL_BASE_URL")); p.add_argument("--product-path",default=os.environ.get("BYL_SMOKE_PRODUCT_PATH","")); a=p.parse_args()
if not a.base_url: raise SystemExit("Set --base-url or BYL_BASE_URL")
base=a.base_url.rstrip("/"); paths=["/","/shop/","/cart/","/my-account/","/wp-json/","/robots.txt"]
if a.product_path: paths.append("/"+a.product_path.strip("/"))
fail=[]
for path in paths:
    url=base+path; req=urllib.request.Request(url,headers={"User-Agent":"BYL-V3-Smoke/1.0"})
    try:
        with urllib.request.urlopen(req,timeout=20) as r: status=r.status; body=r.read(2000000).decode("utf-8","replace"); final=r.geturl()
    except urllib.error.HTTPError as e: status=e.code; body=e.read(2000000).decode("utf-8","replace"); final=e.geturl()
    except Exception as e: fail.append(f"{url}: {e}"); continue
    print(f"{status} {url} -> {final}")
    if status>=500: fail.append(f"{url}: server error {status}")
if fail:
    print("SMOKE FAIL"); [print("-",x) for x in fail]; sys.exit(1)
print("SMOKE PASS")
