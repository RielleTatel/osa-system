# Local tickets: activity email notifications

Spec: [Activity email notifications](../specs/activity-email-notifications.md)

All tickets are local; triage label: `ready-for-agent`.

## EMAIL-1 — Durable submission emails

Status: ready-for-agent

Persist per-recipient delivery records and background jobs atomically with activity submission. Notify OSA admins, directors, and the configured office mailbox; deduplicate addresses. Add the SMTP email summary with protected links, configured sender, and office Reply-To. Acceptance: submission succeeds without contacting SMTP; only agreed recipients receive the summary after worker processing.

## EMAIL-2 — Director readiness

Status: ready-for-agent

Queue director-only emails when digital documents reach Docs complete. Serialize the transition and suppress repeated scheduling. Acceptance: repeated checklist verification sends one notice per director; endorsement and OSA Form 3 saves add no emails.

## EMAIL-3 — Delivery operations

Status: ready-for-agent

Add bounded retries and a staff-only delivery history/failure page. Document SMTP, worker supervision, and administrator recovery. Acceptance: SMTP failures retry independently, remain visible after exhaustion, and can recover without duplicate successful sends.

## EMAIL-4 — Validation and review

Status: ready-for-agent

Use the confirmed HTTP and worker seams for behavior tests, run focused checks and the final suite/build, then perform standards/spec reviews and commit this feature locally. Record outcomes here when complete.
