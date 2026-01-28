import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { Plus, Search, Edit2, Trash2, X, Check } from 'lucide-react';
import { toast } from 'sonner';
import { ActivityTypeIndexProps, ActivityType, ActivityTypeFormData } from '@/types/admin';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ColorPicker } from '@/components/admin/ColorPicker';
import { Badge } from '@/components/ui/Badge';
import { EmptyState } from '@/components/ui/empty-state';

function Index({ activityTypes, filters }: ActivityTypeIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [editingId, setEditingId] = useState<number | null>(null);
    const [isCreating, setIsCreating] = useState(false);
    const [formData, setFormData] = useState<ActivityTypeFormData>({
        name: '',
        color: 'blue',
    });
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Debounced search
    React.useEffect(() => {
        const timer = setTimeout(() => {
            router.get(
                route('admin.activity-types.index'),
                { search },
                { preserveState: true, replace: true }
            );
        }, 300);

        return () => clearTimeout(timer);
    }, [search]);

    const handleCreate = () => {
        setIsCreating(true);
        setEditingId(null);
        setFormData({ name: '', color: 'blue' });
    };

    const handleEdit = (activityType: ActivityType) => {
        setEditingId(activityType.id);
        setIsCreating(false);
        setFormData({
            name: activityType.name,
            color: activityType.color,
        });
    };

    const handleCancel = () => {
        setIsCreating(false);
        setEditingId(null);
        setFormData({ name: '', color: 'blue' });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!formData.name.trim()) {
            toast.error('Activity type name is required');
            return;
        }

        setIsSubmitting(true);

        if (isCreating) {
            router.post(route('admin.activity-types.store'), formData as any, {
                onSuccess: () => {
                    toast.success('Activity type created successfully');
                    handleCancel();
                },
                onError: (errors: any) => {
                    toast.error(errors.name || 'Failed to create activity type');
                },
                onFinish: () => setIsSubmitting(false),
            });
        } else if (editingId) {
            router.put(route('admin.activity-types.update', { activity_type: editingId }), formData as any, {
                onSuccess: () => {
                    toast.success('Activity type updated successfully');
                    handleCancel();
                },
                onError: (errors: any) => {
                    toast.error(errors.name || 'Failed to update activity type');
                },
                onFinish: () => setIsSubmitting(false),
            });
        }
    };

    const handleDelete = (activityType: ActivityType) => {
        if (activityType.sub_activities_count && activityType.sub_activities_count > 0) {
            toast.error('Cannot delete activity type with sub-activities. Delete sub-activities first.');
            return;
        }

        if (activityType.usage_count && activityType.usage_count > 0) {
            toast.error('Cannot delete activity type that is being used by tasks. Consider deactivating it instead.');
            return;
        }

        if (!confirm('Are you sure you want to delete this activity type?')) {
            return;
        }

        router.delete(route('admin.activity-types.destroy', { activity_type: activityType.id }), {
            onSuccess: () => {
                toast.success('Activity type deleted successfully');
            },
            onError: () => {
                toast.error('Failed to delete activity type');
            },
        });
    };

    const getColorClasses = (color: string) => {
        const colorMap: Record<string, string> = {
            blue: 'bg-blue-100 text-blue-800',
            green: 'bg-green-100 text-green-800',
            purple: 'bg-purple-100 text-purple-800',
            pink: 'bg-pink-100 text-pink-800',
            yellow: 'bg-yellow-100 text-yellow-800',
            red: 'bg-red-100 text-red-800',
            gray: 'bg-gray-100 text-gray-800',
            indigo: 'bg-indigo-100 text-indigo-800',
            amber: 'bg-amber-100 text-amber-800',
            emerald: 'bg-emerald-100 text-emerald-800',
            cyan: 'bg-cyan-100 text-cyan-800',
            rose: 'bg-rose-100 text-rose-800',
        };
        return colorMap[color] || 'bg-gray-100 text-gray-800';
    };

    return (
        <>
            <Head title="Activity Types" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Activity Types</h1>
                        <p className="text-sm text-gray-600 mt-1">
                            Manage activity types for employee task tracking
                        </p>
                    </div>
                    <Button onClick={handleCreate} disabled={isCreating}>
                        <Plus className="w-4 h-4 mr-2" />
                        Add Activity Type
                    </Button>
                </div>

                {/* Search */}
                <div className="bg-white rounded-xl border border-gray-200 p-4">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <Input
                            type="text"
                            placeholder="Search activity types..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-10"
                        />
                    </div>
                </div>

                {/* Create Form */}
                <AnimatePresence>
                    {isCreating && (
                        <motion.div
                            initial={{ opacity: 0, height: 0 }}
                            animate={{ opacity: 1, height: 'auto' }}
                            exit={{ opacity: 0, height: 0 }}
                            className="bg-white rounded-xl border border-gray-200 overflow-hidden"
                        >
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                    Create Activity Type
                                </h3>
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div>
                                        <Label htmlFor="name">Name *</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            value={formData.name}
                                            onChange={(e) =>
                                                setFormData({ ...formData, name: e.target.value })
                                            }
                                            placeholder="Enter activity type name"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="color">Color *</Label>
                                        <ColorPicker
                                            label=""
                                            value={formData.color}
                                            onChange={(color) =>
                                                setFormData({ ...formData, color })
                                            }
                                        />
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Button type="submit" disabled={isSubmitting}>
                                            <Check className="w-4 h-4 mr-2" />
                                            Create
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={handleCancel}
                                            disabled={isSubmitting}
                                        >
                                            <X className="w-4 h-4 mr-2" />
                                            Cancel
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>

                {/* Activity Types List */}
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    {activityTypes.data.length === 0 ? (
                        <EmptyState
                            title="No activity types found"
                            description={
                                search
                                    ? 'Try adjusting your search query'
                                    : 'Get started by creating your first activity type'
                            }
                        />
                    ) : (
                        <div className="divide-y divide-gray-200">
                            {activityTypes.data.map((activityType) => (
                                <motion.div
                                    key={activityType.id}
                                    layout
                                    className="p-6 hover:bg-gray-50 transition-colors"
                                >
                                    {editingId === activityType.id ? (
                                        <form onSubmit={handleSubmit} className="space-y-4">
                                            <div>
                                                <Label htmlFor={`edit-name-${activityType.id}`}>
                                                    Name *
                                                </Label>
                                                <Input
                                                    id={`edit-name-${activityType.id}`}
                                                    type="text"
                                                    value={formData.name}
                                                    onChange={(e) =>
                                                        setFormData({
                                                            ...formData,
                                                            name: e.target.value,
                                                        })
                                                    }
                                                    placeholder="Enter activity type name"
                                                    required
                                                />
                                            </div>

                                            <div>
                                                <Label htmlFor={`edit-color-${activityType.id}`}>
                                                    Color *
                                                </Label>
                                                <ColorPicker
                                                    label=""
                                                    value={formData.color}
                                                    onChange={(color) =>
                                                        setFormData({ ...formData, color })
                                                    }
                                                />
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <Button type="submit" disabled={isSubmitting}>
                                                    <Check className="w-4 h-4 mr-2" />
                                                    Save
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={handleCancel}
                                                    disabled={isSubmitting}
                                                >
                                                    <X className="w-4 h-4 mr-2" />
                                                    Cancel
                                                </Button>
                                            </div>
                                        </form>
                                    ) : (
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-4">
                                                <Badge className={getColorClasses(activityType.color)}>
                                                    {activityType.name}
                                                </Badge>
                                                <div className="flex items-center gap-4 text-sm text-gray-600">
                                                    <span>
                                                        {activityType.sub_activities_count || 0} sub-activities
                                                    </span>
                                                    <span>
                                                        {activityType.usage_count || 0} tasks
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleEdit(activityType)}
                                                >
                                                    <Edit2 className="w-4 h-4" />
                                                </Button>
                                                {(!activityType.sub_activities_count || activityType.sub_activities_count === 0) &&
                                                    (!activityType.usage_count || activityType.usage_count === 0) && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDelete(activityType)}
                                                            className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </Button>
                                                    )}
                                            </div>
                                        </div>
                                    )}
                                </motion.div>
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    {activityTypes.meta.last_page > 1 && (
                        <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                            <div className="text-sm text-gray-600">
                                Showing {activityTypes.meta.from} to {activityTypes.meta.to} of{' '}
                                {activityTypes.meta.total} results
                            </div>
                            <div className="flex items-center gap-2">
                                {activityTypes.meta.current_page > 1 && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                route('admin.activity-types.index'),
                                                {
                                                    ...filters,
                                                    page: activityTypes.meta.current_page - 1,
                                                },
                                                { preserveState: true }
                                            )
                                        }
                                    >
                                        Previous
                                    </Button>
                                )}
                                {activityTypes.meta.current_page < activityTypes.meta.last_page && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                route('admin.activity-types.index'),
                                                {
                                                    ...filters,
                                                    page: activityTypes.meta.current_page + 1,
                                                },
                                                { preserveState: true }
                                            )
                                        }
                                    >
                                        Next
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

export default Index;
