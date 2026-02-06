# Project Structure

This repo holds a WordPress site plus custom snippets and infra code. The
structure below keeps WordPress runtime files separate from custom code and
ops assets.

## Suggested root layout (recommended)

- `wordpress/`
  - Full WordPress install (currently `pia-staging-wp/`).
- `wp-custom/`
  - Custom plugins and snippets (currently `avada-diff-logger/` and
    `patriotsinaction.com/`).
- `infra/`
  - Infrastructure code like Cloudflare workers (currently `cloudflare/`).
- `ops/`
  - Operational artifacts such as SSH notes (currently `ssh-*.txt`).
- `docs/`
  - Project docs, runbooks, and checklists.

## Current folders and intent

- `pia-staging-wp/`
  - WordPress core, plugins, themes, and site content.
- `patriotsinaction.com/`
  - Site-specific JS/PHP snippets and code blocks.
- `avada-diff-logger/`
  - Custom plugin for tracking Avada diffs.
- `cloudflare/`
  - Cloudflare worker code.
- `ssh-production.txt`, `ssh-staging.txt`
  - Operational SSH notes (should remain untracked).

## Notes

- The `.gitignore` at repo root is set up to keep secrets, uploads, caches,
  and build outputs out of version control.
- If you want me to move files into the suggested layout, I can do that next.
