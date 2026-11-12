#!/usr/bin/env python
"""One-off: generate PWA icons (amber pin on espresso). Run from project root."""
from PIL import Image, ImageDraw

AMBER = (243, 156, 18)
ESPRESSO = (92, 56, 6)


def draw(size: int, pad_ratio: float, background: bool) -> Image.Image:
    img = Image.new("RGBA", (size, size), ESPRESSO if background else (0, 0, 0, 0))
    d = ImageDraw.Draw(img)
    pad = size * pad_ratio
    r = size * 0.28
    cx = cy = size / 2
    d.ellipse([cx - r, cy - r - size * 0.06, cx + r, cy + r - size * 0.06], fill=AMBER)
    d.polygon(
        [
            (cx - r * 0.55, cy + r * 0.62),
            (cx + r * 0.55, cy + r * 0.62),
            (cx, cy + r * 1.5),
        ],
        fill=AMBER,
    )
    hole = size * 0.10
    d.ellipse(
        [cx - hole, cy - hole - size * 0.06, cx + hole, cy + hole - size * 0.06],
        fill=ESPRESSO,
    )
    return img


for size, background in ((192, True), (512, True), (72, False)):
    icon = draw(size, 0.16, background)
    name = f"public/icons/{'badge-72x72' if size == 72 else f'icon-{size}x{size}'}.png"
    icon.save(name)
    print(name)
