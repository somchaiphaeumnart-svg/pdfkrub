#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Add Page Numbers to PDF.
Supports:
1. 6 position presets: bottom-center, bottom-left, bottom-right, top-center, top-left, top-right
2. Multiple formats: 'n', 'n-of-total', 'page-n', 'page-n-of-total'
3. Custom start number, skip cover page (first page), font size, margin, and color
"""

import sys
import os
import argparse
import subprocess

def hex_to_rgb(hex_str):
    hex_str = hex_str.lstrip('#')
    if len(hex_str) == 3:
        hex_str = ''.join([c*2 for c in hex_str])
    if len(hex_str) == 6:
        r = int(hex_str[0:2], 16) / 255.0
        g = int(hex_str[2:4], 16) / 255.0
        b = int(hex_str[4:6], 16) / 255.0
        return (r, g, b)
    return (0.2, 0.2, 0.2)

def find_thai_font():
    """Find available font file that supports Thai characters."""
    font_candidates = [
        "/usr/share/fonts/truetype/tlwg/Sarabun.ttf",
        "/usr/share/fonts/truetype/tlwg/Loma.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
        "/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf",
        "C:/Windows/Fonts/tahoma.ttf",
        "C:/Windows/Fonts/cordia.ttf",
        "C:/Windows/Fonts/angsa.ttf",
        "C:/Windows/Fonts/sarabun.ttf",
    ]
    for p in font_candidates:
        if os.path.exists(p):
            return p
    return None

def add_numbers_with_pymupdf(input_pdf, output_pdf, position="bottom-center", fmt="n",
                             start_num=1, skip_first=False, font_size=11, margin_x=36, margin_y=36, color_hex="#333333"):
    try:
        import fitz
        doc = fitz.open(input_pdf)
        total_pages = len(doc)
        if total_pages == 0:
            doc.close()
            return False

        color_tuple = hex_to_rgb(color_hex)
        thai_font = find_thai_font()

        for i in range(total_pages):
            page_num = i + 1
            if skip_first and page_num == 1:
                continue

            current_num = start_num + (page_num - (2 if skip_first else 1))
            display_total = (total_pages - 1) if skip_first else total_pages

            if fmt == "n":
                text = str(current_num)
            elif fmt == "n-of-total":
                text = f"{current_num} / {display_total}"
            elif fmt == "page-n":
                text = f"หน้า {current_num}" if thai_font else f"Page {current_num}"
            elif fmt == "page-n-of-total":
                text = f"หน้า {current_num} จาก {display_total}" if thai_font else f"Page {current_num} of {display_total}"
            else:
                text = str(current_num)

            page = doc[i]
            rect = page.rect
            width = rect.width
            height = rect.height

            # Determine font settings
            font_kwargs = {}
            if thai_font:
                font_kwargs["fontfile"] = thai_font
                font_kwargs["fontname"] = "thai_font"
            else:
                font_kwargs["fontname"] = "helv"

            try:
                text_len = fitz.get_text_length(text, fontsize=font_size, **font_kwargs)
            except Exception:
                # Fallback to pure numbers if font issue
                text = f"{current_num} / {display_total}" if "of" in fmt or "จาก" in fmt else str(current_num)
                font_kwargs = {"fontname": "helv"}
                text_len = fitz.get_text_length(text, fontname="helv", fontsize=font_size)

            # Calculate X, Y
            if position == "bottom-center":
                x = (width - text_len) / 2
                y = height - margin_y
            elif position == "bottom-left":
                x = margin_x
                y = height - margin_y
            elif position == "bottom-right":
                x = width - margin_x - text_len
                y = height - margin_y
            elif position == "top-center":
                x = (width - text_len) / 2
                y = margin_y + font_size
            elif position == "top-left":
                x = margin_x
                y = margin_y + font_size
            elif position == "top-right":
                x = width - margin_x - text_len
                y = margin_y + font_size
            else:
                x = (width - text_len) / 2
                y = height - margin_y

            try:
                page.insert_text((x, y), text, fontsize=font_size, color=color_tuple, **font_kwargs)
            except Exception:
                # Fallback without fontfile
                page.insert_text((x, y), str(current_num), fontname="helv", fontsize=font_size, color=color_tuple)

        doc.save(output_pdf, garbage=3, deflate=True)
        doc.close()
        return os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0
    except Exception as e:
        sys.stderr.write(f"PyMuPDF add page numbers notice: {e}\n")
        return False

def main():
    parser = argparse.ArgumentParser(description="Add page numbers to PDF")
    parser.add_argument("input_pdf", help="Input PDF file path")
    parser.add_argument("output_pdf", help="Output PDF file path")
    parser.add_argument("--position", default="bottom-center",
                        choices=["bottom-center", "bottom-left", "bottom-right", "top-center", "top-left", "top-right"],
                        help="Number placement position")
    parser.add_argument("--format", default="n",
                        choices=["n", "n-of-total", "page-n", "page-n-of-total"],
                        help="Numbering format")
    parser.add_argument("--start-num", type=int, default=1, help="Starting page number")
    parser.add_argument("--skip-first", default="0", help="Skip numbering on first page (1/0)")
    parser.add_argument("--font-size", type=int, default=11, help="Font size in pt")
    parser.add_argument("--color", default="#333333", help="Font color hex code")
    parser.add_argument("--margin-x", type=int, default=36, help="Horizontal margin in pt")
    parser.add_argument("--margin-y", type=int, default=36, help="Vertical margin in pt")

    args = parser.parse_args()

    input_pdf = os.path.abspath(args.input_pdf)
    output_pdf = os.path.abspath(args.output_pdf)

    if not os.path.exists(input_pdf) or os.path.getsize(input_pdf) == 0:
        sys.stderr.write(f"Error: Input file {input_pdf} does not exist or is empty\n")
        sys.exit(1)

    os.makedirs(os.path.dirname(output_pdf), exist_ok=True)
    skip_first_bool = (str(args.skip_first).strip() in ['1', 'true', 'True'])

    ok = add_numbers_with_pymupdf(
        input_pdf=input_pdf,
        output_pdf=output_pdf,
        position=args.position,
        fmt=args.format,
        start_num=args.start_num,
        skip_first=skip_first_bool,
        font_size=args.font_size,
        margin_x=args.margin_x,
        margin_y=args.margin_y,
        color_hex=args.color
    )

    if ok:
        sys.exit(0)
    else:
        sys.stderr.write("Failed to add page numbers to PDF\n")
        sys.exit(1)

if __name__ == '__main__':
    main()
