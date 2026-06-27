import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const srcDir = path.join(__dirname, 'src');
const distDir = path.join(__dirname, 'dist');

console.log('Building project...');

// Ensure dist directory exists
if (!fs.existsSync(distDir)) {
  fs.mkdirSync(distDir, { recursive: true });
}

// Copy Code.gs and other GAS files to dist/gas
const gasSrcDir = path.join(__dirname, 'gas');
const gasDistDir = path.join(distDir, 'gas');

if (fs.existsSync(gasSrcDir)) {
  if (!fs.existsSync(gasDistDir)) {
    fs.mkdirSync(gasDistDir, { recursive: true });
  }
  const gasFiles = fs.readdirSync(gasSrcDir);
  for (const file of gasFiles) {
    fs.copyFileSync(path.join(gasSrcDir, file), path.join(gasDistDir, file));
  }
  console.log(`GAS scripts copied to ${gasDistDir}`);
}

// Read source files
const htmlPath = path.join(srcDir, 'index.html');
const cssPath = path.join(srcDir, 'style.css');
const appPath = path.join(srcDir, 'app.js');

if (!fs.existsSync(htmlPath) || !fs.existsSync(cssPath) || !fs.existsSync(appPath)) {
  console.error('Error: Source files (index.html, style.css, app.js) do not exist in src/ yet.');
  process.exit(1);
}

let htmlContent = fs.readFileSync(htmlPath, 'utf8');
const cssContent = fs.readFileSync(cssPath, 'utf8');
const appContent = fs.readFileSync(appPath, 'utf8');

// Replace stylesheet link with inline style block
htmlContent = htmlContent.replace(
  /<link[^>]*href=["'][./]*style\.css["'][^>]*>/i,
  () => `<style>\n${cssContent}\n</style>`
);

// Remove mock-api.js script tag
htmlContent = htmlContent.replace(
  /<script[^>]*src=["'][./]*mock-api\.js["'][^>]*><\/script>\s*/i,
  ''
);

// Replace app.js script tag with inline script block
htmlContent = htmlContent.replace(
  /<script[^>]*src=["'][./]*app\.js["'][^>]*><\/script>/i,
  () => `<script>\n${appContent}\n</script>`
);

// Write final single HTML file to dist/Index.html (GAS convention prefers capitalized I)
fs.writeFileSync(path.join(distDir, 'Index.html'), htmlContent);

console.log('Build completed! Single-page app generated at dist/Index.html');
