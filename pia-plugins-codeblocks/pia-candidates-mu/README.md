# PIA Candidates (MU)

Per-site candidate profiles for the PIA WordPress multisite. This MU plugin registers a **Candidate** custom post type, a **Candidate Category** taxonomy, and provides shortcodes that can be dropped into Avada Builder pages.

## Key features

- **Per-site candidates** (each multisite site manages its own candidates).
- Candidate details stored as post meta (portrait image, video URL, CTA buttons).
- Shortcodes to render directory listings and profile layouts.
- Template override for single candidate pages if the theme does not provide one.

## Shortcodes (Avada Builder)

Use these in Avada Builder content blocks:

### Directory

```
[pia_candidate_directory per_page="12" category="state-house,school-board"]
```

**Attributes**
- `per_page` (number) — candidates per page.
- `category` (comma-separated slugs) — optional taxonomy filter.

### Profile

```
[pia_candidate_profile]
```

- Renders the current candidate profile on single candidate pages.
- Can also be used with `id` to embed a specific candidate:

```
[pia_candidate_profile id="123"]
```

## Notes

- If the active theme provides `single-pia_candidate.php`, it will take precedence.
- The plugin template is used otherwise and simply renders the profile shortcode.
