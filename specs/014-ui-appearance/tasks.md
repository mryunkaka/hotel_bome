# Tasks: UI-001 Appearance controls and dark-mode contrast

**Prerequisites**: `spec.md`, `plan.md`, `acceptance.md`
**Active branch**: `main` (project single-mainline mandate)

## Phase 1 — Appearance controls

- [x] T001 [US1] Add `tests/Feature/AppearanceThemeTest.php` assertions that desktop and mobile each render one Light/Dark/System default action, the Appearance settings page still renders all three values, and the membership notice includes its dark amber/text classes. RED: expected menu actions and new label missing. GREEN: focused feature test passed 3 tests/13 assertions.
- [x] T002 [US1] Create `resources/views/components/theme-mode-menu.blade.php` using Flux menu items that set `$flux.appearance` to `light`, `dark`, or `system`; include it from `resources/views/components/desktop-user-menu.blade.php` and the mobile profile menu in `resources/views/layouts/app/sidebar.blade.php`.
- [x] T003 [US1] Update the `system` option label in `resources/views/pages/settings/⚡appearance.blade.php` to “System default”, then rerun the focused feature test and confirm both menus and the settings page expose the three modes.

## Phase 2 — Dark-mode asset and visual acceptance

- [x] T004 [US2] Build current Vite assets with `npm run build`; confirm the fresh CSS contains `dark:bg-amber-950` and `dark:text-amber-100` selectors required by `resources/views/identity/users/memberships.blade.php`. `vp build` passed (22 modules); both selectors are present; local HTTP serves the new manifest bundle and CSS with HTTP 200.
- [x] T005 [US2] Run `git diff --check`, verify `/login` references the new CSS bundle and the CSS asset returns HTTP 200, and ensure generated `public/build` output is ignored and unstaged.
- [ ] T006 [US2] Manually inspect authenticated `/admin/users/memberships` in Light, Dark, and System default at desktop and mobile widths. Reload after each explicit selection and verify System default follows the OS setting. Browser visual acceptance remains open because no Windows computer-use connector is available in this session.
