# Forista Attendance PRD (Final)

Oct 27, 2026 · @MatchaHollic

## Overview and Purpose

Forista Attendance is a mobile-first web app that records meeting attendance for Forum OSIS Surakarta only when a member's phone confirms they are at the agenda location after the meeting.

- **What:** Admins create accounts and recurring weekly agendas with a map location. After a meeting ends, members have a 15-minute window to slide a button, share their GPS position, and be matched against the agenda location.
- **Why it matters:** Attendance becomes verifiable, instant, and automatically summarized, so the board can see who took part without manual counting.
- **Strategic fit:** Participation data feeds a simple 1 to 10 attendance score per member and gives the forum a reliable record for evaluation and recognition.
- **Scale:** 60 accounts (3 Admins, 57 Members), about 15 people checking in at the same time.
- **Stack:** Laravel 13 with the React starter kit (Inertia JS), Tailwind and shadcn/ui, PostgreSQL, OpenStreetMap with Leaflet, hosted on a private Coolify VPS. The interface is in Indonesian only, light theme only.

## Problem Statement

Without a location-verified check-in, Forum OSIS Surakarta cannot confirm that members were physically present at a meeting, and building the attendance recap takes manual effort. Members also need a quick, reliable way to check in during a short window, and a reminder so they do not miss it.

## Target Users and Personas

The personas below are working profiles built from the brief, not research findings.

| Persona | Who                                                   | Context                                                                                                                               | Typical scenario                                                                                                                                                                                   |
| ------- | ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Member  | One of 57 forum members, each belonging to a division | Uses a smartphone (Android or iPhone) with mobile data; attends weekly meetings at a venue in Surakarta; not tech-savvy by assumption | The meeting ends. A reminder arrives, the member opens Forista, slides the button, waits a few seconds while the map confirms the location, and sees "Presensi berhasil!"                          |
| Admin   | One of 3 board members who run the forum              | Uses a phone or laptop; prepares agendas ahead of time; needs accurate records quickly                                                | Before the term starts, creates accounts and a weekly agenda with a map pin. After each meeting, reviews who is missing, resets a forgotten password, or records a manual attendance with a reason |

## Goals and Success Metrics

Targets are proposed starting points for the first term of use; please adjust them.

| Goal                | Metric                                                                               | Target                                  | How measured                                                                 |
| ------------------- | ------------------------------------------------------------------------------------ | --------------------------------------- | ---------------------------------------------------------------------------- |
| Fast check-in       | Time from opening the dashboard to the success pop up                                | 60 seconds or less for 90% of check-ins | Timestamps of the first attempt and the attendance record                    |
| Adoption            | Members who attended a meeting and checked in themselves                             | 90% or more per meeting                 | Attendance records with source = auto, divided by attendees counted by Admin |
| Trustworthy results | Duplicate attendance records per member per meeting                                  | 0                                       | Database unique constraint and test suite                                    |
| Minimal manual work | Attendance recorded manually by Admin                                                | 10% or less of all records              | Records with source = manual                                                 |
| Dependable recap    | Excel and PDF export totals matching the on-screen recap                             | 100%                                    | Automated comparison test                                                    |
| Responsive system   | Server decision time per check-in at 15 simultaneous users                           | Under 2 seconds                         | Load test before release                                                     |
| Timely reminders    | Active push subscribers who receive a reminder within 1 minute of its scheduled time | 95% or more                             | Notification log                                                             |

## Scope (In-Scope vs. Out-of-Scope)

**In scope for release 1**

- Accounts created by Admin only; username and password login; 2 roles (Admin, Member); 3 Admins, who also check in as members.
- Password reset by Admin; members contact an Admin when they forget a password.
- Member data: full name, division.
- Weekly recurring agendas with start time, end time, and a location set by address or coordinates on an OpenStreetMap map.
- Per-agenda radius (default 20 m) and check-in window (default 15 minutes after the meeting ends), both editable by Admin.
- Check-in from a phone: welcome pop up, dashboard, slide-to-track button, location match, success pop up.
- Up to 3 failed location attempts per member per meeting; Admin can record attendance manually with a mandatory reason.
- Single status "Hadir" (present); members without a record count as absent in recaps.
- Member dashboard: total attended, attendance percentage, score from 1 to 10, history by month.
- Admin dashboard: overview home, recap per agenda, list of members not yet present, session cancellation, settings page, log screen, Excel and PDF export.
- Reminders as in-app notifications and web push (PWA) where the device supports it.
- Indonesian interface, light theme, deployment on a private Coolify VPS with PostgreSQL.

**Out of scope for release 1**

- Late status, leave or sick requests, check-out (leave) attendance.
- Fake GPS detection and device binding.
- Check-in from laptops or desktops.
- Self-registration, email-based or self-service password reset.
- Dark mode and any language other than Indonesian.
- Designed mockups of the Admin screens (they are built responsive, but only the Member screens have a prototype).

## Key Features and User Stories

The member flow is: log in, see the welcome pop up, open the dashboard, slide the button after the meeting, wait while the map tracks and matches the location, see "Presensi berhasil!", and find the result on the dashboard.

| ID   | Feature           | User story                                                                                                                                             |
| ---- | ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| F1-1 | Accounts          | As an Admin, I want to create member accounts with a username, full name, and division so that only real members can use the app.                      |
| F1-2 | Login             | As a Member, I want to log in with my username and password so that I can reach my own attendance.                                                     |
| F1-3 | Password reset    | As an Admin, I want to reset a member's password so that a member who forgot it can get back in after contacting me.                                   |
| F2-1 | Welcome           | As a Member, I want to see "Selamat Datang, (my name)" after logging in so that I know I am in the right account.                                      |
| F3-1 | Agenda            | As an Admin, I want to create a weekly recurring agenda with start time, end time, and location so that meeting sessions are generated automatically.  |
| F3-2 | Location          | As an Admin, I want to set the location by address or coordinates and see the radius on a map so that I know where check-in will be accepted.          |
| F3-3 | Rules per agenda  | As an Admin, I want to set the radius and the check-in window per agenda so that venues of different sizes can be handled.                             |
| F4-1 | Check-in          | As a Member, I want to slide a button to track my location after the meeting so that my attendance is recorded without typing.                         |
| F4-2 | Failure feedback  | As a Member, I want to see why a check-in failed and how many attempts remain so that I can fix the problem and try again.                             |
| F4-3 | Confirmation      | As a Member, I want a "Presensi berhasil!" pop up so that I know my attendance is saved.                                                               |
| F5-1 | Member dashboard  | As a Member, I want to see my total attended meetings, attendance percentage, 1 to 10 score, and monthly history so that I can track my participation. |
| F6-1 | Reminders         | As a Member, I want a reminder before a meeting and when check-in opens so that I do not miss the window.                                              |
| F7-1 | Recap             | As an Admin, I want a recap per agenda and a list of members who are not yet present so that I can follow up quickly.                                  |
| F7-2 | Export            | As an Admin, I want to export the recap to Excel and PDF so that I can report to the board.                                                            |
| F8-1 | Manual attendance | As an Admin, I want to record attendance manually with a reason so that members with a real problem are still counted and the change is traceable.     |

### Admin dashboard and functions

The Admin area is responsive (phone and laptop). It opens on the recap for today's or the next meeting. Admins are also members, so they can switch to the Member screens to check in.

| Area                          | Functions                                                                                                                                                                                                                                                                                                           | User story                                                                                                       |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| Members and divisions         | List with search and division filter; create a member (full name, username, division, initial password); edit; deactivate or reactivate; reset a password to a temporary one; create, rename, and delete divisions (delete is blocked while members are assigned); open any member's history, percentage, and score | As an Admin, I want to manage members and divisions so that the roster and passwords are always correct.         |
| Agendas and sessions          | Create, edit, and stop weekly recurring agendas; set the location on the map, by address, or by coordinates; set radius and check-in window; see generated sessions; shift the check-in window of one session when a meeting runs long                                                                              | As an Admin, I want to control when and where check-in is valid so that it matches the real meeting.             |
| Attendance recap              | Choose an agenda and a date or date range; every active member shown as Hadir or Belum hadir with time, distance, source (auto or manual), and attempts used; counts per status; division filter; per-member totals with percentage and score over the range                                                        | As an Admin, I want to see who attended each meeting and over time so that I can evaluate participation.         |
| Not yet present and reminders | List of members not yet present for a session, filterable by division, with a button to send them a reminder; members locked after 3 failed attempts are visible here                                                                                                                                               | As an Admin, I want to follow up with members who have not checked in so that the recap is complete.             |
| Manual attendance             | Record attendance for a member with a mandatory reason; the Admin and time are stored                                                                                                                                                                                                                               | As an Admin, I want to record attendance manually with a reason so that real problems are handled and traceable. |
| Export                        | Excel and PDF of the recap exactly as filtered on screen                                                                                                                                                                                                                                                            | As an Admin, I want to export the recap so that I can report to the board.                                       |

**Approved additions, included in release 1**

- An overview home with a live present count and a window countdown.
- Cancelling a single session so it is left out of percentages.
- A settings page for the default radius, window length, accuracy ceiling, and reminder times (otherwise these live in configuration and only radius and window are editable per agenda).
- A screen to browse the log of manual attendance and password resets (the data is recorded either way).

## Acceptance Criteria

A feature is done when every condition under it passes in an automated test or a recorded manual check on a real phone.

**F1 Accounts, login, password reset**

- [ ] The register route returns 404; no sign-up link exists in the interface.
- [ ] A deactivated account cannot log in.
- [ ] Repeated failed logins are throttled (default 5 per minute per username and IP).
- [ ] After an Admin resets a password, the member can log in with the new password and the old one stops working.
- [ ] A Member cannot open any /admin page; an Admin is redirected to the admin area after login and can open the Member screens to check in.

**F2 Welcome**

- [ ] After a successful login the pop up shows the member's full name and closes after 4 seconds or on tap.

**F3 Agenda and location**

- [ ] A weekly agenda creates sessions for the next 4 weeks with no duplicate for the same agenda and date.
- [ ] Editing an agenda changes only future sessions.
- [ ] Location can be set by address search, map click, or coordinates; invalid coordinates are rejected.
- [ ] Radius accepts 5 to 100 m (default 20 m); window length accepts 5 to 120 minutes (default 15).
- [ ] The check-in window opens at the meeting end time and closes after the window length.

**F4 Check-in**

- [ ] Before the window opens the slider is disabled and shows the opening time; after it closes it shows "closed".
- [ ] Sliding past 75% of the track starts tracking; releasing earlier returns the knob to the start.
- [ ] Distance at or below the radius: attendance "Hadir" is saved and the success pop up appears.
- [ ] Distance above the radius: nothing is saved, the member sees the distance and remaining attempts, and one of 3 attempts is used.
- [ ] Reported GPS accuracy worse than 20 m: the request is rejected with advice to move to an open area, and no attempt is used.
- [ ] After 3 out-of-radius attempts the member is locked for that meeting and told to contact an Admin.
- [ ] A second check-in for the same meeting creates no second record.
- [ ] Time is taken from the server; changing the phone clock has no effect.
- [ ] If location permission is denied, the screen explains how to enable it.

**F5 Member dashboard**

- [ ] Attendance percentage = attended meetings divided by meetings held so far, rounded to a whole number.
- [ ] Score = percentage divided by 10, rounded to one decimal, with a minimum of 1.0 (85% gives 8.5; 0% gives 1.0).
- [ ] After a successful check-in the dashboard updates without a full page reload.
- [ ] A member can see only their own records.

**F6 Reminders**

- [ ] Each member receives each reminder once: 30 minutes before the meeting starts, when the check-in window opens, and 5 minutes before it closes (the last one only for members not yet present).
- [ ] Deactivated accounts receive nothing.
- [ ] The bell shows the unread count and clears it when opened.
- [ ] On iPhone, when the site is not added to the Home Screen, the app shows an instruction instead of the push opt-in.

**F7 Recap and export**

- [ ] The recap lists every active member with "Hadir" or "Belum hadir" for the selected meeting.
- [ ] The not-yet-present list can be filtered by division.
- [ ] Excel and PDF totals equal the on-screen totals.

**F8 Manual attendance**

- [ ] A reason of at least 10 characters is required.
- [ ] The record stores the Admin who created it and the time, and is marked source "manual".
- [ ] A member who is already present cannot be given a second record.

**F9 Admin dashboard**

- [ ] Only Admins can open the Admin area; every Admin action is rejected for a Member account.
- [ ] An Admin can create, edit, deactivate, and reactivate members, and can reset a password.
- [ ] A division with members assigned cannot be deleted.
- [ ] Shifting one session's check-in window changes only that session and recalculates its reminders.
- [ ] The recap for a date range shows attended count, percentage, and score per member, and the export matches it.
- [ ] The 3 Admin accounts appear in recaps like any other member.
- [ ] The not-yet-present list shows attempts used and marks members locked after 3 failed attempts.
- [ ] A member who never checks in appears as absent as soon as the window closes.

**F10 Approved additions**

- [ ] The overview home shows, for today's or the next session, the present count against active members and a countdown to the window opening or closing.
- [ ] Cancelling a session removes it from every percentage and score and from the reminder schedule; it cannot be cancelled after check-ins exist unless they are removed first.
- [ ] The settings page edits the default radius (20 m), default window length (15 minutes), GPS accuracy ceiling (20 m), and the three reminder offsets; changes apply to future sessions only.
- [ ] The log screen lists manual attendance and password resets with Admin, member, reason, and time, newest first, and cannot be edited.

## Non-Functional Requirements

| Area                   | Requirement                                                                                                                                                                                                                                                                                                                                                                                                                     |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Performance            | Server decision for a check-in in under 2 seconds with 15 simultaneous users; dashboard loads in under 3 seconds on a 4G phone                                                                                                                                                                                                                                                                                                  |
| Security               | Hashed passwords; login and check-in throttling; CSRF protection; server-side validation; role-based authorization on every route; audit log for manual attendance and password resets; HTTPS only; no public registration                                                                                                                                                                                                      |
| Data integrity         | One attendance record per member per meeting enforced by a database unique constraint; concurrent requests cannot create duplicates                                                                                                                                                                                                                                                                                             |
| Scalability            | Built for 60 accounts today; separate web, queue worker, and scheduler processes so it can grow to a few hundred users without redesign                                                                                                                                                                                                                                                                                         |
| Reliability            | Daily PostgreSQL backup with a tested restore; health check endpoint for Coolify                                                                                                                                                                                                                                                                                                                                                |
| Accessibility          | Touch targets of at least 44 px; text contrast of at least 4.5 to 1; tap or Enter alternative to the slide gesture; reduced-motion respected; icon buttons have labels                                                                                                                                                                                                                                                          |
| Compatibility          | Current Chrome and Safari on Android and iOS; GPS requires HTTPS; web push on iPhone requires iOS 16.4 or later with the site added to the Home Screen                                                                                                                                                                                                                                                                          |
| Privacy and compliance | Location is requested only during check-in; raw GPS coordinates are used only for the match and are not stored; only the attendance result (status, time, distance, source) is kept; members see only their own data; a short consent notice explains this. Location is personal data under Indonesia's Personal Data Protection Law (UU PDP, Law No. 27 of 2022), so the forum should confirm its obligations with its advisor |
| Localization           | Indonesian interface; all dates and times in Asia/Jakarta (WIB)                                                                                                                                                                                                                                                                                                                                                                 |
| Map usage              | OpenStreetMap tiles and Nominatim are used within their usage policies, with visible attribution and rate limiting                                                                                                                                                                                                                                                                                                              |

## Assumptions, Constraints, and Dependencies

| Type       | Item                                                                              | Impact if wrong                                                                             |
| ---------- | --------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| Assumption | Every member has a smartphone with GPS, mobile data, and a modern browser         | Members without one need manual attendance                                                  |
| Assumption | Members check in at the venue shortly after the meeting ends                      | Members who leave early may fail the radius check                                           |
| Assumption | The default radius of 20 m suits the venues used                                  | Too tight rejects real attendees; too loose accepts people outside                          |
| Constraint | GPS inside buildings is often accurate only to 10 to 30 m                         | Some honest members will be rejected; the 3 attempts and manual attendance cover this       |
| Constraint | No fake GPS detection in release 1                                                | A determined member could spoof a location                                                  |
| Constraint | iPhone web push works only for sites added to the Home Screen (iOS 16.4 or later) | Some iPhone members will see in-app reminders only                                          |
| Constraint | Passwords are reset only by Admins                                                | Admins must be reachable when members are locked out                                        |
| Dependency | Private Coolify VPS with a domain and HTTPS certificate                           | GPS and push do not work without HTTPS                                                      |
| Dependency | OpenStreetMap tiles and Nominatim geocoding                                       | Address search or the map is unavailable if they are down; coordinates can be typed instead |
| Dependency | Web push keys (VAPID) and a running queue worker and scheduler                    | Reminders are not sent without them                                                         |
| Dependency | Laravel 13 React starter kit, PostgreSQL, Leaflet                                 | Version changes may require adjustments                                                     |

## Open Questions and Appendix

**Open questions**

- [x] Radius: the final answer says 20 m from the agenda point, while the first brief said 15 m. This PRD uses 20 m as the default (editable per agenda) and also 20 m as the GPS accuracy ceiling. Confirmed: yes.
- [x] Reminder schedule: assumed 30 minutes before the meeting starts, when the window opens, and 5 minutes before it closes. Confirmed: yes.
- [x] Do the 3 Admins also check in as members at meetings? Confirmed: yes.
- [x] If a meeting runs long, may an Admin move that meeting's check-in window? Confirmed: yes, by editing the session.
- [x] Members who never check in count as absent automatically when the window closes. Confirmed: yes.
- [x] GPS coordinate retention: raw coordinates are not stored; only the attendance result is kept. Confirmed.
- [x] Is the 1 to 10 score visible only to the member and Admins? Confirmed: yes.
- [x] Are the proposed success targets acceptable? Confirmed: yes.

**Appendix A: links**

- [Interactive prototype of the Member screens](https://claude.ai/artifact/8F23d7z88Auy2fkjx3FhDG). It still shows the earlier rules (late status, 15 m, window starting at meeting start) and will be updated to match this PRD.
- [Build prompts for Laravel 13](https://claude.ai/artifact/Kfh119eQNEjoCaB9oBZFQF). Written against the earlier draft; to be regenerated from this PRD.
- [Earlier draft PRD in Indonesian](https://claude.ai/artifact/4hnmqSu4b61Amw6WxdnmBV). Superseded by this document.
- Release plan: internal rollout only, with a pilot of about 15 members first, then all 60. No go-to-market plan is needed.

**Appendix B: key data entities**

| Entity                                | Main fields                                                                                                                                                   |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| users                                 | full name, username, password, role (admin or member), division, is\_active                                                                                   |
| divisions                             | name                                                                                                                                                          |
| agendas                               | title, location name, address, latitude, longitude, radius\_m (default 20), weekday, start time, end time, window\_minutes (default 15), starts\_on, ends\_on |
| agenda\_sessions                      | agenda, date, meeting end, window opens\_at, window closes\_at                                                                                                |
| attendances                           | session, user, status (hadir), checked\_at, distance\_m, source (auto or manual), manual\_reason, created\_by                                                 |
| attendance\_attempts                  | session, user, distance\_m, result (matched, outside\_radius, poor\_accuracy)                                                                                 |
| notifications and push\_subscriptions | in-app notifications and browser push endpoints per user                                                                                                      |

**Appendix C: brand palette from the FORISTA logo**

Red #B92F25, gold #F0C93B, green #0E8A45, brown #8A5A2E, cream #FFF6E3, ink #2B1A14. No blue-purple gradients.
