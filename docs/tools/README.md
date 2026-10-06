# Documentation tools / أدوات التوثيق

Rebuild the generated documentation when the database schema or routes change.

## 1) Regenerate the data dictionary and API reference

Run from the project root (needs a scratch MySQL database; never use your real one):

```bash
# a) create a scratch database and migrate it
php artisan tinker --execute="DB::statement('CREATE DATABASE zz_doc_schema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');"
DB_DATABASE=zz_doc_schema php artisan migrate --force

# b) dump schema + routes to JSON in your TEMP folder
DB_DATABASE=zz_doc_schema php artisan tinker --execute="$(cat docs/tools/dump_schema.php)"
php artisan route:list --json > "$TEMP/routes.json"

# c) generate the markdown (Arabic and English)
python docs/tools/gen_docs_ar.py
python docs/tools/gen_docs_en.py

# d) drop the scratch database
php artisan tinker --execute="DB::statement('DROP DATABASE zz_doc_schema');"
```

`dump_schema.php` writes `schema.json` into the system temp folder; the generators read `schema.json` and `routes.json` from `$TEMP`.

## 2) Browse as a website

```bash
pip install mkdocs mkdocs-material
python -m mkdocs serve          # http://127.0.0.1:8000
python -m mkdocs build --strict # output in site/ (ignored by git)
```

Diagrams are loaded from a CDN by the theme, so the website needs internet to show them.

## 3) Build the PDFs

Needs Python `markdown`, Node (`npm install mermaid@10` in any folder), and Microsoft Edge or Chrome:

1. Copy `node_modules/mermaid/dist/mermaid.min.js` into `site-pdf/`.
2. `python docs/tools/build_pdf.py` (creates `site-pdf/edu-bridge-docs-{en,ar}.html`).
3. Print each HTML to PDF with the browser:
   `msedge --headless=new --no-sandbox --allow-file-access-from-files --virtual-time-budget=90000 --no-pdf-header-footer --print-to-pdf=out.pdf file:///.../site-pdf/edu-bridge-docs-en.html`
4. Copy the PDFs to `docs/pdf/`.
