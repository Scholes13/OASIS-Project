const fs = require('fs');

const filePath = './resources/js/inertia/Pages/Activity/Dashboard.tsx';
let content = fs.readFileSync(filePath, 'utf8');

// 1. Change Dashboard layout logic by using createPortal to inject title into Navbar if possible, 
// OR just move the whole control bar into the main content and rely on the page context for the title.

const headerOld = `            {/* Top Bar (Header) */}
            <header className="h-16 flex-shrink-0 flex items-center justify-between px-8 bg-white border-b border-slate-200">
                <div className="flex flex-col">
                    <h1 className="text-[18px] font-semibold text-slate-800 m-0">My Tasks</h1>
                </div>
            </header>`;

const contentNew = `            {/* Note: The main header 'My Tasks' would be best handled by injecting into the AppLayout's Navbar. For now, we integrate smoothly. */}
            {/* Main Content Area */}
            <div className="flex-1 overflow-y-auto p-6 md:p-8">
                <div className="max-w-[1200px] mx-auto flex flex-col gap-6">
                    
                    <div className="flex flex-col gap-1 mb-2">
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">My Tasks</h1>
                        <p className="text-sm text-slate-500">Manage and track your activities across the workspace.</p>
                    </div>`;

const modifiedContent = content.replace(headerOld, '').replace(`            {/* Main Content Area */}
            <div className="flex-1 overflow-y-auto p-8">
                <div className="max-w-[1200px] mx-auto flex flex-col gap-6">`, contentNew);

fs.writeFileSync(filePath, modifiedContent);
console.log('Done');
