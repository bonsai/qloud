import os
import random
import glob

def main():
    root_dir = r"c:\Users\dance\zone\icons"
    # Supported image extensions
    extensions = ['*.png', '*.jpg', '*.jpeg', '*.svg']
    image_files = []

    # Gather all image files
    for ext in extensions:
        # Recursive glob
        found = glob.glob(os.path.join(root_dir, '**', ext), recursive=True)
        image_files.extend(found)

    print(f"Total images found: {len(image_files)}")

    if not image_files:
        print("No images found.")
        return

    # Select 2 random images (or fewer if not enough)
    count = min(len(image_files), 2)
    selected_images = random.sample(image_files, count)

    # Generate HTML
    html_content = """<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Images</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }
        h1 { color: #333; }
        .gallery {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 2rem;
        }
        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
        }
        img {
            max-width: 100%;
            height: auto;
            max-height: 300px;
            margin-bottom: 1rem;
        }
        .filename {
            font-weight: bold;
            color: #555;
            word-break: break-all;
        }
        .path {
            font-size: 0.8rem;
            color: #888;
            margin-top: 0.5rem;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <h1>Randomly Selected Images</h1>
    <div class="gallery">
"""

    for img_path in selected_images:
        filename = os.path.basename(img_path)
        # Use relative path for the image source
        try:
            rel_path = os.path.relpath(img_path, root_dir)
        except ValueError:
            rel_path = img_path

        html_content += f"""
        <div class="card">
            <img src="{rel_path}" alt="{filename}">
            <div class="filename">{filename}</div>
            <div class="path">{rel_path}</div>
        </div>"""

    html_content += """
    </div>
</body>
</html>"""

    output_path = os.path.join(root_dir, "random_images.html")
    with open(output_path, "w", encoding="utf-8") as f:
        f.write(html_content)

    print(f"HTML generated: {output_path}")
    for img in selected_images:
        print(f"Selected: {img}")

if __name__ == "__main__":
    main()
