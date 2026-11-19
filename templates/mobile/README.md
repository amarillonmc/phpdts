# Mobile Dark Aero Template (`templates/mobile`)

Dedicated mobile & tablet interface for PHPDTS (`TEMPLATEID = 3`, `TPLDIR = './templates/mobile'`), matching the Dark Aero aesthetic of `nouveau`.

## Automatic Detection & Switching

- Mobile phones and tablets (`Android`, `iPhone`, `iPad`, `HarmonyOS`, `Sec-CH-UA-Mobile: ?1`, and iPadOS desktop-UA touch devices) automatically load `templates/mobile` on first visit (both guests and logged-in players).
- Players can switch freely at any time between **手机版** (`?ui_mode=mobile`) and **原版界面** (`?ui_mode=desktop`) via the top navigation bar, footer, or `user.php` (`MOBILE（移动专版）`), persisted in the `dts_ui_mode` cookie without overwriting desktop preferences.

## Layout & Compatibility

- **Phone (`< 900px`)**: Sticky battlefield HUD (location, danger status, HP/SP/Rage/SS gel bars, 1-tap wound bandaging, quick skill/quest/terminal triggers) + 4-tab bottom thumb dock (`行动`, `状态·装备`, `地图`, `通讯`). Encounters (`battle_rev`, `battle`), corpses, and radar screens automatically surface in the `行动` view.
- **Tablet (`>= 900px`)**: Two-column Dark Aero split workspace with status/gear/map/chat on the left and live action log + command terminal on the right.
- Submenus, shops, synthesis, endings, dialogues, and RuleSet panels fall back cleanly to `templates/default` fragments with touch-optimized controls and modal close headers.
