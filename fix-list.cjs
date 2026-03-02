const fs = require('fs');
const filePath = './resources/js/inertia/Pages/Activity/Dashboard.tsx';
let content = fs.readFileSync(filePath, 'utf8');

// The main problem is the wrapper around ActivityDataTable has a white background and padding, making it look boxed.
// Linear/Asana list is completely transparent on the outside and only the rows/headers are white.

const listOld = `{view === 'list' && (
                                    <motion.div key="list" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-full">
                                        <ActivityDataTable tasks={tasks} stats={safeStats} filters={filters} showHeader={false} compact={false} />
                                    </motion.div>
                                )}`;

const listNew = `{view === 'list' && (
                                    <motion.div key="list" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-full">
                                        <ActivityDataTable tasks={tasks} stats={safeStats} filters={filters} showHeader={false} compact={true} />
                                    </motion.div>
                                )}`;

content = content.replace(listOld, listNew);

// We need to also check the ActivityDataTable component directly to ensure it removes its own wrapper if compact=true
const dataTablePath = './resources/js/inertia/components/activity/ActivityDataTable.tsx';
let dataTableContent = fs.readFileSync(dataTablePath, 'utf8');

const returnOld = `    return (
        <>
            <div className={cn(
                "overflow-visible rounded-xl bg-[var(--background)]",
                !compact && "border border-[var(--border)] shadow-[0_8px_24px_rgba(15,53,85,0.06)]"
            )}>`;

const returnNew = `    return (
        <>
            <div className={cn(
                "overflow-visible",
                !compact && "rounded-xl bg-white border border-[var(--border)] shadow-[0_8px_24px_rgba(15,53,85,0.06)]"
            )}>`;

dataTableContent = dataTableContent.replace(returnOld, returnNew);

const sectionClassOld = `                <div className={cn("space-y-6", showHeader && "border-t border-[var(--border)] p-6")}>`;
const sectionClassNew = `                <div className={cn("space-y-8", showHeader && "border-t border-[var(--border)] p-6")}>`;
dataTableContent = dataTableContent.replace(sectionClassOld, sectionClassNew);

fs.writeFileSync(filePath, content);
fs.writeFileSync(dataTablePath, dataTableContent);
console.log('Fixed wrapper container transparency');
