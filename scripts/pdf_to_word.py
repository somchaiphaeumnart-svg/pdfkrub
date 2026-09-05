#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Advanced PDF to Word (.docx) Converter.
Supports two main engines:
1. Standard High-Fidelity Layout mode (using pdf2docx with LibreOffice fallback)
   Preserves Thai fonts, tables, shapes, colors, and layout structure.
2. Thai OCR mode (for scanned PDFs or image-based PDFs)
   Extracts text using PyMuPDF + Tesseract OCR (tha+eng) and outputs editable Word .docx.
"""

import sys
import os
import argparse
import subprocess
import tempfile
import zipfile
import xml.sax.saxutils as saxutils

def parse_page_range(range_str, total_pages):
    """
    Parse page range string like 'all', '1-3,5', '2,4,6'
    Returns 0-indexed list of page numbers.
    """
    if not range_str or range_str.strip().lower() == 'all':
        return list(range(total_pages))

    pages = set()
    parts = [p.strip() for p in range_str.split(',') if p.strip()]
    for part in parts:
        if '-' in part:
            bounds = part.split('-')
            if len(bounds) == 2:
                try:
                    start = int(bounds[0])
                    end = int(bounds[1])
                    for p in range(start, end + 1):
                        if 1 <= p <= total_pages:
                            pages.add(p - 1)
                except ValueError:
                    pass
        else:
            try:
                p = int(part)
                if 1 <= p <= total_pages:
                    pages.add(p - 1)
            except ValueError:
                pass

    sorted_pages = sorted(list(pages))
    return sorted_pages if sorted_pages else list(range(total_pages))

def get_pdf_page_count(pdf_path):
    """Get total page count of a PDF using PyMuPDF or pdfinfo."""
    try:
        import fitz
        doc = fitz.open(pdf_path)
        count = len(doc)
        doc.close()
        return count
    except Exception:
        pass

    try:
        res = subprocess.run(['pdfinfo', pdf_path], capture_output=True, text=True, timeout=15)
        if res.returncode == 0:
            for line in res.stdout.splitlines():
                if line.startswith('Pages:'):
                    return int(line.split(':', 1)[1].strip())
    except Exception:
        pass

    return 1

def build_docx_builtin(pages_text, output_path):
    """
    Generate a valid OpenXML .docx file using standard Python built-in modules.
    Ensures zero dependency failure.
    """
    os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
    temp_file = output_path + ".tmp"

    body_xml_parts = []
    for p_idx, text in enumerate(pages_text):
        if p_idx > 0:
            # Page break
            body_xml_parts.append('<w:p><w:r><w:br w:type="page"/></w:r></w:p>')

        paragraphs = text.split("\n")
        for para in paragraphs:
            para_clean = para.strip()
            if not para_clean:
                body_xml_parts.append('<w:p><w:pPr><w:spacing w:after="120"/></w:pPr></w:p>')
                continue
            escaped = saxutils.escape(para_clean)
            p_xml = (
                '<w:p>'
                '<w:pPr><w:spacing w:after="120"/><w:rPr><w:rFonts w:ascii="TH Sarabun New" w:hAnsi="TH Sarabun New" w:cs="TH Sarabun New"/><w:sz w:val="32"/><w:szCs w:val="32"/></w:rPr></w:pPr>'
                f'<w:r><w:rPr><w:rFonts w:ascii="TH Sarabun New" w:hAnsi="TH Sarabun New" w:cs="TH Sarabun New"/><w:sz w:val="32"/><w:szCs w:val="32"/></w:rPr><w:t>{escaped}</w:t></w:r>'
                '</w:p>'
            )
            body_xml_parts.append(p_xml)

    document_xml = (
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
        '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">\n'
        '<w:body>\n'
        + '\n'.join(body_xml_parts) +
        '\n<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/></w:sectPr>\n'
        '</w:body>\n'
        '</w:document>'
    )

    content_types = (
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">\n'
        '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>\n'
        '  <Default Extension="xml" ContentType="application/xml"/>\n'
        '  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>\n'
        '</Types>'
    )

    pkg_rels = (
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">\n'
        '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>\n'
        '</Relationships>'
    )

    with zipfile.ZipFile(temp_file, 'w', zipfile.ZIP_DEFLATED) as zf:
        zf.writestr('[Content_Types].xml', content_types)
        zf.writestr('_rels/.rels', pkg_rels)
        zf.writestr('word/document.xml', document_xml)

    if os.path.exists(output_path):
        os.remove(output_path)
    os.rename(temp_file, output_path)
    return True

def convert_with_pdf2docx(input_pdf, output_docx, page_indices=None):
    """Convert using pdf2docx for layout and Thai font preservation."""
    try:
        from pdf2docx import Converter
        cv = Converter(input_pdf)
        if page_indices is not None and len(page_indices) > 0:
            cv.convert(output_docx, pages=page_indices)
        else:
            cv.convert(output_docx)
        cv.close()
        return os.path.exists(output_docx) and os.path.getsize(output_docx) > 0
    except Exception as e:
        sys.stderr.write(f"pdf2docx notice: {e}\n")
        return False

def convert_with_libreoffice(input_pdf, output_docx):
    """Fallback conversion using LibreOffice headless."""
    try:
        out_dir = os.path.dirname(os.path.abspath(output_docx))
        base_name = os.path.splitext(os.path.basename(input_pdf))[0]

        cmd = [
            'soffice',
            '--headless',
            '--norestore',
            '--infilter=writer_pdf_import',
            '--convert-to', 'docx',
            '--outdir', out_dir,
            input_pdf
        ]
        res = subprocess.run(cmd, capture_output=True, timeout=120)
        generated = os.path.join(out_dir, f"{base_name}.docx")
        if os.path.exists(generated) and os.path.getsize(generated) > 0:
            if generated != output_docx:
                if os.path.exists(output_docx):
                    os.remove(output_docx)
                os.rename(generated, output_docx)
            return True
    except Exception as e:
        sys.stderr.write(f"LibreOffice notice: {e}\n")
    return False

def convert_with_ocr(input_pdf, output_docx, page_indices=None, ocr_lang="tha+eng"):
    """
    Render PDF pages to high-res images, perform Tesseract OCR,
    and generate editable Word document.
    """
    try:
        import fitz
        doc = fitz.open(input_pdf)
        total = len(doc)
        if page_indices is None or len(page_indices) == 0:
            target_pages = list(range(total))
        else:
            target_pages = [p for p in page_indices if 0 <= p < total]

        pages_text = []

        with tempfile.TemporaryDirectory() as tmp_dir:
            for idx, p_num in enumerate(target_pages):
                page = doc[p_num]
                pix = page.get_pixmap(dpi=200)
                img_path = os.path.join(tmp_dir, f"page_{p_num}.png")
                pix.save(img_path)

                out_base = os.path.join(tmp_dir, f"ocr_{p_num}")
                cmd = ['tesseract', img_path, out_base, '-l', ocr_lang, '--psm', '3', 'txt']
                res = subprocess.run(cmd, capture_output=True, timeout=90)
                txt_file = f"{out_base}.txt"
                if os.path.exists(txt_file):
                    with open(txt_file, 'r', encoding='utf-8', errors='ignore') as f:
                        text = f.read().strip()
                else:
                    text = page.get_text().strip()

                pages_text.append(text)

        doc.close()

        # Try building docx via python-docx if installed
        try:
            from docx import Document
            from docx.shared import Pt, Inches, RGBColor
            from docx.oxml.ns import qn

            word_doc = Document()
            # Set Margins to 1 inch
            for section in word_doc.sections:
                section.top_margin = Inches(1)
                section.bottom_margin = Inches(1)
                section.left_margin = Inches(1)
                section.right_margin = Inches(1)

            for idx, page_content in enumerate(pages_text):
                if idx > 0:
                    word_doc.add_page_break()

                for line in page_content.splitlines():
                    clean_line = line.strip()
                    p = word_doc.add_paragraph()
                    p.paragraph_format.space_after = Pt(4)
                    p.paragraph_format.line_spacing = 1.15
                    if clean_line:
                        run = p.add_run(clean_line)
                        run.font.name = 'TH Sarabun New'
                        run._element.rPr.rFonts.set(qn('w:eastAsia'), 'TH Sarabun New')
                        run._element.rPr.rFonts.set(qn('w:cs'), 'TH Sarabun New')
                        run.font.size = Pt(16)
                        run.font.color.rgb = RGBColor(30, 30, 30)

            word_doc.save(output_docx)
            if os.path.exists(output_docx) and os.path.getsize(output_docx) > 0:
                return True
        except Exception as e:
            sys.stderr.write(f"python-docx notice: {e}, falling back to built-in OpenXML writer\n")

        # Fallback to builtin XML generator
        return build_docx_builtin(pages_text, output_docx)

    except Exception as e:
        sys.stderr.write(f"OCR conversion notice: {e}\n")
        return False

def main():
    parser = argparse.ArgumentParser(description="Convert PDF to Word (.docx)")
    parser.add_argument("input_pdf", help="Input PDF file path")
    parser.add_argument("output_docx", help="Output DOCX file path")
    parser.add_argument("--mode", default="standard", choices=["standard", "ocr"], help="Conversion mode")
    parser.add_argument("--pages", default="all", help="Pages to convert (e.g. 'all', '1-3,5')")
    parser.add_argument("--ocr-lang", default="tha+eng", help="OCR language")
    parser.add_argument("--detect-tables", default="1", help="Detect tables (1/0)")
    parser.add_argument("--keep-images", default="1", help="Keep embedded images (1/0)")

    args = parser.parse_args()

    input_pdf = os.path.abspath(args.input_pdf)
    output_docx = os.path.abspath(args.output_docx)

    if not os.path.exists(input_pdf) or os.path.getsize(input_pdf) == 0:
        sys.stderr.write(f"Error: Input file {input_pdf} does not exist or is empty\n")
        sys.exit(1)

    os.makedirs(os.path.dirname(output_docx), exist_ok=True)
    total_pages = get_pdf_page_count(input_pdf)
    page_indices = parse_page_range(args.pages, total_pages)

    success = False

    if args.mode == "ocr":
        success = convert_with_ocr(input_pdf, output_docx, page_indices, args.ocr_lang)

    if not success and args.mode == "standard":
        success = convert_with_pdf2docx(input_pdf, output_docx, page_indices)
        if not success:
            success = convert_with_libreoffice(input_pdf, output_docx)
        if not success:
            success = convert_with_ocr(input_pdf, output_docx, page_indices, args.ocr_lang)

    if success and os.path.exists(output_docx) and os.path.getsize(output_docx) > 0:
        sys.exit(0)
    else:
        sys.stderr.write("PDF to Word conversion failed with all available engines\n")
        sys.exit(1)

if __name__ == '__main__':
    main()
