# Changelog

## 0.1.1 (2026-10-02)

- The paste box and Rewrite button are hidden once the rewrite appears; a "Paste new text" button brings back an empty box.
- "Easier version" heading renamed to "New version".
- Added a Pause/Resume button for Read aloud.

## 0.1.0 (2026-10-02)

- First release.
- Resource-type module (no grade) that rewrites pasted text at a chosen reading level using the Moodle core AI subsystem.
- Teacher settings: name (default "Simplify Text"), reading level Grade 2-8 with Lexile ceiling (default Grade 3), extra AI instructions (pre-filled from a site default), standard Restrict access.
- Student interface: paste box with live word count, Rewrite, Easier version with Read aloud, Stop and Slow/Normal speed, and Redo for a fresh version.
- Site settings: default reading level, default extra AI instructions, maximum words (3000).
- No student text or AI output is stored; a "Text simplified" log event records only word count and rewrite number.
- Backup/restore, privacy provider, course index page.
