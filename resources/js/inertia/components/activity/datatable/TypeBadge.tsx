import { cn } from "@/lib/utils"
import { ACTIVITY_TYPE_COLORS } from "@/lib/activityConstants"

interface TypeBadgeProps {
    name: string
    color?: string
}

const typeColors = ACTIVITY_TYPE_COLORS

export function TypeBadge({ name, color }: TypeBadgeProps) {
    const colorClass = typeColors[color || "gray"] || typeColors.gray

    return (
        <span
            className={cn(
                "inline-flex items-center px-2.5 py-1 rounded-md text-sm font-medium ring-1 ring-inset",
                colorClass.bg,
                colorClass.text
            )}
        >
            {name}
        </span>
    )
}

export default TypeBadge
