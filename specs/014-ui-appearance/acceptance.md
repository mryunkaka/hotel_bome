# UI-001 Acceptance checklist

- [x] Desktop account menu renders Light, Dark, and System default actions (feature test).
- [x] Mobile profile menu renders Light, Dark, and System default actions (feature test).
- [x] The existing Appearance settings page offers the same modes with a clear System default label (feature test).
- [ ] Each selected mode applies immediately and survives a full reload.
- [ ] System default follows the operating system setting.
- [ ] The Property access notice has readable amber colors in both light and dark modes.
- [x] Fresh Vite CSS contains the dark utility selectors used by the Blade views.
- [ ] Desktop and mobile page layouts remain usable (visual review pending).
- [x] No Filament dependency, database change, or generated asset is included in the source changes.

Automated verification: focused `AppearanceThemeTest` 3 tests/13 assertions PASS; full `composer test --no-interaction` 194 tests/979 assertions PASS; Vite build 22 modules PASS; local HTTP references and serves the refreshed CSS. The mode-persistence, OS-following, and rendered color contrast checks still need visual browser acceptance.
