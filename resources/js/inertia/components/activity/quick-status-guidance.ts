import { router } from "@inertiajs/react"
import { showToast } from "../ui/toast"
import type { Task } from "@/types"

type ValidationErrors = Record<string, string | string[]>

export function extractExecutionTimeGuidance(errors: ValidationErrors): string | null {
  const statusError = errors.status
  const message = Array.isArray(statusError) ? statusError[0] : statusError

  if (!message || !message.toLowerCase().includes("actual execution time")) {
    return null
  }

  return message
}

export function handleExecutionTimeGuidance(
  task: Task,
  errors: ValidationErrors,
  onEditTask?: (task: Task) => void,
  onHandled?: () => void
): boolean {
  const message = extractExecutionTimeGuidance(errors)

  if (!message) {
    return false
  }

  showToast.error(message)

  if (onEditTask) {
    onEditTask(task)
  } else {
    router.visit(route("activity.task.index", { task: task.id, modal: "edit" }))
  }

  onHandled?.()

  return true
}
