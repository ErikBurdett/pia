# Candidate Data Scripts

These scripts generate JSON that matches the **PIA Candidates MU** import schema.

## 1) FEC (federal candidates in Texas)

This pulls **US House, US Senate, and President** candidates for Texas via the FEC API.

```bash
python scripts/candidates/fetch_fec_tx.py \
  --api-key "YOUR_FEC_KEY" \
  --cycle 2024 \
  --offices H,S,P \
  --output fec-tx.json
```

Upload the resulting JSON to a URL and set that URL as the **Data Source URL** with
`Data Source Type = Custom JSON`, or paste the JSON into **Inline JSON**.

FEC API docs: https://api.open.fec.gov/developers/

## 2) Texas SOS CSV normalization

Texas SOS data is often provided as CSV or PDF. If you can export or convert a CSV
(Excel → Save As CSV), normalize it into the plugin’s schema:

```bash
python scripts/candidates/normalize_sos_csv.py \
  --input tx-sos.csv \
  --output sos-tx.json \
  --name "candidate_name" \
  --office "office" \
  --county "county" \
  --district "district"
```

If your CSV uses different column names, pass them via the CLI flags.

Texas SOS elections resources: https://www.sos.texas.gov/elections/

## 3) Combined FEC + SOS

This script merges FEC federal candidates and Texas SOS CSV data into a single JSON
file. It preserves **all source fields** under `source.raw` so nothing is lost.

```bash
python scripts/candidates/combine_fec_sos.py \
  --fec-api-key "YOUR_FEC_KEY" \
  --fec-cycle 2024 \
  --fec-offices H,S,P \
  --sos-csv tx-sos.csv \
  --output tx-candidates.json
```

Use `--sos-csv` only for SOS data, or `--fec-api-key` only for FEC data.
