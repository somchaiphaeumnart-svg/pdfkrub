#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Organize & Reorder PDF Pages (จัดเรียงหน้า PDF)
Uses PyMuPDF (fitz) to reorder, duplicate, rotate, or delete pages losslessly.
Accepts a JSON array of page objects: [{"page": 1, "rotation": 0}, {"page": 3, "rotation": 90}, ...]
"""

import sys
import os
import json
import argparse
import fitz  # PyMuPDF


def organize_pdf(input_path, output_path, pages_data_raw):
    if not os.path.isfile(input_path):
        raise FileNotFoundError(f"Input file not found: {input_path}")

    # Parse pages data JSON
    if os.path.isfile(pages_data_raw):
        with open(pages_data_raw, 'r', encoding='utf-8') as f:
            pages_data = json.load(f)
    else:
        pages_data = json.loads(pages_data_raw)

    if not isinstance(pages_data, list) or len(pages_data) == 0:
        raise ValueError("Invalid pages data: must be a non-empty list of page specifications.")

    src_doc = fitz.open(input_path)
    total_src = len(src_doc)
    if total_src == 0:
        raise ValueError("The source PDF has no pages.")

    out_doc = fitz.open()

    for item in pages_data:
        try:
            p_num = int(item.get("page", 1))
            rot = int(item.get("rotation", 0)) % 360
        except (ValueError, TypeError):
            continue

        if 1 <= p_num <= total_src:
            p_idx = p_num - 1
            out_doc.insert_pdf(src_doc, from_page=p_idx, to_page=p_idx)
            if rot != 0:
                new_page = out_doc[-1]
                new_page.set_rotation((new_page.rotation + rot) % 360)

    if len(out_doc) == 0:
        raise ValueError("No valid pages were included in the output document.")

    out_doc.save(output_path, garbage=3, deflate=True)
    out_doc.close()
    src_doc.close()


def main():
    parser = argparse.ArgumentParser(description="Organize and Reorder PDF Pages Losslessly")
    parser.add_argument("input_pdf", help="Path to source PDF")
    parser.add_argument("output_pdf", help="Path to destination PDF")
    parser.add_argument("--pages-data", required=True, help="JSON string or file path containing page reorder list")

    args = parser.parse_args()

    try:
        organize_pdf(
            input_path=args.input_pdf,
            output_path=args.output_pdf,
            pages_data_raw=args.pages_data,
        )
        print("SUCCESS")
    except Exception as e:
        print(f"ERROR: {str(e)}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
