import { cn } from "@/lib/utils"
import { AVATAR_COLORS } from "@/lib/activityConstants"

interface AvatarStackProps {
    participants: Array<{
        id?: number
        user_id?: number
        name?: string
        user?: { name?: string }
    }>
    max?: number
}

const avatarColors = AVATAR_COLORS

export function AvatarStack({ participants, max = 2 }: AvatarStackProps) {
    if (!participants || participants.length === 0) {
        return <span className="text-sm text-gray-400">—</span>
    }

    const visible = participants.slice(0, max)
    const remaining = participants.length - max

    return (
        <div className="flex items-center">
            <div className="flex items-center" style={{ marginLeft: 0 }}>
                {visible.map((participant, index) => {
                    const name = participant.name || participant.user?.name || "U"

                    return (
                        <div
                            key={participant.id || participant.user_id || index}
                            className={cn(
                                "w-9 h-9 rounded-full border-2 border-white flex items-center justify-center text-sm font-semibold shadow-sm",
                                avatarColors[index % avatarColors.length]
                            )}
                            style={{ marginLeft: index === 0 ? 0 : -10 }}
                            title={name}
                        >
                            {name.charAt(0).toUpperCase()}
                        </div>
                    )
                })}
                {remaining > 0 && (
                    <div
                        className="w-9 h-9 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-xs font-semibold text-gray-600 shadow-sm"
                        style={{ marginLeft: -10 }}
                    >
                        +{remaining}
                    </div>
                )}
            </div>
        </div>
    )
}

export default AvatarStack
