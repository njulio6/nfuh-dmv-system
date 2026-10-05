import re

file_path = r"c:\xampp\htdocs\nfuh-dmv-system\resources\views\loans\member_applications.blade.php"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# Replace zinc-955 with zinc-950
modified = content.replace("zinc-955", "zinc-950")

with open(file_path, "w", encoding="utf-8") as f:
    f.write(modified)

print("Done! Replaced zinc-955 with zinc-950.")
