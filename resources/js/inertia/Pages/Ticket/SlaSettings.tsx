import { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useForm as useRHForm } from 'react-hook-form';
import {
    Clock, Save, AlertCircle, Info,
} from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/Card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { PageProps, TicketSlaSettings, TicketPriority } from '@/types';

// ── Types ──────────────────────────────────────────────────────────────
interface SlaSettingsProps extends PageProps {
    settings: TicketSlaSettings[];
}

// ── Priority labels ──────────────────────────────────────────────────────
const priorityLabels: Record<TicketPriority, string> = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi',
    critical: 'Kritis',
};

const priorityDescriptions: Record<TicketPriority, string> = {
    low: 'Non-critical issues, can be addressed within 3 days',
    medium: 'Standard support issues, should be addressed within 24 hours',
    high: 'Critical business impact, should be addressed within 8 hours',
    critical: 'System down or major disruption, must be addressed immediately',
};

const priorityColors: Record<TicketPriority, string> = {
    low: 'bg-slate-100 text-slate-700 border-slate-200',
    medium: 'bg-blue-100 text-blue-700 border-blue-200',
    high: 'bg-orange-100 text-orange-700 border-orange-200',
    critical: 'bg-red-100 text-red-700 border-red-200',
};

// ── Default settings ─────────────────────────────────────────────────
const defaultSettings: TicketSlaSettings[] = [
    { id: 1, priority: 'low', resolution_hours: 72 },
    { id: 2, priority: 'medium', resolution_hours: 24 },
    { id: 3, priority: 'high', resolution_hours: 8 },
    { id: 4, priority: 'critical', resolution_hours: 4 },
];

export default function TicketSlaSettings({ settings }: SlaSettingsProps) {
    const { flash } = usePage<PageProps>().props;

    // Use provided settings or fallbacks to defaults
    const currentSettings = settings?.length > 0 ? settings : defaultSettings;

    // Form state for each priority
    const [settingsForm, setSettingsForm] = useState<Record<TicketPriority, number>>(() => {
        const initial: Record<TicketPriority, number> = {
            low: 72,
            medium: 24,
            high: 8,
            critical: 4,
        };
        currentSettings.forEach(s => {
            initial[s.priority] = s.resolution_hours;
        });
        return initial;
    });

    const [isSaving, setIsSaving] = useState(false);

    const updateResolutionHours = (priority: TicketPriority, hours: number) => {
        setSettingsForm(prev => ({
            ...prev,
            [priority]: hours,
        }));
    };

    // Handle save using useForm from Inertia
    interface SlaSettingsFormData {
        settings: Array<{ priority: string; resolution_hours: number }>;
    }
    const { put, setData, processing } = useForm<SlaSettingsFormData>({
        settings: [],
    });

    const handleSave = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSaving(true);

        const settingsData = [
            { priority: 'low', resolution_hours: settingsForm.low },
            { priority: 'medium', resolution_hours: settingsForm.medium },
            { priority: 'high', resolution_hours: settingsForm.high },
            { priority: 'critical', resolution_hours: settingsForm.critical },
        ] as const;

        setData('settings', [...settingsData]);

        put(route('it-support.admin.sla-settings.update'), {
            onSuccess: () => {
                toast.success('SLA settings saved successfully');
                setIsSaving(false);
            },
            onError: () => {
                toast.error('Failed to save SLA settings');
                setIsSaving(false);
            },
        });
    };

    const priorities: TicketPriority[] = ['low', 'medium', 'high', 'critical'];

    return (
        <>
            <Head title="SLA Settings" />

            <div className="w-full px-6 py-6 lg:px-8 space-y-6">
                {/* ── Header ──────────────────────────────────────────────── */}
                <div className="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                    <div className="flex flex-col gap-1.5">
                        <h1 className="text-2xl font-bold text-gray-900 tracking-tight">SLA Settings</h1>
                        <p className="text-sm text-gray-500">Configure Service Level Agreement resolution times</p>
                    </div>
                </div>

                {/* ── Info Box ──────────────────────────────────────────────── */}
                <Card className="bg-blue-50 border-blue-200">
                    <div className="flex gap-3 p-4">
                        <Info className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                        <div>
                            <h3 className="text-sm font-medium text-blue-900 mb-2">About SLA Settings</h3>
                            <div className="text-sm text-blue-800 space-y-1">
                                <p>
                                    SLA (Service Level Agreement) settings define the maximum time allowed to resolve tickets based on their priority level.
                                </p>
                                <p>
                                    When a ticket approaches or exceeds its SLA deadline, the system will show visual warnings on the ticket.
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* ── Settings Table ──────────────────────────────────────── */}
                <Card className="border border-gray-200 rounded-lg">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Clock className="w-5 h-5" />
                            Resolution Time by Priority
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form id="sla-form" onSubmit={handleSave}>
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="bg-gray-100 border-b border-gray-200">
                                            <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700 w-48">Priority</th>
                                            <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Description</th>
                                            <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700 w-48">Resolution Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {priorities.map((priority) => (
                                            <tr key={priority} className="border-b border-gray-100 hover:bg-gray-50/80">
                                                <td className="px-5 py-4">
                                                    <span className={cn(
                                                        'inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium border',
                                                        priorityColors[priority]
                                                    )}>
                                                        {priorityLabels[priority]}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <span className="text-sm text-gray-600">
                                                        {priorityDescriptions[priority]}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <div className="flex items-center gap-2">
                                                        <Input
                                                            type="number"
                                                            min={1}
                                                            max={999}
                                                            value={settingsForm[priority]}
                                                            onChange={(e) => updateResolutionHours(priority, parseInt(e.target.value) || 0)}
                                                            className="w-24 text-center"
                                                        />
                                                        <span className="text-sm text-gray-500">hours</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </CardContent>
                    <CardFooter className="flex justify-end gap-2 pt-4 border-t border-gray-100">
                        <Button
                            type="submit"
                            form="sla-form"
                            loading={isSaving || processing}
                        >
                            <Save className="w-4 h-4 mr-2" />
                            Save Settings
                        </Button>
                    </CardFooter>
                </Card>

                {/* ── Quick Reference ────────────────────────────────────── */}
                <Card className="bg-gray-50 border-gray-200">
                    <div className="flex gap-3 p-4">
                        <Info className="w-5 h-5 text-gray-600 flex-shrink-0 mt-0.5" />
                        <div>
                            <h3 className="text-sm font-medium text-gray-900 mb-2">Recommended SLA Values</h3>
                            <div className="text-sm text-gray-700 space-y-1">
                                <p>
                                    <strong>Standard:</strong> Low: 72h, Medium: 24h, High: 8h, Critical: 4h
                                </p>
                                <p>
                                    <strong>24/7 Support:</strong> Low: 48h, Medium: 12h, High: 4h, Critical: 2h
                                </p>
                                <p>
                                    <strong>Mission Critical:</strong> Low: 24h, Medium: 8h, High: 4h, Critical: 1h
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </>
    );
}