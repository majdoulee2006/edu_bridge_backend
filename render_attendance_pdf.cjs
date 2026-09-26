const puppeteer = require('d:/Graduation project/edu_bridge_backend/node_modules/puppeteer-core');
const path = require('path');
const fs = require('fs');

const inputHtml = process.argv[2];
const outputPath = process.argv[3];

if (!inputHtml || !outputPath) {
    console.error('Usage: node render_attendance_pdf.cjs <inputHtmlPath> <outputPath>');
    process.exit(1);
}

(async () => {
    try {
        const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
        const browser = await puppeteer.launch({
            executablePath: edgePath,
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
        });
        const page = await browser.newPage();
        await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });
        
        const fileUrl = 'file:///' + inputHtml.replace(/\\/g, '/');
        await page.goto(fileUrl, { waitUntil: 'networkidle0', timeout: 20000 });
        
        await page.pdf({
            path: outputPath,
            format: 'A4',
            landscape: true,
            printBackground: true,
            margin: { top: '6mm', bottom: '6mm', left: '6mm', right: '6mm' }
        });
        
        await browser.close();
        console.log('SUCCESS: Attendance PDF generated at ' + outputPath);
        process.exit(0);
    } catch (err) {
        console.error('PDF Render Error:', err);
        process.exit(1);
    }
})();
