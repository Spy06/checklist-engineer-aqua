import re

with open('resources/views/filament/pages/matrix-checklist.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Remove vite directive if exists
content = content.replace("    @vite('resources/css/app.css')\n", "")

# Replace colors to Filament standard colors
content = content.replace('zinc-', 'gray-')
content = content.replace('cyan-', 'primary-')
content = content.replace('emerald-', 'success-')
content = content.replace('amber-', 'warning-')
content = content.replace('red-', 'danger-')

# Replace flux icon
content = content.replace('<flux:icon name="table-cells" class="w-3.5 h-3.5" />', '<x-heroicon-o-table-cells style="width: 14px; height: 14px;" />')

# Add inline styles for specific widths/heights on SVG to prevent huge icons in case Filament JIT purged them
def svg_style_replacer(match):
    cls = match.group(0)
    # Parse w-X and h-Y
    w_match = re.search(r'w-([0-9\.]+)', cls)
    h_match = re.search(r'h-([0-9\.]+)', cls)
    if w_match and h_match:
        w = float(w_match.group(1)) * 0.25
        h = float(h_match.group(1)) * 0.25
        return f'{cls}" style="width: {w}rem; height: {h}rem;'
    return cls

content = re.sub(r'class="[^"]*(w-[0-9\.]+\s+h-[0-9\.]+|h-[0-9\.]+\s+w-[0-9\.]+)[^"]*', svg_style_replacer, content)

with open('resources/views/filament/pages/matrix-checklist.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Done.")
