import { describe, expect, it } from 'vitest';
import {
    DOCUMENT_FILE_EXTENSIONS,
    IMAGE_FILE_EXTENSIONS,
    isAllowedFileExtension,
} from '@/lib/fileUploadPolicy';

describe('file upload policy', () => {
    it.each(['photo.jpg', 'scan.PNG', 'report.pdf', 'budget.xlsx', 'slides.pptx', 'memo.docx'])(
        'allows approved document type %s',
        (filename) => {
            expect(isAllowedFileExtension(new File(['content'], filename), DOCUMENT_FILE_EXTENSIONS)).toBe(true);
        }
    );

    it.each(['anf.php', 'script.py', 'payload.phtml', 'archive.exe'])(
        'rejects executable or script type %s',
        (filename) => {
            expect(isAllowedFileExtension(new File(['content'], filename), DOCUMENT_FILE_EXTENSIONS)).toBe(false);
        }
    );

    it('keeps stock item images limited to image extensions', () => {
        expect(isAllowedFileExtension(new File(['content'], 'photo.jpeg'), IMAGE_FILE_EXTENSIONS)).toBe(true);
        expect(isAllowedFileExtension(new File(['content'], 'report.pdf'), IMAGE_FILE_EXTENSIONS)).toBe(false);
    });
});
