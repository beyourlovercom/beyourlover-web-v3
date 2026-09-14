# G2 Release + Rollback Contract

Every deploy is identified by repository, Git SHA, optional tag, build time, environment and migration-set hash.

## Rules
- Production deploys use immutable artifacts from one Git SHA.
- No direct Production code editing.
- Migration IDs use `V3_DB_###`.
- A migration is applied only after verification passes.
- Destructive cleanup is deferred until post-launch stability.
- Rehearsals use a fresh Production snapshot.

## Rollback
Before checkout reopens, previous code, pre-cutover DB snapshot and media recovery must be available.
After new Production transactions exist, never blindly restore an old full DB; prefer code rollback/compatibility mode and reconcile any post-cutover transactions.
