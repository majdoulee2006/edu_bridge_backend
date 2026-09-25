const puppeteer = require('d:/Graduation project/edu_bridge_backend/node_modules/puppeteer-core');
const fs = require('fs');

const inputHtml = process.argv[2];
const outputPath = process.argv[3];

if (!inputHtml || !outputPath) {
    console.error('Usage: node render_schedule.js <inputHtmlPath> <outputPath>');
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
        await page.setViewport({ width: 1440, height: 1100, deviceScaleFactor: 2 });
        
        const fileUrl = 'file:///' + inputHtml.replace(/\\/g, '/');
        await page.goto(fileUrl, { waitUntil: 'networkidle0', timeout: 15000 });
        
        const element = await page.$('#officialScheduleSheet');
        if (element) {
            await element.screenshot({ path: outputPath, type: 'png' });
            console.log('SUCCESS:' + outputPath);
        } else {
            console.error('Element #officialScheduleSheet not found');
            process.exit(2);
        }
        await browser.close();
    } catch (e) {
        console.error('Error rendering:', e);
        process.exit(1);
    }
})();
