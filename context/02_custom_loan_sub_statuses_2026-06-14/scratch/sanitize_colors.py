import re

def sanitize_content(content):
    # Mapping of non-standard tailwind zinc/emerald colors to closest standard colors
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
    
    for old, new in replacements.items():
        content = content.replace(old, new)
    return content

files = [
    r"c:\xampp\htdocs\nfuh-dmv-system\resources\views\members\partials\form.blade.php",
    r"c:\xampp\htdocs\nfuh-dmv-system\resources\views\members\partials\table.blade.php",
    r"c:\xampp\htdocs\nfuh-dmv-system\resources\views\layouts\app.blade.php",
    r"c:\xampp\htdocs\nfuh-dmv-system\resources\views\members\create.blade.php",
    r"c:\xampp\htdocs\nfuh-dmv-system\resources\views\members\edit.blade.php",
]

for filepath in files:
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            original = f.read()
        sanitized = sanitize_content(original)
        if original != sanitized:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(sanitized)
            print(f"Sanitized: {filepath}")
        else:
            print(f"No changes needed: {filepath}")
    except Exception as e:
        print(f"Error {filepath}: {e}")
