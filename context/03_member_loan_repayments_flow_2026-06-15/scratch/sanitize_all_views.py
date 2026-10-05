import os
import re

replacements = {
    'zinc-955': 'zinc-950',
    'zinc-905': 'zinc-900',
    'zinc-855': 'zinc-800',
    'zinc-850': 'zinc-800',
    'zinc-655': 'zinc-600',
    'zinc-650': 'zinc-600',
    'zinc-605': 'zinc-600',
    'zinc-350': 'zinc-300',
    'zinc-455': 'zinc-400',
    'emerald-255': 'emerald-200',
    'emerald-450': 'emerald-400',
    'emerald-850': 'emerald-800',
    'red-850': 'red-800',
}

views_dir = r"c:\xampp\htdocs\nfuh-dmv-system\resources\views"

def sanitize_file(filepath):
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        for old, new in replacements.items():
            content = content.replace(old, new)
            
        if original != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Sanitized: {filepath}")
    except Exception as e:
        print(f"Error processing {filepath}: {e}")

for root, dirs, files in os.walk(views_dir):
    for file in files:
        if file.endswith('.blade.php'):
            sanitize_file(os.path.join(root, file))
