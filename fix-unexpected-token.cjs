const fs = require('fs');
const filePath = './resources/js/inertia/components/activity/ActivityDataTable.tsx';
let content = fs.readFileSync(filePath, 'utf8');

// We have a syntax error somewhere due to mismatched parentheses or tags in renderTaskSection.
// Let's replace the whole renderTaskSection function with a guaranteed valid block.

const sectionStart = content.indexOf('    const renderTaskSection = (title: string, sectionTasks: Task[]) => (');
const sectionEnd = content.indexOf('    return (\n        <>\n            <div className={cn(');

if (sectionStart !== -1 && sectionEnd !== -1) {
    const startStr = content.substring(0, sectionStart);
    const endStr = content.substring(sectionEnd);

    // Provide the clean, correct, bug-free block
    const correctRenderTaskSection = `    const renderTaskSection = (title: string, sectionTasks: Task[]) => (
        <section className="mb-6">
            <h3 className="mb-3 text-[15px] font-bold text-slate-800 tracking-tight ml-2">{title}</h3>

            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm flex flex-col">
                <div className="min-w-[850px] w-full flex flex-col">
                    {/* Native Grid Table Header */}
                    <div className="grid grid-cols-[50px_minmax(250px,_3fr)_180px_120px_120px_120px_50px] items-center gap-3 border-b border-slate-200 bg-slate-50/80 px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <div></div>
                        <div>Task Name</div>
                        <div>Category</div>
                        <div>Priority</div>
                        <div>Due Date</div>
                        <div>Status</div>
                        <div></div>
                    </div>

                    {/* Table Body */}
                    {sectionTasks.length === 0 ? (
                        <div className="px-6 py-8 text-sm text-slate-500 text-center">No tasks in this section.</div>
                    ) : (
                        sectionTasks.map((task) => {
                            const isCompleted = task.status === "completed"
                            const isReadOnly = viewMode === "department" && !canEditTask(task, currentUserId)
                            const categoryLabel = task.activity_type?.name || "General"
                            const statusBadge = statusBadgeConfig[task.status] || statusBadgeConfig.planned

                            return (
                                <div
                                    key={task.id}
                                    onClick={() => handleRowClick(task)}
                                    className="group grid cursor-pointer grid-cols-[50px_minmax(250px,_3fr)_180px_120px_120px_120px_50px] items-center gap-3 border-b border-slate-100 px-4 py-3.5 last:border-b-0 hover:bg-slate-50/60 transition-colors"
                                >
                                    <div className="flex items-center justify-center">
                                        <button
                                            type="button"
                                            disabled={isReadOnly || updatingTaskId === task.id}
                                            onClick={(e) => {
                                                e.stopPropagation()
                                                handleToggleTaskComplete(task, isCompleted)
                                            }}
                                            className={cn(
                                                "inline-flex h-[18px] w-[18px] items-center justify-center rounded-[4px] border transition-colors shadow-sm",
                                                isCompleted
                                                    ? "border-[#16599c] bg-[#16599c] text-white"
                                                    : "border-slate-300 hover:border-[#16599c] text-transparent bg-white",
                                                (isReadOnly || updatingTaskId === task.id) && "cursor-not-allowed opacity-50 hover:border-slate-300"
                                            )}
                                        >
                                            <Check className="h-3.5 w-3.5" strokeWidth={3} />
                                        </button>
                                    </div>

                                    <div className="min-w-0 pr-4">
                                        <p className={cn(
                                            "truncate text-[14px] font-medium transition-colors duration-200",
                                            isCompleted ? "text-slate-400 line-through" : "text-slate-900"
                                        )}>
                                            {task.task_title}
                                        </p>
                                    </div>

                                    <div className="min-w-0 pr-4">
                                        <p className="truncate text-[13px] text-slate-500">{categoryLabel}</p>
                                    </div>

                                    <div>
                                        <span className={cn("inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold border", 
                                            task.priority === 'high' ? "bg-rose-50 text-rose-600 border-rose-100" :
                                            task.priority === 'medium' ? "bg-amber-50 text-amber-600 border-amber-100" :
                                            "bg-sky-50 text-sky-600 border-sky-100"
                                        )}>
                                            {task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                                        </span>
                                    </div>

                                    <div>
                                        <p className="text-[13px] text-slate-500">{formatTaskDue(task.due_date)}</p>
                                    </div>

                                    <div>
                                        <span className={cn("inline-flex rounded-md px-2 py-0.5 text-[12px] font-medium border shadow-sm",
                                            task.status === 'completed' ? "bg-emerald-50 text-emerald-700 border-emerald-100" :
                                            task.status === 'in_progress' ? "bg-indigo-50 text-indigo-700 border-indigo-100" :
                                            "bg-slate-50 text-slate-600 border-slate-200"
                                        )}>
                                            {statusBadge.label}
                                        </span>
                                    </div>

                                    <div className="flex justify-end pr-2">
                                        {showActions && (
                                            <div
                                                className="opacity-0 group-hover:opacity-100 transition-opacity"
                                                onClick={(e) => e.stopPropagation()}
                                            >
                                                <RowActions task={task} visible={true} isReadOnly={isReadOnly} />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            </div>
        </section>
    );

`;

    fs.writeFileSync(filePath, startStr + correctRenderTaskSection + endStr);
    console.log('Fixed syntax error');
}
