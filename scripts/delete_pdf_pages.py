#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
High-quality, lossless PDF page deletion script.
Removes specified pages from a PDF document without quality loss or recompression.

Engine hierarchy:
1. PyMuPDF (fitz) - fastest, 100% lossless & preserves structure
2. pypdf - pure Python fallback
3. qpdf CLI - system-level fallback
"""

import sys
import os
import subprocess

def parse_pages(pages_str, total_pages):
    """
    Parse comma-separated page numbers or ranges (1-indexed).
    E.g. "1,3,5-7" -> {1, 3, 5, 6, 7}
    """
    to_delete = set()
    for part in pages_str.split(","):
        part = part.strip()
        if not part:
            continue
        if "-" in part:
            try:
                start, end = part.split("-", 1)
                start = max(1, int(start.strip()))
                end = min(total_pages, int(end.strip()))
                for p in range(start, end + 1):
                    to_delete.add(p)
            except ValueError:
                pass
        else:
            try:
                p = int(part)
                if 1 <= p <= total_pages:
                    to_delete.add(p)
            except ValueError:
                pass
    return to_delete

def delete_with_pymupdf(input_path, output_path, pages_str):
    try:
        import fitz
        doc = fitz.open(input_path)
        total = len(doc)
        pages_to_delete = parse_pages(pages_str, total)
        pages_to_keep = [i for i in range(total) if (i + 1) not in pages_to_delete]

        if not pages_to_keep:
            sys.stderr.write("Error: Cannot delete all pages. At least 1 page must remain.\n")
            return False

        doc.select(pages_to_keep)
        doc.save(output_path, garbage=3, deflate=True)
        doc.close()
        if os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"PyMuPDF delete notice: {e}\n")
    return False

def delete_with_pypdf(input_path, output_path, pages_str):
    try:
        import pypdf
        reader = pypdf.PdfReader(input_path)
        total = len(reader.pages)
        pages_to_delete = parse_pages(pages_str, total)
        pages_to_keep = [i for i in range(total) if (i + 1) not in pages_to_delete]

        if not pages_to_keep:
            sys.stderr.write("Error: Cannot delete all pages. At least 1 page must remain.\n")
            return False

        writer = pypdf.PdfWriter()
        for i in pages_to_keep:
            writer.add_page(reader.pages[i])

        with open(output_path, "wb") as f:
            writer.write(f)

        if os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"pypdf delete notice: {e}\n")
    return False

def delete_with_qpdf(input_path, output_path, pages_str):
    try:
        # First get page count via pdfinfo or qpdf
        res = subprocess.run(["qpdf", "--show-npages", input_path], capture_output=True, text=True, timeout=30)
        if res.returncode != 0:
            return False
        total = int(res.stdout.strip())
        pages_to_delete = parse_pages(pages_str, total)
        pages_to_keep = [i + 1 for i in range(total) if (i + 1) not in pages_to_delete]

        if not pages_to_keep:
            return False

        # Group pages into ranges for qpdf, e.g. 1,3,4-10
        ranges = []
        range_start = pages_to_keep[0]
        prev = pages_to_keep[0]
        for p in pages_to_keep[1:]:
            if p == prev + 1:
                prev = p
            else:
                if range_start == prev:
                    ranges.append(str(range_start))
                else:
                    ranges.append(f"{range_start}-{prev}")
                range_start = p
                prev = p
        if range_start == prev:
            ranges.append(str(range_start))
        else:
            ranges.append(f"{range_start}-{prev}")

        qpdf_pages = ",".join(ranges)
        cmd = ["qpdf", input_path, "--pages", input_path, qpdf_pages, "--", output_path]
        res = subprocess.run(cmd, capture_output=True, timeout=60)
        if res.returncode == 0 and os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"qpdf delete notice: {e}\n")
    return False

def main():
    if len(sys.argv) < 4:
        print("Usage: python3 delete_pdf_pages.py <input.pdf> <output.pdf> <pages_to_delete>")
        print("Example: python3 delete_pdf_pages.py in.pdf out.pdf '2,4,5-7'")
        sys.exit(1)

    input_pdf = sys.argv[1]
    output_pdf = sys.argv[2]
    pages_to_delete = sys.argv[3]

    if not os.path.exists(input_pdf):
        print(f"Error: input file '{input_pdf}' not found")
        sys.exit(1)

    os.makedirs(os.path.dirname(os.path.abspath(output_pdf)), exist_ok=True)

    ok = delete_with_pymupdf(input_pdf, output_pdf, pages_to_delete)
    if not ok:
        ok = delete_with_pypdf(input_pdf, output_pdf, pages_to_delete)
    if not ok:
        ok = delete_with_qpdf(input_pdf, output_pdf, pages_to_delete)

    if ok and os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0:
        print(f"Success: deleted pages [{pages_to_delete}] from PDF")
        sys.exit(0)
    else:
        print("Error: failed to delete pages from PDF")
        sys.exit(1)

if __name__ == "__main__":
    main()
