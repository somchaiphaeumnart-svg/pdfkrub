#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
High-quality, lossless PDF watermark script.
Supports:
- Image watermarks (PNG, JPG, WEBP, SVG)
- Text watermarks (any language/text)
- Opacity / Transparency (0.05 to 1.0)
- Scale / Sizing (0.1 to 1.0)
- Positioning (9 grid positions + full-page tile)
- Rotation (arbitrary angles: -180° to 180°)
- Page selection (all, first, custom ranges)
"""

import sys
import os
import json
import io
import math

def parse_target_pages(pages_spec, total_pages):
    if not pages_spec or pages_spec == "all":
        return list(range(total_pages))
    if pages_spec == "first":
        return [0]
    
    pages = set()
    for part in str(pages_spec).split(","):
        part = part.strip()
        if not part:
            continue
        if "-" in part:
            try:
                s, e = part.split("-", 1)
                start = max(1, int(s.strip()))
                end = min(total_pages, int(e.strip()))
                for p in range(start, end + 1):
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
    return sorted(list(pages)) if pages else list(range(total_pages))

def create_text_watermark_image(text, opacity=0.35, color=(220, 38, 38)):
    from PIL import Image, ImageDraw, ImageFont
    
    # Try to load standard fonts or fall back to default
    font = None
    font_size = 72
    font_paths = [
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
        "/usr/share/fonts/truetype/noto/NotoSansThai-Bold.ttf",
        "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf",
        "C:/Windows/Fonts/tahoma.ttf",
        "C:/Windows/Fonts/arial.ttf",
    ]
    for fp in font_paths:
        if os.path.exists(fp):
            try:
                font = ImageFont.truetype(fp, font_size)
                break
            except Exception:
                pass
    if font is None:
        try:
            font = ImageFont.load_default()
        except Exception:
            pass

    # Measure text bounding box
    dummy = Image.new("RGBA", (10, 10), (0, 0, 0, 0))
    draw = ImageDraw.Draw(dummy)
    if hasattr(draw, "textbbox") and font:
        bbox = draw.textbbox((0, 0), text, font=font)
        w = max(100, bbox[2] - bbox[0] + 40)
        h = max(40, bbox[3] - bbox[1] + 20)
    else:
        w = max(100, len(text) * 45 + 40)
        h = 100

    img = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    alpha = int(255 * max(0.05, min(1.0, opacity)))
    fill_color = (color[0], color[1], color[2], alpha)
    
    # Draw centered text
    if hasattr(draw, "textbbox") and font:
        draw.text((20, 10), text, font=font, fill=fill_color)
    else:
        draw.text((20, 10), text, fill=fill_color)

    return img

def apply_watermark(input_pdf, output_pdf, config):
    import fitz # PyMuPDF
    from PIL import Image

    doc = fitz.open(input_pdf)
    total_pages = len(doc)
    target_pages = parse_target_pages(config.get("pages", "all"), total_pages)

    wm_type = config.get("type", "image")
    opacity = float(config.get("opacity", 0.35))
    rotation = float(config.get("rotation", 0))
    scale_factor = float(config.get("scale", 0.4))
    position = config.get("position", "center").lower()

    # Load / create base watermark PIL Image
    if wm_type == "text":
        text = config.get("text", "WATERMARK")
        color_hex = config.get("color", "#dc2626").lstrip("#")
        try:
            rgb = tuple(int(color_hex[i:i+2], 16) for i in (0, 2, 4))
        except Exception:
            rgb = (220, 38, 38)
        base_img = create_text_watermark_image(text, opacity=opacity, color=rgb)
    else:
        image_path = config.get("image_path")
        if not image_path or not os.path.exists(image_path):
            raise FileNotFoundError(f"Watermark image not found: {image_path}")
        base_img = Image.open(image_path).convert("RGBA")
        
        # Apply opacity to alpha channel
        if opacity < 0.99:
            r, g, b, a = base_img.split()
            a = a.point(lambda p: int(p * opacity))
            base_img = Image.merge("RGBA", (r, g, b, a))

    # Apply rotation
    if rotation != 0:
        base_img = base_img.rotate(rotation, expand=True, resample=Image.BICUBIC)

    for page_idx in target_pages:
        page = doc[page_idx]
        rect = page.rect
        p_w = rect.width
        p_h = rect.height

        # Calculate target size proportional to page
        target_w = p_w * max(0.05, min(1.0, scale_factor))
        aspect = base_img.height / max(1, base_img.width)
        target_h = target_w * aspect
        if target_h > p_h * 0.9:
            target_h = p_h * 0.9
            target_w = target_h / max(0.001, aspect)

        # Resize PIL image for this page
        proc_img = base_img.resize((int(max(10, target_w)), int(max(10, target_h))), Image.LANCZOS)
        buf = io.BytesIO()
        proc_img.save(buf, format="PNG")
        img_data = buf.getvalue()

        margin = 25.0

        if position == "tile":
            # 3x3 pattern across the page
            for row in range(3):
                for col in range(3):
                    tile_x = (col * 0.35 + 0.05) * p_w
                    tile_y = (row * 0.35 + 0.05) * p_h
                    tile_rect = fitz.Rect(tile_x, tile_y, tile_x + target_w * 0.7, tile_y + target_h * 0.7)
                    page.insert_image(tile_rect, stream=img_data, keep_proportion=True, overlay=True)
        else:
            # 9-point grid placement
            if "left" in position:
                x = margin
            elif "right" in position:
                x = p_w - target_w - margin
            else:
                x = (p_w - target_w) / 2.0

            if "top" in position:
                y = margin
            elif "bottom" in position:
                y = p_h - target_h - margin
            else:
                y = (p_h - target_h) / 2.0

            target_rect = fitz.Rect(x, y, x + target_w, y + target_h)
            page.insert_image(target_rect, stream=img_data, keep_proportion=True, overlay=True)

    doc.save(output_pdf, garbage=3, deflate=True)
    doc.close()
    return True

def main():
    if len(sys.argv) < 4:
        print("Usage: python3 watermark_pdf.py <input.pdf> <output.pdf> <config_json_path_or_string>")
        sys.exit(1)

    input_pdf = sys.argv[1]
    output_pdf = sys.argv[2]
    config_arg = sys.argv[3]

    if not os.path.exists(input_pdf):
        print(f"Error: input file '{input_pdf}' not found")
        sys.exit(1)

    if os.path.exists(config_arg):
        with open(config_arg, "r", encoding="utf-8") as f:
            config = json.load(f)
    else:
        config = json.loads(config_arg)

    os.makedirs(os.path.dirname(os.path.abspath(output_pdf)), exist_ok=True)

    try:
        ok = apply_watermark(input_pdf, output_pdf, config)
        if ok and os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 0:
            print("Success: Watermark applied successfully")
            sys.exit(0)
    except Exception as e:
        sys.stderr.write(f"Watermark error: {e}\n")

    sys.exit(1)

if __name__ == "__main__":
    main()
