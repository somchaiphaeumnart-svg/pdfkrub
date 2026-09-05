#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
High-Fidelity PDF to Images (JPG/PNG) Converter.
Supports:
1. Precise DPI selection (150 DPI standard, 300 DPI high-quality)
2. Selective page extraction (e.g. 1-3, 5, or all)
3. Fast rendering with PyMuPDF (fitz) and Ghostscript fallback
"""

import sys
import os
import argparse
import subprocess

def parse_pages_arg(pages_str):
    if not pages_str or pages_str.strip().lower() == 'all':
        return None

    pages = set()
    for part in pages_str.split(','):
        part = part.strip()
        if '-' in part:
            bounds = part.split('-')
            if len(bounds) == 2:
                try:
                    s = int(bounds[0])
                    e = int(bounds[1])
                    for p in range(s, e + 1):
                        if p > 0:
                            pages.add(p)
                except ValueError:
                    pass
        elif part.isdigit():
            p = int(part)
            if p > 0:
                pages.add(p)

    return sorted(list(pages)) if pages else None

def convert_with_pymupdf(input_pdf, output_dir, format_ext, dpi=150, pages=None):
    try:
        import fitz
        doc = fitz.open(input_pdf)
        total = len(doc)
        if pages is None or len(pages) == 0:
            target_pages = list(range(1, total + 1))
        else:
            target_pages = [p for p in pages if 1 <= p <= total]

        os.makedirs(output_dir, exist_ok=True)
        count = 0
        for p in target_pages:
            page = doc[p - 1]
            pix = page.get_pixmap(dpi=dpi)
            out_file = os.path.join(output_dir, f"page_{p:04d}.{format_ext}")
            pix.save(out_file)
            count += 1

        doc.close()
        return count > 0
    except Exception as e:
        sys.stderr.write(f"PyMuPDF notice: {e}\n")
        return False

def convert_with_ghostscript(input_pdf, output_dir, format_ext, dpi=150, pages=None):
    try:
        device = 'pngalpha' if format_ext == 'png' else 'jpeg'
        output_pattern = os.path.join(output_dir, f"page_%04d.{format_ext}")
        cmd = [
            'gs', '-dBATCH', '-dNOPAUSE', '-q',
            f"-sDEVICE={device}", f"-r{dpi}",
            f"-sOutputFile={output_pattern}",
            input_pdf
        ]
        res = subprocess.run(cmd, capture_output=True, timeout=180)
        if res.returncode != 0:
            return False

        # If specific pages requested, delete unneeded ones
        if pages is not None and len(pages) > 0:
            all_files = sorted([f for f in os.listdir(output_dir) if f.startswith('page_') and f.endswith(f'.{format_ext}')])
            for f in all_files:
                try:
                    p_num = int(f.split('_')[1].split('.')[0])
                    if p_num not in pages:
                        os.remove(os.path.join(output_dir, f))
                except Exception:
                    pass

        remaining = [f for f in os.listdir(output_dir) if f.startswith('page_') and f.endswith(f'.{format_ext}')]
        return len(remaining) > 0
    except Exception as e:
        sys.stderr.write(f"Ghostscript notice: {e}\n")
        return False

def main():
    parser = argparse.ArgumentParser(description="Convert PDF to JPG/PNG images")
    parser.add_argument("input_pdf", help="Input PDF file path")
    parser.add_argument("output_dir", help="Output directory for images")
    parser.add_argument("--format", default="jpg", choices=["jpg", "jpeg", "png"], help="Image format")
    parser.add_argument("--dpi", type=int, default=150, choices=[150, 300], help="Image resolution DPI")
    parser.add_argument("--pages", default="all", help="Pages to convert e.g. 'all' or '1-3,5'")

    args = parser.parse_args()

    input_pdf = os.path.abspath(args.input_pdf)
    output_dir = os.path.abspath(args.output_dir)
    fmt = "png" if args.format == "png" else "jpg"
    dpi = args.dpi
    pages = parse_pages_arg(args.pages)

    if not os.path.exists(input_pdf) or os.path.getsize(input_pdf) == 0:
        sys.stderr.write(f"Error: Input file {input_pdf} does not exist or is empty\n")
        sys.exit(1)

    os.makedirs(output_dir, exist_ok=True)

    ok = convert_with_pymupdf(input_pdf, output_dir, fmt, dpi, pages)
    if not ok:
        ok = convert_with_ghostscript(input_pdf, output_dir, fmt, dpi, pages)

    if ok:
        sys.exit(0)
    else:
        sys.stderr.write("PDF to image conversion failed\n")
        sys.exit(1)

if __name__ == '__main__':
    main()
