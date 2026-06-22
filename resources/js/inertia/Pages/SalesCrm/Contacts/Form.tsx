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
    name: string;
    email: string | null;
    phone: string | null;
    mobile: string | null;
    birth_date: string | null;
    company: string | null;
    department: string | null;
    position: string | null;
    status: string;
    category: string;
    address: string | null;
    notes: string | null;
    social_media: {
        linkedin?: string;
        instagram?: string;
        facebook?: string;
    } | null;
}

interface ContactFormProps extends PageProps {
    contact?: Contact;
}

export default function Form({ contact }: ContactFormProps) {
    const { flash } = usePage<PageProps>().props;
    const isEditMode = !!contact;

    // Form data
    const { data, setData, post, put, processing, errors } = useForm({
        name: contact?.name || '',
        email: contact?.email || '',
        phone: contact?.phone || '',
        mobile: contact?.mobile || '',
        birth_date: contact?.birth_date || '',
        company: contact?.company || '',
        department: contact?.department || '',
        position: contact?.position || '',
        status: contact?.status || 'active',
        category: contact?.category || 'lead',
        address: contact?.address || '',
        notes: contact?.notes || '',
        linkedin: contact?.social_media?.linkedin || '',
        instagram: contact?.social_media?.instagram || '',
        facebook: contact?.social_media?.facebook || '',
    });

    // Handle flash messages
    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    // Handle form submission
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEditMode && contact) {
            put(route('sales-crm.contacts.update', { contact: contact.id }));
        } else {
            post(route('sales-crm.contacts.store'));
        }
    };

    // Handle cancel
    const handleCancel = () => {
        if (isEditMode && contact) {
            router.visit(route('sales-crm.contacts.show', { contact: contact.id }));
        } else {
            router.visit(route('sales-crm.contacts.index'));
        }
    };

    return (
        <>
            <Head title={isEditMode ? 'Edit Contact' : 'New Contact'} />

            <div className="w-full px-6 py-6 lg:px-8 max-w-4xl">
                    {/* Page Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">
                            {isEditMode ? 'Edit Contact' : 'New Contact'}
                        </h1>
                        <p className="mt-1 text-sm text-gray-600">
                            {isEditMode
                                ? 'Update contact information'
                                : 'Add a new contact to your CRM'}
                        </p>
                    </div>

                    {/* Form */}
                    <form onSubmit={handleSubmit}>
                        <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-100">
                                <h3 className="text-base font-semibold text-gray-900">
                                    Contact Information
                                </h3>
                            </div>

                            <div className="p-6 space-y-6">
                                {/* Basic Information */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Name <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="Enter contact name"
                                            className={cn(
                                                'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                                                errors.name ? 'border-red-300' : 'border-gray-300'
                                            )}
                                        />
                                        {errors.name && (
                                            <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Email
                                        </label>
                                        <input
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            placeholder="Enter email address"
                                            className={cn(
                                                'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                                                errors.email ? 'border-red-300' : 'border-gray-300'
                                            )}
                                        />
                                        {errors.email && (
                                            <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Phone
                                        </label>
                                        <input
                                            type="text"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            placeholder="Enter phone number"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Mobile
                                        </label>
                                        <input
                                            type="text"
                                            value={data.mobile}
                                            onChange={(e) => setData('mobile', e.target.value)}
                                            placeholder="Enter mobile number"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Birth Date
                                        </label>
                                        <input
                                            type="date"
                                            value={data.birth_date}
                                            onChange={(e) => setData('birth_date', e.target.value)}
                                            className={cn(
                                                'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                                                errors.birth_date ? 'border-red-300' : 'border-gray-300'
                                            )}
                                        />
                                        {errors.birth_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.birth_date}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Company Information */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-sm font-semibold text-gray-900 mb-4">
                                        Company Information
                                    </h4>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Company
                                            </label>
                                            <input
                                                type="text"
                                                value={data.company}
                                                onChange={(e) => setData('company', e.target.value)}
                                                placeholder="Enter company name"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            />
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
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Position
                                            </label>
                                            <input
                                                type="text"
                                                value={data.position}
                                                onChange={(e) => setData('position', e.target.value)}
                                                placeholder="Enter position"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Status & Category */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Status <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className={cn(
                                                'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                                                errors.status ? 'border-red-300' : 'border-gray-300'
                                            )}
                                        >
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="archived">Archived</option>
                                        </select>
                                        {errors.status && (
                                            <p className="mt-1 text-sm text-red-600">{errors.status}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Category <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={data.category}
                                            onChange={(e) => setData('category', e.target.value)}
                                            className={cn(
                                                'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                                                errors.category ? 'border-red-300' : 'border-gray-300'
                                            )}
                                        >
                                            <option value="lead">Lead</option>
                                            <option value="prospect">Prospect</option>
                                            <option value="customer">Customer</option>
                                            <option value="partner">Partner</option>
                                        </select>
                                        {errors.category && (
                                            <p className="mt-1 text-sm text-red-600">{errors.category}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Social Media */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-sm font-semibold text-gray-900 mb-4">
                                        Social Media
                                    </h4>

                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                LinkedIn
                                            </label>
                                            <input
                                                type="text"
                                                value={data.linkedin}
                                                onChange={(e) => setData('linkedin', e.target.value)}
                                                placeholder="LinkedIn profile URL"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Instagram
                                            </label>
                                            <input
                                                type="text"
                                                value={data.instagram}
                                                onChange={(e) => setData('instagram', e.target.value)}
                                                placeholder="Instagram username"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Facebook
                                            </label>
                                            <input
                                                type="text"
                                                value={data.facebook}
                                                onChange={(e) => setData('facebook', e.target.value)}
                                                placeholder="Facebook profile URL"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Address */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Address
                                    </label>
                                    <textarea
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        rows={3}
                                        placeholder="Enter address"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>

                                {/* Notes */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Notes
                                    </label>
                                    <textarea
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        rows={4}
                                        placeholder="Enter any additional notes"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
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
                                    className="bg-blue-600 hover:bg-blue-700 text-white"
                                >
                                    <Save className="w-4 h-4 mr-2" />
                                    {isEditMode ? 'Update Contact' : 'Save Contact'}
                                </Button>
                            </div>
                        </div>
                    </form>
            </div>
        </>
    );
}
