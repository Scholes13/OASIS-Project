import { MessageCircle } from 'lucide-react';

export default function SupportBanner() {
    return (
        <div className="bg-gradient-to-br from-[#eff6ff] to-[#dbeafe] rounded-xl p-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <h3 className="text-lg font-semibold text-[#1e40af] mb-1">Still need help?</h3>
                <p className="text-[#1e3a8a] text-sm">Our support team is available Mon–Fri, 9am – 6pm.</p>
            </div>
            <button className="flex items-center px-5 py-2.5 bg-[#16599c] text-white rounded-lg text-sm font-medium hover:bg-[#124a82] transition-colors shrink-0">
                <MessageCircle className="w-4 h-4 mr-2" />
                Contact Support
            </button>
        </div>
    );
}
