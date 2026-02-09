#!/usr/bin/env python3
"""Combine FEC and Texas SOS data into plugin-ready JSON.

This script preserves all available source fields under `source` so no data is lost.
"""

import argparse
import csv
import json
from typing import Dict, List

import requests

FEC_ENDPOINT = "https://api.open.fec.gov/v1/candidates/search/"


def fetch_fec(api_key: str, cycle: int, offices: List[str]) -> List[Dict]:
    results: List[Dict] = []
    for office in offices:
        page = 1
        while True:
            params = {
                "api_key": api_key,
                "state": "TX",
                "office": office,
                "cycle": cycle,
                "per_page": 100,
                "page": page,
            }
            resp = requests.get(FEC_ENDPOINT, params=params, timeout=30)
            resp.raise_for_status()
            payload = resp.json()
            candidates = payload.get("results", [])
            for candidate in candidates:
                results.append(normalize_fec(candidate))
            pagination = payload.get("pagination", {})
            if page >= pagination.get("pages", 0):
                break
            page += 1
    return results


def normalize_fec(candidate: Dict) -> Dict:
    return {
        "external_id": candidate.get("candidate_id", ""),
        "name": candidate.get("name", ""),
        "state": candidate.get("state", "TX"),
        "district": candidate.get("district", ""),
        "office": candidate.get("office_full") or candidate.get("office", ""),
        "party": candidate.get("party_full") or candidate.get("party", ""),
        "incumbent_challenge": candidate.get("incumbent_challenge", ""),
        "incumbent_challenge_full": candidate.get("incumbent_challenge_full", ""),
        "election_years": candidate.get("election_years", []),
        "website": candidate.get("website", ""),
        "featured": False,
        "approved": False,
        "source": {
            "source_type": "fec",
            "raw": candidate,
        },
    }


def load_sos_csv(path: str, mapping: Dict[str, str]) -> List[Dict]:
    data: List[Dict] = []
    with open(path, newline="", encoding="utf-8") as handle:
        reader = csv.DictReader(handle)
        for row in reader:
            if not row:
                continue
            normalized = normalize_sos_row(row, mapping)
            if normalized["name"]:
                data.append(normalized)
    return data


def normalize_sos_row(row: Dict[str, str], mapping: Dict[str, str]) -> Dict:
    def read(field: str) -> str:
        return row.get(mapping.get(field, ""), "").strip()

    name = read("name") or " ".join(filter(None, [read("first_name"), read("last_name")])).strip()
    return {
        "external_id": read("external_id"),
        "name": name,
        "state": read("state") or "Texas",
        "county": read("county"),
        "district": read("district"),
        "office": read("office") or read("race"),
        "party": read("party"),
        "incumbent": read("incumbent"),
        "website": read("website"),
        "summary": read("summary"),
        "bio": read("bio"),
        "featured": False,
        "approved": False,
        "source": {
            "source_type": "tx_sos",
            "raw": row,
        },
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--fec-api-key", help="FEC API key")
    parser.add_argument("--fec-cycle", type=int, default=2024, help="FEC cycle year")
    parser.add_argument("--fec-offices", default="H,S,P", help="Comma-separated offices")
    parser.add_argument("--sos-csv", help="Path to Texas SOS CSV")
    parser.add_argument("--output", default="tx-candidates.json", help="Output JSON file")
    parser.add_argument("--name", default="candidate_name", help="CSV column for candidate name")
    parser.add_argument("--first-name", default="first_name", help="CSV column for first name")
    parser.add_argument("--last-name", default="last_name", help="CSV column for last name")
    parser.add_argument("--external-id", default="candidate_id", help="CSV column for external ID")
    parser.add_argument("--state", default="state", help="CSV column for state")
    parser.add_argument("--county", default="county", help="CSV column for county")
    parser.add_argument("--district", default="district", help="CSV column for district")
    parser.add_argument("--office", default="office", help="CSV column for office")
    parser.add_argument("--race", default="race", help="CSV column for race")
    parser.add_argument("--party", default="party", help="CSV column for party")
    parser.add_argument("--incumbent", default="incumbent", help="CSV column for incumbent flag")
    parser.add_argument("--website", default="website", help="CSV column for website")
    parser.add_argument("--summary", default="summary", help="CSV column for summary")
    parser.add_argument("--bio", default="bio", help="CSV column for bio")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    combined: List[Dict] = []

    if args.fec_api_key:
        offices = [office.strip().upper() for office in args.fec_offices.split(",") if office.strip()]
        combined.extend(fetch_fec(args.fec_api_key, args.fec_cycle, offices))

    if args.sos_csv:
        mapping = {
            "name": args.name,
            "first_name": args.first_name,
            "last_name": args.last_name,
            "external_id": args.external_id,
            "state": args.state,
            "county": args.county,
            "district": args.district,
            "office": args.office,
            "race": args.race,
            "party": args.party,
            "incumbent": args.incumbent,
            "website": args.website,
            "summary": args.summary,
            "bio": args.bio,
        }
        combined.extend(load_sos_csv(args.sos_csv, mapping))

    with open(args.output, "w", encoding="utf-8") as handle:
        json.dump(combined, handle, indent=2)

    print(f"Wrote {len(combined)} candidates to {args.output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
