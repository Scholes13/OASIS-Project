const fs = require('fs');

const filePath = './resources/js/inertia/Pages/Activity/Dashboard.tsx';
let content = fs.readFileSync(filePath, 'utf8');

const fixContent = `    return (
        <div className="flex flex-col min-h-[calc(100vh-64px)] bg-[#f8fafc] font-sans text-slate-800">
            <Head title="Tasks" />

            {isLoading && <LoadingOverlay message="Syncing workspace..." />}

            {/* Main Content Area */}
            <div className="flex-1 overflow-y-auto px-4 py-6 md:px-8 md:py-8">
                <div className="max-w-[1200px] mx-auto flex flex-col gap-6">
                    
                    {/* Header inline with the page */}
                    <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-2">
                        <div className="flex flex-col gap-1.5">
                            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">My Tasks</h1>
                            <div className="flex items-center gap-2">
                                <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 border border-slate-200/60">
                                    {safeStats.total} Total
                                </span>
                                {safeStats.in_progress > 0 && (
                                    <span className="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 border border-indigo-100">
                                        {safeStats.in_progress} Active
                                    </span>
                                )}
                                {safeStats.overdue > 0 && (
                                    <span className="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 border border-rose-100">
                                        {safeStats.overdue} Overdue
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Control Bar (Filters & Views) */}
                        <div className="flex flex-wrap items-center gap-3">
                            {/* Scope Toggle (My Tasks vs Team) */}
                            <div className="flex items-center bg-white p-1 rounded-lg border border-slate-200 shadow-sm">
                                <button 
                                    onClick={() => setLocalFilters(prev => ({ ...prev, scope: 'my' }))}
                                    className={cn("px-3 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200", (!localFilters.scope || localFilters.scope === 'my') ? "bg-slate-100 text-slate-900" : "text-slate-500 hover:text-slate-700")}
                                >
                                    My Tasks
                                </button>
                                <button 
                                    onClick={() => setLocalFilters(prev => ({ ...prev, scope: 'department' }))}
                                    className={cn("px-3 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200", localFilters.scope === 'department' ? "bg-slate-100 text-slate-900" : "text-slate-500 hover:text-slate-700")}
                                >
                                    Team
                                </button>
                            </div>

                            <div className="w-px h-6 bg-slate-200 hidden sm:block"></div>

                            {/* View Switcher Tabs */}
                            <div className="flex bg-white p-1 rounded-lg border border-slate-200 shadow-sm">
                                {viewConfig.map(({ id, icon, tooltip }) => (
                                    <button
                                        key={id}
                                        onClick={() => handleViewChange(id)}
                                        title={tooltip}
                                        className={cn(
                                            "flex items-center gap-2 px-3 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200",
                                            view === id
                                                ? "bg-slate-100 text-indigo-600"
                                                : "text-slate-500 hover:text-slate-700 hover:bg-slate-50"
                                        )}
                                    >
                                        <span className={cn(view === id ? "text-indigo-600" : "text-slate-400")}>
                                            {icon}
                                        </span>
                                        <span className="capitalize hidden md:inline-block">{id}</span>
                                    </button>
                                ))}
                            </div>
                            
                            <div className="w-px h-6 bg-slate-200 hidden sm:block"></div>
                            
                            {/* Filter Dropdown */}
                            <div className="bg-white rounded-lg border border-slate-200 shadow-sm">
                                <FilterDropdown
                                    filters={localFilters}
                                    onChange={setLocalFilters}
                                    activityTypes={activityTypes}
                                    isFiltering={isFiltering}
                                />
                            </div>

                            <Link href={route('activity.task.create')} className="ml-auto sm:ml-0">
                                <button className="flex items-center gap-2 bg-[#16599c] hover:bg-[#124a82] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors border-none cursor-pointer shadow-sm">
                                    <Plus className="h-4 w-4" strokeWidth={2.5} />
                                    Create Task
                                </button>
                            </Link>
                        </div>
                    </div>

                    {/* Workspace Area - Rendering the views */}
                    <div className="w-full mt-2">
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
                                    <motion.div key="calendar" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-
