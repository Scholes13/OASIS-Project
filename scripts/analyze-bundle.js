#!/usr/bin/env node

/**
 * Bundle Analysis Script
 * 
 * Analyzes the production build output to show chunk sizes and optimization opportunities.
 * Run after: npm run build
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const buildDir = path.join(__dirname, '..', 'public', 'build', 'js');

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

function analyzeBundle() {
    if (!fs.existsSync(buildDir)) {
        console.error('❌ Build directory not found. Run "npm run build" first.');
        process.exit(1);
    }

    const files = fs.readdirSync(buildDir);
    const jsFiles = files.filter(f => f.endsWith('.js'));
    const cssFiles = files.filter(f => f.endsWith('.css'));

    console.log('\n📦 Bundle Analysis\n');
    console.log('='.repeat(80));

    // Analyze JavaScript files
    console.log('\n📄 JavaScript Chunks:\n');
    
    const jsStats = jsFiles.map(file => {
        const filePath = path.join(buildDir, file);
        const stats = fs.statSync(filePath);
        return { file, size: stats.size };
    }).sort((a, b) => b.size - a.size);

    let totalJsSize = 0;
    jsStats.forEach(({ file, size }) => {
        totalJsSize += size;
        const category = categorizeChunk(file);
        console.log(`  ${category.padEnd(20)} ${file.padEnd(40)} ${formatBytes(size).padStart(12)}`);
    });

    console.log('\n' + '-'.repeat(80));
    console.log(`  ${'TOTAL JS'.padEnd(20)} ${' '.padEnd(40)} ${formatBytes(totalJsSize).padStart(12)}`);

    // Analyze CSS files
    console.log('\n\n🎨 CSS Files:\n');
    
    const cssStats = cssFiles.map(file => {
        const filePath = path.join(buildDir, file);
        const stats = fs.statSync(filePath);
        return { file, size: stats.size };
    }).sort((a, b) => b.size - a.size);

    let totalCssSize = 0;
    cssStats.forEach(({ file, size }) => {
        totalCssSize += size;
        console.log(`  ${file.padEnd(60)} ${formatBytes(size).padStart(12)}`);
    });

    console.log('\n' + '-'.repeat(80));
    console.log(`  ${'TOTAL CSS'.padEnd(60)} ${formatBytes(totalCssSize).padStart(12)}`);

    // Summary
    console.log('\n\n📊 Summary:\n');
    console.log(`  Total JavaScript: ${formatBytes(totalJsSize)}`);
    console.log(`  Total CSS:        ${formatBytes(totalCssSize)}`);
    console.log(`  Total Assets:     ${formatBytes(totalJsSize + totalCssSize)}`);
    console.log(`  Number of JS chunks: ${jsStats.length}`);
    console.log(`  Number of CSS files: ${cssStats.length}`);

    // Recommendations
    console.log('\n\n💡 Optimization Tips:\n');
    
    const largeChunks = jsStats.filter(s => s.size > 500000); // > 500KB
    if (largeChunks.length > 0) {
        console.log('  ⚠️  Large chunks detected (>500KB):');
        largeChunks.forEach(({ file, size }) => {
            console.log(`     - ${file}: ${formatBytes(size)}`);
        });
        console.log('     Consider further code splitting or lazy loading.');
    } else {
        console.log('  ✅ All chunks are optimally sized (<500KB)');
    }

    const vendorChunks = jsStats.filter(s => s.file.includes('vendor'));
    if (vendorChunks.length > 0) {
        const vendorSize = vendorChunks.reduce((sum, s) => sum + s.size, 0);
        console.log(`\n  📦 Vendor bundles: ${vendorChunks.length} chunks, ${formatBytes(vendorSize)} total`);
    }

    console.log('\n' + '='.repeat(80) + '\n');
}

function categorizeChunk(filename) {
    if (filename.includes('vendor-react')) return '⚛️  React Core';
    if (filename.includes('vendor-ui')) return '🎨 UI Libraries';
    if (filename.includes('vendor-utils')) return '🔧 Utilities';
    if (filename.includes('vendor-state')) return '📊 State Mgmt';
    if (filename.includes('vendor-charts')) return '📈 Charts';
    if (filename.includes('vendor-calendar')) return '📅 Calendar';
    if (filename.includes('vendor-dnd')) return '🎯 Drag & Drop';
    if (filename.includes('vendor-toast')) return '🔔 Toast';
    if (filename.includes('vendor-cmdk')) return '⌨️  Command';
    if (filename.includes('shared-layout')) return '🏗️  Layout';
    if (filename.includes('shared-ui')) return '🧩 UI Components';
    if (filename.includes('module-purchasing')) return '🛒 Purchasing';
    if (filename.includes('module-activity')) return '📋 Activity';
    if (filename.includes('module-admin')) return '⚙️  Admin';
    if (filename.includes('app')) return '🚀 App Entry';
    return '📦 Other';
}

analyzeBundle();
