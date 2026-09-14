from pathlib import Path
import datetime, hashlib, json, os, subprocess
def git(*args): return subprocess.check_output(["git",*args],text=True).strip()
sha=git("rev-parse","HEAD")
dirty=bool(git("status","--porcelain"))
migrations=sorted(Path("platform/byl-platform/migrations").glob("V3_DB_*.php"))
mhash=hashlib.sha256(b"".join(p.read_bytes() for p in migrations)).hexdigest()
manifest={"release_id":os.environ.get("BYL_RELEASE_ID",sha[:12]),"git_sha":sha,"dirty":dirty,"built_at_utc":datetime.datetime.now(datetime.timezone.utc).isoformat(),"environment":os.environ.get("BYL_ENV","unknown"),"migration_set_sha256":mhash}
Path("release").mkdir(exist_ok=True)
Path("release/release.json").write_text(json.dumps(manifest,indent=2)+"\n",encoding="utf-8")
print(json.dumps(manifest,indent=2))
