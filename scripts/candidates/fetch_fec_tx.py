#!/usr/bin/env python3
"""Fetch Texas federal candidates from the FEC API and output plugin-ready JSON."""

import argparse
import json
import sys
from typing import Dict, List

import requests

FEC_ENDPOINT = "https://api.open.fec.gov/v1/candidates/search/"


def fetch_candidates(api_key: str, cycle: int, offices: List[str]) -> List[Dict]:
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
                results.append(
                    {
                        "external_id": candidate.get("candidate_id", ""),
                        "name": candidate.get("name", ""),
                        "state": candidate.get("state", "TX"),
                        "district": candidate.get("district", ""),
                        "office": candidate.get("office_full") or candidate.get("office", ""),
                        "website": candidate.get("website", ""),
                        "featured": False,
                        "approved": False,
                    }
                )

            pagination = payload.get("pagination", {})
            if page >= pagination.get("pages", 0):
                break
            page += 1

    return results


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--api-key", required=True, help="FEC API key")
    parser.add_argument("--cycle", type=int, default=2024, help="Election cycle year")
    parser.add_argument(
        "--offices",
        default="H,S,P",
        help="Comma-separated offices to include (H,S,P)",
    )
    parser.add_argument("--output", default="fec-tx.json", help="Output JSON file")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    offices = [office.strip().upper() for office in args.offices.split(",") if office.strip()]
    if not offices:
        print("No offices provided.", file=sys.stderr)
        return 1

    data = fetch_candidates(args.api_key, args.cycle, offices)
    with open(args.output, "w", encoding="utf-8") as handle:
        json.dump(data, handle, indent=2)
    print(f"Wrote {len(data)} candidates to {args.output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
