#!/usr/bin/env python3
"""
Parcourt récursivement le projet (par défaut /home/max/Déclic) et détecte :
  - les attributs style="..." / style='...' (.php, .tpl, .html, .htm, .js)
  - les blocs <style>...</style> (.php, .tpl, .html, .htm, .js)
  - les affectations JS .style.<prop> = ... / setAttribute('style', ...) (.js)
  - les couleurs codées en dur (#hex, rgb/rgba/hsl/hsla) (.php, .tpl, .html, .htm, .js, .css)

Contrairement à l'ancien script, ne filtre pas les dossiers "template/" ni "templates/",
couvre toutes les extensions susceptibles de contenir du style (.tpl, .html, .htm, .php, .js, .css)
et exclut par défaut data/, vendor/, node_modules/, .git/ et plugin/tinymce/.

Usage :
    python3 scripts/detect_inline_style.py
    python3 scripts/detect_inline_style.py /home/max/Déclic -o /tmp/inline_styles.json
    python3 scripts/detect_inline_style.py /home/max/Déclic/plugin -x blog
"""

import argparse
import json
import os
import re
import sys

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DEFAULT_ROOT = os.path.dirname(SCRIPT_DIR)

EXTENSIONS = (".php", ".tpl", ".html", ".htm", ".js", ".css")
MARKUP_EXTENSIONS = (".php", ".tpl", ".html", ".htm")

DEFAULT_EXCLUDED = {
    ".git",
    "node_modules",
    "vendor",
    "data",
    "cache",
    "plugin/tinymce",
    "plugin_back",
    "common/vendor",
}

BACKUP_DIR_NAMES = {"tmp", "temp", "backup", "backups", "old", "plugin_back"}
BACKUP_NAME_PREFIXES = ("tmp_", "temp_")
BACKUP_NAME_SUFFIXES = (".bak", ".orig", ".old", "_back", "_backup", "~")

RE_STYLE_ATTR = re.compile(r"""\bstyle\s*=\s*(["'])(.*?)\1""", re.IGNORECASE | re.DOTALL)
RE_STYLE_TAG = re.compile(r"<style\b[^>]*>(.*?)</style\s*>", re.IGNORECASE | re.DOTALL)
RE_JS_STYLE_PROP = re.compile(r"""\.style\.[a-zA-Z_$][\w$]*\s*=\s*[^;\n]+""", re.MULTILINE)
RE_JS_SET_STYLE = re.compile(r"""\.setAttribute\(\s*(["'])style\1\s*,[^)]+\)""", re.IGNORECASE)
RE_COLOR_LITERAL = re.compile(
    r"""(?<![&\w])(?:#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})\b|(?:rgba?|hsla?)\([^)]+\))""",
    re.IGNORECASE,
)


def is_backup_or_minified(name):
    lower = name.lower()
    if lower in BACKUP_DIR_NAMES:
        return True
    if lower.startswith(BACKUP_NAME_PREFIXES) or lower.endswith(BACKUP_NAME_SUFFIXES):
        return True
    if ".bak" in lower or ".min.css" in lower or ".min.js" in lower:
        return True
    return False


def read_file(path):
    for enc in ("utf-8", "latin-1"):
        try:
            with open(path, "r", encoding=enc) as f:
                return f.read()
        except UnicodeDecodeError:
            continue
        except OSError as e:
            print(f"[!] Impossible de lire {path} : {e}", file=sys.stderr)
            return None
    return None


def line_number(content, pos):
    return content.count("\n", 0, pos) + 1


def find_styles(content, ext, rel_path):
    results = []

    if ext in MARKUP_EXTENSIONS or ext == ".js":
        for m in RE_STYLE_ATTR.finditer(content):
            if m.group(2).strip():
                results.append((line_number(content, m.start()), "attribut", m.group(0)))

        for m in RE_STYLE_TAG.finditer(content):
            results.append((line_number(content, m.start()), "balise <style>", m.group(0)))

    if ext == ".js":
        for m in RE_JS_STYLE_PROP.finditer(content):
            results.append((line_number(content, m.start()), "js .style", m.group(0).strip()))
        for m in RE_JS_SET_STYLE.finditer(content):
            results.append((line_number(content, m.start()), "js setAttribute(style)", m.group(0).strip()))

    # Détection des couleurs codées en dur (hors fichier socle ui-tokens.css qui déclare les tokens)
    if rel_path.replace("\\", "/") != "common/css/ui-tokens.css":
        for idx, line_text in enumerate(content.splitlines(), start=1):
            stripped = line_text.strip()
            if not stripped or stripped.startswith(("/*", "*", "//", "#")):
                continue
            if ext == ".css" and "var(--evo-" in line_text:
                continue
            # Ignore inline SVG data URI or regex/PHP hex patterns where possible
            if "data:image/" in line_text or "preg_" in line_text:
                continue
            matches = RE_COLOR_LITERAL.findall(line_text)
            if matches:
                # Dans le markup, ne duplique pas si déjà dans un style="..." capturé sur la même ligne
                if ext == ".css" or ("style=" not in line_text.lower() and ext != ".css"):
                    results.append((idx, "couleur en dur", f"{', '.join(matches)} -> {stripped[:120]}"))

    results.sort(key=lambda r: (r[0], r[1]))
    return results


def build_exclusions(root, excluded, use_defaults=True):
    root_abs = os.path.abspath(root)
    names = set()
    paths = set()
    items = list(DEFAULT_EXCLUDED if use_defaults else []) + list(excluded)

    for e in items:
        if os.path.isabs(e) and os.path.abspath(e).startswith(root_abs + os.sep):
            paths.add(os.path.abspath(e))
            continue
        e = e.strip("/")
        if not e:
            continue
        if "/" in e:
            paths.add(os.path.normpath(os.path.join(root_abs, e)))
        else:
            names.add(e)

    return names, paths


def scan(root, excluded=(), use_defaults=True):
    root_abs = os.path.abspath(root)
    files_scanned = 0
    files_with_style = []
    by_ext = {}
    excluded_names, excluded_paths = build_exclusions(root_abs, excluded, use_defaults)

    for dirpath, dirnames, filenames in os.walk(root_abs):
        dirnames[:] = sorted(
            d for d in dirnames
            if d not in excluded_names
            and not is_backup_or_minified(d)
            and os.path.abspath(os.path.join(dirpath, d)) not in excluded_paths
        )

        for name in sorted(filenames):
            lower = name.lower()
            ext = os.path.splitext(lower)[1]
            if ext not in EXTENSIONS:
                continue
            if is_backup_or_minified(name):
                continue

            path = os.path.join(dirpath, name)
            content = read_file(path)
            if content is None:
                continue

            files_scanned += 1
            by_ext[ext] = by_ext.get(ext, 0) + 1
            rel_path = os.path.relpath(path, root_abs)
            found = find_styles(content, ext, rel_path)
            if found:
                files_with_style.append((path, found))

    return files_scanned, by_ext, files_with_style


def export_json(output, root, files_scanned, by_ext, files_with_style):
    total = sum(len(f) for _, f in files_with_style)
    data = {
        "root": os.path.abspath(root),
        "extensions": list(EXTENSIONS),
        "files_scanned": files_scanned,
        "files_by_extension": by_ext,
        "files_with_inline_style": len(files_with_style),
        "total_occurrences": total,
        "results": [
            {
                "file": path,
                "count": len(found),
                "occurrences": [
                    {"line": line, "type": kind, "content": content}
                    for line, kind, content in found
                ],
            }
            for path, found in files_with_style
        ],
    }
    with open(output, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)


def main():
    parser = argparse.ArgumentParser(
        description="Détecte les styles inline et couleurs codées en dur (.php, .tpl, .html, .htm, .js, .css)."
    )
    parser.add_argument("folder", nargs="?", default=DEFAULT_ROOT,
                        help=f"Dossier à analyser (défaut : {DEFAULT_ROOT})")
    parser.add_argument("-o", "--output", default="inline_styles.json",
                        help="Fichier JSON de sortie (défaut : inline_styles.json)")
    parser.add_argument("-x", "--exclude", action="append", default=[], metavar="DOSSIER",
                        help="Dossier à exclure en plus des exclusions par défaut (répétable)")
    parser.add_argument("--no-default-excludes", action="store_true",
                        help="Désactive les exclusions par défaut (data, vendor, tinymce...)")
    parser.add_argument("-q", "--quiet", action="store_true",
                        help="Affiche uniquement le résumé global sans détailler chaque ligne")
    args = parser.parse_args()

    if not os.path.isdir(args.folder):
        print(f"Erreur : '{args.folder}' n'est pas un dossier valide.", file=sys.stderr)
        sys.exit(1)

    files_scanned, by_ext, files_with_style = scan(
        args.folder, args.exclude, use_defaults=not args.no_default_excludes
    )

    if not args.quiet:
        for path, found in files_with_style:
            rel_path = os.path.relpath(path, os.path.abspath(args.folder))
            print(f"\n{rel_path}  ({len(found)} occurrence(s))")
            for line, kind, full_text in found:
                print(f"  ligne {line} | {kind}")
                for part in full_text.strip().splitlines()[:6]:
                    print(f"      {part.rstrip()}")

    by_kind = {}
    for _, found in files_with_style:
        for _, kind, _ in found:
            by_kind[kind] = by_kind.get(kind, 0) + 1

    total = sum(len(f) for _, f in files_with_style)
    print("\n" + "=" * 60)
    print(f"Dossier analysé            : {os.path.abspath(args.folder)}")
    print(f"Fichiers analysés          : {files_scanned} ({', '.join(f'{k}:{v}' for k, v in sorted(by_ext.items()))})")
    print(f"Fichiers concernés         : {len(files_with_style)}")
    print(f"Occurrences totales        : {total}")
    for kind, count in sorted(by_kind.items()):
        print(f"  - {kind:<22} : {count}")

    export_json(args.output, args.folder, files_scanned, by_ext, files_with_style)
    print(f"Export JSON                : {os.path.abspath(args.output)}")


if __name__ == "__main__":
    main()
