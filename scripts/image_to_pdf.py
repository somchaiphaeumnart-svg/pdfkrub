#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
High-quality Image to PDF converter.
Converts one or multiple images (JPG, PNG, WEBP, BMP, GIF, TIFF) into a clean, multi-page PDF.

Engine hierarchy:
1. img2pdf (lossless direct embedding, fast)
2. Pillow (PIL.Image - handles all formats, transparency to white bg)
3. PyMuPDF (fitz)
4. Built-in Pure-Python direct JPEG embedder (0 dependencies, 100% offline)
5. ImageMagick / GraphicsMagick CLI fallback
"""

import sys
import os
import struct
import subprocess

def convert_with_img2pdf(image_paths, output_pdf):
    try:
        import img2pdf
        with open(output_pdf, "wb") as f:
            f.write(img2pdf.convert(image_paths))
        if os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0:
            return True
    except Exception:
        pass
    return False

PAGE_SIZES_PT = {
    "a4": (595.28, 841.89),
    "letter": (612.0, 792.0),
}

MARGIN_RATIOS = {
    "none": 0.0,
    "small": 0.05,
    "big": 0.10,
}

def convert_with_pillow_layout(image_paths, output_pdf, orientation="auto", page_size="fit", margin="none"):
    """Convert images placing them on formatted pages with orientation and margins."""
    try:
        from PIL import Image
        resampling = getattr(Image, 'Resampling', Image).LANCZOS

        pil_pages = []
        for p in image_paths:
            im = Image.open(p)
            if im.mode in ("RGBA", "LA") or (im.mode == "P" and "transparency" in im.info):
                bg = Image.new("RGB", im.size, (255, 255, 255))
                if im.mode == "P":
                    im = im.convert("RGBA")
                bg.paste(im, mask=im.split()[3])
                im = bg
            elif im.mode != "RGB":
                im = im.convert("RGB")

            if page_size == "fit" and margin == "none" and orientation == "auto":
                pil_pages.append(im)
                continue

            # Determine page aspect & target dimensions
            if page_size in PAGE_SIZES_PT:
                pt_w, pt_h = PAGE_SIZES_PT[page_size]
            else:
                pt_w, pt_h = im.width, im.height

            if orientation == "portrait":
                pw, ph = min(pt_w, pt_h), max(pt_w, pt_h)
            elif orientation == "landscape":
                pw, ph = max(pt_w, pt_h), min(pt_w, pt_h)
            else:  # auto
                if im.width > im.height:
                    pw, ph = max(pt_w, pt_h), min(pt_w, pt_h)
                else:
                    pw, ph = min(pt_w, pt_h), max(pt_w, pt_h)

            # Scale canvas to high resolution (match image size or 150 DPI)
            scale = max(im.width / pw, im.height / ph, 2.0)
            canvas_w = int(pw * scale)
            canvas_h = int(ph * scale)

            m_ratio = MARGIN_RATIOS.get(margin, 0.0)
            avail_w = canvas_w * (1.0 - 2.0 * m_ratio)
            avail_h = canvas_h * (1.0 - 2.0 * m_ratio)

            img_scale = min(avail_w / im.width, avail_h / im.height)
            new_w = max(1, int(im.width * img_scale))
            new_h = max(1, int(im.height * img_scale))

            im_resized = im.resize((new_w, new_h), resampling)
            canvas = Image.new("RGB", (canvas_w, canvas_h), (255, 255, 255))
            paste_x = int((canvas_w - new_w) / 2)
            paste_y = int((canvas_h - new_h) / 2)
            canvas.paste(im_resized, (paste_x, paste_y))
            pil_pages.append(canvas)

        if pil_pages:
            first = pil_pages[0]
            rest = pil_pages[1:] if len(pil_pages) > 1 else []
            first.save(output_pdf, "PDF", resolution=150.0, save_all=True, append_images=rest)
            if os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0:
                return True
    except Exception as e:
        sys.stderr.write(f"Pillow layout notice: {e}\n")
    return False

def convert_with_pillow(image_paths, output_pdf):
    return convert_with_pillow_layout(image_paths, output_pdf, "auto", "fit", "none")


def convert_with_pymupdf(image_paths, output_pdf):
    try:
        import fitz
        doc = fitz.open()
        for p in image_paths:
            img = fitz.open(p)
            rect = img[0].rect
            pdfbytes = img.convert_to_pdf()
            imgpdf = fitz.open("pdf", pdfbytes)
            page = doc.new_page(width=rect.width, height=rect.height)
            page.show_pdf_page(rect, imgpdf, 0)
        doc.save(output_pdf)
        if os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0:
            return True
    except Exception as e:
        sys.stderr.write(f"PyMuPDF notice: {e}\n")
    return False

def get_jpeg_size(data):
    """Parse width and height from raw JPEG byte stream."""
    idx = 2
    while idx < len(data) - 8:
        if data[idx] != 0xFF:
            idx += 1
            continue
        marker = data[idx + 1]
        length = struct.unpack(">H", data[idx + 2:idx + 4])[0]
        if marker in (0xC0, 0xC1, 0xC2, 0xC3):  # SOF markers
            h, w = struct.unpack(">HH", data[idx + 5:idx + 9])
            return w, h
        idx += 2 + length
    return 800, 600

def convert_pure_python_jpeg(image_paths, output_pdf):
    """Pure Python fallback for JPEG files with 0 dependencies."""
    try:
        pages = []
        for p in image_paths:
            with open(p, "rb") as f:
                data = f.read()
            # Verify JPEG header
            if not data.startswith(b"\xFF\xD8"):
                return False
            w, h = get_jpeg_size(data)
            pages.append((w, h, data))

        if not pages:
            return False

        objects = []
        # obj 1: Catalog
        # obj 2: Pages
        # obj 3..N: Page, Contents, Image objects
        page_obj_ids = []
        current_id = 3

        page_records = []
        for w, h, img_data in pages:
            page_id = current_id
            content_id = current_id + 1
            xobject_id = current_id + 2
            current_id += 3
            page_obj_ids.append(page_id)
            page_records.append({
                "page_id": page_id,
                "content_id": content_id,
                "xobject_id": xobject_id,
                "w": w,
                "h": h,
                "data": img_data
            })

        catalog = "<< /Type /Catalog /Pages 2 0 R >>"
        pages_dict = f"<< /Type /Pages /Kids [{' '.join(f'{pid} 0 R' for pid in page_obj_ids)}] /Count {len(page_obj_ids)} >>"

        obj_list = [(1, catalog), (2, pages_dict)]

        for rec in page_records:
            w, h = rec["w"], rec["h"]
            # Page object
            page_str = (f"<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {w} {h}] "
                        f"/Contents {rec['content_id']} 0 R "
                        f"/Resources << /XObject << /Im1 {rec['xobject_id']} 0 R >> >> >>")
            # Content stream
            stream_body = f"q {w} 0 0 {h} 0 0 cm /Im1 Do Q"
            content_str = f"<< /Length {len(stream_body)} >>\nstream\n{stream_body}\nendstream"
            # Image XObject
            img_head = (f"<< /Type /XObject /Subtype /Image /Width {w} /Height {h} "
                        f"/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode "
                        f"/Length {len(rec['data'])} >>\nstream\n")
            obj_list.append((rec["page_id"], page_str))
            obj_list.append((rec["content_id"], content_str))
            obj_list.append((rec["xobject_id"], (img_head, rec["data"], "\nendstream")))

        with open(output_pdf, "wb") as out:
            out.write(b"%PDF-1.4\n")
            offsets = {}
            for item in obj_list:
                oid = item[0]
                offsets[oid] = out.tell()
                out.write(f"{oid} 0 obj\n".encode("latin1"))
                content = item[1]
                if isinstance(content, tuple):
                    out.write(content[0].encode("latin1"))
                    out.write(content[1])
                    out.write(content[2].encode("latin1"))
                else:
                    out.write(content.encode("latin1"))
                out.write(b"\nendobj\n")

            xref_pos = out.tell()
            out.write(f"xref\n0 {len(obj_list) + 1}\n0000000000 65535 f \n".encode("latin1"))
            for i in range(1, len(obj_list) + 1):
                out.write(f"{offsets[i]:010d} 00000 n \n".encode("latin1"))

            out.write(f"trailer\n<< /Size {len(obj_list) + 1} /Root 1 0 R >>\nstartxref\n{xref_pos}\n%%EOF\n".encode("latin1"))

        return os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0
    except Exception as e:
        sys.stderr.write(f"Pure-Python JPEG notice: {e}\n")
    return False

def convert_with_imagemagick(image_paths, output_pdf):
    for cmd_bin in ["magick", "convert", "gm"]:
        try:
            cmd = [cmd_bin]
            if cmd_bin == "gm":
                cmd.append("convert")
            cmd.extend(image_paths)
            cmd.append(output_pdf)
            res = subprocess.run(cmd, capture_output=True, timeout=120)
            if res.returncode == 0 and os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0:
                return True
        except Exception:
            pass
    return False

def main():
    import argparse
    parser = argparse.ArgumentParser(description="Convert images to PDF")
    parser.add_argument("--orientation", default="auto", choices=["auto", "portrait", "landscape"], help="Page orientation")
    parser.add_argument("--page-size", default="fit", choices=["fit", "a4", "letter"], help="Page size")
    parser.add_argument("--margin", default="none", choices=["none", "small", "big"], help="Page margin")
    parser.add_argument("output_pdf", help="Path to output PDF")
    parser.add_argument("image_paths", nargs="+", help="Input image paths")

    args = parser.parse_args()

    output_pdf = args.output_pdf
    image_paths = args.image_paths

    valid_images = [p for p in image_paths if os.path.exists(p) and os.path.getsize(p) > 0]
    if not valid_images:
        print("Error: No valid input images provided")
        sys.exit(1)

    os.makedirs(os.path.dirname(os.path.abspath(output_pdf)), exist_ok=True)

    has_custom_layout = (args.page_size != "fit" or args.margin != "none" or args.orientation != "auto")
    ok = False

    if has_custom_layout:
        ok = convert_with_pillow_layout(valid_images, output_pdf, args.orientation, args.page_size, args.margin)

    if not ok:
        ok = convert_with_img2pdf(valid_images, output_pdf)
    if not ok:
        ok = convert_with_pillow_layout(valid_images, output_pdf, args.orientation, args.page_size, args.margin)
    if not ok:
        ok = convert_with_pymupdf(valid_images, output_pdf)
    if not ok:
        ok = convert_pure_python_jpeg(valid_images, output_pdf)
    if not ok:
        ok = convert_with_imagemagick(valid_images, output_pdf)

    if ok and os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0:
        print(f"Success: Converted {len(valid_images)} images to {output_pdf}")
        sys.exit(0)
    else:
        print("Error: Failed to convert images to PDF")
        sys.exit(1)

if __name__ == "__main__":
    main()
