import * as React from "react"
import { TrendingUp, TrendingDown, Minus, Clock, CheckCircle, AlertCircle, PlayCircle } from "lucide-react"
import { cn } from "@/lib/utils"
import { formatDistanceToNow, differenceInMinutes, differenceInHours, format } from "date-fns"
import { id as idLocale } from "date-fns/locale"

// Stat Card Component
interface StatCardProps {
  title: string
  value: string | number
  description?: string
  icon?: React.ReactNode
  trend?: {
    value: number
    label?: string
  }
  className?: string
  variant?: "default" | "success" | "warning" | "danger" | "info"
}

const variantStyles = {
  default: {
    bg: "bg-white",
    icon: "bg-gray-100 text-gray-600",
    trend: {
      up: "text-green-600",
      down: "text-red-600",
      neutral: "text-gray-500",
    },
  },
  success: {
    bg: "bg-green-50",
    icon: "bg-green-100 text-green-600",
    trend: {
      up: "text-green-700",
      down: "text-red-600",
      neutral: "text-green-600",
    },
  },
  warning: {
    bg: "bg-amber-50",
    icon: "bg-amber-100 text-amber-600",
    trend: {
      up: "text-green-600",
      down: "text-red-600",
      neutral: "text-amber-600",
    },
  },
  danger: {
    bg: "bg-red-50",
    icon: "bg-red-100 text-red-600",
    trend: {
      up: "text-red-700",
      down: "text-green-600",
      neutral: "text-red-600",
    },
  },
  info: {
    bg: "bg-blue-50",
    icon: "bg-blue-100 text-blue-600",
    trend: {
      up: "text-green-600",
      down: "text-red-600",
      neutral: "text-blue-600",
    },
  },
}

export function StatCard({
  title,
  value,
  description,
  icon,
  trend,
  className,
  variant = "default",
}: StatCardProps) {
  const styles = variantStyles[variant]
  const trendDirection = trend
    ? trend.value > 0
      ? "up"
      : trend.value < 0
      ? "down"
      : "neutral"
    : "neutral"

  return (
    <div
      className={cn(
        "rounded-xl border border-gray-200 p-6 shadow-sm",
        styles.bg,
        className
      )}
    >
      <div className="flex items-start justify-between">
        <div className="space-y-2">
          <p className="text-sm font-medium text-gray-500">{title}</p>
          <p className="text-3xl font-bold text-gray-900">{value}</p>
          {description && (
            <p className="text-sm text-gray-500">{description}</p>
          )}
          {trend && (
            <div className={cn("flex items-center gap-1 text-sm", styles.trend[trendDirection])}>
              {trendDirection === "up" && <TrendingUp className="h-4 w-4" />}
              {trendDirection === "down" && <TrendingDown className="h-4 w-4" />}
              {trendDirection === "neutral" && <Minus className="h-4 w-4" />}
              <span>
                {trend.value > 0 ? "+" : ""}
                {trend.value}%
              </span>
              {trend.label && <span className="text-gray-500">{trend.label}</span>}
            </div>
          )}
        </div>
        {icon && (
          <div className={cn("rounded-lg p-3", styles.icon)}>
            {icon}
          </div>
        )}
      </div>
    </div>
  )
}

// Activity Stats Grid
interface ActivityStats {
  total: number
  completed: number
  inProgress: number
  planned: number
  overdue: number
  totalHours?: number
  avgDuration?: number
}

interface ActivityStatsGridProps {
  stats: ActivityStats
  className?: string
  period?: string
  previousStats?: ActivityStats
}

export function ActivityStatsGrid({
  stats,
  className,
  period = "this week",
  previousStats,
}: ActivityStatsGridProps) {
  const calculateTrend = (current: number, previous?: number) => {
    if (!previous || previous === 0) return undefined
    return Math.round(((current - previous) / previous) * 100)
  }

  const completionRate = stats.total > 0
    ? Math.round((stats.completed / stats.total) * 100)
    : 0

  return (
    <div className={cn("grid grid-cols-2 lg:grid-cols-4 gap-4", className)}>
      <StatCard
        title="Total Activities"
        value={stats.total}
        description={period}
        icon={<CheckCircle className="h-5 w-5" />}
        trend={
          previousStats
            ? { value: calculateTrend(stats.total, previousStats.total) || 0, label: "vs last period" }
            : undefined
        }
      />
      <StatCard
        title="Completed"
        value={stats.completed}
        description={`${completionRate}% completion rate`}
        icon={<CheckCircle className="h-5 w-5" />}
        variant="success"
        trend={
          previousStats
            ? { value: calculateTrend(stats.completed, previousStats.completed) || 0 }
            : undefined
        }
      />
      <StatCard
        title="In Progress"
        value={stats.inProgress}
        icon={<PlayCircle className="h-5 w-5" />}
        variant="info"
      />
      <StatCard
        title="Overdue"
        value={stats.overdue}
        icon={<AlertCircle className="h-5 w-5" />}
        variant={stats.overdue > 0 ? "danger" : "default"}
      />
    </div>
  )
}

// Duration Display Component
interface DurationDisplayProps {
  minutes: number
  showSeconds?: boolean
  variant?: "compact" | "full" | "badge"
  className?: string
}

export function DurationDisplay({
  minutes,
  showSeconds = false,
  variant = "compact",
  className,
}: DurationDisplayProps) {
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60

  if (variant === "badge") {
    return (
      <span
        className={cn(
          "inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium",
          hours >= 8 ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-700",
          className
        )}
      >
        <Clock className="h-3 w-3" />
        {hours}h {mins}m
      </span>
    )
  }

  if (variant === "full") {
    return (
      <div className={cn("flex items-center gap-2", className)}>
        <Clock className="h-4 w-4 text-gray-400" />
        <span className="text-sm text-gray-600">
          {hours > 0 && `${hours} hour${hours !== 1 ? "s" : ""} `}
          {mins > 0 && `${mins} minute${mins !== 1 ? "s" : ""}`}
          {hours === 0 && mins === 0 && "0 minutes"}
        </span>
      </div>
    )
  }

  // Compact
  return (
    <span className={cn("text-sm text-gray-600", className)}>
      {hours}:{mins.toString().padStart(2, "0")}
    </span>
  )
}

// Time Tracker Component
interface TimeTrackerProps {
  startTime?: Date | string
  endTime?: Date | string
  isRunning?: boolean
  className?: string
}

export function TimeTracker({
  startTime,
  endTime,
  isRunning = false,
  className,
}: TimeTrackerProps) {
  const [elapsed, setElapsed] = React.useState(0)

  React.useEffect(() => {
    if (!isRunning || !startTime) return

    const start = typeof startTime === "string" ? new Date(startTime) : startTime
    
    const updateElapsed = () => {
      setElapsed(differenceInMinutes(new Date(), start))
    }

    updateElapsed()
    const interval = setInterval(updateElapsed, 60000) // Update every minute

    return () => clearInterval(interval)
  }, [isRunning, startTime])

  if (!startTime) {
    return (
      <div className={cn("flex items-center gap-2 text-gray-400", className)}>
        <Clock className="h-4 w-4" />
        <span className="text-sm">Not started</span>
      </div>
    )
  }

  const start = typeof startTime === "string" ? new Date(startTime) : startTime
  const end = endTime
    ? typeof endTime === "string"
      ? new Date(endTime)
      : endTime
    : null

  const duration = end
    ? differenceInMinutes(end, start)
    : elapsed

  return (
    <div className={cn("space-y-1", className)}>
      <div className="flex items-center gap-2">
        <Clock className={cn("h-4 w-4", isRunning ? "text-green-500 animate-pulse" : "text-gray-400")} />
        <DurationDisplay minutes={duration} variant="compact" />
        {isRunning && (
          <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
            <span className="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse" />
            Running
          </span>
        )}
      </div>
      <div className="text-xs text-gray-500">
        {format(start, "HH:mm", { locale: idLocale })}
        {end && ` - ${format(end, "HH:mm", { locale: idLocale })}`}
      </div>
    </div>
  )
}

// Weekly Hours Summary
interface WeeklyHoursProps {
  data: Array<{
    day: string
    hours: number
  }>
  targetHours?: number
  className?: string
}

export function WeeklyHoursSummary({
  data,
  targetHours = 8,
  className,
}: WeeklyHoursProps) {
  const totalHours = data.reduce((sum, d) => sum + d.hours, 0)
  const avgHours = data.length > 0 ? totalHours / data.length : 0

  return (
    <div className={cn("space-y-4", className)}>
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-900">Weekly Hours</p>
          <p className="text-2xl font-bold text-gray-900">{totalHours.toFixed(1)}h</p>
        </div>
        <div className="text-right">
          <p className="text-sm text-gray-500">Daily Average</p>
          <p className="text-lg font-semibold text-gray-700">{avgHours.toFixed(1)}h</p>
        </div>
      </div>
      <div className="space-y-2">
        {data.map((item) => {
          const percentage = Math.min((item.hours / targetHours) * 100, 100)
          const isOverTarget = item.hours > targetHours
          
          return (
            <div key={item.day} className="space-y-1">
              <div className="flex items-center justify-between text-sm">
                <span className="text-gray-600">{item.day}</span>
                <span className={cn(
                  "font-medium",
                  isOverTarget ? "text-amber-600" : item.hours >= targetHours * 0.8 ? "text-green-600" : "text-gray-600"
                )}>
                  {item.hours.toFixed(1)}h
                </span>
              </div>
              <div className="h-2 rounded-full bg-gray-100 overflow-hidden">
                <div
                  className={cn(
                    "h-full rounded-full transition-all duration-300",
                    isOverTarget ? "bg-amber-500" : "bg-blue-500"
                  )}
                  style={{ width: `${percentage}%` }}
                />
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}
