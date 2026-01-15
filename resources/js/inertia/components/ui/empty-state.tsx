import * as React from "react"
import { FileX, Search, Inbox, AlertTriangle, Calendar, ClipboardList, Users } from "lucide-react"
import { cn } from "@/lib/utils"
import { Button } from "./button"

interface EmptyStateProps {
  icon?: React.ReactNode
  title: string
  description?: string
  action?: {
    label: string
    onClick: () => void
  }
  secondaryAction?: {
    label: string
    onClick: () => void
  }
  className?: string
  variant?: "default" | "compact" | "full"
}

export function EmptyState({
  icon,
  title,
  description,
  action,
  secondaryAction,
  className,
  variant = "default",
}: EmptyStateProps) {
  const iconElement = icon || <Inbox className="h-12 w-12" />

  if (variant === "compact") {
    return (
      <div className={cn("flex flex-col items-center justify-center py-8 text-center", className)}>
        <div className="text-gray-300 mb-3">{iconElement}</div>
        <p className="text-sm font-medium text-gray-900">{title}</p>
        {description && <p className="text-sm text-gray-500 mt-1">{description}</p>}
        {action && (
          <Button size="sm" onClick={action.onClick} className="mt-3">
            {action.label}
          </Button>
        )}
      </div>
    )
  }

  if (variant === "full") {
    return (
      <div className={cn("flex min-h-[400px] flex-col items-center justify-center text-center", className)}>
        <div className="rounded-full bg-gray-100 p-6 text-gray-400 mb-6">
          {iconElement}
        </div>
        <h3 className="text-xl font-semibold text-gray-900 mb-2">{title}</h3>
        {description && (
          <p className="text-gray-500 max-w-md mb-6">{description}</p>
        )}
        <div className="flex gap-3">
          {secondaryAction && (
            <Button variant="outline" onClick={secondaryAction.onClick}>
              {secondaryAction.label}
            </Button>
          )}
          {action && (
            <Button onClick={action.onClick}>
              {action.label}
            </Button>
          )}
        </div>
      </div>
    )
  }

  // Default
  return (
    <div className={cn("flex flex-col items-center justify-center py-12 text-center", className)}>
      <div className="rounded-full bg-gray-100 p-4 text-gray-400 mb-4">
        {iconElement}
      </div>
      <h3 className="text-lg font-semibold text-gray-900 mb-1">{title}</h3>
      {description && (
        <p className="text-sm text-gray-500 max-w-sm mb-4">{description}</p>
      )}
      <div className="flex gap-3">
        {secondaryAction && (
          <Button variant="outline" size="sm" onClick={secondaryAction.onClick}>
            {secondaryAction.label}
          </Button>
        )}
        {action && (
          <Button size="sm" onClick={action.onClick}>
            {action.label}
          </Button>
        )}
      </div>
    </div>
  )
}

// Pre-built Empty States

export function NoTasksEmpty({ onCreate }: { onCreate?: () => void }) {
  return (
    <EmptyState
      icon={<ClipboardList className="h-12 w-12" />}
      title="No tasks yet"
      description="Get started by creating your first task. Track your daily activities and stay organized."
      action={onCreate ? { label: "Create Task", onClick: onCreate } : undefined}
    />
  )
}

export function NoSearchResultsEmpty({ 
  query,
  onClear 
}: { 
  query?: string
  onClear?: () => void 
}) {
  return (
    <EmptyState
      icon={<Search className="h-12 w-12" />}
      title="No results found"
      description={query ? `No results for "${query}". Try adjusting your search or filters.` : "Try adjusting your search or filters."}
      action={onClear ? { label: "Clear Search", onClick: onClear } : undefined}
    />
  )
}

export function NoEventsEmpty({ 
  date,
  onCreate 
}: { 
  date?: string
  onCreate?: () => void 
}) {
  return (
    <EmptyState
      icon={<Calendar className="h-12 w-12" />}
      title="No activities scheduled"
      description={date ? `No activities scheduled for ${date}.` : "No activities scheduled for this period."}
      action={onCreate ? { label: "Add Activity", onClick: onCreate } : undefined}
      variant="compact"
    />
  )
}

export function NoTeamMembersEmpty({ onInvite }: { onInvite?: () => void }) {
  return (
    <EmptyState
      icon={<Users className="h-12 w-12" />}
      title="No team members"
      description="Invite team members to collaborate on tasks and activities."
      action={onInvite ? { label: "Invite Members", onClick: onInvite } : undefined}
    />
  )
}

export function ErrorState({ 
  title = "Something went wrong",
  description = "An error occurred while loading the content. Please try again.",
  onRetry 
}: { 
  title?: string
  description?: string
  onRetry?: () => void 
}) {
  return (
    <EmptyState
      icon={<AlertTriangle className="h-12 w-12 text-amber-500" />}
      title={title}
      description={description}
      action={onRetry ? { label: "Try Again", onClick: onRetry } : undefined}
    />
  )
}

export function NoDataEmpty({ 
  type = "data" 
}: { 
  type?: "data" | "file" | "activity" | "chart" 
}) {
  const configs = {
    data: {
      icon: <FileX className="h-12 w-12" />,
      title: "No data available",
      description: "There's no data to display at the moment.",
    },
    file: {
      icon: <FileX className="h-12 w-12" />,
      title: "No files found",
      description: "Upload files to get started.",
    },
    activity: {
      icon: <ClipboardList className="h-12 w-12" />,
      title: "No activities",
      description: "Activities will appear here once created.",
    },
    chart: {
      icon: <FileX className="h-12 w-12" />,
      title: "No chart data",
      description: "Not enough data to display the chart.",
    },
  }

  const config = configs[type]

  return (
    <EmptyState
      icon={config.icon}
      title={config.title}
      description={config.description}
      variant="compact"
    />
  )
}
