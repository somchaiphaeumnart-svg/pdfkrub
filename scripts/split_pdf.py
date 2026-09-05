#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Split PDF (แยกหน้าเอกสาร PDF)
Losslessly splits, extracts, or groups pages of a PDF using PyMuPDF (fitz).
Supports:
1. Range Mode:
   - Merge selected pages into a single PDF (e.g. pages 1, 3, 5-7 -> extracted.pdf)
   - Extract selected pages as individual PDFs inside a ZIP
2. All Pages Mode:
   - Extract every page as a separate PDF inside a ZIP (or single PDF if only 1 page)
3. Fixed Mode:
   - Split every N pages into separate PDF documents packed inside a ZIP
"""

import sys
import os
import argparse
import zipfile
import re

try:
    import fitz  # PyMuPDF
except ImportError:
    fitz = None


def parse_page_range(range_str, total_pages):
    """
    Parses strings like "1-3, 5, 8-10" into a list of 1-based unique page numbers in order.
    """
    pages = []
    seen = set()
    if not range_str or not range_str.strip():
        return list(range(1, total_pages + 1))

    # Split by comma or space
    parts = re.split(r'[,;\s]+', range_str.strip())
    for part in parts:
        part = part.strip()
        if not part:
            continue
        if '-' in part:
            sub = part.split('-')
            if len(sub) == 2 and sub[0].isdigit() and sub[1].isdigit():
                start = int(sub[0])
                end = int(sub[1])
                if start <= end:
                    step = 1
                else:
                    step = -1
                for p in range(start, end + step, step):
                    if 1 <= p <= total_pages and p not in seen:
                        seen.add(p)
                        pages.append(p)
            elif len(sub) == 2 and sub[0].isdigit() and not sub[1]:
                start = int(sub[0])
                for p in range(start, total_pages + 1):
                    if 1 <= p <= total_pages and p not in seen:
                        seen.add(p)
                        pages.append(p)
        elif part.isdigit():
            p = int(part)
            if 1 <= p <= total_pages and p not in seen:
                seen.add(p)
                pages.append(p)

    return pages


def split_pdf(input_path, output_path, mode='range', pages_str='', merge=True, split_every_n=1, tmp_dir=None):
    if fitz is None:
        raise RuntimeError("PyMuPDF (fitz) is not installed.")

    if not os.path.isfile(input_path):
        raise FileNotFoundError(f"Input file not found: {input_path}")

    doc = fitz.open(input_path)
    total_pages = len(doc)
    if total_pages == 0:
        raise ValueError("The source PDF has no pages.")

    basename = os.path.splitext(os.path.basename(input_path))[0]
    if not tmp_dir:
        tmp_dir = os.path.dirname(output_path)
    os.makedirs(tmp_dir, exist_ok=True)

    generated_pdfs = []

    if mode == 'all':
        # Split every single page into its own PDF
        for p in range(1, total_pages + 1):
            out_pdf = fitz.open()
            out_pdf.insert_pdf(doc, from_page=p - 1, to_page=p - 1)
            pdf_filename = f"{basename}_page_{p}.pdf"
            target = os.path.join(tmp_dir, pdf_filename)
            out_pdf.save(target, garbage=4, deflate=True)
            out_pdf.close()
            generated_pdfs.append((target, pdf_filename))

    elif mode == 'fixed':
        # Split every N pages into chunks
        n = max(1, int(split_every_n))
        part_idx = 1
        for start_idx in range(0, total_pages, n):
            end_idx = min(start_idx + n - 1, total_pages - 1)
            out_pdf = fitz.open()
            out_pdf.insert_pdf(doc, from_page=start_idx, to_page=end_idx)
            
            p_start = start_idx + 1
            p_end = end_idx + 1
            if p_start == p_end:
                pdf_filename = f"{basename}_part_{part_idx}_page_{p_start}.pdf"
            else:
                pdf_filename = f"{basename}_part_{part_idx}_pages_{p_start}-{p_end}.pdf"

            target = os.path.join(tmp_dir, pdf_filename)
            out_pdf.save(target, garbage=4, deflate=True)
            out_pdf.close()
            generated_pdfs.append((target, pdf_filename))
            part_idx += 1

    else:
        # Mode: range / custom selection
        selected_pages = parse_page_range(pages_str, total_pages)
        if not selected_pages:
            selected_pages = list(range(1, total_pages + 1))

        if merge:
            # Merge all selected pages into a single PDF
            out_pdf = fitz.open()
            for p in selected_pages:
                out_pdf.insert_pdf(doc, from_page=p - 1, to_page=p - 1)

            out_pdf.save(output_path, garbage=4, deflate=True)
            out_pdf.close()
            doc.close()
            return output_path
        else:
            # Extract each selected page as an individual PDF
            for p in selected_pages:
                out_pdf = fitz.open()
                out_pdf.insert_pdf(doc, from_page=p - 1, to_page=p - 1)
                pdf_filename = f"{basename}_page_{p}.pdf"
                target = os.path.join(tmp_dir, pdf_filename)
                out_pdf.save(target, garbage=4, deflate=True)
                out_pdf.close()
                generated_pdfs.append((target, pdf_filename))

    doc.close()

    # If only 1 PDF was generated, we can either write it directly to output_path or zip it
    if len(generated_pdfs) == 1 and output_path.lower().endswith('.pdf'):
        single_src, _ = generated_pdfs[0]
        if os.path.exists(output_path):
            os.remove(output_path)
        os.replace(single_src, output_path)
        return output_path

    # Pack into ZIP file
    zip_path = output_path if output_path.lower().endswith('.zip') else output_path + '.zip'
    with zipfile.ZipFile(zip_path, 'w', compression=zipfile.ZIP_DEFLATED) as zf:
        for file_path, arcname in generated_pdfs:
            zf.write(file_path, arcname)

    return zip_path


def main():
    parser = argparse.ArgumentParser(description="Split PDF with PyMuPDF")
    parser.add_argument("input", help="Path to input PDF file")
    parser.add_argument("output", help="Path to output file (.pdf or .zip)")
    parser.add_argument("--mode", choices=["range", "all", "fixed"], default="range", help="Split mode")
    parser.add_argument("--pages", default="", help="Page list e.g. 1-3,5,8")
    parser.add_argument("--merge", action="store_true", default=False, help="Merge extracted pages into single PDF")
    parser.add_argument("--split-every-n", type=int, default=1, help="Split every N pages")
    parser.add_argument("--tmp-dir", default=None, help="Directory for temporary files")

    args = parser.parse_args()

    try:
        res = split_pdf(
            input_path=args.input,
            output_path=args.output,
            mode=args.mode,
            pages_str=args.pages,
            merge=args.merge,
            split_every_n=args.split_every_n,
            tmp_dir=args.tmp_dir
        )
        print(f"SUCCESS: {res}")
        sys.exit(0)
    except Exception as e:
        sys.stderr.write(f"ERROR: {str(e)}\n")
        sys.exit(1)


if __name__ == "__main__":
    main()
