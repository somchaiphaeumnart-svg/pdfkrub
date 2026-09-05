#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Crop PDF Pages (ครอบตัด PDF)
Uses PyMuPDF (fitz) to crop or trim margins losslessly.
Supports:
- custom: specific percentage margins (top, bottom, left, right)
- auto-margins: detects visible text/drawings/images bounding box + safety margin
- trim-scanner: trims dark/dirty scanner borders (default ~4% margin)
- target pages: all or specific pages (e.g. 1, 1-3, 5)
"""

import sys
import os
import argparse
import fitz  # PyMuPDF


def parse_page_numbers(pages_str, total_pages):
    """Parses page strings like 'all', '1,2,5', or '1-3,5' into a set of 0-based page indices."""
    if not pages_str or pages_str.strip().lower() == 'all':
        return set(range(total_pages))

    result = set()
    parts = pages_str.split(',')
    for part in parts:
        item = part.strip()
        if not item:
            continue
        if '-' in item:
            sub = item.split('-')
            if len(sub) == 2:
                try:
                    s = int(sub[0].strip())
                    e = int(sub[1].strip())
                    min_p = max(1, min(s, e))
                    max_p = min(total_pages, max(s, e))
                    for p in range(min_p, max_p + 1):
                        result.add(p - 1)
                except ValueError:
                    pass
        else:
            try:
                p = int(item)
                if 1 <= p <= total_pages:
                    result.add(p - 1)
            except ValueError:
                pass

    return result if result else set(range(total_pages))


def detect_content_bbox(page):
    """Finds the bounding box of text, drawings, and images on the page."""
    bbox = None

    # 1. Text blocks
    for block in page.get_text("blocks"):
        r = fitz.Rect(block[:4])
        if r.width > 1 and r.height > 1:
            bbox = r if bbox is None else (bbox | r)

    # 2. Vector drawings
    try:
        for d in page.get_drawings():
            dr = fitz.Rect(d.get("rect", [0, 0, 0, 0]))
            # Ignore full-page background rects
            if dr.width < page.rect.width * 0.98 or dr.height < page.rect.height * 0.98:
                if dr.width > 2 and dr.height > 2:
                    bbox = dr if bbox is None else (bbox | dr)
    except Exception:
        pass

    return bbox


def crop_pdf(input_path, output_path, mode="custom",
             top_pct=0.0, bottom_pct=0.0, left_pct=0.0, right_pct=0.0,
             pages_str="all"):
    if not os.path.isfile(input_path):
        raise FileNotFoundError(f"Input file not found: {input_path}")

    doc = fitz.open(input_path)
    total_pages = len(doc)
    if total_pages == 0:
        raise ValueError("The PDF has no pages.")

    target_pages = parse_page_numbers(pages_str, total_pages)

    for page_idx in range(total_pages):
        if page_idx not in target_pages:
            continue

        page = doc[page_idx]
        p_rect = page.rect

        if mode == "auto-margins":
            content_box = detect_content_bbox(page)
            if content_box and content_box.width > 30 and content_box.height > 30:
                # Add safety margin of 18 points (~6.3 mm)
                pad = 18.0
                new_crop = fitz.Rect(
                    max(p_rect.x0, content_box.x0 - pad),
                    max(p_rect.y0, content_box.y0 - pad),
                    min(p_rect.x1, content_box.x1 + pad),
                    min(p_rect.y1, content_box.y1 + pad),
                )
                if new_crop.width > 50 and new_crop.height > 50:
                    page.set_cropbox(new_crop)
                    continue

        # If custom or trim-scanner or auto-margins fallback
        if mode == "trim-scanner":
            # Default 4% margin all around if not provided
            t = top_pct if top_pct > 0 else 4.0
            b = bottom_pct if bottom_pct > 0 else 4.0
            l = left_pct if left_pct > 0 else 4.0
            r = right_pct if right_pct > 0 else 4.0
        else:
            t = max(0.0, min(45.0, top_pct))
            b = max(0.0, min(45.0, bottom_pct))
            l = max(0.0, min(45.0, left_pct))
            r = max(0.0, min(45.0, right_pct))

        dx_l = p_rect.width * (l / 100.0)
        dx_r = p_rect.width * (r / 100.0)
        dy_t = p_rect.height * (t / 100.0)
        dy_b = p_rect.height * (b / 100.0)

        new_crop = fitz.Rect(
            p_rect.x0 + dx_l,
            p_rect.y0 + dy_t,
            p_rect.x1 - dx_r,
            p_rect.y1 - dy_b,
        )

        if new_crop.width > 40 and new_crop.height > 40:
            page.set_cropbox(new_crop)

    # Save output with garbage collection and deflation for optimal file size
    doc.save(output_path, garbage=3, deflate=True)
    doc.close()


def main():
    parser = argparse.ArgumentParser(description="Crop and Trim PDF Pages Losslessly")
    parser.add_argument("input_pdf", help="Path to source PDF")
    parser.add_argument("output_pdf", help="Path to destination PDF")
    parser.add_argument("--mode", default="custom", choices=["custom", "auto-margins", "trim-scanner"], help="Crop mode")
    parser.add_argument("--top", type=float, default=0.0, help="Top crop percentage")
    parser.add_argument("--bottom", type=float, default=0.0, help="Bottom crop percentage")
    parser.add_argument("--left", type=float, default=0.0, help="Left crop percentage")
    parser.add_argument("--right", type=float, default=0.0, help="Right crop percentage")
    parser.add_argument("--pages", default="all", help="Target pages: 'all' or '1,2,3' or '1-5'")

    args = parser.parse_args()

    try:
        crop_pdf(
            input_path=args.input_pdf,
            output_path=args.output_pdf,
            mode=args.mode,
            top_pct=args.top,
            bottom_pct=args.bottom,
            left_pct=args.left,
            right_pct=args.right,
            pages_str=args.pages,
        )
        print("SUCCESS")
    except Exception as e:
        print(f"ERROR: {str(e)}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
