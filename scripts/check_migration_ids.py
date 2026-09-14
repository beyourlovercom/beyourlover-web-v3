from pathlib import Path
import re
files=sorted(Path("platform/byl-platform/migrations").glob("V3_DB_*.php"))
ids=[]
for path in files:
    m=re.search(r"'id'\s*=>\s*'(V3_DB_\d{3})'", path.read_text(encoding="utf-8"))
    if not m: raise SystemExit(f"Missing valid migration id in {path}")
    ids.append(m.group(1))
if len(ids)!=len(set(ids)): raise SystemExit("Duplicate migration IDs found")
if ids!=sorted(ids): raise SystemExit("Migration IDs are not ordered")
print("Migration ID check PASS: "+(", ".join(ids) if ids else "none"))
