import { Paperclip, Download, FileText, File, Image, Film, Music, FileIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { TicketAttachment } from '@/types';

interface AttachmentListProps {
    attachments: TicketAttachment[];
    className?: string;
}

/**
 * Get icon based on file type
 */
function getFileIcon(fileType: string | null) {
    if (!fileType) return FileIcon;
    
    const type = fileType.toLowerCase();
    
    if (type.startsWith('image/')) return Image;
    if (type.startsWith('video/')) return Film;
    if (type.startsWith('audio/')) return Music;
    if (type.includes('pdf')) return FileText;
    if (type.includes('word') || type.includes('document')) return FileText;
    if (type.includes('excel') || type.includes('spreadsheet')) return FileText;
    if (type.includes('powerpoint') || type.includes('presentation')) return FileText;
    if (type.includes('text')) return FileText;
    if (type.includes('zip') || type.includes('rar') || type.includes('archive')) return FileIcon;
    
    return File;
}

/**
 * Format file size to human readable
 */
function formatFileSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

/**
 * Get file extension from filename
 */
function getFileExtension(filename: string): string {
    const parts = filename.split('.');
    return parts.length > 1 ? parts.pop()?.toUpperCase() || '' : '';
}

export function AttachmentList({ attachments, className }: AttachmentListProps) {
    if (attachments.length === 0) {
        return (
            <div className={cn('text-center py-4 text-gray-400 text-sm', className)}>
                <Paperclip className="w-6 h-6 mx-auto mb-1 text-gray-300" />
                Tidak ada lampiran
            </div>
        );
    }

    return (
        <div className={cn('space-y-2', className)}>
            <div className="flex items-center gap-2 text-sm font-medium text-gray-700">
                <Paperclip className="w-4 h-4" />
                Lampiran ({attachments.length})
            </div>
            <div className="space-y-1">
                {attachments.map((attachment) => {
                    const FileIconComponent = getFileIcon(attachment.file_type);
                    const extension = getFileExtension(attachment.original_filename);

                    return (
                        <div
                            key={attachment.id}
                            className="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg group hover:bg-gray-100 transition-colors"
                        >
                            <div className="flex items-center gap-3 min-w-0">
                                <FileIconComponent className="w-5 h-5 text-gray-400 flex-shrink-0" />
                                <div className="min-w-0">
                                    <p className="text-sm text-gray-900 truncate max-w-[200px]" title={attachment.original_filename}>
                                        {attachment.original_filename}
                                    </p>
                                    <div className="flex items-center gap-2 text-xs text-gray-400">
                                        {extension && (
                                            <span className="px-1.5 py-0.5 bg-gray-200 rounded text-gray-600">
                                                {extension}
                                            </span>
                                        )}
                                        <span>{formatFileSize(attachment.file_size)}</span>
                                    </div>
                                </div>
                            </div>
                            {attachment.download_url && (
                                <a
                                    href={attachment.download_url}
                                    download={attachment.original_filename}
                                    className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                    title="Download"
                                >
                                    <Download className="w-4 h-4" />
                                </a>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}