"""Crop SuperSky mark from brand PDF and stamp Crew instead of tech."""

from pathlib import Path

import pymupdf
from PIL import Image, ImageDraw, ImageFont

SRC = Path(r"c:\Users\zoran.havriluk\AppData\Local\Temp\SuperSkyTech logo finaln2i.pdf")
OUT = Path(__file__).resolve().parent
ZOOM = 5
# Lime lockup on page 0 (PDF points), skipping the selection checkbox.
CELL = pymupdf.Rect(338, 218, 482, 348)
SPHERE = pymupdf.Rect(371, 232, 437, 300)


def load_font(size: float) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    for candidate in (
        r"C:\Windows\Fonts\segoeuib.ttf",
        r"C:\Windows\Fonts\seguisb.ttf",
        r"C:\Windows\Fonts\arialbd.ttf",
        r"C:\Windows\Fonts\arial.ttf",
    ):
        path = Path(candidate)
        if path.exists():
            return ImageFont.truetype(str(path), size=size)
    return ImageFont.load_default()


def white_to_transparent(image: Image.Image, threshold: int = 245) -> Image.Image:
    rgba = image.convert("RGBA")
    pixels = rgba.load()
    width, height = rgba.size
    for y in range(height):
        for x in range(width):
            r, g, b, a = pixels[x, y]
            if r >= threshold and g >= threshold and b >= threshold:
                pixels[x, y] = (r, g, b, 0)
    return rgba


def main() -> None:
    doc = pymupdf.open(SRC)
    page = doc[0]
    mat = pymupdf.Matrix(ZOOM, ZOOM)

    lockup_pix = page.get_pixmap(matrix=mat, clip=CELL, alpha=False)
    lockup_path = OUT / "superskycrew-lockup.png"
    lockup_pix.save(lockup_path)

    image = Image.open(lockup_path).convert("RGBA")
    draw = ImageDraw.Draw(image)
    width, height = image.size
    # Cover only the "tech" letters, not the SUPERSKY baseline.
    cover = (
        int(width * 0.30),
        int(height * 0.798),
        int(width * 0.70),
        int(height * 0.93),
    )
    draw.rectangle(cover, fill=(255, 255, 255, 255))
    font = load_font(max(22, int(height * 0.052)))
    text = "Crew"
    bbox = draw.textbbox((0, 0), text, font=font)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    tx = (width - tw) / 2
    ty = int(height * 0.812) - bbox[1]
    draw.text((tx, ty), text, font=font, fill=(43, 42, 41, 255))
    image.save(lockup_path)
    print("lockup", image.size, lockup_path)

    sphere_pix = page.get_pixmap(matrix=mat, clip=SPHERE, alpha=False)
    sphere_tmp = OUT / "supersky-mark-tmp.png"
    sphere_pix.save(sphere_tmp)
    sphere = white_to_transparent(Image.open(sphere_tmp))
    sphere_path = OUT / "supersky-mark.png"
    sphere.save(sphere_path)
    sphere_tmp.unlink(missing_ok=True)
    print("mark", sphere.size, sphere_path)


if __name__ == "__main__":
    main()
