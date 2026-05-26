import * as React from "react"
import { AlertTriangle, CheckCircle2, ListTodo, PlayCircle } from "lucide-react"

import { cn } from "@/lib/utils"
import type { TaskStats } from "@/types"

export type StatusFilter = "all" | "in_progress" | "overdue" | "completed"

const colorThemes = {
    indigo: {
        bg: "bg-blue-500",
        border: "border-primary",
        hoverBorder: "hover:border-primary",
        text: "text-primary",
        icon: "text-primary",
        ring: "ring-primary",
    },
    amber: {
        bg: "bg-amber-50",
        border: "border-amber-200",
        hoverBorder: "hover:border-amber-400",
        text: "text-amber-700",
        icon: "text-amber-600",
        ring: "ring-amber-500",
    },
    rose: {
        bg: "bg-rose-50",
        border: "border-rose-200",
        hoverBorder: "hover:border-rose-400",
        text: "text-rose-700",
        icon: "text-rose-600",
        ring: "ring-rose-500",
    },
    emerald: {
        bg: "bg-emerald-50",
        border: "border-emerald-200",
        hoverBorder: "hover:border-emerald-400",
        text: "text-emerald-700",
        icon: "text-emerald-600",
        ring: "ring-emerald-500",
    },
    neutral: {
        bg: "bg-gray-50",
        border: "border-gray-200",
        hoverBorder: "hover:border-gray-300",
        text: "text-gray-700",
        icon: "text-gray-500",
        ring: "ring-gray-400",
    },
}

interface StatsCardConfig {
    label: string
    status: StatusFilter
    color: keyof typeof colorThemes
    icon: React.ReactNode
}

interface InteractiveStatsCardProps {
    config: StatsCardConfig
    value: number
    isActive: boolean
    isQuietDefault: boolean
    onClick: () => void
}

interface MetricCardsProps {
    stats?: TaskStats
    filteredCount: number
    activeFilter: StatusFilter
    onFilterChange: (filter: StatusFilter) => void
}

function InteractiveStatsCard({
    config,
    value,
    isActive,
    isQuietDefault,
    onClick,
}: InteractiveStatsCardProps) {
    const theme = isQuietDefault ? colorThemes.neutral : colorThemes[config.color]
    const showRing = isActive && !isQuietDefault

    return (
        <button
            onClick={onClick}
            className={cn(
                "flex items-center gap-3 px-4 py-3 rounded-xl border transition-all cursor-pointer text-left w-full",
                theme.bg,
                theme.border,
                theme.hoverBorder,
                "hover:shadow-md",
                showRing && "ring-2 ring-offset-2",
                showRing && colorThemes[config.color].ring
            )}
        >
            <div
                className={cn(
                    "flex-shrink-0",
                    isQuietDefault ? colorThemes.neutral.icon : colorThemes[config.color].icon
                )}
            >
                {config.icon}
            </div>
            <div className="flex-1 min-w-0">
                <p
                    className={cn(
                        "text-xs font-medium truncate opacity-80",
                        isQuietDefault ? colorThemes.neutral.text : colorThemes[config.color].text
                    )}
                >
                    {config.label}
                </p>
                <p
                    className={cn(
                        "text-2xl font-bold tracking-tight",
                        isQuietDefault ? colorThemes.neutral.text : colorThemes[config.color].text
                    )}
                >
                    {value}
                </p>
            </div>
            {showRing && (
                <CheckCircle2
                    className={cn("h-4 w-4 flex-shrink-0", colorThemes[config.color].icon)}
                    strokeWidth={2}
                />
            )}
        </button>
    )
}

export function MetricCards({
    stats,
    filteredCount,
    activeFilter,
    onFilterChange,
}: MetricCardsProps) {
    const safeStats = stats || {
        total: filteredCount,
        planned: 0,
        in_progress: 0,
        completed: 0,
        overdue: 0,
    }

    const statsConfig: StatsCardConfig[] = [
        {
            label: "Total Activities",
            status: "all",
            color: "indigo",
            icon: <ListTodo className="h-5 w-5" strokeWidth={1.5} />,
        },
        {
            label: "In Progress",
            status: "in_progress",
            color: "amber",
            icon: <PlayCircle className="h-5 w-5" strokeWidth={1.5} />,
        },
        {
            label: "Overdue",
            status: "overdue",
            color: "rose",
            icon: <AlertTriangle className="h-5 w-5" strokeWidth={1.5} />,
        },
        {
            label: "Completed",
            status: "completed",
            color: "emerald",
            icon: <CheckCircle2 className="h-5 w-5" strokeWidth={1.5} />,
        },
    ]

    const getValueForStatus = (status: StatusFilter): number => {
        switch (status) {
            case "all":
                return safeStats.total
            case "in_progress":
                return safeStats.in_progress
            case "overdue":
                return safeStats.overdue
            case "completed":
                return safeStats.completed
            default:
                return 0
        }
    }

    return (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 px-6 py-5">
            {statsConfig.map((config) => {
                const isActive = activeFilter === config.status
                const isQuietDefault = config.status === "all" && activeFilter === "all"

                return (
                    <InteractiveStatsCard
                        key={config.status}
                        config={config}
                        value={getValueForStatus(config.status)}
                        isActive={isActive}
                        isQuietDefault={isQuietDefault}
                        onClick={() => onFilterChange(config.status)}
                    />
                )
            })}
        </div>
    )
}

export default MetricCards
