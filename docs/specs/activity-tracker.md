# Activity tracker and saved reports

Status: ready-for-agent (local only)

## Problem Statement

OSA maintains an Excel activity tracker separately from submitted activity requests. Officers manually update activity progress, and reporting may be requested while activities remain unfinished. Approval of paperwork does not establish that an activity finished. Recreating reports after records change can also lose what OSA actually issued.

## Solution

Make the system the official tracker for submitted activities. OSA admins maintain activity progress independently of approval. OSA admins and the director can filter the current tracker and generate a saved report at any time, downloadable as Excel and PDF. Excel retains the familiar in-campus/off-campus grouping and columns of the supplied 2026–2027 tracker. Every generated report preserves its original data and files.

## User Stories

1. As an OSA admin, I want all submitted activities in one tracker, so that I stop maintaining a second spreadsheet.
2. As an OSA admin, I want drafts excluded, so that the tracker represents submitted requests.
3. As an OSA admin, I want Pending to mean not started, so that future activities are recognizable.
4. As an OSA admin, I want Ongoing to mean started but unfinished, so that work in progress remains reportable.
5. As an OSA admin, I want Completed to mean the activity finished, so that approval is not mistaken for implementation.
6. As an OSA admin, I want to mark an activity Cancelled, so that cancelled plans remain visible.
7. As an OSA admin, I want Not approved derived from denial, so that I do not duplicate an approval decision.
8. As an OSA admin, I want manual progress updates, so that scheduled dates do not incorrectly certify completion.
9. As an OSA admin, I want to correct progress and add remarks, so that operational records stay accurate.
10. As an OSA director, I want to see who changed progress and when, so that updates are accountable.
11. As an OSA admin, I want existing activities marked Needs confirmation, so that migration does not invent their progress.
12. As an OSA admin, I want new submissions to start Pending, so that new activities have a clear initial state.
13. As an OSA staff member, I want all statuses included by default, so that unfinished activities do not prevent reporting.
14. As an OSA staff member, I want progress and approval filters, so that I can answer specific reporting requests.
15. As an OSA staff member, I want date, campus type, and organization filters, so that I can report the requested scope.
16. As an OSA staff member, I want activities overlapping my date range included once, so that activities spanning months remain visible without duplicate rows.
17. As an OSA staff member, I want the expected participant count from the initial submission, so that reports do not imply actual attendance.
18. As an OSA staff member, I want Excel reports with familiar columns and two campus sheets, so that OSA can continue its reporting practice.
19. As an OSA staff member, I want readable PDF reports with repeated table headers, so that reports can be printed and shared.
20. As an OSA staff member, I want reports to state their filters and generation time, so that recipients understand the scope.
21. As an OSA staff member, I want one generated snapshot available in both formats, so that Excel and PDF describe the same data.
22. As an OSA staff member, I want to download earlier reports unchanged, so that I can reproduce previously issued information.
23. As an OSA director, I want to view and generate reports without editing progress, so that my permissions match my responsibilities.
24. As an organization officer, I want reporting data sourced from my submission, so that I do not re-enter it in another tracker.
25. As an OSA staff member, I want unauthorized users blocked from saved reports, so that activity records remain restricted.
26. As an OSA staff member, I want an explicit empty result, so that a report with no matching activities is unambiguous.

## Implementation Decisions

- Keep the existing Laravel, Blade, Tailwind, role middleware, and server-rendered architecture. No separate API or frontend framework.
- Add a tracker module with list, detail/history, admin update, report generation, report detail, and protected download interfaces.
- Progress is a separate enum: needs_confirmation, pending, ongoing, completed, cancelled. Approval continues to use the existing workflow; display denied as Not approved in the tracker and exports.
- Migration backfills existing requests to Needs confirmation. New requests default to Pending. Dates never automatically advance progress. Admins may correct any progress value; this is tracking, not a new approval gate.
- Store each changed progress/remarks update with old/new values, actor, and timestamp in the same transaction. Display history to both OSA roles. No-op updates do not create history.
- Only submitted, non-draft requests appear. Tracker updates do not modify approval status.
- Filter inclusively by activity start <= report end and activity end >= report start. Optional bounds may be omitted. Sort by start date then request ID. Each request is one row, regardless of participants or moderators.
- Support separate approval and progress filters, organization, and campus type. Validate filter values and date order. Do not silently omit records on later pages when exporting.
- Initial submissions currently capture a participant list, not a numeric expected count. Capture its count on submission and backfill older requests from existing participant rows; absent lists remain unknown/blank. Do not derive counts from later OSA Form 3 submissions or uploaded files.
- Map Person in charge to the submitting officer, and Moderator/Dean to assigned organization moderators. Use explicit labels/help to avoid implying that a dean is captured. Preserve multiple moderators in one cell.
- Export Date, Venue, Organization, Activity, Expected participants, Person in charge, Moderator, Approval, Progress, Remarks. Show a date range when applicable.
- Use genuine XLSX with in-campus and off-campus sheets, readable widths, wrapping, frozen headers, status colors, and a legend. No formulas are needed; activity strings must be written as literal text.
- PDF uses the existing PDF renderer, landscape pages, campus sections, repeated headers, and page numbers. Both formats record the generation timestamp, filters, and snapshot identifier.
- A report stores copied row data and display labels plus immutable private Excel/PDF files, creator, generation timestamp, and filters. Downloads serve saved files, never rebuild from live activities. Generation failure must not leave a report advertised as ready.
- Both OSA roles may generate/view/download. Only OSA admins edit progress/remarks. No public storage links.
- Add tracker navigation for both OSA roles and correct the archive description to say approved rather than completed requests.

## Testing Decisions

- Confirmed by the user: use the existing HTTP feature-test boundary for the entire module, including downloadable artifacts. Verify visible behavior, not service internals or private methods.
- Follow the existing Pest feature tests for archive filtering, role restrictions, activity submissions, and PDF downloads. Use factories only to arrange records; observe changes through HTTP pages and downloads.
- Build vertical slices with a failing test followed by implementation: tracker visibility/filtering; progress and history; submission defaults/count; report generation; XLSX/PDF downloads and snapshot preservation; authorization/validation.
- Include cross-month overlap, endpoint dates, drafts, denied requests, unknown participant counts, both campus groups, multiple pages of records, malicious spreadsheet text, and saved output after live data changes.
- Verify exported ZIP/XML structure and PDF content through the download responses. The PDF content regression uses Poppler's pdftotext (skipped with an explicit message when unavailable). Inspect representative rendered PDF pages for readability and repeated headers. Print whitespace may be normalized to keep multiline remarks within page bounds; saved source data remains unchanged.
- Run focused tests during development, PHP syntax checks, frontend build, and the full test suite at completion. No standalone typechecker is configured in this PHP/Blade repository.
- Review changes against this local spec and repository conventions before committing only this feature's files.

## Out of Scope

- GitHub issues, remote publishing, pushing commits, or deployment.
- Importing the supplied spreadsheet, direct OSA activity entry, or synchronizing an editable external spreadsheet.
- Actual attendance, arbitrary historical-date reconstruction, automatic completion, or new approval stages.
- Organization-officer or moderator access to the OSA tracker or report exports.
- Changing existing activity schedules or adding recurring-event modeling.

## Further Notes

The reference is the supplied [TRACKER] ACTIVITY FORMS TRACKER [2026-2027] workbook. Its status dropdowns and colors disagree across rows, so its visual familiarity is preserved while the agreed status definitions govern behavior. The user explicitly approved the shared understanding and requested a local spec followed by implementation and a local commit.
