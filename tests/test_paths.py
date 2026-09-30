"""Check case-sensitive local URLs and external-script syntax without a web server."""
import re
import subprocess
import unittest
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit

ROOT = Path(__file__).resolve().parents[1]
PUBLIC = ROOT / 'public'

class Page(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []
        self.ids = set()
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if 'id' in attrs:
            self.ids.add(attrs['id'])
        for name in ['href', 'src']:
            if name in attrs:
                self.links.append(attrs[name])

class Paths(unittest.TestCase):
    def test_local_links_and_fragments(self):
        for path in PUBLIC.rglob('*.html'):
            page = Page()
            page.feed(path.read_text())
            for link in page.links:
                url = urlsplit(link)
                if url.scheme or url.netloc or link == '#':
                    continue
                target = PUBLIC / url.path.lstrip('/') if url.path.startswith('/') else path.parent / url.path
                if not url.path:
                    target = path
                self.assertTrue(target.is_file(), f'{path}: {link}')
                if url.fragment and target.suffix == '.html':
                    destination = Page()
                    destination.feed(target.read_text())
                    self.assertIn(url.fragment, destination.ids, f'{path}: {link}')
    def test_api_urls_and_js_syntax(self):
        for script in (PUBLIC / 'assets/js').glob('*.js'):
            subprocess.run(['node', '--check', str(script)], check=True, capture_output=True)
            for endpoint in re.findall(r'/php/([a-z_]+\.php)', script.read_text()):
                self.assertTrue((PUBLIC / 'php' / endpoint).is_file(), endpoint)

if __name__ == '__main__':
    unittest.main()
