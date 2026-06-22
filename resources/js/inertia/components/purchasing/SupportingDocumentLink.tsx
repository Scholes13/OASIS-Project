import React from 'react';
import { Download, Eye, FileText } from 'lucide-react';

interface SupportingDocumentLinkProps {
    filename: string | null;
    url: string;
    downloadUrl?: string;
    iconLabel?: string;
    className?: string;
}

export const SupportingDocumentLink: React.FC<SupportingDocumentLinkProps> = ({
    filename,
    url,
    downloadUrl,
    iconLabel = 'View',
    className = 'bg-gray-50 rounded-lg border border-gray-200',
}) => {
    return (
        <div className={`flex items-center justify-between p-3 ${className}`}>
            <div className="flex items-center space-x-3 min-w-0">
                <div className="flex-shrink-0 w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                    <FileText className="w-5 h-5 text-gray-500" />
                </div>
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-medium text-gray-900 truncate">{filename || 'Document'}</p>
                    <p className="text-xs text-gray-500">Document</p>
                </div>
            </div>
            <div className="flex items-center space-x-2 flex-shrink-0">
                <a
                    href={url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center px-3 py-1.5 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors"
                    title="View document"
                >
                    <Eye className="w-4 h-4 mr-1.5" />
                    {iconLabel}
                </a>
                {downloadUrl && (
                    <a
                        href={downloadUrl}
                        download={filename || undefined}
                        className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md transition-colors"
                        title="Download document"
                    >
                        <Download className="w-4 h-4 mr-1.5" />
                        Download
                    </a>
                )}
            </div>
        </div>
    );
};
