#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
High-security, lossless PDF protection script.
Encrypts PDF documents with 256-bit AES encryption without recompressing or degrading document quality.

Engine hierarchy:
1. PyMuPDF (fitz) - AES-256 lossless encryption
2. pypdf - pure Python AES-256 fallback
3. qpdf CLI - system-level fallback
"""

import sys
import os
import subprocess

def protect_with_pymupdf(input_path, output_path, password):
    try:
        import fitz
        doc = fitz.open(input_path)
        perm = int(
            fitz.PDF_PERM_ACCESSIBILITY |
            fitz.PDF_PERM_PRINT |
            fitz.PDF_PERM_COPY
        )
        doc.save(
            output_path,
            encryption=fitz.PDF_ENCRYPT_AES_256,
            user_pw=password,
            owner_pw=password,
            permissions=perm,
            garbage=3,
            deflate=True
        )
        doc.close()
        if os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"PyMuPDF protect notice: {e}\n")
    return False

def protect_with_pypdf(input_path, output_path, password):
    try:
        import pypdf
        reader = pypdf.PdfReader(input_path)
        writer = pypdf.PdfWriter()
        writer.append(reader)
        writer.encrypt(user_password=password, owner_password=password, algorithm="AES-256")
        with open(output_path, "wb") as f:
            writer.write(f)
        if os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"pypdf protect notice: {e}\n")
    return False

def protect_with_qpdf(input_path, output_path, password):
    try:
        cmd = ["qpdf", "--encrypt", password, password, "256", "--", input_path, output_path]
        res = subprocess.run(cmd, capture_output=True, timeout=60)
        if res.returncode == 0 and os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"qpdf protect notice: {e}\n")
    return False

def main():
    if len(sys.argv) < 4:
        print("Usage: python3 protect_pdf.py <input.pdf> <output.pdf> <password>")
        sys.exit(1)

    input_pdf = sys.argv[1]
    output_pdf = sys.argv[2]
    password = sys.argv[3]

    if not password:
        sys.stderr.write("Error: Password cannot be empty\n")
        sys.exit(1)

    if not os.path.exists(input_pdf):
        sys.stderr.write(f"Error: input file '{input_pdf}' not found\n")
        sys.exit(1)

    os.makedirs(os.path.dirname(os.path.abspath(output_pdf)), exist_ok=True)

    ok = protect_with_pymupdf(input_pdf, output_pdf, password)
    if not ok:
        ok = protect_with_pypdf(input_pdf, output_pdf, password)
    if not ok:
        ok = protect_with_qpdf(input_pdf, output_pdf, password)

    if ok and os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0:
        print("Success: PDF protected successfully with AES-256 encryption")
        sys.exit(0)
    else:
        sys.stderr.write("Error: Failed to protect PDF\n")
        sys.exit(1)

if __name__ == "__main__":
    main()
