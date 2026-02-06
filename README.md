# PIA WordPress Multisite

This repository is a **manual-push-only** WordPress multisite codebase used primarily as a **backup and reference source** for the team. It holds custom plugins, custom code blocks/snippets, and related operational assets that the team maintains and periodically pushes to GitHub for safekeeping and collaboration.

> **Intent:** This repo is *not* a deployment pipeline. It is a curated, human-managed snapshot of the multisite code and supporting assets that the team needs to reference, extend, and continue developing.

## What this repo is used for

- **Manual backup of WordPress multisite code** and related assets.
- **Reference source** for custom plugins and custom code blocks.
- **Team collaboration** on site-specific enhancements that will continue to evolve.

## Project structure (current)

The top-level layout reflects the current contents of the repository:

- `pia-live-wp/`
  - WordPress multisite core, themes, plugins, and site content.
- `pia-plugins-codeblocks/`
  - Custom plugins and reusable code blocks/snippets used across the multisite.
- `avada-diff-logger/`
  - Custom plugin for tracking Avada diffs.
- `cloudflare/`
  - Cloudflare worker and edge-related configuration/code.
- `avada-diff-logger-workingish.php`
  - Standalone plugin prototype/working reference.
- `PROJECT_STRUCTURE.md`
  - Notes on a potential future re-organization of the repo.

## Best practices for working in this repo

- **Treat this as a manual backup repo.**
  - Do not expect CI/CD or automated deploys from this repository.
  - Push only vetted changes that the team wants to preserve or reference.

- **Keep secrets out of version control.**
  - Use `.gitignore` for environment files, uploads, caches, and secrets.
  - Never commit database dumps with sensitive data.

- **Prefer small, well-labeled commits.**
  - Clearly describe what changed and why (e.g., plugin updates, new code blocks).
  - Group changes by feature or fix rather than by large bulk updates.

- **Document custom additions.**
  - When adding new custom plugins or snippets, include a short README or inline notes
    describing purpose, usage, and any dependencies.

- **Preserve references and prototypes.**
  - If keeping experimental files (e.g., prototypes), label them explicitly so they
    are not mistaken for production-ready code.

- **Align with the team’s workflow.**
  - This repo exists to support continued development and knowledge sharing. If a
    change is important for future maintenance, it belongs here.

## Notes

If you want to update the structure to match the proposed layout in `PROJECT_STRUCTURE.md`,
create a new folder plan and migrate incrementally to avoid breaking local workflows.
