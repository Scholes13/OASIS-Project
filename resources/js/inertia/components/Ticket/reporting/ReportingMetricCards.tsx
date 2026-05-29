import { motion } from 'framer-motion';
import { CheckCircle2, Clock, FileText } from 'lucide-react';
import type { ReportData } from './types';

const fadeUp = {
    hidden: { opacity: 0, y: 16 },
    show: { opacity: 1, y: 0, transition: { duration: 0.4 } },
};

export default function ReportingMetricCards({ reportData }: { reportData: ReportData }) {
    return (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-4">
            <motion.div
                variants={fadeUp}
                initial="hidden"
                animate="show"
            >
                <div className="border border-gray-200/80 rounded-lg p-5 bg-slate-50/80">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-slate-200 flex items-center justify-center">
                            <FileText className="w-5 h-5 text-slate-600" />
                        </div>
                        <div>
                            <p className="text-[13px] font-medium text-gray-500">Total Tickets</p>
                            <p className="text-2xl font-bold tabular-nums text-gray-900">{reportData.total_tickets}</p>
                        </div>
                    </div>
                </div>
            </motion.div>
            <motion.div
                variants={fadeUp}
                initial="hidden"
                animate="show"
            >
                <div className="border border-gray-200/80 rounded-lg p-5 bg-emerald-50/60">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                        </div>
                        <div>
                            <p className="text-[13px] font-medium text-gray-500">Resolved</p>
                            <p className="text-2xl font-bold tabular-nums text-emerald-600">{reportData.resolved_tickets}</p>
                        </div>
                    </div>
                </div>
            </motion.div>
            <motion.div
                variants={fadeUp}
                initial="hidden"
                animate="show"
            >
                <div className="border border-gray-200/80 rounded-lg p-5 bg-blue-50/60">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                            <Clock className="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p className="text-[13px] font-medium text-gray-500">Avg Resolution</p>
                            <p className="text-2xl font-bold tabular-nums text-blue-600">{reportData.avg_resolution_hours}h</p>
                        </div>
                    </div>
                </div>
            </motion.div>
        </div>
    );
}
