# Feature Specification: Appearance controls and dark-mode contrast

**Feature ID**: UI-001
**Created**: 2026-10-25
**Status**: Implemented; browser visual acceptance pending
**Reference**: `D:\Project\hotel-bome` (visual and navigation reference only; no Filament code or dependency is copied)

## Intent

Give staff a quick, consistent way to choose a light, dark, or system-default appearance, and ensure the existing dark-mode styling actually reaches the browser. The current authenticated UI already has an Appearance settings page and dark color classes; the global menus do not expose the choice, and the compiled CSS served locally predates those Blade classes.

This is the first bounded UI parity slice. It does not rebuild the legacy Filament modules or change business workflows.

## User scenarios

### US1 — Change appearance from the app menu (P1)

As a signed-in staff member, I can choose Light, Dark, or System default from the desktop account menu or mobile profile menu so I can quickly set the contrast I prefer.

**Acceptance scenarios**

1. Given an authenticated user on any app page, when the desktop account menu is opened, then Light, Dark, and System default choices are available.
2. Given an authenticated user on a narrow screen, when the mobile profile menu is opened, then the same three choices are available.
3. When a mode is selected, the page appearance changes immediately and the choice survives navigation and reload using Flux's existing browser preference handling.
4. System default follows the operating system appearance and reacts to system preference changes.

### US2 — Read notices and content in dark mode (P1)

As a staff member using Dark mode, I can read warning, status, and form surfaces without pale-on-pale text or unexplained white panels.

**Acceptance scenarios**

1. Given Dark mode on `/admin/users/memberships`, when the page loads, then the explanatory amber notice uses its dark background and readable light text.
2. Given the generated CSS is served without a Vite development server, when the page loads, then the stylesheet includes the dark utility classes referenced by the current Blade views.
3. Light mode remains readable and existing intentional white surfaces such as QR codes retain their required light background.

## Functional requirements

- **FR-001**: The desktop account menu and mobile profile menu expose Light, Dark, and System default choices.
- **FR-002**: Choices use Flux's existing `$flux.appearance` values `light`, `dark`, and `system`; no database field, custom storage layer, package, or version change is introduced.
- **FR-003**: The existing Appearance settings page continues to provide all three choices and labels the `system` option clearly as the default device appearance.
- **FR-004**: The authenticated app keeps `@fluxAppearance` and the Tailwind selector-based dark variant; current responsive layouts remain intact.
- **FR-005**: The checked-in Blade classes and the served Vite stylesheet are synchronized so dark-mode surfaces in the current UI receive their intended colors.
- **FR-006**: The new Laravel + Flux app remains Filament-free; the legacy project is used only to inform future screen placement and navigation mapping.

## Out of scope

- Porting the legacy PMS modules, data tables, or Filament components.
- Changing authorization, hotel data, or account-scoped settings persistence.
- Changing Flux, Livewire, Tailwind, or PHP versions.

## Success criteria

- **SC-001**: The desktop and mobile app menus each render one accessible choice for all three appearance modes.
- **SC-002**: Selecting each mode works immediately and remains selected after a full page reload.
- **SC-003**: A fresh Vite build contains the amber dark-mode selectors used by the membership notice; the notice is visually readable in Dark mode.
- **SC-004**: Existing Appearance settings and authenticated page navigation continue to render.
