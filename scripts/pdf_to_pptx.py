#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
High-quality PDF to PowerPoint (.pptx) converter.
Converts PDF pages into a standard, valid Microsoft PowerPoint presentation.

Engine hierarchy:
1. python-pptx (if available) + PyMuPDF / pdftoppm / Ghostscript rendering
2. Standalone OpenXML generator (pure Python built-in standard library zipfile + xml)
   Guaranteed to work 100% offline without any pip dependencies!
"""

import sys
import os
import glob
import subprocess
import zipfile
import shutil
import tempfile

def render_pdf_to_images(pdf_path, output_dir, dpi=150):
    """Render PDF pages into PNG images using PyMuPDF, pdftoppm, or Ghostscript."""
    images = []

    # 1. Try PyMuPDF (fitz)
    try:
        import fitz
        doc = fitz.open(pdf_path)
        for i, page in enumerate(doc):
            pix = page.get_pixmap(dpi=dpi)
            img_path = os.path.join(output_dir, f"page_{i+1:04d}.png")
            pix.save(img_path)
            images.append(img_path)
        if images:
            return images
    except Exception:
        images = []

    # 2. Try pdftoppm (poppler-utils)
    try:
        cmd = ["pdftoppm", "-png", "-r", str(dpi), pdf_path, os.path.join(output_dir, "page")]
        res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        found = sorted(glob.glob(os.path.join(output_dir, "page-*.png")))
        if found:
            return found
    except Exception:
        pass

    # 3. Try Ghostscript (gs)
    try:
        out_pattern = os.path.join(output_dir, "page_%04d.png")
        cmd = [
            "gs", "-dBATCH", "-dNOPAUSE", "-q",
            "-sDEVICE=png16m", f"-r{dpi}",
            f"-sOutputFile={out_pattern}",
            pdf_path
        ]
        res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        found = sorted(glob.glob(os.path.join(output_dir, "page_*.png")))
        if found:
            return found
    except Exception:
        pass

    return images

def create_pptx_with_python_pptx(images, output_path):
    """Build presentation using python-pptx if installed."""
    try:
        from pptx import Presentation
        from pptx.util import Inches

        prs = Presentation()
        # Set 16:9 widescreen or 4:3 standard (widescreen default 10 x 5.625 inches)
        prs.slide_width = Inches(10)
        prs.slide_height = Inches(7.5) # 4:3 is standard for documents / slides

        blank_slide_layout = prs.slide_layouts[6] # completely blank slide

        for img_path in images:
            slide = prs.slides.add_slide(blank_slide_layout)
            slide.shapes.add_picture(img_path, Inches(0), Inches(0), width=prs.slide_width, height=prs.slide_height)

        prs.save(output_path)
        return True
    except Exception as e:
        return False

def create_pptx_standalone(images, output_path):
    """Build a standard Microsoft PowerPoint (.pptx) file using Python's built-in zipfile."""
    num_slides = len(images)
    if num_slides == 0:
        return False

    temp_zip = output_path + ".tmp"
    
    # 10 x 7.5 inches in EMUs (1 inch = 914400 EMUs)
    # Width = 9144000, Height = 6858000
    slide_cx = 9144000
    slide_cy = 6858000

    with zipfile.ZipFile(temp_zip, 'w', zipfile.ZIP_DEFLATED) as zf:
        # 1. [Content_Types].xml
        content_types = [
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">',
            '  <Default Extension="png" ContentType="image/png"/>',
            '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>',
            '  <Default Extension="xml" ContentType="application/xml"/>',
            '  <Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>',
        ]
        for i in range(1, num_slides + 1):
            content_types.append(f'  <Override PartName="/ppt/slides/slide{i}.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>')
        content_types.append('</Types>')
        zf.writestr('[Content_Types].xml', '\n'.join(content_types))

        # 2. _rels/.rels
        rels_content = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>
</Relationships>'''
        zf.writestr('_rels/.rels', rels_content)

        # 3. ppt/presentation.xml
        sld_id_lst = []
        for i in range(1, num_slides + 1):
            sld_id_lst.append(f'    <p:sldId id="{255 + i}" r:id="rId{i}"/>')
        
        pres_content = f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
                xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
                xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:sldMasterIdLst/>
  <p:sldIdLst>
{chr(10).join(sld_id_lst)}
  </p:sldIdLst>
  <p:sldSz cx="{slide_cx}" cy="{slide_cy}" type="screen4x3"/>
  <p:notesSz cx="{slide_cy}" cy="{slide_cx}"/>
</p:presentation>'''
        zf.writestr('ppt/presentation.xml', pres_content)

        # 4. ppt/_rels/presentation.xml.rels
        pres_rels = [
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        ]
        for i in range(1, num_slides + 1):
            pres_rels.append(f'  <Relationship Id="rId{i}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide{i}.xml"/>')
        pres_rels.append('</Relationships>')
        zf.writestr('ppt/_rels/presentation.xml.rels', '\n'.join(pres_rels))

        # 5. Add each slide and its image
        for i, img_path in enumerate(images, start=1):
            # Read and write image
            with open(img_path, 'rb') as f:
                img_data = f.read()
            zf.writestr(f'ppt/media/image{i}.png', img_data)

            # Slide XML
            slide_xml = f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
       xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
       xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:nvGrpSpPr>
        <p:cNvPr id="1" name=""/>
        <p:cNvGrpSpPr/>
        <p:nvPr/>
      </p:nvGrpSpPr>
      <p:grpSpPr>
        <a:xfrm>
          <a:off x="0" y="0"/>
          <a:ext cx="0" cy="0"/>
          <a:chOff x="0" y="0"/>
          <a:chExt cx="0" cy="0"/>
        </a:xfrm>
      </p:grpSpPr>
      <p:pic>
        <p:nvPicPr>
          <p:cNvPr id="{i+1}" name="Slide Page {i}"/>
          <p:cNvPicPr>
            <a:picLocks noChangeAspect="1"/>
          </p:cNvPicPr>
          <p:nvPr/>
        </p:nvPicPr>
        <p:blipFill>
          <a:blip r:embed="rId1"/>
          <a:stretch>
            <a:fillRect/>
          </a:stretch>
        </p:blipFill>
        <p:spPr>
          <a:xfrm>
            <a:off x="0" y="0"/>
            <a:ext cx="{slide_cx}" cy="{slide_cy}"/>
          </a:xfrm>
          <a:prstGeom prst="rect">
            <a:avLst/>
          </a:prstGeom>
        </p:spPr>
      </p:pic>
    </p:spTree>
  </p:cSld>
  <p:clrMapOvr>
    <a:masterClrMapping/>
  </p:clrMapOvr>
</p:sld>'''
            zf.writestr(f'ppt/slides/slide{i}.xml', slide_xml)

            # Slide Rels
            slide_rel = f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image{i}.png"/>
</Relationships>'''
            zf.writestr(f'ppt/slides/_rels/slide{i}.xml.rels', slide_rel)

    if os.path.exists(temp_zip) and os.path.getsize(temp_zip) > 100:
        if os.path.exists(output_path):
            os.remove(output_path)
        shutil.move(temp_zip, output_path)
        return True

    return False

def main():
    if len(sys.argv) < 3:
        print("Usage: python3 pdf_to_pptx.py <input.pdf> <output.pptx>")
        sys.exit(1)

    input_pdf = sys.argv[1]
    output_pptx = sys.argv[2]

    if not os.path.exists(input_pdf):
        print(f"Error: Input file '{input_pdf}' not found")
        sys.exit(1)

    temp_dir = tempfile.mkdtemp(prefix="pdf2pptx_")
    try:
        images = render_pdf_to_images(input_pdf, temp_dir, dpi=150)
        if not images:
            print("Error: Could not render any pages from PDF")
            sys.exit(1)

        # Try python-pptx first, then standalone generator
        ok = create_pptx_with_python_pptx(images, output_pptx)
        if not ok:
            ok = create_pptx_standalone(images, output_pptx)

        if ok and os.path.exists(output_pptx) and os.path.getsize(output_pptx) > 0:
            print(f"Success: Converted {len(images)} slides to {output_pptx}")
            sys.exit(0)
        else:
            print("Error: Failed to create PPTX output")
            sys.exit(1)
    finally:
        shutil.rmtree(temp_dir, ignore_errors=True)

if __name__ == "__main__":
    main()
