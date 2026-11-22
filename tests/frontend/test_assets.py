"""Testa CSS, JS e navegação nos dois modos: Nginx / e Live Server /frontend/."""
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urljoin, urlsplit, unquote
import re
import unittest

ROOT = Path(__file__).resolve().parents[2] / "frontend"


class PageParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.references = []
        self.scripts = []

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if tag == "script" and values.get("src"):
            self.references.append(values["src"])
            self.scripts.append(values["src"])
        elif tag == "link" and "stylesheet" in values.get("rel", "").split():
            self.references.append(values.get("href", ""))
        elif tag == "a" and values.get("href"):
            self.references.append(values["href"])


def is_local_reference(value):
    if not value or value.startswith(("#", "//")):
        return False
    parsed = urlsplit(value)
    return not parsed.scheme and not parsed.netloc and bool(parsed.path)


def resolve(page, url):
    if not is_local_reference(url):
        return None
    path = unquote(urlsplit(url).path)
    return ROOT / path.lstrip("/") if path.startswith("/") else page.parent / path


class FrontendAssetsTest(unittest.TestCase):
    def test_every_page_resolves_css_js_and_links_on_both_servers(self):
        pages = sorted(ROOT.rglob("*.html"))
        self.assertGreater(len(pages), 10)
        checked = 0

        for page in pages:
            relative_page = page.relative_to(ROOT).as_posix()
            parser = PageParser()
            parser.feed(page.read_text(encoding="utf-8"))

            for url in parser.references:
                if not is_local_reference(url):
                    continue
                with self.subTest(page=relative_page, url=url):
                    self.assertFalse(
                        url.startswith("/"),
                        "Caminho absoluto não funciona no Live Server /frontend/"
                    )
                    for mount in ("/", "/frontend/"):
                        base = "http://localhost:8082" + mount + relative_page
                        resolved_path = unquote(urlsplit(urljoin(base, url)).path)
                        self.assertTrue(
                            resolved_path.startswith(mount),
                            f"Escapou do prefixo {mount}: {resolved_path}"
                        )
                        disk_path = (ROOT / resolved_path[len(mount):]).resolve()
                        self.assertTrue(
                            disk_path.is_relative_to(ROOT.resolve()) and disk_path.is_file(),
                            f"Recurso ausente em {mount}: {disk_path}"
                        )
                    checked += 1
        self.assertGreater(checked, 40)

    def test_private_pages_load_session_and_have_logout(self):
        for page in (ROOT / "pages").rglob("*.html"):
            parser = PageParser()
            source = page.read_text(encoding="utf-8")
            parser.feed(source)
            if not any(urlsplit(src).path.endswith("/session.js") for src in parser.scripts):
                continue
            with self.subTest(page=page.name):
                self.assertIn("data-logout", source)
                self.assertIn("common.js", source)
                self.assertLess(source.index("common.js"), source.index("session.js"))

    def test_css_imported_local_files_exist(self):
        for css in (ROOT / "assets/css").rglob("*.css"):
            for token in re.findall(r'url\(\s*["\']?([^\)"\']+)', css.read_text(encoding="utf-8")):
                path = resolve(css, token.strip())
                if path is not None:
                    with self.subTest(css=css.name, url=token):
                        self.assertTrue(path.is_file(), f"Recurso do CSS ausente: {path}")


if __name__ == "__main__":
    unittest.main()
