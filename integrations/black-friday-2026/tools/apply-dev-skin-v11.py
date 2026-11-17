#!/usr/bin/env python3
"""Dev-only atomic UI skin revision, no WordPress data or settings changed."""
import hashlib
import os
import shutil
import subprocess
from datetime import datetime
from pathlib import Path

ROOT=Path("/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/plugins/black-friday-2026")
STAGE=Path("/home/u247343654/bf2026-promotion-ui-r3-staging/skin-v11")
BACKUP_ROOT=Path("/home/u247343654/bf2026-r3-backups")
FILES={
 "includes/class-byl-bf2026-promotion-ui.php": "fcea1c19c9e901b2ac33c95e89fb56ac71abacb62f917ccf28ca78c2bafb0d51",
 "assets/promotion-ui.js": "99d16e7442929ac76a23beabea4d2753785569ce11a6563cabcdd83a0d5eb50c",
 "assets/promotion-ui.css": "de7179887e4aac07b4eedf76f18ba4c238591d4c759b6a4c2ce25324b3fbcfdd",
}
MAIN="71ec8da0452d3c32aad75bffc95b62a4df36ec3b90651e87ba6b41223c560dab"
def sha(path):
 return hashlib.sha256(path.read_bytes()).hexdigest()
def fail(msg):raise SystemExit("FAIL "+msg)
def main(dry_run):
 if str(ROOT).find("/domains/dev.beyourlover.com/")<0: fail("host restriction violated")
 if sha(ROOT/"black-friday-2026.php")!=MAIN:fail("entrypoint changed; stop")
 for path,expected in FILES.items():
  current=ROOT/path; incoming=STAGE/path
  if not current.is_file() or sha(current)!=expected:fail("baseline SHA mismatch "+path)
  if not incoming.is_file() or incoming.stat().st_size<150:fail("incoming missing "+path)
  if sha(current)==sha(incoming):fail("file unchanged "+path)
 result=subprocess.run(["php","-l",str(STAGE/"includes/class-byl-bf2026-promotion-ui.php")],stdout=subprocess.PIPE,stderr=subprocess.PIPE,universal_newlines=True)
 if result.returncode:fail("PHP lint "+result.stdout+" "+result.stderr)
 script=(STAGE/"assets/promotion-ui.js").read_text()
 css=(STAGE/"assets/promotion-ui.css").read_text()
 php=(STAGE/"includes/class-byl-bf2026-promotion-ui.php").read_text()
 if "1.1.0-dev-rc2" not in php or "Black Friday" not in script or "linear-gradient" not in css:fail("design contract missing")
 print("PASS source guard, exact SHA match, PHP lint")
 if dry_run:
  print("DRY_RUN only, files:"+",".join(FILES))
  return
 backup=BACKUP_ROOT/("skin-v11-"+datetime.utcnow().strftime("%Y%m%dT%H%M%SZ"))
 for path in FILES:
  (backup/path).parent.mkdir(parents=True,exist_ok=True)
  shutil.copy2(ROOT/path,backup/path)
  if sha(backup/path)!=FILES[path]:fail("backup readback mismatch "+path)
 for path in FILES:
  target=ROOT/path
  temp=target.with_name("."+target.name+".v11-new")
  shutil.copy2(STAGE/path,temp)
  os.replace(str(temp),str(target))
  if sha(target)!=sha(STAGE/path):fail("write readback failed "+path)
 print("PASS Dev-only three files updated")
 print("BACKUP "+str(backup))
 for path in FILES:print("NEW_SHA "+path+" "+sha(ROOT/path))
 print("PASS main plugin untouched",sha(ROOT/"black-friday-2026.php"))
if __name__=="__main__":
 import sys
 main("--dry-run" in sys.argv)
