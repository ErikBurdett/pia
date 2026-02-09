#!/usr/bin/env python3
"""Normalize a Texas SOS CSV into the plugin JSON schema."""

import argparse
import csv
import json
from typing import Dict, List


def normalize_row(row: Dict[str, str], mapping: Dict[str, str]) -> Dict:
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
        "website": read("website"),
        "summary": read("summary"),
        "bio": read("bio"),
        "featured": False,
        "approved": False,
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--input", required=True, help="Input CSV file path")
    parser.add_argument("--output", default="sos-tx.json", help="Output JSON file path")
    parser.add_argument("--name", default="candidate_name", help="CSV column for candidate name")
    parser.add_argument("--first-name", default="first_name", help="CSV column for first name")
    parser.add_argument("--last-name", default="last_name", help="CSV column for last name")
    parser.add_argument("--external-id", default="candidate_id", help="CSV column for external ID")
    parser.add_argument("--state", default="state", help="CSV column for state")
    parser.add_argument("--county", default="county", help="CSV column for county")
    parser.add_argument("--district", default="district", help="CSV column for district")
    parser.add_argument("--office", default="office", help="CSV column for office")
    parser.add_argument("--race", default="race", help="CSV column for race")
    parser.add_argument("--website", default="website", help="CSV column for website")
    parser.add_argument("--summary", default="summary", help="CSV column for summary")
    parser.add_argument("--bio", default="bio", help="CSV column for bio")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
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
        "website": args.website,
        "summary": args.summary,
        "bio": args.bio,
    }

    data: List[Dict] = []
    with open(args.input, newline="", encoding="utf-8") as handle:
        reader = csv.DictReader(handle)
        for row in reader:
            if not row:
                continue
            normalized = normalize_row(row, mapping)
            if normalized["name"]:
                data.append(normalized)

    with open(args.output, "w", encoding="utf-8") as handle:
        json.dump(data, handle, indent=2)

    print(f"Wrote {len(data)} candidates to {args.output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
