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

## Deploy to Kinsta Staging (SCP)

This is an MU plugin, so once the files are on the server it will auto-load (no “Activate” button).

### Critical safety note (prevents site-wide crashes)

Because MU plugins auto-load, a PHP syntax error will take down the entire network. The safest workflow is:

- Upload to a **temporary path/name**
- **Validate** (or at least ensure the file sizes/timestamps look right)
- Then **atomically rename/move** into place
- Keep a quick rollback by renaming the loader file

### 1) Copy the MU plugin files via `scp`

From your local repo root (the folder that contains `pia-staging-wp/`), run:

```bash
# Copy the loader file (must live directly in mu-plugins/)
scp -P 21268 \
  "pia-staging-wp/wp-content/mu-plugins/pia-candidates-mu-loader.php" \
  texasmssite@34.174.186.154:"/path/to/wordpress/public/wp-content/mu-plugins/"

# Copy the MU plugin folder (contains pia-candidates-mu.php, templates/, data/, README.md)
scp -P 21268 -r \
  "pia-staging-wp/wp-content/mu-plugins/pia-candidates-mu" \
  texasmssite@34.174.186.154:"/path/to/wordpress/public/wp-content/mu-plugins/"
```

### Safer “atomic” deploy (recommended)

```bash
# Upload to temporary names first
scp -P 21268 \
  "pia-staging-wp/wp-content/mu-plugins/pia-candidates-mu-loader.php" \
  texasmssite@34.174.186.154:"/path/to/wordpress/public/wp-content/mu-plugins/pia-candidates-mu-loader.php.new"

scp -P 21268 -r \
  "pia-staging-wp/wp-content/mu-plugins/pia-candidates-mu" \
  texasmssite@34.174.186.154:"/path/to/wordpress/public/wp-content/mu-plugins/pia-candidates-mu.new"

# SSH in and move into place (atomic-ish rename)
ssh texasmssite@34.174.186.154 -p 21268
cd "/path/to/wordpress/public/wp-content/mu-plugins/"
mv "pia-candidates-mu" "pia-candidates-mu.bak.$(date +%Y%m%d%H%M%S)" 2>/dev/null || true
mv "pia-candidates-mu.new" "pia-candidates-mu"
mv "pia-candidates-mu-loader.php" "pia-candidates-mu-loader.php.bak.$(date +%Y%m%d%H%M%S)" 2>/dev/null || true
mv "pia-candidates-mu-loader.php.new" "pia-candidates-mu-loader.php"
```

### Emergency rollback (if the site errors after deploy)

MU plugins can’t be disabled in wp-admin. Roll back by renaming the loader (this stops loading the MU plugin):

```bash
ssh texasmssite@34.174.186.154 -p 21268
cd "/path/to/wordpress/public/wp-content/mu-plugins/"
mv "pia-candidates-mu-loader.php" "pia-candidates-mu-loader.php.disabled"
```

Notes:
- Replace `"/path/to/wordpress/public/"` with your actual Kinsta WordPress docroot for the staging environment (it’s commonly a `.../public/` directory).
- If your `data/*.json` is large and you only want code changes, you can omit the `data/` folder copy — but then set the import source to URL or Inline JSON.

### 2) (Optional) Verify on the server

SSH in and confirm the files exist:

```bash
ssh texasmssite@34.174.186.154 -p 21268
# then check:
# wp-content/mu-plugins/pia-candidates-mu-loader.php
# wp-content/mu-plugins/pia-candidates-mu/pia-candidates-mu.php
```

## Admin settings

Go to **Settings → PIA Candidates**:

- **Data Source Type**: choose Custom JSON, FEC API, or Texas SOS import.
- **Data Source URL**: JSON feed URL for national/state/county candidate data.
- **Local JSON File (MU plugin)**: store a JSON file inside the MU plugin folder and reference it by path (relative to `pia-candidates-mu/`). Default/recommended: `data/texas_candidates_2026-0.json`.
- **Inline JSON**: paste JSON data directly in the admin.
- **FEC API Key / Cycle / Offices**: pulls federal candidates (US House, US Senate, President) for Texas.
- **Texas SOS URL**: provide a JSON or CSV export link from the Texas Secretary of State.
- **Default State/County/District**: per-site defaults used by the directory shortcode.
- **PIA Approved Badge Image**: upload or paste a badge URL to overlay on approved candidates.
- **Fetch Ballotpedia Photos (on import)**: pulls the candidate photo from their Ballotpedia page (if available) and stores it as `portrait_url`. Ballotpedia placeholder images (like “Submit photo”) are ignored.

Use **Run Import** to populate candidates. Existing manual candidates are preserved; imports update records that match the **External ID**.

## Multisite setup checklist (after files are deployed)

### Network Admin (once)

- **Permalinks**: Visit **Network Admin → Sites → (Edit a site) → Dashboard → Settings → Permalinks** and click **Save Changes** once (or do this per-site) to ensure the `candidates` CPT rewrite rules are registered.
  - If permalinks are disabled / using plain URLs, the directory shortcode will still work but pretty permalinks for candidate profiles may not.
- **Theme templates (optional)**: If your theme provides `single-pia_candidate.php`, it will override the plugin’s built-in template.

### Each county site (repeat per site)

This plugin stores candidates and settings **per site**, so you must configure/import per county site.

- **Configure defaults**: Go to **Settings → PIA Candidates**:
  - Set **Default State / County / District** as appropriate for the county site.
  - Choose your **Data Source Type** (Custom JSON / FEC / Texas SOS).
  - If using the bundled JSON file, confirm **Local JSON File (MU plugin)** points to something like `data/texas_candidates_2026-0.json`.
- **Run Import**: Click **Run Import** (this updates existing candidates by `external_id` and adds new ones).
- **Create/Update the directory page**: Add the directory shortcode to a page (Avada Builder works fine). Recommended for realtime filtering:

```
[pia_candidate_directory per_page="all" scope="county"]
```

- **Test candidate profiles**:
  - Visit a candidate single page (or click “Candidate Profile” from the directory).
  - Confirm portrait image displays:
    - If you set a **Portrait Image** in the editor (media picker), that is used.
    - If you paste a **Portrait URL**, it will be used (and will override/clear any previous portrait ID).

## Shortcodes (Avada Builder)

Use these in Avada Builder content blocks.

### Directory

```
[pia_candidate_directory per_page="12" state="Texas" county="Potter" district="District 5" featured="1" approved="1"]
```

**Attributes**
- `per_page` (number | `all`) — candidates per page. Use `all` (or `-1`) to load every candidate on the site (recommended when using the realtime search/filters).
- `scope` — controls how location filtering works:
  - `auto` (default): use the site defaults (`Default State/County/District`) if set.
  - `county`: force county-only (uses `Default County` if you don’t pass `county`).
  - `state`: statewide (ignores `Default County` unless you explicitly pass `county`).
  - `all`: ignore site defaults; show all candidates on the site (unless you explicitly pass filters).
- `state`, `county`, `district` — optional filters (default to the site settings unless `scope="all"` or `scope="state"`).
- `featured` — `1` to show featured only.
- `approved` — `1` to show PIA Approved only.
- `category` — optional candidate category filter (comma-separated slugs).
- `search` — `1` (default) shows the realtime search box; `0` hides it.
- `filters` — `1` (default) shows the realtime filter dropdowns; `0` hides them.

**Troubleshooting**
- If directory cards show the *page title* (e.g. “Candidates Directory”) instead of candidate names, make sure you’ve deployed plugin version `0.3.0` or newer.

**Examples**

```
[pia_candidate_directory per_page="30" scope="county"]
```

Load *all* candidates for realtime filtering:

```
[pia_candidate_directory per_page="all" scope="county"]
```

```
[pia_candidate_directory per_page="30" scope="state"]
```

```
[pia_candidate_directory per_page="30" scope="all"]
```

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

The importer accepts either a **flat list** or a **grouped object** (it will auto-flatten groups like `federal`, `state`, etc.).

### Flat list

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

### Grouped object (auto-flattened)

```
{
  "federal": [
    { "external_id": "tx-2026-senate-001", "name": "Example Candidate", "office": "U.S. Senate", "state": "Texas" }
  ],
  "state": [
    { "external_id": "tx-2026-state-001", "name": "Example Candidate 2", "office": "Texas State Senate", "state": "Texas" }
  ]
}
```

## Notes

- Imports are additive and will **not delete** manual candidates. If an API/URL fails, the import exits with a notice and existing candidates remain unchanged.
- If the active theme provides `single-pia_candidate.php`, it will take precedence.
- The plugin template is used otherwise and simply renders the profile shortcode.
- Ballotpedia links: for county-level Texas races, the importer will automatically expand simple Ballotpedia URLs like `https://ballotpedia.org/Candace_Hanson` into the more specific 2026 candidate page format like `https://ballotpedia.org/Candace_Hanson_(Gray_County_Clerk,_Texas,_candidate_2026)` on the next import (when the `external_id` follows `tx-2026-<county>-...`).

## Reference links for data sources

- Texas Secretary of State elections resources: https://www.sos.texas.gov/elections/
- FEC API documentation: https://api.open.fec.gov/developers/

## Generating JSON locally

See `candidates-data/README.md` for Python scripts that fetch and normalize FEC and Texas SOS data into the plugin’s JSON format.
