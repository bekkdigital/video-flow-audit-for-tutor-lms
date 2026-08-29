# AGENTS.md

See `CLAUDE.md` for the working rules on this repo — they apply to every
agent (ChatGPT, Claude Code, Codex).

Non-negotiables, in short:

1. **Read-only plugin.** No API calls, no writes, no migration. Ever.
2. The scanner is upstream in `../../packages/video-flow-core`. Edit it
   there, then `composer run sync-core` and commit `lib/`. Never hand-edit
   `lib/`.
3. wordpress.org rules: no phone-home, no bundled premium code, one
   non-nagging upgrade link, text domain === slug, escape all output.
4. Own prefixes only: `vfaudit_`, `VFAUDIT_`, `vfaudit_core_`.
