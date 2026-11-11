#!/usr/bin/env python3
"""
Compare les styles détectés par scripts/detect_inline_style.py (JSON)
avec les règles des fichiers .css du projet (/home/max/Déclic),
tout en résumant les styles JS et les couleurs codées en dur par fichier.

Usage :
    python3 scripts/compare_inline_css.py /tmp/inline_styles.json
    python3 scripts/compare_inline_css.py /tmp/inline_styles.json /home/max/Déclic -o /tmp/compare_css.json
"""

import argparse
import html
import json
import os
import re
import sys
from bisect import bisect_right
from collections import Counter, defaultdict

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DEFAULT_ROOT = os.path.dirname(SCRIPT_DIR)

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

RE_COMMENT = re.compile(r"/\*.*?\*/", re.DOTALL)
RE_STYLE_ATTR = re.compile(r"""\bstyle\s*=\s*(["'])(.*?)\1""", re.IGNORECASE | re.DOTALL)
RE_STYLE_TAG = re.compile(r"<style\b[^>]*>(.*?)</style\s*>", re.IGNORECASE | re.DOTALL)
RE_TOKEN = re.compile(r"[{};]")
RE_BRACES = re.compile(r"[{}]")

DYNAMIC_MARKERS = ("$", "{{", "{%", "<?", "?>", "' .", ". '", '" .', '. "')
NESTED_AT_RULES = ("@media", "@supports", "@layer", "@container", "@document")
RANK = {"identique": 0, "inclus": 1, "partiel": 2}


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


def is_backup_or_minified(name):
    lower = name.lower()
    if lower in BACKUP_DIR_NAMES:
        return True
    if lower.startswith(BACKUP_NAME_PREFIXES) or lower.endswith(BACKUP_NAME_SUFFIXES):
        return True
    if ".bak" in lower or ".min.css" in lower or ".min.js" in lower:
        return True
    return False


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


def collect_css(root, excluded, use_defaults=True):
    names, paths = build_exclusions(root, excluded, use_defaults)
    found = []
    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = sorted(
            d for d in dirnames
            if d not in names
            and not is_backup_or_minified(d)
            and os.path.abspath(os.path.join(dirpath, d)) not in paths
        )
        for name in sorted(filenames):
            if name.lower().endswith(".css") and not is_backup_or_minified(name):
                found.append(os.path.join(dirpath, name))
    return sorted(found)


def rel(path, base):
    try:
        return os.path.relpath(path, base)
    except ValueError:
        return path


def strip_comments(text):
    return RE_COMMENT.sub(lambda m: re.sub(r"[^\n]", " ", m.group(0)), text)


def split_declarations(block):
    parts, buf, depth, quote = [], [], 0, None
    for ch in block:
        if quote:
            buf.append(ch)
            if ch == quote:
                quote = None
        elif ch in "\"'":
            quote = ch
            buf.append(ch)
        elif ch == "(":
            depth += 1
            buf.append(ch)
        elif ch == ")":
            depth = max(0, depth - 1)
            buf.append(ch)
        elif ch == ";" and depth == 0:
            parts.append("".join(buf))
            buf = []
        else:
            buf.append(ch)
    parts.append("".join(buf))
    return parts


def normalize_value(value):
    value = re.sub(r"\s+", " ", value.strip().lower())
    value = re.sub(r"\s*,\s*", ",", value)
    value = re.sub(r"\(\s+", "(", value)
    value = re.sub(r"\s+\)", ")", value)
    value = re.sub(r"\s*!\s*important", "!important", value)
    return value


def parse_declarations(block):
    decls = set()
    for part in split_declarations(block):
        if ":" not in part:
            continue
        prop, value = part.split(":", 1)
        prop, value = prop.strip().lower(), normalize_value(value)
        if prop and value:
            decls.add(f"{prop}:{value}")
    return frozenset(decls)


def find_closing(text, open_pos):
    depth = 0
    for m in RE_BRACES.finditer(text, open_pos):
        depth += 1 if m.group() == "{" else -1
        if depth == 0:
            return m.start()
    return len(text) - 1


def parse_css(text):
    text = strip_comments(text)
    newlines = [m.start() for m in re.finditer("\n", text)]
    rules, context = [], []
    start = i = 0

    while True:
        m = RE_TOKEN.search(text, i)
        if not m:
            break
        pos, ch = m.start(), m.group()

        if ch == ";":
            start = i = pos + 1
        elif ch == "}":
            if context:
                context.pop()
            start = i = pos + 1
        else:
            prelude = text[start:pos]
            at = re.match(r"\s*(@[\w-]+)", prelude)
            if at and at.group(1).lower() in NESTED_AT_RULES:
                context.append(" ".join(prelude.split()))
                start = i = pos + 1
                continue
            end = find_closing(text, pos)
            if not at:
                selector = " ".join(prelude.split())
                decls = parse_declarations(text[pos + 1:end])
                if selector and decls:
                    sel_pos = start + len(prelude) - len(prelude.lstrip())
                    rules.append({
                        "selector": re.sub(r"\s*,\s*", ", ", selector),
                        "media": " ".join(context),
                        "decls": decls,
                        "line": bisect_right(newlines, sel_pos) + 1,
                    })
            start = i = end + 1

    return rules


def is_dynamic(value):
    return any(marker in value for marker in DYNAMIC_MARKERS)


def fmt(decls):
    pairs = (d.split(":", 1) for d in sorted(decls))
    return "; ".join(f"{p}: {v}" for p, v in pairs) + ";"


def match_style(decls, rules, index, min_ratio):
    k = len(decls)
    shared = Counter()
    for d in decls:
        shared.update(index.get(d, ()))

    found = []
    for rid, s in shared.items():
        r = len(rules[rid]["decls"])
        if s == k:
            kind = "identique" if r == k else "inclus"
        elif s >= 2 and s / k >= min_ratio:
            kind = "partiel"
        else:
            continue
        found.append((RANK[kind], -s, r - s, rid, kind, s))
    found.sort()
    return found


def load_inline(results, json_root):
    unique, blocks = {}, []
    dynamic = empty = js_styles = hardcoded_colors = 0
    color_files = Counter()

    for entry in results:
        file_rel = rel(entry["file"], json_root)
        for occ in entry["occurrences"]:
            where = f'{file_rel}:{occ["line"]}'
            content = occ["content"]
            occ_type = occ["type"]

            if occ_type == "attribut":
                m = RE_STYLE_ATTR.search(content)
                if not m:
                    continue
                value = html.unescape(strip_comments(m.group(2)))
                if is_dynamic(value):
                    dynamic += 1
                    continue
                decls = parse_declarations(value)
                if not decls:
                    empty += 1
                    continue
                item = unique.setdefault(decls, {"occ": 0, "found_in": []})
                item["occ"] += 1
                item["found_in"].append(where)
            elif occ_type == "balise <style>":
                m = RE_STYLE_TAG.search(content)
                if m:
                    blocks.append((where, parse_css(m.group(1))))
            elif occ_type in ("js .style", "js setAttribute(style)"):
                js_styles += 1
            elif occ_type == "couleur en dur":
                hardcoded_colors += 1
                color_files[file_rel] += 1

    return unique, blocks, dynamic, empty, js_styles, hardcoded_colors, color_files


def compare_blocks(blocks, rules):
    exact, by_selector = defaultdict(set), defaultdict(set)
    for rule in rules:
        exact[(rule["selector"], rule["decls"])].add(rule["file"])
        by_selector[rule["selector"]].add(rule["file"])

    out = []
    for where, block_rules in blocks:
        identical = same_selector = 0
        files = set()
        for br in block_rules:
            key = (br["selector"], br["decls"])
            if key in exact:
                identical += 1
                files |= exact[key]
            elif br["selector"] in by_selector:
                same_selector += 1
                files |= by_selector[br["selector"]]
        out.append({
            "where": where,
            "rules": len(block_rules),
            "identical_in_css": identical,
            "same_selector_different_declarations": same_selector,
            "css_files": sorted(files),
        })
    return out


def short(text, limit=100):
    return text if len(text) <= limit else text[:limit - 3] + "..."


def main():
    parser = argparse.ArgumentParser(description="Compare les styles détectés aux fichiers .css.")
    parser.add_argument("json_file", nargs="?", help="JSON produit par scripts/detect_inline_style.py")
    parser.add_argument("css_folder", nargs="?", help=f"Dossier où chercher les .css (défaut : root du JSON ou {DEFAULT_ROOT})")
    parser.add_argument("-o", "--output", default="compare_css.json",
                        help="Fichier JSON de sortie (défaut : compare_css.json)")
    parser.add_argument("-x", "--exclude", action="append", default=[], metavar="DOSSIER",
                        help="Dossier à exclure en plus des exclusions par défaut (répétable)")
    parser.add_argument("--no-default-excludes", action="store_true",
                        help="Désactive les exclusions par défaut")
    parser.add_argument("--min-ratio", type=float, default=0.5,
                        help="Part minimale de déclarations communes pour un match partiel (défaut : 0.5)")
    parser.add_argument("--top", type=int, default=3,
                        help="Nombre de règles proches gardées par style inline (défaut : 3)")
    args = parser.parse_args()

    json_file = args.json_file or input("Chemin du JSON : ").strip().strip('"')
    if not os.path.isfile(json_file):
        print(f"Erreur : '{json_file}' n'est pas un fichier valide.", file=sys.stderr)
        sys.exit(1)

    with open(json_file, "r", encoding="utf-8") as f:
        data = json.load(f)
    if "results" not in data:
        print("Erreur : ce JSON ne vient pas de detect_inline_style.py.", file=sys.stderr)
        sys.exit(1)

    json_root = data.get("root", "")
    css_root = args.css_folder or json_root or DEFAULT_ROOT
    if not os.path.isdir(css_root):
        print(f"Erreur : '{css_root}' n'est pas un dossier valide.", file=sys.stderr)
        sys.exit(1)

    css_paths = collect_css(css_root, args.exclude, use_defaults=not args.no_default_excludes)
    if not css_paths:
        print("Aucun fichier .css trouvé.", file=sys.stderr)
        sys.exit(1)

    rules, css_stats = [], {}
    for path in css_paths:
        text = read_file(path)
        if text is None:
            continue
        name = rel(path, css_root)
        parsed = parse_css(text)
        for r in parsed:
            r["file"] = name
        rules.extend(parsed)
        css_stats[name] = {
            "file": name,
            "rules": len(parsed),
            "identique": 0,
            "inclus": 0,
            "partiel": 0,
            "styles_covered": 0,
        }

    index = defaultdict(list)
    for rid, rule in enumerate(rules):
        for d in rule["decls"]:
            index[d].append(rid)

    unique, blocks, dynamic, empty, js_styles, hardcoded_colors, color_files = load_inline(data["results"], json_root)

    inline_out = []
    by_kind = Counter()
    for decls, item in unique.items():
        found = match_style(decls, rules, index, args.min_ratio)

        seen = set()
        for _, _, _, rid, kind, _ in found:
            fname = rules[rid]["file"]
            if fname in seen:
                continue
            seen.add(fname)
            css_stats[fname][kind] += item["occ"]
            css_stats[fname]["styles_covered"] += 1

        by_kind[found[0][4] if found else "aucun"] += item["occ"]
        inline_out.append({
            "style": fmt(decls),
            "occurrences": item["occ"],
            "found_in": item["found_in"],
            "matches": [{
                "match": kind,
                "css_file": rules[rid]["file"],
                "selector": rules[rid]["selector"],
                "media": rules[rid]["media"],
                "line": rules[rid]["line"],
                "shared_declarations": shared,
                "rule_declarations": len(rules[rid]["decls"]),
            } for _, _, _, rid, kind, shared in found[:args.top]],
        })
    inline_out.sort(key=lambda s: -s["occurrences"])

    blocks_out = compare_blocks(blocks, rules)
    css_out = sorted(css_stats.values(), key=lambda c: (-(c["identique"] + c["inclus"]), -c["partiel"], c["file"]))

    total_occ = sum(u["occ"] for u in unique.values())
    block_rules = sum(b["rules"] for b in blocks_out)
    block_identical = sum(b["identical_in_css"] for b in blocks_out)

    report = {
        "source_json": os.path.abspath(json_file),
        "css_root": os.path.abspath(css_root),
        "summary": {
            "css_files": len(css_stats),
            "css_rules": len(rules),
            "inline_occurrences_static": total_occ,
            "inline_unique_styles": len(unique),
            "inline_dynamic_ignored": dynamic,
            "inline_empty_ignored": empty,
            "js_inline_styles": js_styles,
            "hardcoded_color_lines": hardcoded_colors,
            "best_match_identique": by_kind["identique"],
            "best_match_inclus": by_kind["inclus"],
            "best_match_partiel": by_kind["partiel"],
            "no_match": by_kind["aucun"],
            "style_blocks": len(blocks_out),
            "style_block_rules": block_rules,
            "style_block_rules_identical_in_css": block_identical,
        },
        "css_files": css_out,
        "inline_styles": inline_out,
        "style_blocks": blocks_out,
        "hardcoded_color_files": [{"file": k, "lines": v} for k, v in color_files.most_common()],
    }
    with open(args.output, "w", encoding="utf-8") as f:
        json.dump(report, f, ensure_ascii=False, indent=2)

    print(f"Fichiers .css analysés        : {len(css_stats)} ({len(rules)} règles)")
    print(f"Styles inline (attributs)     : {total_occ} occurrences, {len(unique)} styles uniques")
    print(f"Ignorés (dynamiques / vides)  : {dynamic} / {empty}")
    print(f"  identique dans un .css      : {by_kind['identique']}")
    print(f"  inclus dans une règle       : {by_kind['inclus']}")
    print(f"  partiellement présent       : {by_kind['partiel']}")
    print(f"  aucun équivalent            : {by_kind['aucun']}")
    print(f"Blocs <style>                 : {len(blocks_out)} ({block_identical}/{block_rules} règles identiques à un .css)")
    print(f"Styles JS (.style / setAttr)  : {js_styles}")
    print(f"Lignes couleurs en dur (#/rgb): {hardcoded_colors}")

    if color_files:
        print("\nTop fichiers avec couleurs codées en dur :")
        for fname, cnt in color_files.most_common(10):
            print(f"  {cnt:>4} | {fname}")

    print("\nFichiers .css les plus proches des styles inline :")
    for c in css_out[:10]:
        print(f"  {c['file']} : {c['identique']} identique(s), {c['inclus']} inclus, {c['partiel']} partiel(s)")

    print(f"\nExport JSON                   : {os.path.abspath(args.output)}")


if __name__ == "__main__":
    main()
