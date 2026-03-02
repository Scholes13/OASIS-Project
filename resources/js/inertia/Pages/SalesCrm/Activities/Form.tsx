import { useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

// Types
interface Contact {
    id: number;
    label: string;
}

interface Activity {
    id: number;
    activity_type: string;
    activity_date: string;
    title: string;
    department: string | null;
    pic_name: string | null;
    pic_email: string | null;
    pic_phone: string | null;
    pic_position: string | null;
    pic_birth_date: string | null;
    office_address: string | null;
    description: string | null;
    status: string;
    contact_id: number | null;
}

interface ActivityFormProps extends PageProps {
    activity?: Activity;
    availableContacts: Contact[];
}

export default function Form({ activity, availableContacts }: ActivityFormProps) {
    const { flash } = usePage<PageProps>().props;
    const isEditMode = !!activity;

    // Form data
    const { data, setData, post, put, processing, errors } = useForm({
        activity_type: activity?.activity_type || 'call',
        activity_date: activity?.activity_date || new Date().toISOString().split('T')[0],
        company_name: activity?.title || '',
        department: activity?.department || '',
        pic_name: activity?.pic_name || '',
        pic_email: activity?.pic_email || '',
        pic_phone: activity?.pic_phone || '',
        pic_position: activity?.pic_position || '',
        pic_birth_date: activity?.pic_birth_date || '',
        office_address: activity?.office_address || '',
        description: activity?.description || '',
        status: activity?.status || 'completed',
        link_contact: false,
        existing_contact_id: activity?.contact_id || null,
        create_new_contact: false,
        linkedin: '',
        instagram: '',
        facebook: '',
    });

    // Handle flash messages
    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    // Handle form submission
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEditMode && activity) {
            put(route('sales-crm.activities.update', activity.id));
        } else {
            post(route('sales-crm.activities.store'));
        }
    };

    // Handle cancel
    const handleCancel = () => {
        if (isEditMode && activity) {
            router.visit(route('sales-crm.activities.show', activity.id));
        } else {
            router.visit(route('sales-crm.activities.index'));
        }
    };

    // Handle contact selection
    const handleContactChange = (contactId: string) => {
        const selectedId = contactId ? parseInt(contactId) : null;
        setData('existing_contact_id', selectedId);

        if (selectedId) {
            // In a real implementation, you would fetch contact details
            // For now, we'll just set the ID
        }
    };

    return (
        <>
            <Head title={isEditMode ? 'Edit Activity' : 'New Activity'} />

            <div className="w-full px-6 py-6 lg:px-8 max-w-4xl">
                    {/* Page Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">
                            {isEditMode ? 'Edit Activity' : 'New Activity'}
                        </h1>
                        <p className="mt-1 text-sm text-gray-600">
                            {isEditMode
                                ? 'Update activity details'
                                : 'Record a new sales activity'}
                        </p>
                    </div>

                    {/* Form */}
                    <form onSubmit={handleSubmit}>
                        <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-100">
                                <h3 className="text-base font-semibold text-gray-900">
                                    Activity Information
                                </h3>
                            </div>

                            <div className="p-6 space-y-6">
                                {/* Activity Type & Date */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Activity Type <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={data.activity_type}
                                            onChange={(e) => setData('activity_type', e.target.value)}
                                            className={cn(
                                                'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary',
                                                errors.activity_type ? 'border-red-300' : 'border-gray-300'
                                            )}
                                        >
                                            <option value="call">Phone Call</option>
                                            <option value="visit">Site Visit</option>
                                            <option value="meeting">Meeting</option>
                                            <option value="blitz">Blitz</option>
                                            <option value="follow_up">Follow Up</option>
                                            <option value="other">Other</option>
                                        </select>
                                        {errors.activity_type && (
                                            <p className="mt-1 text-sm text-red-600">{errors.activity_type}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Activity Date <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            value={data.activity_date}
                                            onChange={(e) => setData('activity_date', e.target.value)}
                                            className={cn(
                                                'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary',
                                                errors.activity_date ? 'border-red-300' : 'border-gray-300'
                                            )}
                                        />
                                        {errors.activity_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.activity_date}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Company Name & Department */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Company Name <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={data.company_name}
                                            onChange={(e) => setData('company_name', e.target.value)}
                                            placeholder="Enter company name"
                                            className={cn(
                                                'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary',
                                                errors.company_name ? 'border-red-300' : 'border-gray-300'
                                            )}
                                        />
                                        {errors.company_name && (
                                            <p className="mt-1 text-sm text-red-600">{errors.company_name}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Department
                                        </label>
                                        <input
                                            type="text"
                                            value={data.department}
                                            onChange={(e) => setData('department', e.target.value)}
                                            placeholder="Enter department"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                        />
                                    </div>
                                </div>

                                {/* PIC Information */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-sm font-semibold text-gray-900 mb-4">
                                        Person in Charge (PIC) Information
                                    </h4>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                PIC Name
                                            </label>
                                            <input
                                                type="text"
                                                value={data.pic_name}
                                                onChange={(e) => setData('pic_name', e.target.value)}
                                                placeholder="Enter PIC name"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                PIC Position <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                value={data.pic_position}
                                                onChange={(e) => setData('pic_position', e.target.value)}
                                                placeholder="Enter PIC position"
                                                className={cn(
                                                    'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary',
                                                    errors.pic_position ? 'border-red-300' : 'border-gray-300'
                                                )}
                                            />
                                            {errors.pic_position && (
                                                <p className="mt-1 text-sm text-red-600">{errors.pic_position}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                PIC Email
                                            </label>
                                            <input
                                                type="email"
                                                value={data.pic_email}
                                                onChange={(e) => setData('pic_email', e.target.value)}
                                                placeholder="Enter PIC email"
                                                className={cn(
                                                    'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary',
                                                    errors.pic_email ? 'border-red-300' : 'border-gray-300'
                                                )}
                                            />
                                            {errors.pic_email && (
                                                <p className="mt-1 text-sm text-red-600">{errors.pic_email}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                PIC Phone
                                            </label>
                                            <input
                                                type="text"
                                                value={data.pic_phone}
                                                onChange={(e) => setData('pic_phone', e.target.value)}
                                                placeholder="Enter PIC phone"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Office Address */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Office Address
                                    </label>
                                    <textarea
                                        value={data.office_address}
                                        onChange={(e) => setData('office_address', e.target.value)}
                                        rows={3}
                                        placeholder="Enter office address"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    />
                                </div>

                                {/* Description */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Description / Notes
                                    </label>
                                    <textarea
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={4}
                                        placeholder="Enter activity description or notes"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    />
                                </div>

                                {/* Status */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Status <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className={cn(
                                            'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary',
                                            errors.status ? 'border-red-300' : 'border-gray-300'
                                        )}
                                    >
                                        <option value="planned">Planned</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    {errors.status && (
                                        <p className="mt-1 text-sm text-red-600">{errors.status}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        Note: Contact will only be created when status is "Completed"
                                    </p>
                                </div>

                                {/* Link Contact */}
                                <div className="border-t border-gray-200 pt-6">
                                    <div className="flex items-center mb-4">
                                        <input
                                            type="checkbox"
                                            checked={data.link_contact}
                                            onChange={(e) => setData('link_contact', e.target.checked)}
                                            className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                        />
                                        <label className="ml-2 block text-sm font-medium text-gray-700">
                                            Link to existing contact
                                        </label>
                                    </div>

                                    {data.link_contact && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Select Contact
                                            </label>
                                            <select
                                                value={data.existing_contact_id || ''}
                                                onChange={(e) => handleContactChange(e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                            >
                                                <option value="">Select a contact...</option>
                                                {availableContacts.map((contact) => (
                                                    <option key={contact.id} value={contact.id}>
                                                        {contact.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleCancel}
                                    disabled={processing}
                                >
                                    <X className="w-4 h-4 mr-2" />
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    loading={processing}
                                    className="bg-primary hover:bg-blue-600 text-white"
                                >
                                    <Save className="w-4 h-4 mr-2" />
                                    {isEditMode ? 'Update Activity' : 'Save Activity'}
                                </Button>
                            </div>
                        </div>
                    </form>
            </div>
        </>
    );
}
