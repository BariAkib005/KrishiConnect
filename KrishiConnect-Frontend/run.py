import os
import subprocess
import sys


def find_php_executable() -> str:
    xampp_php = r"C:\xampp\php\php.exe"
    if os.path.isfile(xampp_php):
        return xampp_php
    return "php"


def main() -> int:
    project_root = os.path.abspath(os.path.dirname(__file__))
    php_exe = find_php_executable()

    host = os.environ.get("KRISHI_HOST", "localhost")
    port = os.environ.get("KRISHI_PORT", "8000")
    url = f"http://{host}:{port}/index.php"

    print("Starting PHP dev server...")
    print(f"Project root: {project_root}")
    print(f"URL: {url}")
    print("Press Ctrl+C to stop.")

    try:
        return subprocess.call([php_exe, "-S", f"{host}:{port}", "-t", project_root])
    except FileNotFoundError:
        print("Error: PHP executable not found.")
        print("Install PHP or use XAMPP, or update your PATH.")
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
