import * as React from "react"

type KeyboardShortcut = {
  key: string
  ctrl?: boolean
  shift?: boolean
  alt?: boolean
  meta?: boolean
  action: () => void
  description?: string
}

export function useKeyboardShortcuts(shortcuts: KeyboardShortcut[]) {
  React.useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      // Ignore if typing in an input, textarea, or contenteditable
      const target = event.target as HTMLElement
      if (
        target.tagName === "INPUT" ||
        target.tagName === "TEXTAREA" ||
        target.isContentEditable
      ) {
        return
      }

      for (const shortcut of shortcuts) {
        const keyMatch = event.key.toLowerCase() === shortcut.key.toLowerCase()
        const ctrlMatch = shortcut.ctrl ? event.ctrlKey || event.metaKey : !event.ctrlKey && !event.metaKey
        const shiftMatch = shortcut.shift ? event.shiftKey : !event.shiftKey
        const altMatch = shortcut.alt ? event.altKey : !event.altKey

        if (keyMatch && ctrlMatch && shiftMatch && altMatch) {
          event.preventDefault()
          shortcut.action()
          break
        }
      }
    }

    window.addEventListener("keydown", handleKeyDown)
    return () => window.removeEventListener("keydown", handleKeyDown)
  }, [shortcuts])
}

// Pre-built shortcuts for activity module
export function useActivityShortcuts({
  onCreateTask,
  onOpenSearch,
  onSwitchView,
  onRefresh,
}: {
  onCreateTask?: () => void
  onOpenSearch?: () => void
  onSwitchView?: (view: string) => void
  onRefresh?: () => void
}) {
  const shortcuts = React.useMemo<KeyboardShortcut[]>(() => {
    const list: KeyboardShortcut[] = []

    if (onCreateTask) {
      list.push({
        key: "c",
        action: onCreateTask,
        description: "Create new task",
      })
    }

    if (onOpenSearch) {
      list.push({
        key: "/",
        action: onOpenSearch,
        description: "Open search",
      })
    }

    if (onSwitchView) {
      list.push(
        { key: "1", action: () => onSwitchView("overview"), description: "Overview view" },
        { key: "2", action: () => onSwitchView("list"), description: "List view" },
        { key: "3", action: () => onSwitchView("board"), description: "Board view" },
        { key: "4", action: () => onSwitchView("calendar"), description: "Calendar view" },
        { key: "5", action: () => onSwitchView("timeline"), description: "Timeline view" }
      )
    }

    if (onRefresh) {
      list.push({
        key: "r",
        action: onRefresh,
        description: "Refresh data",
      })
    }

    return list
  }, [onCreateTask, onOpenSearch, onSwitchView, onRefresh])

  useKeyboardShortcuts(shortcuts)
}

// Global shortcuts hook
export function useGlobalShortcuts() {
  const [commandPaletteOpen, setCommandPaletteOpen] = React.useState(false)

  React.useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      // Cmd/Ctrl + K for command palette
      if ((event.metaKey || event.ctrlKey) && event.key === "k") {
        event.preventDefault()
        setCommandPaletteOpen((prev) => !prev)
      }
    }

    window.addEventListener("keydown", handleKeyDown)
    return () => window.removeEventListener("keydown", handleKeyDown)
  }, [])

  return {
    commandPaletteOpen,
    setCommandPaletteOpen,
  }
}

// Keyboard shortcuts help component
interface ShortcutHelpProps {
  shortcuts: Array<{
    keys: string[]
    description: string
  }>
  className?: string
}

export function ShortcutHelp({ shortcuts, className }: ShortcutHelpProps) {
  return (
    <div className={className}>
      <h4 className="text-sm font-medium text-gray-900 mb-3">Keyboard Shortcuts</h4>
      <div className="space-y-2">
        {shortcuts.map((shortcut, index) => (
          <div key={index} className="flex items-center justify-between text-sm">
            <span className="text-gray-600">{shortcut.description}</span>
            <div className="flex gap-1">
              {shortcut.keys.map((key, i) => (
                <kbd
                  key={i}
                  className="px-2 py-1 bg-gray-100 border border-gray-200 rounded text-xs font-mono text-gray-700"
                >
                  {key}
                </kbd>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

// Default shortcuts for activity module
export const activityShortcuts = [
  { keys: ["C"], description: "Create new task" },
  { keys: ["/"], description: "Open search" },
  { keys: ["1"], description: "Overview view" },
  { keys: ["2"], description: "List view" },
  { keys: ["3"], description: "Board view" },
  { keys: ["4"], description: "Calendar view" },
  { keys: ["5"], description: "Timeline view" },
  { keys: ["R"], description: "Refresh" },
  { keys: ["⌘", "K"], description: "Command palette" },
]
