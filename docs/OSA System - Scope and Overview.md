# OSA Activity Request & Document Tracking System
### Scope and Overview

---

## 1. Purpose

The system streamlines how student organizations at Ateneo de Zamboanga University request, submit, and track documentation for in-campus and off-campus activities under the Office of Student Affairs (OSA).

It does **not** replace the university's official approval chain or wet-signature requirements. It digitizes the document preparation, submission, and status-tracking layer that currently causes delays — incomplete requirements, lost paperwork, and repeated trips to the OSA office.

---

## 2. Constraints (Non-Negotiable)

These items must remain physical, wet-signature processes and cannot be digitized:

- **FORM A1.1** (Pink Form / In-Campus Activity Form) — see [in-campus forms/FORM A2-1.md](in-campus%20forms/FORM%20A2-1.md)
- **FORM A2.2** (Blue Form / Off-Campus Activity Form) — see [out-campus forms/FORM A2-2.md](out-campus%20forms/FORM%20A2-2.md)
- **Certificate of Compliance** — bundled with and submitted alongside the Blue Form; its notarization block must remain physical. See [out-campus forms/CERTIFICATE OF COMPLIANCE.md](out-campus%20forms/CERTIFICATE%20OF%20COMPLIANCE.md)
- **Parent's Consent signature** (3 physical copies, wet signature — off-campus activities; 1 copy in-campus)
- **Medical Certificate** (requires infirmary clearance in person)

Students/orgs must still personally obtain the Pink/Blue forms (with the attached Certificate of Compliance for off-campus) from OSA and route them through moderator, OSA Director, and VPA/VP for Higher Education for signature.

---

## 3. What Gets Digitized

| Current (Paper) | Digitized Version |
|---|---|
| Letter of Request (handwritten/typed, physically noted by moderator) | Digital Activity Request form, routed to moderator for in-system endorsement |
| List of Participants (separate paper) | Structured form entry or digital upload |
| Schedule of Activities / Program Flow (separate paper) | Structured form entry or digital upload |
| Photocopy of Moderator/Dean ID | Digital upload |
| ID of Parent/Guardian and Student | Digital upload (the ID photocopies themselves are digitizable; only the Parent's Consent signature stays wet-ink) |
| Activity Schedule/Itinerary (off-campus) | Structured form entry or digital upload |
| **OSA Form 3** (Local Off-Campus Activities Report of Compliance) | Structured digital form with a single digital recommending-approval step from the moderator only. See [out-campus forms/OSA FORM 3.md](out-campus%20forms/OSA%20FORM%203.md) |

Everything else in Section 2 stays physical but is still **tracked** in the system as a checklist item (submitted / not yet submitted), even though the file itself isn't uploaded.

---

## 4. User Roles

- **Organization Account** — Designated officer(s) per accredited org (e.g., President, Secretary) submit and manage requests under the org's profile. Individual students do not get separate accounts; access is org-level.
- **Moderator Account** — Reviews and digitally endorses/approves requests for their assigned org(s), replacing the "duly noted by moderator" step on the old paper letter.
- **OSA Admin Account** — Combined OSA Program Officer / Admin role. Reviews submissions, verifies completeness, manages the request queue, and also handles system administration: org accreditation status, account provisioning, moderator assignments, and document templates.
- **OSA Director Account** — Final digital notation before the request moves to the physical signing stage.

---

## 5. Core Features

### 5.1 Digital Activity Request
Replaces the paper Letter of Request. Structured form capturing activity type (in-campus/off-campus), date, time, venue, purpose, and participant count. Submitted requests route automatically to the assigned moderator for endorsement, then into OSA's review queue.

### 5.2 Document Checklist & Upload Center
Auto-generated checklist per activity type, based on the actual forms in [`docs/in-campus forms/`](in-campus%20forms/) and [`docs/out-campus forms/`](out-campus%20forms/). Digitally uploadable items (participant list, schedule, moderator/dean/parent/student IDs, OSA Form 3) are submitted as files or form data. Physical-only items (Pink/Blue form, Certificate of Compliance, Parent's Consent signature, Medical Certificate) appear as tracked checklist items with status flags, not upload fields. Certificate of Compliance is checked off together with the Blue Form, not as a separate item.

### 5.3 Status Tracking Dashboard
- **Org view:** Submitted → Moderator Endorsed → OSA Reviewing → Digital Docs Complete → Awaiting Physical Forms/Signatures → Approved → Denied/Revisions Needed
- **OSA Admin view:** Filterable queue of all pending requests by type, date, org, and completeness

### 5.4 Physical Document Bridge
Once the digital packet is complete, the system generates a reference number/slip. The org presents this at OSA to receive the physical Pink/Blue form and begin the signature chain. After wet-signature approval, OSA can optionally scan the final signed form back into the system as a record copy.

### 5.5 Notifications
- Moderator: new request awaiting endorsement
- Org: missing documents, approaching deadlines, status changes
- OSA Admin: packet complete and ready for physical stage

### 5.6 Records/Archive
Searchable history per org of past requests, approval turnaround times, and submitted digital documents — supporting accreditation renewal and OSA reporting needs.

### 5.7 OSA Admin Configuration
- Manage accredited org list and designated account holders
- Manage moderator-to-org assignments
- Manage document templates (participant list, schedule formats, OSA Form 3)
- Enforce the 3-day-prior submission rule (flag or block late submissions)

---

## 6. Out of Scope (for this phase)

- Replacing or digitizing wet-signature forms (A1.1, A2.2) or the Parent's Consent signature
- Replacing physical notarization of the Certificate of Compliance
- Processing medical clearances or infirmary records
- Handling Office of Administration / University Security Office permissions for external guests, materials, or equipment

---

## 7. Reference Forms

Source form transcriptions live alongside their original scans:

- `docs/in-campus forms/` — FORM A1 (general requirements), FORM A2-1 (Pink Form)
- `docs/out-campus forms/` — FORM A1 (general requirements), FORM A2-2 (Blue Form), OSA FORM 3, Certificate of Compliance

---

## 8. Expected Outcome

Organizations submit a complete digital packet before ever visiting OSA in person. OSA Admin verifies completeness ahead of time instead of during the counter visit. The only remaining manual steps are the physical form pickup, wet signatures, and notarization — the paperwork-assembly and status-chasing friction is removed, while the university's required approval chain stays intact.
