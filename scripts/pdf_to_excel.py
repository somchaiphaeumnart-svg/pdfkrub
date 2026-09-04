#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
High-quality PDF to Excel (.xlsx) converter.
Extracts tables and tabular data from PDF files and outputs native .xlsx.

Engine hierarchy:
1. pdfplumber (best table grid structure detection) + openpyxl
2. PyMuPDF (fitz) page.find_tables() + openpyxl or built-in XLSX writer
3. pdftotext -layout (system poppler-utils) fallback + built-in XLSX writer
"""

import sys
import os
import re
import zipfile
import xml.sax.saxutils as saxutils
import subprocess

def col_to_letter(col_idx):
    """Convert 0-indexed column number to Excel column name (0 -> A, 1 -> B, 26 -> AA)."""
    result = ""
    col_idx += 1
    while col_idx > 0:
        col_idx, remainder = divmod(col_idx - 1, 26)
        result = chr(65 + remainder) + result
    return result

def clean_cell_value(val):
    if val is None:
        return ""
    s = str(val).strip()
    # Strip non-printable ASCII control characters except newline and tab
    s = re.sub(r'[\x00-\x08\x0B\x0C\x0E-\x1F]', '', s)
    return s

def write_xlsx_builtin(pages_data, output_path):
    """Generates standard OpenXML .xlsx file using only Python built-in modules."""
    if not pages_data:
        pages_data = [[[""]]]

    os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
    temp_output = output_path + ".tmp"

    with zipfile.ZipFile(temp_output, 'w', zipfile.ZIP_DEFLATED) as zf:
        num_sheets = len(pages_data)

        # [Content_Types].xml
        content_types = [
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">',
            '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>',
            '  <Default Extension="xml" ContentType="application/xml"/>',
            '  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>',
            '  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        ]
        for i in range(num_sheets):
            content_types.append(f'  <Override PartName="/xl/worksheets/sheet{i+1}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>')
        content_types.append('</Types>')
        zf.writestr('[Content_Types].xml', '\n'.join(content_types))

        # _rels/.rels
        zf.writestr('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">\n'
            '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>\n'
            '</Relationships>'
        )

        # xl/_rels/workbook.xml.rels
        wb_rels = [
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        ]
        for i in range(num_sheets):
            wb_rels.append(f'  <Relationship Id="rId{i+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet{i+1}.xml"/>')
        wb_rels.append(f'  <Relationship Id="rId{num_sheets+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>')
        wb_rels.append('</Relationships>')
        zf.writestr('xl/_rels/workbook.xml.rels', '\n'.join(wb_rels))

        # xl/workbook.xml
        wb_xml = [
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">',
            '  <sheets>'
        ]
        for i in range(num_sheets):
            sheet_title = f"Page {i+1}" if num_sheets > 1 else "Sheet1"
            wb_xml.append(f'    <sheet name="{sheet_title}" sheetId="{i+1}" r:id="rId{i+1}"/>')
        wb_xml.append('  </sheets>')
        wb_xml.append('</workbook>')
        zf.writestr('xl/workbook.xml', '\n'.join(wb_xml))

        # xl/styles.xml
        zf.writestr('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">\n'
            '  <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>\n'
            '  <fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>\n'
            '  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>\n'
            '  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>\n'
            '  <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>\n'
            '</styleSheet>'
        )

        # worksheets/sheet{i+1}.xml
        for i, page_rows in enumerate(pages_data):
            sheet_xml = [
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
                '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">',
                '  <sheetData>'
            ]
            row_counter = 1
            for row in page_rows:
                row_cells = []
                for col_idx, cell_val in enumerate(row):
                    str_val = clean_cell_value(cell_val)
                    if not str_val:
                        continue
                    cell_ref = f"{col_to_letter(col_idx)}{row_counter}"
                    # Check if numeric
                    is_num = False
                    try:
                        clean_num = str_val.replace(',', '')
                        float(clean_num)
                        if str_val == '0' or not str_val.startswith('0') or str_val.startswith('0.'):
                            is_num = True
                            str_val = clean_num
                    except ValueError:
                        is_num = False

                    if is_num:
                        row_cells.append(f'<c r="{cell_ref}"><v>{str_val}</v></c>')
                    else:
                        escaped = saxutils.escape(str_val)
                        row_cells.append(f'<c r="{cell_ref}" t="inlineStr"><is><t>{escaped}</t></is></c>')

                if row_cells:
                    sheet_xml.append(f'    <row r="{row_counter}">')
                    sheet_xml.extend(f'      {c}' for c in row_cells)
                    sheet_xml.append('    </row>')
                row_counter += 1

            sheet_xml.append('  </sheetData>')
            sheet_xml.append('</worksheet>')
            zf.writestr(f'xl/worksheets/sheet{i+1}.xml', '\n'.join(sheet_xml))

    os.replace(temp_output, output_path)
    return True

def save_to_excel(pages_data, output_path):
    # Try openpyxl first if available
    try:
        import openpyxl
        wb = openpyxl.Workbook()
        wb.remove(wb.active) # Remove default sheet
        for i, page_rows in enumerate(pages_data):
            title = f"Page {i+1}" if len(pages_data) > 1 else "Sheet1"
            ws = wb.create_sheet(title=title)
            for row in page_rows:
                cleaned_row = []
                for cell in row:
                    val = clean_cell_value(cell)
                    try:
                        clean_num = val.replace(',', '')
                        if (val == '0' or not val.startswith('0') or val.startswith('0.')):
                            float_val = float(clean_num)
                            cleaned_row.append(float_val if '.' in clean_num else int(clean_num))
                            continue
                    except Exception:
                        pass
                    cleaned_row.append(val)
                ws.append(cleaned_row)
        wb.save(output_path)
        return True
    except ImportError:
        pass
    except Exception as e:
        sys.stderr.write(f"openpyxl warning, falling back to built-in writer: {e}\n")

    return write_xlsx_builtin(pages_data, output_path)

def extract_from_pdf(input_path):
    pages_data = []

    # Method 1: pdfplumber (highest quality table detection)
    try:
        import pdfplumber
        with pdfplumber.open(input_path) as pdf:
            for page in pdf.pages:
                page_rows = []
                tables = page.extract_tables()
                if tables:
                    for table in tables:
                        for row in table:
                            page_rows.append([cell if cell is not None else "" for cell in row])
                        page_rows.append([])
                else:
                    text = page.extract_text()
                    if text:
                        for line in text.splitlines():
                            cols = re.split(r'\s{2,}|\t', line.strip())
                            page_rows.append(cols)
                pages_data.append(page_rows)
        if any(len(p) > 0 for p in pages_data):
            return pages_data
    except ImportError:
        pass
    except Exception as e:
        sys.stderr.write(f"pdfplumber notice: {e}\n")

    # Method 2: PyMuPDF (fitz) - table extraction
    try:
        import fitz
        doc = fitz.open(input_path)
        pages_data = []
        for page in doc:
            page_rows = []
            has_tables = False
            try:
                tabs = page.find_tables()
                if tabs and tabs.tables:
                    has_tables = True
                    for tab in tabs:
                        for row in tab.extract():
                            page_rows.append([cell if cell is not None else "" for cell in row])
                        page_rows.append([])
            except Exception:
                has_tables = False

            if not has_tables or not page_rows:
                text = page.get_text("text")
                if text:
                    for line in text.splitlines():
                        cols = re.split(r'\s{2,}|\t', line.strip())
                        page_rows.append(cols)
            pages_data.append(page_rows)
        if any(len(p) > 0 for p in pages_data):
            return pages_data
    except ImportError:
        pass
    except Exception as e:
        sys.stderr.write(f"fitz notice: {e}\n")

    # Method 3: pdftotext -layout (system command from poppler-utils)
    try:
        res = subprocess.run(['pdftotext', '-layout', input_path, '-'], capture_output=True, text=True, timeout=60)
        if res.returncode == 0 and res.stdout:
            raw_pages = res.stdout.split('\x0c')
            pages_data = []
            for raw_page in raw_pages:
                page_rows = []
                for line in raw_page.splitlines():
                    trimmed = line.strip()
                    if trimmed:
                        cols = re.split(r'\s{2,}|\t', trimmed)
                        page_rows.append(cols)
                if page_rows:
                    pages_data.append(page_rows)
            if pages_data:
                return pages_data
    except Exception as e:
        sys.stderr.write(f"pdftotext notice: {e}\n")

    return pages_data

def main():
    if len(sys.argv) < 3:
        print("Usage: pdf_to_excel.py <input.pdf> <output.xlsx>")
        sys.exit(1)

    input_pdf = sys.argv[1]
    output_xlsx = sys.argv[2]

    if not os.path.isfile(input_pdf):
        sys.stderr.write(f"Input file not found: {input_pdf}\n")
        sys.exit(1)

    pages_data = extract_from_pdf(input_pdf)
    if not pages_data:
        pages_data = [[["ไม่พบข้อมูลในเอกสาร PDF หรือเป็นไฟล์รูปภาพสแกน"]]]

    save_to_excel(pages_data, output_xlsx)

    if os.path.isfile(output_xlsx) and os.path.getsize(output_xlsx) > 0:
        print(f"Success: {output_xlsx}")
        sys.exit(0)
    else:
        sys.stderr.write("Failed to create xlsx file\n")
        sys.exit(1)

if __name__ == '__main__':
    main()
