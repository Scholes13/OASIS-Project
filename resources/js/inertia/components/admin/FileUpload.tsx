import React, { useRef, useState } from 'react';
import { Upload, X, Image as ImageIcon, CheckCircle, AlertCircle, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

export interface FileUploadProgress {
  progress: number; // 0-100
  status: 'idle' | 'uploading' | 'success' | 'error';
  error?: string;
}

interface FileUploadProps {
  label: string;
  accept: string;
  maxSize: number; // in bytes
  onFileSelect: (file: File | null) => void;
  preview?: string;
  error?: string;
  currentFile?: string;
  onRemove?: () => void;
  uploadProgress?: FileUploadProgress;
}

export function FileUpload({
  label,
  accept,
  maxSize,
  onFileSelect,
  preview,
  error,
  currentFile,
  onRemove,
  uploadProgress,
}: FileUploadProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [dragActive, setDragActive] = useState(false);

  const validateFile = (file: File): string | null => {
    const acceptedTypes = accept.split(',').map(t => t.trim());
    const fileType = file.type;
    
    // Check if file type matches
    const isValidType = acceptedTypes.some(type => {
      if (type.startsWith('.')) {
        return file.name.toLowerCase().endsWith(type.toLowerCase());
      }
      if (type.endsWith('/*')) {
        const baseType = type.split('/')[0];
        return fileType.startsWith(baseType + '/');
      }
      return fileType === type;
    });

    if (!isValidType) {
      return `Invalid file type. Please upload: ${accept}`;
    }

    if (file.size > maxSize) {
      const maxSizeMB = (maxSize / (1024 * 1024)).toFixed(1);
      return `File size exceeds ${maxSizeMB}MB. Please upload a smaller file.`;
    }

    return null;
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const validationError = validateFile(file);
      if (validationError) {
        alert(validationError);
        if (inputRef.current) {
          inputRef.current.value = '';
        }
        return;
      }
      onFileSelect(file);
    }
  };

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    const file = e.dataTransfer.files?.[0];
    if (file) {
      const validationError = validateFile(file);
      if (validationError) {
        alert(validationError);
        return;
      }
      onFileSelect(file);
    }
  };

  const handleRemove = () => {
    if (inputRef.current) {
      inputRef.current.value = '';
    }
    onFileSelect(null);
    if (onRemove) {
      onRemove();
    }
  };

  const displayPreview = preview || currentFile;
  const isUploading = uploadProgress?.status === 'uploading';
  const uploadSuccess = uploadProgress?.status === 'success';
  const uploadError = uploadProgress?.status === 'error';

  return (
    <div className="space-y-2">
      <label className="block text-sm font-medium text-gray-700">
        {label}
      </label>

      {displayPreview ? (
        <div className="relative">
          <div className="relative w-full h-48 bg-gray-100 rounded-lg overflow-hidden border border-gray-300">
            <img
              src={displayPreview}
              alt="Preview"
              className="w-full h-full object-contain"
            />
            
            {/* Upload Progress Overlay */}
            {isUploading && (
              <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <div className="text-center text-white">
                  <Loader2 className="w-8 h-8 animate-spin mx-auto mb-2" />
                  <p className="text-sm font-medium">Uploading...</p>
                  <p className="text-xs mt-1">{uploadProgress.progress}%</p>
                </div>
              </div>
            )}
            
            {/* Success Indicator */}
            {uploadSuccess && (
              <div className="absolute top-2 left-2 bg-emerald-500 text-white rounded-full p-1">
                <CheckCircle className="w-5 h-5" />
              </div>
            )}
            
            {/* Error Indicator */}
            {uploadError && (
              <div className="absolute top-2 left-2 bg-red-500 text-white rounded-full p-1">
                <AlertCircle className="w-5 h-5" />
              </div>
            )}
          </div>
          
          {/* Progress Bar */}
          {isUploading && (
            <div className="mt-2">
              <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                <div
                  className="bg-primary h-full transition-all duration-300 ease-out"
                  style={{ width: `${uploadProgress.progress}%` }}
                />
              </div>
            </div>
          )}
          
          {/* Upload Error Message */}
          {uploadError && uploadProgress.error && (
            <div className="mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
              <div className="flex items-start gap-2">
                <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                <div className="flex-1">
                  <p className="text-sm font-medium text-red-800">Upload Failed</p>
                  <p className="text-sm text-red-700 mt-1">{uploadProgress.error}</p>
                </div>
              </div>
            </div>
          )}
          
          <Button
            type="button"
            variant="destructive"
            size="sm"
            onClick={handleRemove}
            disabled={isUploading}
            className="absolute top-2 right-2"
          >
            <X className="w-4 h-4 mr-1" />
            Remove
          </Button>
        </div>
      ) : (
        <div
          onDragEnter={handleDrag}
          onDragLeave={handleDrag}
          onDragOver={handleDrag}
          onDrop={handleDrop}
          className={`
            relative border-2 border-dashed rounded-lg p-6 text-center cursor-pointer
            transition-colors
            ${dragActive 
              ? 'border-primary bg-primary' 
              : 'border-gray-300 hover:border-gray-400'
            }
          `}
          onClick={() => inputRef.current?.click()}
        >
          <input
            ref={inputRef}
            type="file"
            accept={accept}
            onChange={handleFileChange}
            className="hidden"
          />
          
          <div className="flex flex-col items-center space-y-2">
            {accept.includes('image') ? (
              <ImageIcon className="w-12 h-12 text-gray-400" />
            ) : (
              <Upload className="w-12 h-12 text-gray-400" />
            )}
            <div className="text-sm text-gray-600">
              <span className="font-medium text-primary hover:text-primary">
                Click to upload
              </span>
              {' or drag and drop'}
            </div>
            <p className="text-xs text-gray-500">
              {accept} up to {(maxSize / (1024 * 1024)).toFixed(1)}MB
            </p>
          </div>
        </div>
      )}

      {error && (
        <p className="text-sm text-red-600">{error}</p>
      )}
    </div>
  );
}
