import * as React from "react"
import { Menu } from "@headlessui/react"
import { cn } from "@/lib/utils"

interface DropdownMenuProps {
    children: React.ReactNode
    open?: boolean
    onClose?: () => void
}

export function DropdownMenu({ children }: DropdownMenuProps) {
    return (
        <Menu as="div" className="relative inline-block text-left">
            <Menu.Items
                className="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-md bg-white border border-gray-200 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            >
                <div className="py-1">
                    {children}
                </div>
            </Menu.Items>
        </Menu>
    )
}

interface DropdownMenuTriggerProps {
    children: React.ReactNode
    className?: string
}

export function DropdownMenuTrigger({ children, className }: DropdownMenuTriggerProps) {
    return (
        <Menu.Button>
            <div className={className}>
                {children}
            </div>
        </Menu.Button>
    )
}

interface DropdownMenuContentProps {
    children: React.ReactNode
    className?: string
    align?: "start" | "center" | "end"
}

export function DropdownMenuContent({ children, className, align = "end" }: DropdownMenuContentProps) {
    return (
        <div className={cn("py-1", className)}>
            {children}
        </div>
    )
}

interface DropdownMenuItemProps {
    children: React.ReactNode
    onClick?: () => void
    className?: string
}

export function DropdownMenuItem({ children, onClick, className }: DropdownMenuItemProps) {
    return (
        <Menu.Item>
            {({ active }) => (
                <button
                    type="button"
                    onClick={onClick}
                    className={cn(
                        "w-full flex items-center justify-start px-4 py-2 text-sm text-left",
                        active ? "bg-blue-50 text-blue-800" : "text-gray-700",
                        className
                    )}
                >
                    {children}
                </button>
            )}
        </Menu.Item>
    )
}