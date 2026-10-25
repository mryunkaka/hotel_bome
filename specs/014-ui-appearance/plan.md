# UI-001 Implementation Plan

## Approach

Use Flux's existing appearance state (`$flux.appearance`) rather than introducing a separate preference system. Add the three mode actions to the desktop account menu and mobile profile menu, keep the Appearance settings page as a second entry point, and rebuild the local Vite assets because the currently served CSS is dated 2026-10-17 while the current Blade UI is newer.

The old `D:\Project\hotel-bome` project provides visual/navigation reference only. Current app behavior remains in Laravel 13, Livewire 4, Tailwind CSS 4, and Flux Pro 2.20.1.

## Files

- `resources/views/components/theme-mode-menu.blade.php` — reusable Flux menu items for the three appearance values.
- `resources/views/components/desktop-user-menu.blade.php` — desktop account menu entry point.
- `resources/views/layouts/app/sidebar.blade.php` — mobile profile menu entry point.
- `resources/views/pages/settings/⚡appearance.blade.php` — consistent label for the system-default option.
- `resources/views/identity/users/memberships.blade.php` — inspect its existing warning colors; no template change is planned unless the refreshed stylesheet exposes a source-level gap.
- `tests/Feature/AppearanceThemeTest.php` — authenticated menu and membership notice rendering assertions.
- `public/build/assets/*.css` — generated local preview/build output only; ignored and not staged.

## Implementation sequence

1. Add a focused feature test proving both responsive menus expose the three modes, the Appearance settings page retains all three modes, and the membership notice retains its dark color classes.
2. Run that test before the view changes and confirm the expected missing-menu failure.
3. Add a reusable Flux menu fragment and include it from the desktop and mobile menus; keep `@fluxAppearance` as the single appearance controller.
4. Update the Appearance page label to “System default”.
5. Build the Vite assets and confirm the fresh stylesheet includes the dark amber selectors referenced by the Blade view.
6. Run the focused feature test, `git diff --check`, and manually inspect the membership page in Light, Dark, and System default modes at desktop and mobile widths; verify a reload preserves each explicit selection and System default follows the OS setting.

## Constraints and risks

- No database migration or account setting is needed; Flux stores the preference in the browser.
- A user selecting System default can see different appearances on devices with different OS settings.
- Generated `public/build` output is ignored; it is rebuilt for the local preview and is not committed.
- This slice does not claim that every legacy Filament page has been ported.
