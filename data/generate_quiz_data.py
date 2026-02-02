import json
import os
import re
import shutil

def scan_images(base_img_dir):
    """
    Scans the image directory recursively and returns a list of image objects.
    Replaces the functionality of generate_img_tree.ps1.
    """
    print(f"Scanning for images in {base_img_dir}...")
    images = []
    
    # Extensions to include
    extensions = ('.png', '.jpg', '.jpeg')
    
    # Walk through the directory
    for root, dirs, files in os.walk(base_img_dir):
        for file in files:
            if file.lower().endswith(extensions):
                full_path = os.path.join(root, file)
                
                # Calculate relative path (e.g. img/subdir/file.png)
                # The goal is to get a path relative to the project root (CLOUDS)
                # Assuming base_img_dir is "i:\My Drive\CLOUDS\img"
                # We want "img/subdir/file.png"
                
                # Get path relative to the parent of base_img_dir (i.e., CLOUDS root)
                project_root = os.path.dirname(base_img_dir)
                rel_path = os.path.relpath(full_path, project_root).replace('\\', '/')
                
                images.append({
                    "path": rel_path,
                    "name": file
                })
                
    print(f"Found {len(images)} images.")
    return images

def copy_pwa_icon(img_tree, base_dir):
    """
    Finds and copies the best PWA icon to quiz/icon.png.
    Based on build_data.py logic.
    """
    icon_dest = os.path.join(base_dir, 'quiz', 'icon.png')
    best_icon = None
    
    for img in img_tree:
        name = img['name']
        if "AWS-Cloud" in name and "logo" not in name: # Avoid text logo
            if "@5x" in name:
                best_icon = img
                break
            if "64" in name and not best_icon:
                best_icon = img
    
    if best_icon:
        # Construct absolute source path
        src_path = os.path.join(base_dir, best_icon['path'])
        print(f"Copying icon from {src_path} to {icon_dest}")
        try:
            shutil.copy2(src_path, icon_dest)
            print("Icon copied successfully.")
        except Exception as e:
            print(f"Failed to copy icon: {e}")
    else:
        print("No suitable PWA icon found.")

def load_json(path):
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)

def find_images(service_name, img_tree):
    """
    Finds relevant images for a service name.
    Prioritizes 'Arch_Amazon-{Service}_64.png'.
    """
    matches = []
    
    # Normalize service name for matching (e.g. "EC2" -> "ec2")
    # But file names are like "Arch_Amazon-EC2_64.png"
    
    # specific fix for known patterns if needed
    search_term = service_name.replace(" ", "-")
    
    # Regex pattern to match typical AWS icon names
    # We look for the service name surrounded by delimiters
    pattern = re.compile(f".*[-_]{re.escape(search_term)}[-_].*", re.IGNORECASE)
    
    for img in img_tree:
        name = img['name']
        path = img['path']
        
        if search_term.lower() in name.lower():
             # basic filter
             
             # Priority 1: Exact match structure for Architecture icons
             # e.g. Arch_Amazon-EC2_64.png
             if f"Arch_Amazon-{search_term}_64.png" in name:
                 matches.append({"type": "icon", "size": "64", "path": path, "score": 100})
                 continue
             
             if f"Arch_Amazon-{search_term}_32.png" in name:
                 matches.append({"type": "icon", "size": "32", "path": path, "score": 90})
                 continue

             # Skip 16px images as requested
             if f"Arch_Amazon-{search_term}_16.png" in name:
                 continue

             # Priority 2: Resource icons
             if f"Res_Amazon-{search_term}" in name and "_48.png" in name:
                 matches.append({"type": "resource", "size": "48", "path": path, "score": 80})
                 continue
                 
             # Fallback
             matches.append({"type": "other", "path": path, "score": 10})
    
    # Sort by score desc
    matches.sort(key=lambda x: x['score'], reverse=True)
    return matches

def main():
    base_dir = r"i:\My Drive\CLOUDS"
    data_dir = os.path.join(base_dir, "data") # Script location
    aws_json_path = os.path.join(base_dir, "quiz", "aws.json")
    
    # Image directory scan
    img_dir = os.path.join(base_dir, "img")
    
    # If aws.json is not in quiz/, check data/ (legacy path support)
    if not os.path.exists(aws_json_path):
        aws_json_path = os.path.join(data_dir, "aws.json")
        
    output_path = os.path.join(base_dir, "quiz", "quiz_data.json")

    print(f"Loading services from {aws_json_path}...")
    services = load_json(aws_json_path)
    
    # Replaces loading img.tree.json
    # print(f"Loading {img_tree_path}...")
    # images = load_json(img_tree_path)
    
    # Perform scan directly
    images = scan_images(img_dir)
    
    # Optional: Save img.tree.json for debug or legacy purposes
    img_tree_output = os.path.join(base_dir, "img", "img.tree.json")
    # Ensure directory exists
    os.makedirs(os.path.dirname(img_tree_output), exist_ok=True)
    with open(img_tree_output, 'w', encoding='utf-8') as f:
        json.dump(images, f, indent=2, ensure_ascii=False)
    print(f"Saved image tree to {img_tree_output} (legacy support)")
    
    new_data = []
    
    for svc in services:
        name = svc.get('service')
        print(f"Processing {name}...")
        
        found_images = find_images(name, images)
        
        # Select best image (top 1 for now, or all)
        # We'll store the simplified list
        selected_images = []
        if found_images:
            # deduplicate by path
            seen_paths = set()
            for img in found_images:
                if img['path'] not in seen_paths:
                    selected_images.append(img)
                    seen_paths.add(img['path'])
        
        item = {
            "id": f"aws-{name.lower().replace(' ', '-')}",
            "name": name,
            "category": svc.get('genre'),
            "description": {
                "en": svc.get('description_en'),
                "ja": svc.get('description_ja')
            },
            "images": selected_images,
            "meta": {
                "free_tier": svc.get('free_tier')
            }
        }
        new_data.append(item)
        
    print(f"Saving to {output_path}...")
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(new_data, f, indent=2, ensure_ascii=False)
    
    # Copy PWA Icon
    copy_pwa_icon(images, base_dir)

    print("Done.")

if __name__ == "__main__":
    main()
