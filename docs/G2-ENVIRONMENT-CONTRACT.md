# G2 Environment Contract

| Environment | Purpose | Indexing | Payments | Email | ERP |
|---|---|---|---|---|---|
| localnew | primary development/UAT | blocked/noindex | disabled | log | disabled/explicit UAT |
| staging | permanent production-like validation | Access + noindex | sandbox | sandbox/log | explicit UAT |
| production | www.beyourlover.com | page contract | live | live | live |

## Immutable rules
1. Production transactional data is authoritative.
2. Staging/local DB never overwrites newer Production transactional data.
3. Canonical origin remains `https://www.beyourlover.com`.
4. Media origin remains `https://img.beyourlover.com`.
5. Secrets are injected per environment and never committed.
6. Side effects fail closed outside Production.
7. Staging remains permanent; it is not renamed into Production.

Production cutover = exact Git release + latest Production data + deterministic migrations.
