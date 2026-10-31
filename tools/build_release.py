"""Build the assets consumed by FreeScout's native module updater."""

import argparse
import json
from pathlib import Path
import re
import zipfile


ROOT = Path(__file__).resolve().parents[1]
MODULE = "CustomerSystems"


def build(source: Path, output: Path, tag: str, repository: str) -> None:
    if not re.fullmatch(r"[0-9]+\.[0-9]+\.[0-9]+", tag):
        raise ValueError("Tag must be X.Y.Z without a v prefix")
    if not re.fullmatch(r"[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+", repository):
        raise ValueError("Repository must be owner/repo")
    manifest = json.loads((source / "module.json").read_text(encoding="utf-8"))
    if manifest["version"] != tag:
        raise ValueError(f"Tag {tag} does not match module.json version {manifest['version']}")
    if manifest["alias"] != "customersystems":
        raise ValueError("Unexpected module alias")
    base = f"https://github.com/{repository}"
    manifest.update({
        "detailsUrl": base,
        "latestVersionUrl": f"{base}/releases/latest/download/version.txt",
        "latestVersionZipUrl": f"{base}/releases/latest/download/{MODULE}.zip",
    })
    # Only package the FreeScout module, never the repository root or Shopware plugin.
    files = []
    for path in sorted(source.rglob("*")):
        if path.is_symlink():
            raise ValueError(f"Symlinks are not release assets: {path}")
        if not path.is_file():
            continue
        relative = path.relative_to(source)
        if any(part.startswith(".") or part == "__pycache__" for part in relative.parts):
            raise ValueError(f"Hidden/local files are not release assets: {relative}")
        files.append((path, f"{MODULE}/{relative.as_posix()}"))
    # Validate first so a bad tag cannot overwrite existing assets.
    output.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(output / f"{MODULE}.zip", "w", zipfile.ZIP_DEFLATED) as archive:
        for path, name in files:
            if path.name == "module.json" and path.parent == source:
                archive.writestr(name, json.dumps(manifest, indent=4, ensure_ascii=False) + "\n")
            else:
                archive.write(path, name)
    (output / "version.txt").write_text(tag + "\n", encoding="ascii")


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--tag", required=True)
    parser.add_argument("--repository", required=True)
    parser.add_argument("--output", type=Path, default=ROOT / "dist")
    args = parser.parse_args()
    try:
        build(ROOT / "Modules" / MODULE, args.output, args.tag, args.repository)
    except (ValueError, KeyError) as error:
        parser.exit(1, f"Release validation failed: {error}\n")
    print(f"Built {args.output / (MODULE + '.zip')} and version.txt for {args.repository} {args.tag}")


if __name__ == "__main__":
    main()
