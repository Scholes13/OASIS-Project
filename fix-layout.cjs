const fs = require('fs');

const filePath = './resources/js/inertia/Pages/Activity/Dashboard.tsx';
let content = fs.readFileSync(filePath, 'utf8');

const returnIndex = content.indexOf('    return (\n');
if (returnIndex !== -1) {
    const topContent = content.substring(0, returnIndex);
    
    const newReturn = `    return (
        <div className="flex flex-col h-[calc(100vh-64px)] bg-[#f8fafc] font-sans text-slate-800">
            <Head title="Tasks" />

            {isLoading && <LoadingOverlay message="Syncing workspace..." />}

            {/* Top Bar (Header) */}
            <header className="h-16 flex-shrink-0 flex items-center justify-between px-8 bg-white border-b border-slate-200">
                <div className="flex flex-col">
                    <h1 className="text-[18px] font-semibold text-slate-800 m-0">My Tasks</h1>
                    <div className="flex items-center gap-2 mt-0.5">
                        <p className="text-[13px] text-slate-500 m-0">
                            {currentBusinessUnit ? currentBusinessUnit.name : 'Workspace'}
                        </p>
                        <span className="w-1 h-1 rounded-full bg-slate-300"></span>
                        <div className="flex items-center gap-1.5 text-[11px] font-medium">
                            <span className="text-slate-500">{safeStats.total} Total</span>
                            {safeStats.in_progress > 0 && <span className="text-indigo-600">{safeStats.in_progress} Active</span>}
                            {safeStats.overdue > 0 && <span className="text-rose-600">{safeStats.overdue} Overdue</span>}
                        </div>
                    </div>
                </div>
                
                <div className="flex items-center gap-4">
                    {/* Scope Toggle */}
                    <div className="flex bg-slate-100 p-1 rounded-md">
                        <button 
                            onClick={() => setLocalFilters(prev => ({ ...prev, scope: 'my' }))}
                            className={cn("px-3 py-1 text-[13px] font-medium rounded transition-all", (!localFilters.scope || localFilters.scope === 'my') ? "bg-white text-slate-900 shadow-sm" : "text-slate-500 hover:text-slate-700")}
                        >
                            My Tasks
                        </button>
                        <button 
                            onClick={() => setLocalFilters(prev => ({ ...prev, scope: 'department' }))}
                            className={cn("px-3 py-1 text-[13px] font-medium rounded transition-all", localFilters.scope === 'department' ? "bg-white text-slate-900 shadow-sm" : "text-slate-500 hover:text-slate-700")}
                        >
                            Team
                        </button>
                    </div>
                </div>
            </header>

            {/* Main Content Area */}
            <div className="flex-1 overflow-y-auto p-8">
                <div className="max-w-[1200px] mx-auto flex flex-col gap-6">
                    
                    {/* Control Bar (Filters & Views) */}
                    <div className="flex justify-between items-center bg-white p-3 px-4 border border-slate-200 rounded-xl shadow-sm">
                        <div className="flex items-center gap-4">
                            {/* View Switcher Tabs */}
                            <div className="flex bg-slate-50 p-1 rounded-lg">
                                {viewConfig.map(({ id, icon, tooltip }) => (
                                    <button
                                        key={id}
                                        onClick={() => handleViewChange(id)}
                                        title={tooltip}
                                        className={cn(
                                            "flex items-center gap-2 px-3 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200",
                                            view === id
                                                ? "bg-white text-indigo-600 shadow-sm font-semibold"
                                                : "text-slate-500 hover:text-slate-700"
                                        )}
                                    >
                                        <span className={cn(view === id ? "text-indigo-600" : "text-slate-400")}>
                                            {icon}
                                        </span>
                                        <span className="capitalize">{id}</span>
                                    </button>
                                ))}
                            </div>
                            
                            <div className="w-px h-6 bg-slate-200"></div>
                            
                            {/* Filter Dropdown */}
                            <FilterDropdown
                                filters={localFilters}
                                onChange={setLocalFilters}
                                activityTypes={activityTypes}
                                isFiltering={isFiltering}
                            />
                        </div>

                        <Link href={route('activity.task.create')}>
                            <button className="flex items-center gap-2 bg-[#16599c] hover:bg-[#124a82] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors border-none">
                                <Plus className="h-4 w-4" strokeWidth={2.5} />
                                Create Task
                            </button>
                        </Link>
                    </div>

                    {/* Workspace Area - Rendering the views */}
                    <div className="w-full">
                        <Suspense
                            fallback={
                                <div className="flex h-64 items-center justify-center text-slate-400">
                                    <div className="flex flex-col items-center gap-2">
                                        <div className="h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-[#16599c]"></div>
                                        <span className="text-sm font-medium">Loading workspace...</span>
                                    </div>
                                </div>
                            }
                        >
                            <AnimatePresence mode="wait" initial={false}>
                                {view === 'list' && (
                                    <motion.div key="list" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-full">
                                        <ActivityDataTable tasks={tasks} stats={safeStats} filters={filters} showHeader={false} compact={false} />
                                    </motion.div>
                                )}
                                {view === 'board' && (
                                    <motion.div key="board" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-full min-h-[500px]">
                                        <KanbanBoard tasks={taskData} />
                                    </motion.div>
                                )}
                                {view === 'calendar' && (
                                    <motion.div key="calendar" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                                        <ActivityCalendar tasks={taskData} onEventClick={handleTaskClick} />
                                    </motion.div>
                                )}
                                {view === 'timeline' && (
                                    <motion.div key="timeline" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                                     
