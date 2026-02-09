# PIA Candidates (MU)

Per-site candidate profiles for the PIA WordPress multisite. This MU plugin registers a **Candidate** custom post type, a **Candidate Category** taxonomy, and provides shortcodes that can be dropped into Avada Builder pages.

## Key features

- **Per-site candidates** (each multisite site manages its own candidates).
- **Manual + automated data**: keep manual entries and import from JSON (URL or inline).
- Candidate details stored as post meta (portrait image/URL, video URL, CTA buttons, location fields).
- **PIA Approved** badge support for approved candidates.
- Shortcodes to render directory listings and profile layouts.
- Template override for single candidate pages if the theme does not provide one.

## Install (MU)

- Place this folder at: `wp-content/mu-plugins/pia-candidates-mu/`
- Ensure the loader file exists at: `wp-content/mu-plugins/pia-candidates-mu-loader.php`

## Admin settings

Go to **Settings → PIA Candidates**:

- **Data Source Type**: choose Custom JSON, FEC API, or Texas SOS import.
- **Data Source URL**: JSON feed URL for national/state/county candidate data.
- **Inline JSON**: paste JSON data directly in the admin.
- **FEC API Key / Cycle / Offices**: pulls federal candidates (US House, US Senate, President) for Texas.
- **Texas SOS URL**: provide a JSON or CSV export link from the Texas Secretary of State.
- **Default State/County/District**: per-site defaults used by the directory shortcode.
- **PIA Approved Badge Image**: upload or paste a badge URL to overlay on approved candidates.

Use **Run Import** to populate candidates. Existing manual candidates are preserved; imports update records that match the **External ID**.

## Shortcodes (Avada Builder)

Use these in Avada Builder content blocks.

### Directory

```
[pia_candidate_directory per_page="12" state="Texas" county="Potter" district="District 5" featured="1" approved="1"]
```

**Attributes**
- `per_page` (number) — candidates per page.
- `state`, `county`, `district` — optional filters (default to the site settings).
- `featured` — `1` to show featured only.
- `approved` — `1` to show PIA Approved only.
- `category` — optional candidate category filter (comma-separated slugs).

### Profile

```
[pia_candidate_profile]
```

- Renders the current candidate profile on single candidate pages.
- Can also be used with `id` to embed a specific candidate:

```
[pia_candidate_profile id="123"]
```

## JSON schema (example)

```
[
  {
    "external_id": "tx-2024-001",
    "name": "Apollo Hernandez",
    "state": "Texas",
    "county": "Potter",
    "district": "SD-5",
    "office": "Texas State Senate District 5",
    "summary": "Conservative leader focused on ...",
    "bio": "Longer biography text ...",
    "website": "https://example.com",
    "video_url": "https://www.youtube.com/watch?v=...",
    "portrait_url": "https://example.com/images/apollo.jpg",
    "featured": true,
    "approved": true,
    "buttons": [
      { "label": "Candidate Profile", "url": "https://patriotsinactiontx.com/apollo" },
      { "label": "Website", "url": "https://example.com" }
    ],
    "category": ["state-senate"]
  }
]
```

## Notes

- Imports are additive and will **not delete** manual candidates. If an API/URL fails, the import exits with a notice and existing candidates remain unchanged.
- If the active theme provides `single-pia_candidate.php`, it will take precedence.
- The plugin template is used otherwise and simply renders the profile shortcode.

## Reference links for data sources

- Texas Secretary of State elections resources: https://www.sos.texas.gov/elections/
- FEC API documentation: https://api.open.fec.gov/developers/

## Generating JSON locally

See `scripts/candidates/README.md` for Python scripts that fetch and normalize FEC and Texas SOS data into the plugin’s JSON format.
