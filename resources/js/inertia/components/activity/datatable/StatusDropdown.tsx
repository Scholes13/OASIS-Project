import * as React from "react"
import { router } from "@inertiajs/react"
import { CheckCircle2, ChevronDown } from "lucide-react"
import { Popover, Portal, Transition } from "@headlessui/react"

import { cn } from "@/lib/utils"
import { ACTIVITY_STATUS_CONFIG } from "@/lib/activityConstants"
import { showToast } from "../../ui/toast"
import { handleExecutionTimeGuidance } from "../quick-status-guidance"
import type { Task, TaskStatus } from "@/types"

interface StatusDropdownProps {
    task: Task
    isReadOnly?: boolean
    onEditTask?: (task: Task) => void
}

const statusConfig = ACTIVITY_STATUS_CONFIG

export function StatusDropdown({
    task,
    isReadOnly = false,
    onEditTask,
}: StatusDropdownProps) {
    const [isUpdating, setIsUpdating] = React.useState(false)
    const buttonRef = React.useRef<HTMLButtonElement>(null)
    const [panelPosition, setPanelPosition] = React.useState({
        top: 0,
        left: 0,
        openUpward: false,
    })
    const current = statusConfig[task.status] || statusConfig.planned

    const handleStatusChange = (newStatus: TaskStatus, close: () => void) => {
        if (newStatus === task.status) {
            close()
            return
        }

        setIsUpdating(true)
        router.put(
            route("activity.task.update", { task: task.id }),
            { status: newStatus },
            {
                preserveScroll: true,
                preserveState: false,
                only: ["tasks", "stats", "filters"],
                onSuccess: () => {
                    showToast.success(
                        "Status updated",
                        `${task.task_title} → ${statusConfig[newStatus]?.label}`
                    )
                    close()
                },
                onError: (errors) => {
                    if (!handleExecutionTimeGuidance(task, errors, onEditTask, close)) {
                        showToast.error("Failed to update status")
                    }
                },
                onFinish: () => setIsUpdating(false),
            }
        )
    }

    const calculatePosition = () => {
        if (!buttonRef.current) return

        const rect = buttonRef.current.getBoundingClientRect()
        const spaceBelow = window.innerHeight - rect.bottom
        const openUpward = spaceBelow < 200

        setPanelPosition({
            top: openUpward ? rect.top - 6 : rect.bottom + 6,
            left: rect.left,
            openUpward,
        })
    }

    if (isReadOnly) {
        return (
            <div
                className={cn(
                    "inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[12px] font-medium whitespace-nowrap ring-1 ring-inset opacity-75",
                    current.bg,
                    current.text,
                    current.ring
                )}
            >
                {current.icon}
                {current.label}
            </div>
        )
    }

    return (
        <Popover className="relative">
            {({ close }) => (
                <>
                    <Popover.Button
                        ref={buttonRef}
                        className={cn(
                            "inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[12px] font-medium whitespace-nowrap ring-1 ring-inset transition-all",
                            current.bg,
                            current.text,
                            current.ring,
                            "hover:ring-2",
                            isUpdating && "opacity-50 cursor-wait"
                        )}
                        onClick={(event) => {
                            event.stopPropagation()
                            calculatePosition()
                        }}
                        disabled={isUpdating}
                    >
                        {current.icon}
                        {current.label}
                        <ChevronDown className="h-3.5 w-3.5 opacity-50" />
                    </Popover.Button>
                    <Portal>
                        <Transition
                            as={React.Fragment}
                            enter="transition ease-out duration-100"
                            enterFrom="opacity-0 scale-95"
                            enterTo="opacity-100 scale-100"
                            leave="transition ease-in duration-75"
                            leaveFrom="opacity-100 scale-100"
                            leaveTo="opacity-0 scale-95"
                        >
                            <Popover.Panel
                                static
                                className="fixed z-[9999] w-40 bg-white rounded-lg shadow-lg ring-1 ring-gray-200 py-1"
                                style={{
                                    top: panelPosition.openUpward ? "auto" : panelPosition.top,
                                    bottom: panelPosition.openUpward
                                        ? window.innerHeight - panelPosition.top
                                        : "auto",
                                    left: panelPosition.left,
                                }}
                                onClick={(event) => event.stopPropagation()}
                            >
                                {Object.entries(statusConfig).map(([key, config]) => (
                                    <button
                                        key={key}
                                        onClick={() => handleStatusChange(key as TaskStatus, close)}
                                        disabled={isUpdating}
                                        className={cn(
                                            "flex w-full items-center gap-2 px-3 py-2 text-sm transition-colors",
                                            "hover:bg-gray-50",
                                            task.status === key && "bg-gray-50 font-medium"
                                        )}
                                    >
                                        <span className={config.text}>{config.icon}</span>
                                        <span className="text-gray-700">{config.label}</span>
                                        {task.status === key && (
                                            <CheckCircle2 className="h-3.5 w-3.5 ml-auto text-primary" />
                                        )}
                                    </button>
                                ))}
                            </Popover.Panel>
                        </Transition>
                    </Portal>
                </>
            )}
        </Popover>
    )
}

export default StatusDropdown
