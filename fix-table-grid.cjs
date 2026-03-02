const fs = require('fs');
const filePath = './resources/js/inertia/components/activity/ActivityDataTable.tsx';
let content = fs.readFileSync(filePath, 'utf8');

// Also make sure the header in the grid has slightly less harsh borders for the new clean look
const searchGridHeader = 'grid grid-cols-[50px_minmax(250px,_3fr)_180px_120px_120px_120px_50px] items-center gap-3 border-b border-slate-200 bg-slate-50/80 px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider';
const replaceGridHeader = 'grid grid-cols-[50px_minmax(250px,_3fr)_180px_120px_120px_120px_50px] items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3 text-[11px] font-semibold text-slate-500';

content = content.replace(searchGridHeader, replaceGridHeader);
fs.writeFileSync(filePath, content);
console.log('Fixed styling details');
