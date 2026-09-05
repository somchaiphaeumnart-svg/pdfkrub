#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
High-quality, lossless PDF page rotation script.
Rotates PDF pages without quality loss or recompression.

Engine hierarchy:
1. PyMuPDF (fitz) - instant & lossless
2. pypdf - pure python lossless rotation
3. qpdf CLI - system level PDF manipulator
"""

import sys
import os
import subprocess

def rotate_with_pymupdf(input_path, output_path, degrees):
    try:
        import fitz
        doc = fitz.open(input_path)
        for page in doc:
            new_rot = (page.rotation + degrees) % 360
            page.set_rotation(new_rot)
        doc.save(output_path)
        if os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"PyMuPDF rotate notice: {e}\n")
    return False

def rotate_with_pypdf(input_path, output_path, degrees):
    try:
        import pypdf
        reader = pypdf.PdfReader(input_path)
        writer = pypdf.PdfWriter()
        for page in reader.pages:
            page.rotate(degrees)
            writer.add_page(page)
        with open(output_path, "wb") as f:
            writer.write(f)
        if os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"pypdf rotate notice: {e}\n")
    return False

def rotate_with_qpdf(input_path, output_path, degrees):
    try:
        # qpdf --rotate=+90 input.pdf output.pdf
        sign = "+" if degrees >= 0 else "-"
        cmd = ["qpdf", f"--rotate={sign}{abs(degrees)}", input_path, output_path]
        res = subprocess.run(cmd, capture_output=True, timeout=60)
        if res.returncode == 0 and os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"qpdf rotate notice: {e}\n")
    return False

def main():
    if len(sys.argv) < 3:
        print("Usage: python3 rotate_pdf.py <input.pdf> <output.pdf> [degrees=90]")
        sys.exit(1)

    input_pdf = sys.argv[1]
    output_pdf = sys.argv[2]
    degrees = int(sys.argv[3]) if len(sys.argv) > 3 else 90

    # Normalize degrees to 0, 90, 180, 270
    degrees = degrees % 360

    if not os.path.exists(input_pdf):
        print(f"Error: input file '{input_pdf}' not found")
        sys.exit(1)

    os.makedirs(os.path.dirname(os.path.abspath(output_pdf)), exist_ok=True)

    ok = rotate_with_pymupdf(input_pdf, output_pdf, degrees)
    if not ok:
        ok = rotate_with_pypdf(input_pdf, output_pdf, degrees)
    if not ok:
        ok = rotate_with_qpdf(input_pdf, output_pdf, degrees)

    if ok and os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0:
        print(f"Success: rotated PDF by {degrees} degrees")
        sys.exit(0)
    else:
        print("Error: failed to rotate PDF")
        sys.exit(1)

if __name__ == "__main__":
    main()
