import * as React from "react"
import { router } from "@inertiajs/react"
import {
  Command,
  CommandDialog,
  CommandInput,
  CommandList,
  CommandEmpty,
  CommandGroup,
  CommandItem,
  CommandShortcut,
  CommandSeparator,
} from "./command"
import {
  Plus,
  Search,
  Home,
  CalendarDays,
  ClipboardList,
  BarChart3,
  Settings,
  User,
  LogOut,
  Sun,
  Moon,
  Laptop,
  FileText,
  Users,
  Building2,
  FolderKanban,
} from "lucide-react"

interface CommandPaletteProps {
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function CommandPalette({ open, onOpenChange }: CommandPaletteProps) {
  const [search, setSearch] = React.useState("")

  const runCommand = React.useCallback((command: () => void) => {
    onOpenChange(false)
    command()
  }, [onOpenChange])

  // Navigation commands
  const navigationCommands = [
    {
      icon: Home,
      label: "Go to Dashboard",
      shortcut: "G D",
      action: () => router.visit("/dashboard"),
    },
    {
      icon: ClipboardList,
      label: "Go to Activities",
      shortcut: "G A",
      action: () => router.visit("/activity-react"),
    },
    {
      icon: FileText,
      label: "Go to Purchase Requests",
      shortcut: "G P",
      action: () => router.visit("/purchase-requests"),
    },
    {
      icon: Users,
      label: "Go to Users",
      shortcut: "G U",
      action: () => router.visit("/admin/users"),
    },
    {
      icon: Building2,
      label: "Go to Business Units",
      shortcut: "G B",
      action: () => router.visit("/admin/business-units"),
    },
  ]

  // Action commands
  const actionCommands = [
    {
      icon: Plus,
      label: "Create New Activity",
      shortcut: "C A",
      action: () => router.visit("/activity-react/create"),
    },
    {
      icon: Plus,
      label: "Create Purchase Request",
      shortcut: "C P",
      action: () => router.visit("/purchase-requests/create"),
    },
  ]

  // View commands
  const viewCommands = [
    {
      icon: FolderKanban,
      label: "Board View",
      action: () => router.visit("/activity-react?view=board"),
    },
    {
      icon: CalendarDays,
      label: "Calendar View",
      action: () => router.visit("/activity-react?view=calendar"),
    },
    {
      icon: BarChart3,
      label: "Analytics View",
      action: () => router.visit("/activity-react?view=overview"),
    },
  ]

  return (
    <CommandDialog open={open} onOpenChange={onOpenChange}>
      <CommandInput
        placeholder="Type a command or search..."
        value={search}
        onValueChange={setSearch}
        onClear={() => setSearch("")}
      />
      <CommandList>
        <CommandEmpty>No results found.</CommandEmpty>
        
        <CommandGroup heading="Navigation">
          {navigationCommands.map((cmd) => (
            <CommandItem
              key={cmd.label}
              onSelect={() => runCommand(cmd.action)}
            >
              <cmd.icon className="mr-2 h-4 w-4" />
              <span>{cmd.label}</span>
              {cmd.shortcut && <CommandShortcut>{cmd.shortcut}</CommandShortcut>}
            </CommandItem>
          ))}
        </CommandGroup>

        <CommandSeparator />

        <CommandGroup heading="Actions">
          {actionCommands.map((cmd) => (
            <CommandItem
              key={cmd.label}
              onSelect={() => runCommand(cmd.action)}
            >
              <cmd.icon className="mr-2 h-4 w-4" />
              <span>{cmd.label}</span>
              {cmd.shortcut && <CommandShortcut>{cmd.shortcut}</CommandShortcut>}
            </CommandItem>
          ))}
        </CommandGroup>

        <CommandSeparator />

        <CommandGroup heading="Views">
          {viewCommands.map((cmd) => (
            <CommandItem
              key={cmd.label}
              onSelect={() => runCommand(cmd.action)}
            >
              <cmd.icon className="mr-2 h-4 w-4" />
              <span>{cmd.label}</span>
            </CommandItem>
          ))}
        </CommandGroup>

        <CommandSeparator />

        <CommandGroup heading="Settings">
          <CommandItem onSelect={() => runCommand(() => router.visit("/profile"))}>
            <User className="mr-2 h-4 w-4" />
            <span>Profile</span>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => router.visit("/settings"))}>
            <Settings className="mr-2 h-4 w-4" />
            <span>Settings</span>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => router.post("/logout"))}>
            <LogOut className="mr-2 h-4 w-4" />
            <span>Log out</span>
          </CommandItem>
        </CommandGroup>
      </CommandList>
      
      {/* Footer */}
      <div className="border-t px-3 py-2 text-xs text-gray-500 flex items-center justify-between">
        <span>
          Press <kbd className="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-mono">↑↓</kbd> to navigate
        </span>
        <span>
          <kbd className="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-mono">Enter</kbd> to select
        </span>
        <span>
          <kbd className="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-mono">Esc</kbd> to close
        </span>
      </div>
    </CommandDialog>
  )
}

// Hook to use command palette
export function useCommandPalette() {
  const [open, setOpen] = React.useState(false)

  React.useEffect(() => {
    const down = (e: KeyboardEvent) => {
      if (e.key === "k" && (e.metaKey || e.ctrlKey)) {
        e.preventDefault()
        setOpen((open) => !open)
      }
    }

    document.addEventListener("keydown", down)
    return () => document.removeEventListener("keydown", down)
  }, [])

  return { open, setOpen }
}
