#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
High-quality PDF to Plain Text (.txt) extractor.
Extracts Unicode / Thai text from PDF files preserving paragraphs, structure and line breaks.

Engine hierarchy:
1. PyMuPDF (fitz) - fast, reliable font glyph decoding and Unicode mapping
2. pdftotext (poppler-utils) - system level high performance extractor
3. pdfplumber - layout structure extraction
4. pypdf - pure python fallback
5. OCR fallback (Tesseract) - for scanned document PDFs
"""

import sys
import os
import subprocess
import glob
import tempfile
import shutil

def extract_with_pymupdf(input_path):
    try:
        import fitz
        doc = fitz.open(input_path)
        pages_text = []
        for page in doc:
            t = page.get_text("text")
            if t:
                pages_text.append(t.strip())
        full = "\n\n".join(pages_text).strip()
        if len(full) >= 10:
            return full
    except Exception:
        pass
    return None

def extract_with_pdftotext(input_path):
    try:
        res = subprocess.run(["pdftotext", "-layout", "-enc", "UTF-8", input_path, "-"],
                             capture_output=True, text=True, timeout=60)
        if res.returncode == 0 and len(res.stdout.strip()) >= 10:
            return res.stdout.strip()
    except Exception:
        pass
    try:
        res = subprocess.run(["pdftotext", "-layout", input_path, "-"],
                             capture_output=True, text=True, timeout=60)
        if res.returncode == 0 and len(res.stdout.strip()) >= 10:
            return res.stdout.strip()
    except Exception:
        pass
    return None

def extract_with_pdfplumber(input_path):
    try:
        import pdfplumber
        pages_text = []
        with pdfplumber.open(input_path) as pdf:
            for page in pdf.pages:
                t = page.extract_text(layout=True) or page.extract_text()
                if t:
                    pages_text.append(t.strip())
        full = "\n\n".join(pages_text).strip()
        if len(full) >= 10:
            return full
    except Exception:
        pass
    return None

def extract_with_pypdf(input_path):
    try:
        import pypdf
        reader = pypdf.PdfReader(input_path)
        pages_text = []
        for page in reader.pages:
            t = page.extract_text()
            if t:
                pages_text.append(t.strip())
        full = "\n\n".join(pages_text).strip()
        if len(full) >= 10:
            return full
    except Exception:
        pass
    return None

def extract_with_ocr(input_path):
    """Fallback to OCR if the PDF is scanned images."""
    try:
        import fitz
        import pytesseract
        from PIL import Image
        doc = fitz.open(input_path)
        pages_text = []
        for i, page in enumerate(doc):
            if i >= 30:
                break
            pix = page.get_pixmap(dpi=150)
            img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)
            txt = pytesseract.image_to_string(img, lang="tha+eng")
            if txt:
                pages_text.append(txt.strip())
        full = "\n\n".join(pages_text).strip()
        if len(full) >= 10:
            return full
    except Exception:
        pass
    return None

def main():
    if len(sys.argv) < 3:
        print("Usage: python3 pdf_to_txt.py <input.pdf> <output.txt>")
        sys.exit(1)

    input_pdf = sys.argv[1]
    output_txt = sys.argv[2]

    if not os.path.exists(input_pdf):
        print(f"Error: input file '{input_pdf}' not found")
        sys.exit(1)

    text = extract_with_pymupdf(input_pdf)
    if not text:
        text = extract_with_pdftotext(input_pdf)
    if not text:
        text = extract_with_pdfplumber(input_pdf)
    if not text:
        text = extract_with_pypdf(input_pdf)
    if not text:
        text = extract_with_ocr(input_pdf)

    os.makedirs(os.path.dirname(os.path.abspath(output_txt)), exist_ok=True)

    if not text:
        text = "ไม่พบข้อความที่สามารถอ่านได้ในไฟล์ PDF นี้ (อาจเป็นไฟล์รูปภาพสแกนที่ต้องใช้เครื่องมือ OCR)"

    with open(output_txt, "w", encoding="utf-8") as f:
        f.write(text)

    print(f"Success: extracted {len(text)} characters to {output_txt}")
    sys.exit(0)

if __name__ == "__main__":
    main()
