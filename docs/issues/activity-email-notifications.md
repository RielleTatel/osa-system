# Local tickets: activity email notifications

Spec: [Activity email notifications](../specs/activity-email-notifications.md)

All tickets are local. Original triage label: `ready-for-agent`; all four tickets are now complete.

## EMAIL-1 — Durable submission emails

Status: complete

Persist per-recipient delivery records and background jobs atomically with activity submission. Notify OSA admins, directors, and the configured office mailbox; deduplicate addresses. Add the SMTP email summary with protected links, configured sender, and office Reply-To. Acceptance: submission succeeds without contacting SMTP; only agreed recipients receive the summary after worker processing.

## EMAIL-2 — Director readiness

Status: complete

Queue director-only emails when digital documents reach Docs complete. Serialize the transition and suppress repeated scheduling. Acceptance: repeated checklist verification sends one notice per director; endorsement and OSA Form 3 saves add no emails.

## EMAIL-3 — Delivery operations

Status: complete

Add bounded retries and a staff-only delivery history/failure page. Document SMTP, worker supervision, and administrator recovery. Acceptance: SMTP failures retry independently, remain visible after exhaustion, and can recover without duplicate successful sends.

## EMAIL-4 — Validation and review

Status: complete

Use the confirmed HTTP and worker seams for behavior tests, run focused checks and the final suite/build, then perform standards/spec reviews and commit this feature locally. Record outcomes here when complete.

## Validation — 2026-10-02

- Implemented in local commit `02eb84b`, reviewed against starting commit `807483c4837e89bc81be31073b8aadddc1e60c50`.
- Focused email feature suite: 9 tests, 84 assertions, all passing.
- Final full suite: 113 tests, 418 assertions, all passing.
- Frontend production build, targeted PHP syntax checks, Pint formatting, and diff whitespace checks passed. No static typechecker is configured.
- Live university SMTP delivery was not attempted. Deployment migration, university SMTP/sender/office configuration, and worker activation are described in the [operations guide](../operations/activity-email.md).
- Pre-existing unrelated working-tree changes were excluded from the feature commit.

## Standards

No documented-standard violations or meaningful baseline code smells found. The implementation follows the documented Laravel service/controller/Blade structure, role middleware, and design-system conventions. No additional material correctness risks were identified. SMTP's possible duplicate-delivery window after server acceptance is documented.

## Spec

No material spec findings or scope creep. The implementation matches the agreed recipients, exclusions, deduplication, atomic workflow/job persistence, director-readiness event, SMTP configuration, retries, failure visibility, administrator recovery, and protected links. Tests cover the agreed HTTP and worker seams.

Review summary: Standards — 0 findings, no outstanding issue. Spec — 0 findings, no outstanding issue. Both reviews were performed independently; the main implementation agent ran validation.
