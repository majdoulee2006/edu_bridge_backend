import os, re, sys, glob
import markdown

ROOT = os.getcwd()
OUT = os.path.join(ROOT, 'site-pdf')
os.makedirs(OUT, exist_ok=True)

ORDER = ['00-README', '01-product-overview', '02-architecture', '05-key-flows', '06-setup-and-deploy',
         '07-security', '08-project-status', '09-roadmap', '10-handover-checklist', '03-database', '04-api-reference']

CFG = {
    'en': dict(title='Edu-Bridge: Technical Documentation', sub='Architecture, database, API, status and handover guide', dir='ltr', lang='en',
               toc='Contents', appendix='Appendices (auto-generated)'),
    'ar': dict(title='Edu-Bridge: التوثيق الفني', sub='المعمارية، قاعدة البيانات، الـ API، حالة المشروع ودليل التسليم', dir='rtl', lang='ar',
               toc='المحتويات', appendix='الملاحق (مُولَّدة آلياً)'),
}

CSS = """
@page { size: A4; margin: 16mm 14mm; }
body { font-family: 'Segoe UI', Tahoma, 'Arial', sans-serif; font-size: 10.5pt; line-height: 1.55; color: #1b1f23; }
h1 { font-size: 20pt; border-bottom: 2px solid #3f51b5; padding-bottom: 4px; margin-top: 0; }
h2 { font-size: 14.5pt; margin-top: 18px; color: #283593; }
h3 { font-size: 12pt; margin-top: 14px; }
h4 { font-size: 11pt; }
section.doc { page-break-before: always; }
section.cover { height: 235mm; display: flex; flex-direction: column; justify-content: center; page-break-after: always; }
section.cover h1 { font-size: 30pt; border: none; }
section.cover p { font-size: 13pt; color: #444; }
table { border-collapse: collapse; width: 100%; margin: 8px 0; font-size: 8.6pt; page-break-inside: auto; }
tr { page-break-inside: avoid; }
th, td { border: 1px solid #c8ccd0; padding: 3px 5px; vertical-align: top; word-break: break-word; }
th { background: #eef0fb; }
code { font-family: Consolas, 'Courier New', monospace; font-size: 0.92em; background: #f3f4f6; padding: 0 3px; border-radius: 3px; direction: ltr; unicode-bidi: embed; }
pre { background: #f6f8fa; border: 1px solid #e1e4e8; padding: 8px; overflow: hidden; white-space: pre-wrap; direction: ltr; text-align: left; font-size: 8.5pt; }
pre code { background: none; padding: 0; }
pre.mermaid { background: #fff; border: none; text-align: center; white-space: normal; }
pre.mermaid svg { max-width: 100%; height: auto; }
blockquote { border-inline-start: 4px solid #9fa8da; margin: 8px 0; padding: 2px 10px; background: #f5f6ff; }
a { color: #1a49b8; text-decoration: none; }
hr { border: none; border-top: 1px solid #ddd; }
ul.toc li { margin: 2px 0; }
"""

def conv(md_text):
    md = markdown.Markdown(extensions=['tables', 'fenced_code', 'sane_lists'])
    html = md.convert(md_text)
    # mermaid fences -> <pre class="mermaid">
    html = re.sub(r'<pre><code class="language-mermaid">(.*?)</code></pre>',
                  lambda m: '<pre class="mermaid">' + m.group(1) + '</pre>', html, flags=re.S)
    return html

def build(lang):
    c = CFG[lang]
    parts = []
    toc_items = []
    for i, name in enumerate(ORDER):
        path = os.path.join(ROOT, 'docs', lang, name + '.md')
        text = open(path, encoding='utf-8').read()
        html = conv(text)
        # cross-file links -> in-document anchors
        html = re.sub(r'href="(\.\./(?:ar|en)/)?([0-9]{2}-[a-z-]+)\.md(#[^"]*)?"', lambda m: 'href="#doc-' + m.group(2) + '"', html)
        title = re.search(r'<h1[^>]*>(.*?)</h1>', html, flags=re.S)
        t = re.sub('<[^>]+>', '', title.group(1)) if title else name
        toc_items.append((name, t))
        if name == '03-database':
            parts.append('<section class="doc" id="appendix"><h1 style="border:none">' + c['appendix'] + '</h1></section>')
        parts.append(f'<section class="doc" id="doc-{name}">{html}</section>')
    toc = '<ul class="toc">' + ''.join(f'<li><a href="#doc-{n}">{t}</a></li>' for n, t in toc_items) + '</ul>'
    cover = (f'<section class="cover"><h1>{c["title"]}</h1><p>{c["sub"]}</p>'
             f'<p>2026-10-05</p></section>'
             f'<section><h1>{c["toc"]}</h1>{toc}</section>')
    doc = (f'<!doctype html><html lang="{c["lang"]}" dir="{c["dir"]}"><head><meta charset="utf-8">'
           f'<title>{c["title"]}</title><style>{CSS}</style>'
           '<script src="mermaid.min.js"></script>'
           '<script>mermaid.initialize({startOnLoad:true, theme:"neutral", securityLevel:"loose", flowchart:{htmlLabels:true}});</script>'
           f'</head><body>{cover}{"".join(parts)}</body></html>')
    out = os.path.join(OUT, f'edu-bridge-docs-{lang}.html')
    open(out, 'w', encoding='utf-8').write(doc)
    return out

for l in ('en', 'ar'):
    print(build(l))
