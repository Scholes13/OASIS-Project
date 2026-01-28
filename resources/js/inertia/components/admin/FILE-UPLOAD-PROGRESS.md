# File Upload Progress Indicators

## Overview

The FileUpload component and useFileUpload hook provide comprehensive progress tracking for file uploads, including progress bars, status indicators, and detailed error messages.

## Features

- **Real-time Progress Bar**: Shows upload percentage (0-100%)
- **Visual Status Indicators**: Loading spinner, success checkmark, error icon
- **Detailed Error Messages**: Specific error messages for different failure scenarios
- **Upload Overlay**: Semi-transparent overlay during upload to prevent user interaction
- **Smooth Animations**: Progress bar animates smoothly with CSS transitions

## Usage

### Basic Usage with useFileUpload Hook

```typescript
import { useState } from 'react';
import { FileUpload } from '@/components/admin/FileUpload';
import { useFileUpload } from '@/hooks/useFileUpload';

function BusinessUnitForm() {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string>('');
  
  const { uploadFile, progress, reset } = useFileUpload({
    onSuccess: (response) => {
      console.log('Upload successful:', response);
      // Handle success (e.g., update form data)
    },
    onError: (error) => {
      console.error('Upload failed:', error);
      // Handle error (e.g., show toast notification)
    },
  });

  const handleFileSelect = (file: File | null) => {
    if (file) {
      setSelectedFile(file);
      
      // Create preview
      const reader = new FileReader();
      reader.onloadend = () => {
        setPreview(reader.result as string);
      };
      reader.readAsDataURL(file);
      
      // Start upload immediately
      uploadFile(file, '/api/admin/business-units/upload-logo');
    } else {
      setSelectedFile(null);
      setPreview('');
      reset();
    }
  };

  return (
    <FileUpload
      label="Business Unit Logo"
      accept="image/jpeg,image/png,image/gif,image/svg+xml"
      maxSize={2 * 1024 * 1024} // 2MB
      onFileSelect={handleFileSelect}
      preview={preview}
      uploadProgress={progress}
    />
  );
}
```

### Manual Upload (Upload on Form Submit)

```typescript
import { useState } from 'react';
import { FileUpload } from '@/components/admin/FileUpload';
import { useFileUpload } from '@/hooks/useFileUpload';
import { useForm } from 'react-hook-form';

function BusinessUnitForm() {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string>('');
  
  const { uploadFile, progress, isUploading } = useFileUpload({
    onSuccess: (response) => {
      // File uploaded, now submit form with file URL
      submitForm({ logo_url: response.url });
    },
  });
  
  const { handleSubmit } = useForm();

  const onSubmit = async (data: any) => {
    if (selectedFile) {
      // Upload file first
      await uploadFile(selectedFile, '/api/admin/upload', {
        type: 'business_unit_logo',
      });
    } else {
      // No file, submit form directly
      submitForm(data);
    }
  };

  const handleFileSelect = (file: File | null) => {
    if (file) {
      setSelectedFile(file);
      
      // Create preview
      const reader = new FileReader();
      reader.onloadend = () => {
        setPreview(reader.result as string);
      };
      reader.readAsDataURL(file);
    } else {
      setSelectedFile(null);
      setPreview('');
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <FileUpload
        label="Business Unit Logo"
        accept="image/jpeg,image/png,image/gif,image/svg+xml"
        maxSize={2 * 1024 * 1024}
        onFileSelect={handleFileSelect}
        preview={preview}
        uploadProgress={progress}
      />
      
      <button type="submit" disabled={isUploading}>
        {isUploading ? 'Uploading...' : 'Save'}
      </button>
    </form>
  );
}
```

### With Additional Form Data

```typescript
const { uploadFile, progress } = useFileUpload();

const handleFileSelect = async (file: File | null) => {
  if (file) {
    await uploadFile(file, '/api/admin/upload', {
      business_unit_id: 1,
      type: 'logo',
      description: 'Business unit logo',
    });
  }
};
```

## FileUploadProgress Interface

```typescript
export interface FileUploadProgress {
  progress: number; // 0-100
  status: 'idle' | 'uploading' | 'success' | 'error';
  error?: string;
}
```

### Status States

1. **idle**: No upload in progress
2. **uploading**: File is currently being uploaded
3. **success**: Upload completed successfully
4. **error**: Upload failed with error

## Visual Indicators

### Progress Bar

Shows during upload with smooth animation:

```
[████████████████░░░░░░░░] 75%
```

- **Color**: Indigo-600 (matches primary brand color)
- **Height**: 8px (h-2)
- **Animation**: Smooth transition (300ms ease-out)
- **Background**: Gray-200

### Upload Overlay

Semi-transparent overlay during upload:

- **Background**: Black with 50% opacity
- **Content**: Loading spinner + percentage
- **Purpose**: Prevents user interaction during upload

### Success Indicator

Green checkmark in top-left corner:

- **Icon**: CheckCircle (Lucide)
- **Color**: Emerald-500
- **Position**: Absolute, top-2 left-2
- **Background**: Rounded full

### Error Indicator

Red alert icon in top-left corner:

- **Icon**: AlertCircle (Lucide)
- **Color**: Red-500
- **Position**: Absolute, top-2 left-2
- **Background**: Rounded full

## Error Messages

The hook provides specific error messages for different scenarios:

### Network Errors

```
Network error. Please check your connection and try again.
```

### File Size Errors (413)

```
File is too large. Please upload a smaller file.
```

### File Type Errors (415)

```
Unsupported file type. Please upload a different file.
```

### Permission Errors (403)

```
You do not have permission to upload files.
```

### Server Errors (500)

```
Server error. Please try again later.
```

### Validation Errors

```
The file must be an image. (from Laravel validation)
```

## Backend Implementation

### Laravel Controller

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|image|max:2048', // 2MB max
        ]);

        $file = $request->file('file');
        
        // Store file
        $path = $file->store('business-units', 'public');
        
        // Get URL
        $url = Storage::url($path);
        
        return response()->json([
            'success' => true,
            'url' => $url,
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }
}
```

### Route

```php
Route::post('/api/admin/upload', [FileUploadController::class, 'upload'])
    ->middleware(['auth', 'admin.access']);
```

## Progress Tracking

### How It Works

1. **Axios onUploadProgress**: Tracks bytes uploaded
2. **Calculate Percentage**: `(loaded / total) * 100`
3. **Update State**: React state updates trigger re-render
4. **Smooth Animation**: CSS transitions animate progress bar

### Progress Calculation

```typescript
onUploadProgress: (progressEvent: AxiosProgressEvent) => {
  if (progressEvent.total) {
    const percentCompleted = Math.round(
      (progressEvent.loaded * 100) / progressEvent.total
    );
    setProgress({
      progress: percentCompleted,
      status: 'uploading',
    });
  }
}
```

## Performance Considerations

### File Size Limits

- **Frontend Validation**: 2MB (configurable via `maxSize` prop)
- **Backend Validation**: 2MB (Laravel validation)
- **Server Limit**: Check `upload_max_filesize` in php.ini

### Large File Uploads

For files > 10MB, consider:

1. **Chunked Uploads**: Split file into chunks
2. **Resumable Uploads**: Allow resume on failure
3. **Background Processing**: Queue file processing

### Example: Chunked Upload

```typescript
async function uploadLargeFile(file: File, url: string) {
  const chunkSize = 1024 * 1024; // 1MB chunks
  const chunks = Math.ceil(file.size / chunkSize);
  
  for (let i = 0; i < chunks; i++) {
    const start = i * chunkSize;
    const end = Math.min(start + chunkSize, file.size);
    const chunk = file.slice(start, end);
    
    await uploadChunk(chunk, i, chunks, url);
  }
}
```

## Accessibility

### ARIA Labels

```typescript
<div
  role="progressbar"
  aria-valuenow={progress.progress}
  aria-valuemin={0}
  aria-valuemax={100}
  aria-label="File upload progress"
>
  <div style={{ width: `${progress.progress}%` }} />
</div>
```

### Screen Reader Announcements

```typescript
<div aria-live="polite" aria-atomic="true" className="sr-only">
  {isUploading && `Uploading file: ${progress.progress}% complete`}
  {isSuccess && 'File uploaded successfully'}
  {isError && `Upload failed: ${progress.error}`}
</div>
```

## Testing

### Unit Tests

```typescript
import { renderHook, act } from '@testing-library/react';
import { useFileUpload } from '@/hooks/useFileUpload';
import axios from 'axios';

jest.mock('axios');

describe('useFileUpload', () => {
  it('should track upload progress', async () => {
    const { result } = renderHook(() => useFileUpload());
    
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    
    // Mock axios with progress
    (axios.post as jest.Mock).mockImplementation((url, data, config) => {
      // Simulate progress
      config.onUploadProgress({ loaded: 50, total: 100 });
      config.onUploadProgress({ loaded: 100, total: 100 });
      
      return Promise.resolve({ data: { url: '/uploads/test.jpg' } });
    });
    
    await act(async () => {
      await result.current.uploadFile(file, '/api/upload');
    });
    
    expect(result.current.progress.status).toBe('success');
    expect(result.current.progress.progress).toBe(100);
  });
  
  it('should handle upload errors', async () => {
    const { result } = renderHook(() => useFileUpload());
    
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    
    (axios.post as jest.Mock).mockRejectedValue({
      response: { status: 413 },
    });
    
    await act(async () => {
      try {
        await result.current.uploadFile(file, '/api/upload');
      } catch (error) {
        // Expected
      }
    });
    
    expect(result.current.progress.status).toBe('error');
    expect(result.current.progress.error).toContain('too large');
  });
});
```

## Troubleshooting

### Progress Not Updating

**Problem**: Progress bar stays at 0%

**Solutions**:
1. Check if `Content-Length` header is set by server
2. Verify axios is configured correctly
3. Check browser console for errors

### Upload Fails Silently

**Problem**: No error message shown

**Solutions**:
1. Check network tab for actual error
2. Verify error handling in `useFileUpload`
3. Add console.log in catch block

### Progress Bar Jumps

**Problem**: Progress bar doesn't animate smoothly

**Solutions**:
1. Add CSS transition: `transition-all duration-300 ease-out`
2. Throttle progress updates (max 10 updates/second)
3. Use requestAnimationFrame for smoother updates

## Best Practices

1. **Validate on Both Sides**: Frontend and backend validation
2. **Show Clear Errors**: Specific error messages for each scenario
3. **Disable During Upload**: Prevent multiple uploads
4. **Reset on Cancel**: Clear progress when user cancels
5. **Optimize File Size**: Compress images before upload
6. **Use Appropriate Formats**: WebP for images, MP4 for videos
7. **Implement Retry Logic**: Allow retry on network errors
8. **Show File Info**: Display filename, size, type
9. **Provide Feedback**: Toast notifications for success/error
10. **Handle Edge Cases**: Large files, slow connections, timeouts

## Future Enhancements

1. **Drag and Drop Multiple Files**: Upload multiple files at once
2. **Image Cropping**: Crop images before upload
3. **Image Compression**: Compress images client-side
4. **Resumable Uploads**: Resume failed uploads
5. **Upload Queue**: Queue multiple uploads
6. **Background Upload**: Upload in service worker
7. **Upload History**: Show recent uploads
8. **File Preview**: Preview before upload (PDF, video)
