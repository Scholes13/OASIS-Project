import type { User } from '@/types';

interface ParticipantSelectorProps {
    users: User[];
    selectedIds: number[];
    onToggle: (userId: number) => void;
    compact?: boolean;
}

export default function ParticipantSelector({
    users,
    selectedIds,
    onToggle,
    compact = false,
}: ParticipantSelectorProps) {
    if (compact) {
        return (
            <div className="flex flex-wrap gap-2 p-3 border border-slate-200 rounded-lg min-h-[52px]">
                {users.map((user) => {
                    const selected = selectedIds.includes(user.id);

                    return (
                        <button
                            key={user.id}
                            type="button"
                            onClick={() => onToggle(user.id)}
                            className={`flex items-center gap-2 px-2.5 py-1.5 rounded-full text-[12px] font-medium transition-all border ${selected ? 'bg-slate-100 text-slate-800 border-slate-200 shadow-sm' : 'bg-white text-slate-500 border-dashed border-slate-300 hover:bg-slate-50'}`}
                        >
                            <div className={`w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white ${selected ? 'bg-[#16599c]' : 'bg-slate-300'}`}>
                                {user.name.charAt(0).toUpperCase()}
                            </div>
                            {user.name}
                        </button>
                    );
                })}
                {users.length === 0 && (
                    <span className="text-sm text-slate-400 py-1">No other team members in department.</span>
                )}
            </div>
        );
    }

    if (users.length === 0) {
        return null;
    }

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Participants</h3>
                {selectedIds.length > 0 && (
                    <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                        {selectedIds.length} Selected
                    </span>
                )}
            </div>
            <div className="p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {users.map((user) => {
                        const selected = selectedIds.includes(user.id);

                        return (
                            <label
                                key={user.id}
                                className={`relative flex items-center gap-4 p-3 rounded-xl border transition-all cursor-pointer group ${selected ? 'border-blue-300 bg-blue-50 ring-1 ring-blue-300' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'}`}
                            >
                                <input
                                    type="checkbox"
                                    checked={selected}
                                    onChange={() => onToggle(user.id)}
                                    className="sr-only"
                                />
                                <div className={`w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-colors ${selected ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200'}`}>
                                    {user.name.charAt(0).toUpperCase()}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className={`font-medium truncate ${selected ? 'text-primary' : 'text-gray-900'}`}>
                                        {user.name}
                                    </p>
                                    <p className="text-xs text-gray-500 truncate">{user.email}</p>
                                </div>
                                {selected && <div className="absolute top-3 right-3 w-2 h-2 rounded-full bg-primary" />}
                            </label>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
