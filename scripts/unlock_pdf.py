#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
High-quality, lossless PDF password removal script.
Removes password protection from PDF documents without quality loss.
"""

import sys
import os
import subprocess

def unlock_with_pymupdf(input_path, output_path, password):
    try:
        import fitz
        doc = fitz.open(input_path)
        if doc.is_encrypted:
            if not doc.authenticate(password):
                sys.stderr.write("PyMuPDF: Invalid password\n")
                return False
        doc.save(output_path, encryption=fitz.PDF_ENCRYPT_NONE, garbage=3, deflate=True)
        doc.close()
        if os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"PyMuPDF unlock notice: {e}\n")
    return False

def unlock_with_pypdf(input_path, output_path, password):
    try:
        import pypdf
        reader = pypdf.PdfReader(input_path)
        if reader.is_encrypted:
            res = reader.decrypt(password)
            if res == 0:
                sys.stderr.write("pypdf: Invalid password\n")
                return False
        writer = pypdf.PdfWriter()
        for page in reader.pages:
            writer.add_page(page)
        with open(output_path, "wb") as f:
            writer.write(f)
        if os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"pypdf unlock notice: {e}\n")
    return False

def unlock_with_qpdf(input_path, output_path, password):
    try:
        cmd = ["qpdf", f"--password={password}", "--decrypt", input_path, output_path]
        res = subprocess.run(cmd, capture_output=True, timeout=60)
        if res.returncode == 0 and os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"qpdf unlock notice: {e}\n")
    return False

def main():
    if len(sys.argv) < 4:
        print("Usage: python3 unlock_pdf.py <input.pdf> <output.pdf> <password>")
        sys.exit(1)

    input_pdf = sys.argv[1]
    output_pdf = sys.argv[2]
    password = sys.argv[3]

    if not os.path.exists(input_pdf):
        sys.stderr.write(f"Error: input file '{input_pdf}' not found\n")
        sys.exit(1)

    os.makedirs(os.path.dirname(os.path.abspath(output_pdf)), exist_ok=True)

    ok = unlock_with_pymupdf(input_pdf, output_pdf, password)
    if not ok:
        ok = unlock_with_pypdf(input_pdf, output_pdf, password)
    if not ok:
        ok = unlock_with_qpdf(input_pdf, output_pdf, password)

    if ok and os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0:
        print("Success: PDF unlocked successfully")
        sys.exit(0)
    else:
        sys.stderr.write("Error: Failed to unlock PDF (invalid password or corrupted file)\n")
        sys.exit(1)

if __name__ == "__main__":
    main()
