import os

def replace_in_file(filepath):
    try:
        with open(filepath, 'r', encoding='utf-8') as file:
            content = file.read()
    except UnicodeDecodeError:
        # Skip binary files if any
        return

    original = content
    # Order matters: replace longer forms first
    content = content.replace('perijinan', 'perizinan')
    content = content.replace('Perijinan', 'Perizinan')
    content = content.replace('ijin', 'izin')
    content = content.replace('Ijin', 'Izin')
    content = content.replace('IJIN', 'IZIN')

    if content != original:
        with open(filepath, 'w', encoding='utf-8') as file:
            file.write(content)
        print(f"Updated: {filepath}")

def main():
    paths_to_scan = [
        "/home/fauzanms/Documents/absenku/app",
        "/home/fauzanms/Documents/absenku/resources"
    ]

    for base_path in paths_to_scan:
        for root, dirs, files in os.walk(base_path):
            for file in files:
                ext = os.path.splitext(file)[1].lower()
                if ext in ['.php', '.css', '.html', '.js']:
                    filepath = os.path.join(root, file)
                    replace_in_file(filepath)

if __name__ == "__main__":
    main()
