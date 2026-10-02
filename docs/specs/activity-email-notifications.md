# Activity email notifications

Status: ready-for-agent (local only)

## Problem Statement

OSA staff currently have to check the system to discover new organization activity submissions. The university provides SMTP, but the application sends only database notifications for activity workflows. The office needs timely email awareness without making submissions depend on the mail server's availability.

## Solution

Email every OSA admin account, every OSA director account, and the configured shared office mailbox when an organization submits an activity. Email only OSA directors when the activity reaches Docs complete and needs director review. Send in the background, retry temporary failures, and show delivery status to OSA staff.

## User Stories

1. As an OSA staff member, I want email on initial submission, so that I know a new activity has arrived.
2. As an OSA director, I want initial submission notices, so that I have office-wide visibility.
3. As an office mailbox reader, I want submission notices, so that shared office procedures remain usable.
4. As an OSA director, I want an email when digital documents become complete, so that I can provide notation.
5. As an OSA staff member, I want no email on moderator endorsement, so that I receive only the agreed notices.
6. As a moderator, I want this feature to exclude my account, so that it does not add endorsement emails.
7. As an organization officer, I want this feature to exclude my account, so that it does not add organization emails.
8. As a recipient, I want one copy per event and email address, so that a shared address does not receive duplicates.
9. As a recipient, I want the organization, title, dates, and request reference, so that I can identify the activity.
10. As a recipient, I want the action required and a login-protected link, so that I can open the relevant record.
11. As a participant, I want no participant lists or document attachments in these emails, so that email contains only the agreed activity summary.
12. As a recipient, I want the sender named OSA Activity System and replies sent to the office, so that the message has a recognizable source.
13. As an organization officer, I want submission to succeed during SMTP outages, so that my request is not lost or blocked by email.
14. As an OSA staff member, I want automatic retries, so that temporary mail outages can recover without manual work.
15. As an OSA staff member, I want pending, successful, and failed delivery records, so that I can spot missing notices.
16. As an OSA staff member, I want exhausted failures to remain visible, so that I can ask an administrator to resolve them.
17. As a system administrator, I want documented SMTP and worker configuration, so that I can operate delivery using the university server.
18. As a system administrator, I want failed deliveries retryable after configuration is repaired, so that notices can be recovered.
19. As an OSA staff member, I want failed workflow transactions to produce no emails, so that notices correspond to saved submissions.
20. As an OSA director, I want repeated checklist verification to avoid duplicate readiness notices, so that the same review handoff is not announced twice.

## Implementation Decisions

- Use the existing Laravel, Blade, role middleware, mail configuration, and database queue infrastructure.
- Submission recipients are users with OSA admin or OSA director roles plus one configurable office mailbox. Director readiness recipients are OSA directors only. Deduplicate trimmed, lowercase addresses per activity and event.
- No email for moderator endorsement, draft creation, OSA Form 3 saves, approval/rejection, progress changes, organization officers, or moderators. Existing database notifications remain as they are.
- Capture recipients and an activity summary at event time. Persist a delivery record and its database job in the same transaction as the activity event. A unique activity/event/recipient key prevents duplicate scheduling.
- Use a dedicated database queue connection bound to the application's default database, independent of the global synchronous queue setting. Insert jobs within the transaction so workers can only see committed events.
- Serialize the Docs complete transition with an activity row lock. Preserve the current rule: no pending digital checklist items means complete.
- Use the SMTP mailer, the approved configured sender address, the sender name OSA Activity System, and the office mailbox as Reply-To when configured. No SMTP credentials are committed.
- The office mailbox and SMTP values remain deployment configuration; actual university values were not supplied. Missing/invalid office configuration must be visible to OSA staff; individual account notices still work.
- Email includes organization, title, start/end dates, numeric request reference, event-specific action, and the authenticated tracker detail link accessible to both OSA roles. No attachments or participant data.
- Deliver independently per recipient. Use five attempts, with delays of 60, 300, 900, and 3600 seconds. SMTP timeout is 30 seconds; worker timeout is 60 seconds; queue reservation is 120 seconds.
- Record attempt counts, last attempt, SMTP acceptance timestamp, and final failure state. Show these in a paginated Email deliveries page restricted to OSA admins and directors, with a failed-only filter. Do not expose raw SMTP exceptions in the page.
- A successful send means SMTP acceptance, not a guarantee of inbox delivery. SMTP cannot guarantee exactly-once delivery if the worker dies after acceptance but before recording success. Normal duplicate events/jobs must not resend a recorded success.
- Use Laravel's failed-job commands for administrator recovery. No manual retry UI or new notification preferences.

## Testing Decisions

- User confirmed the existing submission/checklist HTTP routes and the delivery worker's public entry point as testing seams.
- Test external behavior: recipient addresses and message content at the mail boundary, workflow responses, and delivery-page output. Exercise the actual database queue through the worker where practical; fake only external mail delivery.
- Follow existing Pest activity-submission and OSA checklist feature tests. Build one failing behavior test followed by its implementation at a time.
- Cover submission fan-out/deduplication, forbidden recipients and events, director-only readiness, repeated verification, login/role restrictions, escaped content and no attachments, transaction rollback, retries/recovery, exhausted failures, and success suppression on job replay.
- Run focused feature tests during development, PHP syntax/style checks, the frontend build, and the full suite once at completion. No PHP static typechecker is configured.
- Review against this spec and repository standards using the starting commit as the fixed point. Commit only this feature's files on the current branch.

## Out of Scope

- Organization emails, moderator emails, and OSA emails upon endorsement.
- Implementing organization edit/resubmit routes. Future resubmission support must explicitly connect its own review event.
- Attachments, participant lists, reminders, digests, preferences, and bounce tracking.
- Remote issue creation, pushing, deployment, real SMTP credentials, or sending live test messages.

## Further Notes

The user confirmed the recommendations and requested local issue tickets, a spec, and implementation. This overrides remote tracker publishing; no tracker setup is needed for this local work. Production activation requires university SMTP details, an approved sender address, the office mailbox, and a supervised worker. Implementation defaults for retries and timeouts are documented above.
