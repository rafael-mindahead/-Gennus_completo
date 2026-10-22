"""Checa os caminhos estáticos que o Nginx resolve a partir de frontend/."""
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit, unquote
import re
import unittest

ROOT = Path(__file__).resolve().parents[2] / "frontend"


class AssetParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.assets = []

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if tag == "script" and values.get("src"):
            self.assets.append(values["src"])
        if tag == "link" and "stylesheet" in values.get("rel", "").split():
            self.assets.append(values.get("href", ""))


def resolve(page, url):
    if not url or url.startswith(("#", "//")):
        return None
    parsed = urlsplit(url)
    if parsed.scheme or parsed.netloc:
        return None
    url_path = unquote(parsed.path)
    if not url_path:
        return None
    if url_path.startswith("/"):
        return ROOT / url_path.lstrip("/")
    return page.parent / url_path


class FrontendAssetsTest(unittest.TestCase):
    def test_all_local_stylesheets_and_scripts_exist(self):
        pages = list(ROOT.rglob("*.html"))
        self.assertTrue(pages, "Sem páginas HTML no frontend")
        checked = 0
        for page in pages:
            parser = AssetParser()
            parser.feed(page.read_text(encoding="utf-8"))
            for url in parser.assets:
                path = resolve(page, url)
                if path is not None:
                    with self.subTest(page=str(page.relative_to(ROOT)), url=url):
                        self.assertTrue(path.is_file(), f"Recurso não encontrado: {path}")
                    checked += 1
        self.assertGreater(checked, 0)

    def test_session_pages_have_explicit_logout(self):
        for page in (ROOT / "pages").rglob("*.html"):
            html = page.read_text(encoding="utf-8")
            if '/assets/js/session.js' not in html:
                continue
            with self.subTest(page=str(page.relative_to(ROOT))):
                self.assertIn("data-logout", html)
                self.assertNotRegex(
                    html,
                    r'<a\b(?=[^>]*href="/pages/auth/login\.html")(?![^>]*data-logout)[^>]*>'
                )

    def test_css_relative_resource_urls_exist(self):
        for page in (ROOT / "assets/css").rglob("*.css"):
            contents = page.read_text(encoding="utf-8")
            for token in re.findall(r'url\(\s*["\']?([^\)"\']+)', contents):
                path = resolve(page, token.strip())
                if path is not None:
                    with self.subTest(css=page.name, url=token):
                        self.assertTrue(path.is_file(), f"Arquivo citado em CSS ausente: {path}")


if __name__ == "__main__":
    unittest.main()
