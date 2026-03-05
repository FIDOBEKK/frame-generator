#!/usr/bin/env python3
"""
Background removal helper for Frame Generator.
Usage: python remove_bg.py <input_image> <output_png>
"""

import sys
from pathlib import Path

from rembg import remove


def main() -> int:
    if len(sys.argv) != 3:
        print("Usage: remove_bg.py <input_image> <output_png>", file=sys.stderr)
        return 2

    input_path = Path(sys.argv[1])
    output_path = Path(sys.argv[2])

    if not input_path.exists():
        print(f"Input file not found: {input_path}", file=sys.stderr)
        return 2

    output_path.parent.mkdir(parents=True, exist_ok=True)

    data = input_path.read_bytes()
    out = remove(data)
    output_path.write_bytes(out)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
