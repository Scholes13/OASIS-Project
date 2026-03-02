const fs = require("fs");
const filePath = "./resources/js/inertia/components/activity/ActivityDataTable.tsx";
let content = fs.readFileSync(filePath, "utf8");

// Search targets
const searchGridHeader = "grid grid-cols-[40px_minmax(200px,3fr)_1.5fr_100px_120px_120px_40px] items-center gap-3 border-b border-[var(--border)] bg-[var(--secondary)] px-5 py-3 text-xs font-semibold text-[var(--muted-foreground)]";
const replaceGridHeader = "flex items-center gap-4 border-b border-slate-200 bg-slate-50 px-6 py-3 text-[12px] font-semibold text-slate-500 uppercase tracking-wider";

const searchGridRow = "group grid cursor-pointer grid-cols-[40px_minmax(200px,3fr)_1.5fr_100px_120px_120px_40px] items-center gap-3 border-b border-[var(--border)] px-5 py-4 last:border-b-0 hover:bg-[var(--secondary)]";
const replaceGridRow = "group flex items-center cursor-pointer gap-4 border-b border-slate-100 px-6 py-3.5 last:border-b-0 hover:bg-slate-50/50 transition-colors";

content = content.replace(searchGridHeader, replaceGridHeader);
content = content.replace(searchGridRow, replaceGridRow);

fs.writeFileSync(filePath, content);
console.log("Replaced grid targets");

