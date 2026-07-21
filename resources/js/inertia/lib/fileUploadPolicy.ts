export const DOCUMENT_FILE_EXTENSIONS = [
    'jpg',
    'jpeg',
    'png',
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
] as const;

export const IMAGE_FILE_EXTENSIONS = ['jpg', 'jpeg', 'png'] as const;

export const DOCUMENT_FILE_ACCEPT = DOCUMENT_FILE_EXTENSIONS.map((extension) => `.${extension}`).join(',');
export const IMAGE_FILE_ACCEPT = IMAGE_FILE_EXTENSIONS.map((extension) => `.${extension}`).join(',');

export const DOCUMENT_FILE_LABEL = 'JPG, JPEG, PNG, PDF, DOC, DOCX, XLS, XLSX, PPT, or PPTX';
export const IMAGE_FILE_LABEL = 'JPG, JPEG, or PNG';

export function isAllowedFileExtension(file: File, allowedExtensions: readonly string[]): boolean {
    const extension = file.name.split('.').pop()?.toLowerCase();

    return Boolean(extension && allowedExtensions.includes(extension));
}
