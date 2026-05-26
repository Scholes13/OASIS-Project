interface Time24InputProps {
    id: string;
    value: string;
    onChange: (value: string) => void;
    hasError?: boolean;
    compact?: boolean;
}

const HOUR_OPTIONS = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
const MINUTE_OPTIONS = Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, '0'));

export default function Time24Input({
    id,
    value,
    onChange,
    hasError = false,
    compact = false,
}: Time24InputProps) {
    const [hour = '', minute = ''] = value ? value.split(':') : ['', ''];
    const baseClass = `w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white ${hasError ? 'border-red-500' : 'border-gray-200'}`;

    const handleHourChange = (nextHour: string) => {
        if (!nextHour) {
            onChange('');
            return;
        }

        onChange(`${nextHour}:${minute || '00'}`);
    };

    const handleMinuteChange = (nextMinute: string) => {
        if (!hour) {
            onChange('');
            return;
        }

        onChange(`${hour}:${nextMinute || '00'}`);
    };

    return (
        <div className={compact ? 'flex gap-1 items-center' : 'grid grid-cols-2 gap-2'}>
            <select
                id={`${id}-hour`}
                value={hour}
                onChange={(event) => handleHourChange(event.target.value)}
                className={baseClass}
            >
                <option value="">{compact ? 'HH' : 'Jam'}</option>
                {HOUR_OPTIONS.map((option) => (
                    <option
                        key={option}
                        value={option}
                    >
                        {option}
                    </option>
                ))}
            </select>

            {compact && <span className="text-slate-400 font-bold">:</span>}

            <select
                id={`${id}-minute`}
                value={minute}
                onChange={(event) => handleMinuteChange(event.target.value)}
                disabled={!compact && !hour}
                className={baseClass}
            >
                <option value="">{compact ? 'MM' : 'Menit'}</option>
                {MINUTE_OPTIONS.map((option) => (
                    <option
                        key={option}
                        value={option}
                    >
                        {option}
                    </option>
                ))}
            </select>
        </div>
    );
}
