# BUGS.md
_Known bugs — updated by Claude on discovery or after test failures_
_Claude writes immediately on discovery — do not wait for session end_
_Fixed and verified bugs move to docs/BUGS_ARCHIVE.md_

<!-- Severity: blocking=no further work | high=no workaround | medium=workaround exists | low=minor -->

## Open bugs

### BUG-1 — Pre-existing Jetstream test failures (8 tests)
- **Discovered:** 2026-06-22 via full suite run
- **Affects:** `tests/Unit/Models/TeamTest`, `tests/Unit/Notifications/TeamInvitationNotificationTest`, `tests/Feature/ExampleTest`, `tests/Feature/PasswordConfirmationTest`
- **Severity:** low
- **Description:** Four test groups fail due to incompatibilities introduced when the app switched to UUID PKs and removed Jetstream teams. Specific issues: (1) `team_members.id NOT NULL` constraint on SQLite in-memory DB — team_members migration predates UUID PK policy; (2) `TeamInvitation::generate()` expects `int $invitedBy` but receives a UUID string; (3) `ExampleTest` expects a redirect from `/` but the app returns 200; (4) `UserFactory::withPersonalTeam()` method missing after Jetstream team feature changes.
- **Blocking:** NONE — Phase 1 and Phase 2 tests all pass. These are Jetstream scaffold tests that no longer match the app.
- **Status:** open
<!-- BUG-N: scan this file for the highest existing number and increment by 1 -->
<!-- Format:
### BUG-[N] — [Short title]
- **Discovered:** YYYY-MM-DD via [test failure / code review / runtime]
- **Affects:** [file or module]
- **Severity:** [blocking / high / medium / low]
- **Description:** [What is wrong]
- **Blocking:** [What this prevents, or NONE]
- **Status:** open / investigating
-->

## Fixed bugs
<!-- Move here when resolved. Include fix summary and covering test. -->
<!-- When this section exceeds 20 entries, archive oldest to docs/BUGS_ARCHIVE.md -->
<!-- Format:
### BUG-[N] — [Short title] ✓
- **Fixed:** YYYY-MM-DD
- **Fix:** [What was done]
- **Covered by:** [test name or file]
-->
