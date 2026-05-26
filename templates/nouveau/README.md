# Nouveau Template Prototype

Nouveau is an experimental desktop-style battlefield terminal for PHPDTS. It changes presentation only: game rules, command names, form actions, item pools, combat, movement, skills, endings, database schema, and default templates remain untouched.

## Compatibility Model

- `include/global.func.php::template()` falls back to `templates/default` when a Nouveau template is missing.
- Risky pages intentionally remain absent from Nouveau so the default template can serve them.
- Core command surfaces wrap the default template fragments where compatibility matters most, especially command buttons, chat, rest, death, and special panels.
- The main game interface keeps the legacy `gamecmd` form, `mode`, `command`, `subcmd`, `subcmd2`, `postCmd(...)`, item slot ids, and button names.

## Window System

The shell provides:

- `.nouveau-ui` body styling.
- `.nouveau-desktop-bg` background layer.
- `.nouveau-desktop` main workspace.
- `.nv-window`, `.nv-window__titlebar`, `.nv-window__body`, `.nv-window__actions`.
- Bottom `.nv-taskbar` with restore buttons.
- Dragging, z-index activation, minimize/restore, and localStorage position persistence.
- Mobile fallback to stacked cards with dragging disabled.

## Rebuilt Pages

- `header.htm`
- `footer.htm`
- `css.htm`
- `index.htm`
- `game.htm`
- `profile.htm`
- `command.htm`
- `battle.htm`
- `chat.htm`
- `rest.htm`
- `death.htm`
- `usergdicon.htm`

## Fallback Pages

Pages not listed above fall back to `templates/default`. This is deliberate for high-risk workflows such as item submenus, shops, team management, skill detail templates, radar/control pages, admin pages, endings, and profile achievement pages.

`slidingpanel.htm` is also left to the default template because it contains club-specific auxiliary UI and fireseed controls.

## Assets

Nouveau uses only local CSS, JavaScript, and small SVG placeholders under:

- `templates/nouveau/assets/css/`
- `templates/nouveau/assets/js/`
- `img/nouveau/placeholders/`

No external CDN or build tool is required.

## Testing

Recommended checks:

```bash
php -l templates/nouveau/templates.lang.php
```

Then run the PHP built-in server and manually verify:

```bash
php -S localhost:8080 -t .
```

Open `localhost:8080`, select Nouveau in user settings, enter the game, and test movement, search, item actions, combat, chat, rest, death handling, skills, shop/depot/team links, and fallback pages.

## Known Limits

- Map nodes are a compatibility topology view, not a new movement system.
- Paperdoll is a placeholder structure and does not yet reflect exact equipment art.
- Resize handles are not implemented yet, but window markup is prepared for them.
- Some complex pages intentionally use default fallback for safety.
