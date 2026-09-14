# G2 Media Exception Workflow

Canonical public media origin: `https://img.beyourlover.com`.

G1 froze 5,976 offloaded attachments and 47 non-trash exceptions.
Each exception must finish in exactly one state before cutover:
1. `OFFLOAD` — verify in R2 and confirm public URL.
2. `LEGACY_RETAIN` — intentionally keep origin copy.
3. `RETIRE` — prove no live reference is needed and retire explicitly.

Verification fields: attachment ID, MIME, references, local path, R2 URL, live-reference count, decision, timestamp.
No exception may disappear as a side effect of making V3 media-stateless.
