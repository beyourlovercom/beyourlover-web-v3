# BeYourLover Newsletter M3.7 — Owner UAT Gate / Readiness Pack (2026-10-10)

**Governance:** This file is an acceptance plan, not an execution permit. No PR merge, Dev runtime safety replacement, subscriber write, SMTP attempt, WWW deployment, ERP sync, or G5 configuration change is authorized by storing this plan. GitHub main is the only merge/release source.

## 0. Exact baseline and release boundary

- Website main (last exact-head audit after G5 Closure): `d4ea6bae459fb57d5ad37f9ac2292dff081ed6aa`; recheck before any release.
- G5 Closure **complete**: PR #48 authorized main-only merge `779c4d8b9167ab695f36c7e1b1fb363ead9b4e65`, then PR #56 authorized main-only merge `d4ea6bae459fb57d5ad37f9ac2292dff081ed6aa`. No Dev/WWW deployment occurred as part of either merge.
- Newsletter security: PR #68 Draft, reconciled onto G5-merged main with Newsletter-owned changes only (HEAD changes with this document; use its CURRENT head), Issue #67. Owner Code UAT approved the prior exact-code design; independent final PR merge authorization remains required. Website Newsletter: PR #62 Draft, Owner visual PASS; it must NOT merge or deploy via this safety PR.
- Installed Dev safety MU SHA256 `8766416cba7e3651ec4f7656fcad9705da832d30adedfb08238e646776f8f137` was read-only verified before G5 #56 main Closure and matches the newly merged G5 main safety source. Revalidate installed hash immediately before any later authorized Dev change. Preserve its AfterShip/Automizely egress + ingress quarantine, Woo orders/customers REST protection, and 11 synthetic-test-order limit.
- Last read-only Dev checks: `staging`, site `https://dev.beyourlover.com`, G2 permit ABSENT; Newsletter API OFF, outbound OFF, one-use mail permit ABSENT, Newsletter table 0 rows, exact private full DB backup SHA256 + gzip integrity PASS. All email safety remains default OFF.
- Core Newsletter main `6eea6cc6a616b5e07ed204d0530618f0f522176f`: site-wide JS for safe email token landing, deliberately requires human button click to POST confirm; never automatically confirm a scanner GET. Owner mailbox remains privately designated and is **not listed in GitHub**.

## 1. Code-only independent regression evidence (Owner security review prerequisite)

Release candidate must prove **both** Newsletter and original G5 protections together:
- Existing Dev Safety MU PHP syntax PASS.
- Newsletter fail-closed smoke: **38 scenarios**, including missing/expired/revoked ticket, 0600 file permission, symlink attacks, backup absence/tamper/path, different recipients, To/Cc/Bcc/Reply-To, attachments/embeds, invalid body/action/tokens, wrong site/production/CLI, prior rejection and retry; plus simultaneous two-process one-ticket race PASS.
- **Original, unmodified** G5 provider/AfterShip offline regression: **61 checks + bidirectional quarantine PASS**; original `pre_wp_mail` priority **99** retained with terminal `PHP_INT_MAX` fail-closed verifier.
- G2 P1.3 **16** scenarios, P5 Store API **51** checks, P7 webhook **14** offline cases PASS.
- GitHub Foundation CI SUCCESS on the **exact current PR HEAD**. Before each merge, re-validate base main and CI after any head change.
- Static contract: Current Newsletter candidate equals G5 PR #56 complete safety source except one reviewed Newsletter guard block replacing the old blanket `pre_wp_mail` guard. **Never deploy an older main-only safety source over the live G5 file**.

All passing code-only tests are **offline**, not a real mail delivery, confirmation, consent or Dev+ERP joint E2E.

## 2. Owner security CODE UAT — four business questions

1. **No accidental customer emails:** Normal order/payment/AfterShip flows on Dev still cannot send external mail. The one-mail gate defaults CLOSED; it is not a general opt-in for SMTP.
2. **Only one explicit test:** A specially authorized, short-lived, private, one-time grant can permit exactly ONE BeYourLover Newsletter confirmation to the owner's test mailbox; any failure consumes the grant and has NO automatic retry.
3. **G5 remains fully protected:** No reduction in the 11-order test quota, AfterShip ingress/egress isolation, native Woo order/customer REST protections, G2 closure or payment test isolation.
4. **No unintended release:** No Website/WWW/ERP change, no real subscriber imports and no marketing campaigns.

**Owner Code UAT outcome:** Record PASS or FAIL in PR #68 discussion with the **current exact head SHA** and the four verified items. A code PASS is NOT a merge approval or real-email authorization.

## 3. Required separate authorization sequence after Code UAT

1. **COMPLETE:** G5 owner separately approved the main-only Closure of PR #48 then PR #56. Actual verified merge SHAs are shown in Section 0; no Dev/WWW deployment.
2. **CODE-ONLY COMPLETE:** PR #68 was reconciled onto the new G5-merged main. The GitHub comparison reports only Newsletter-owned PHP, CI, two Newsletter documents and one Newsletter test. Exact-head Foundation CI and all security regressions must be rechecked again if this document or head changes; preserve installed G5 source and rollback identity.
3. Owner separately approves the final Newsletter PR merge and **Dev-only exact-file safety deploy** with verified private backup and rollback, no global public API enablement, no new plugin, no G5 changes.
4. Only after independent signed approval and no earlier execution safety refusal, use a supported approved operator test facility to provision a 0600 five-minute one-use grant. Allow **at most one** genuine Newsletter double-opt-in confirmation email to the privately owner-designated mailbox. Never disclose the email address, raw message, token or credentials in GitHub.
5. Verify WordPress transport acceptance is distinct from mailbox receipt. If delivery is uncertain, do NOT retry under same grant. Only the Owner deliberately clicks **Confirm my subscription** on the linked Dev landing page; confirm token action must be POST rather than auto-confirming GET.
6. Independently check the one test subscriber `pending → subscribed`, replay rejection, and exact test-record-only cleanup. Confirm Newsletter API+outbound OFF, G2 CLOSED, test permit consumed/absent, final rows return to baseline, all WWW/ERP/payment guards untouched. Real ERP signed read-only connector E2E has its OWN gate.

## 4. STOP conditions / reviewer limitations

Any unexpected code/source SHA, G5 inbound/outbound regression, failure to verify the original Dev safety plugin SHA or backup, wrong mailbox, >1 sender attempt, email to any other recipient, prior transport uncertainty, reused permit, G2 opened, production order write, unauthorized ERP import, or rejected execution security check: STOP immediately. Never hide or bypass a tool/Owner gate.

No active Dev runtime change is needed to perform Section 2 **code UAT**. The actual mailbox UAT (Section 3) is **not yet unblocked**.

## 5. Roadmap

M3.0–M3.7 Newsletter page/contract/isolated DB PASS → Issue #67 code security and Owner Code UAT PASS → G5 #48/#56 main Closure PASS → Newsletter reconciled exact-head CI PASS → **independent Newsletter #68 merge authorization gate** → independently authorized Dev safety installation → one real confirmation mailbox UAT → Core↔ERP E2E → final Website owner/release closure.
