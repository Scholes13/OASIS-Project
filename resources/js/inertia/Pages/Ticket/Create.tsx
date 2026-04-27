import { useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft, LifeBuoy } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { TicketForm } from '@/components/Ticket/TicketForm';
import { PageProps } from '@/types';
import type { TicketCategory } from '@/types';

interface CreatePageProps extends PageProps {
    categories: TicketCategory[];
}

export default function Create({ categories }: CreatePageProps) {
    // Filter active categories
    const activeCategories = useMemo(() => 
        categories.filter(c => c.is_active),
        [categories]
    );

    const handleSuccess = () => {
        router.visit(route('it-support.my-tickets'));
    };

    const handleCancel = () => {
        router.visit(route('it-support.my-tickets'));
    };

    return (
        <AppLayout title="Submit Ticket">
            <Head title="Submit Ticket" />

            <div className="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Page Header */}
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                >
                    {/* Back Navigation */}
                    <div className="mb-6">
                        <Link
                            href={route('it-support.my-tickets')}
                            className="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4" />
                            Kembali ke Tiket Saya
                        </Link>
                    </div>

                    {/* Title Section */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
                                <LifeBuoy className="w-6 h-6 text-primary" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">
                                    Ajukan Tiket Baru
                                </h1>
                                <p className="text-sm text-gray-500 mt-1">
                                    Lengkapi formulir di bawah untuk mengajukan permintaan dukungan IT
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <TicketForm
                            categories={activeCategories}
                            onSuccess={handleSuccess}
                            onCancel={handleCancel}
                            submitLabel="Kirim Tiket"
                        />
                    </div>

                    {/* Help Text */}
                    <div className="mt-6 bg-blue-50 border border-blue-100 rounded-xl p-4">
                        <h3 className="text-sm font-medium text-blue-800 mb-2">
                            Tips Pengajuan Tiket
                        </h3>
                        <ul className="text-sm text-blue-700 space-y-1 list-disc list-inside">
                            <li>Pastikan judul ticket jelas dan spesifik</li>
                            <li>Jelaskan masalah atau permintaan Anda secara detail</li>
                            <li>Lampirkan screenshot jika diperlukan</li>
                            <li>Pilih prioritas yang sesuai dengan urgensi masalah</li>
                        </ul>
                    </div>
                </motion.div>
            </div>
        </AppLayout>
    );
}