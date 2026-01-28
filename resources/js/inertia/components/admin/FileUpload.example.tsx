/**
 * FileUpload Component Examples
 * 
 * This file demonstrates various usage patterns for the FileUpload component
 * with progress tracking.
 */

import React, { useState } from 'react';
import { FileUpload } from './FileUpload';
import { useFileUpload } from '@/hooks/useFileUpload';
import { toast } from 'sonner';

/**
 * Example 1: Basic File Upload with Immediate Upload
 * 
 * Uploads file immediately when selected
 */
export function BasicFileUploadExample() {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string>('');
  
  const { uploadFile, progress, reset } = useFileUpload({
    onSuccess: (response) => {
      toast.success('File uploaded successfully!');
      console.log('Uploaded file URL:', response.url);
    },
    onError: (error) => {
      toast.error(`Upload failed: ${error}`);
    },
  });

  const handleFileSelect = async (file: File | null) => {
    if (file) {
      setSelectedFile(file);
      
      // Create preview
      const reader = new FileReader();
      reader.onloadend = () => {
        setPreview(reader.result as string);
      };
      reader.readAsDataURL(file);
      
      // Upload immediately
      try {
        await uploadFile(file, '/api/admin/business-units/upload-logo');
      } catch (error) {
        // Error handled by hook
      }
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

/**
 * Example 2: Manual Upload on Form Submit
 * 
 * Stores file locally and uploads when form is submitted
 */
export function ManualUploadExample() {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string>('');
  const [formData, setFormData] = useState({ name: '', code: '' });
  
  const { uploadFile, progress, isUploading } = useFileUpload({
    onSuccess: (response) => {
      // File uploaded, now submit form with file URL
      submitFormWithLogo(response.url);
    },
    onError: (error) => {
      toast.error(`Upload failed: ${error}`);
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
    } else {
      setSelectedFile(null);
      setPreview('');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (selectedFile) {
      // Upload file first
      try {
        await uploadFile(selectedFile, '/api/admin/upload', {
          type: 'business_unit_logo',
        });
      } catch (error) {
        // Error handled by hook
      }
    } else {
      // No file, submit form directly
      submitFormWithLogo(null);
    }
  };

  const submitFormWithLogo = (logoUrl: string | null) => {
    // Submit form with logo URL
    console.log('Submitting form:', { ...formData, logo_url: logoUrl });
    toast.success('Business unit created successfully!');
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Name
        </label>
        <input
          type="text"
          value={formData.name}
          onChange={(e) => setFormData({ ...formData, name: e.target.value })}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg"
          required
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Code
        </label>
        <input
          type="text"
          value={formData.code}
          onChange={(e) => setFormData({ ...formData, code: e.target.value })}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg"
          required
        />
      </div>

      <FileUpload
        label="Logo"
        accept="image/jpeg,image/png,image/gif,image/svg+xml"
        maxSize={2 * 1024 * 1024}
        onFileSelect={handleFileSelect}
        preview={preview}
        uploadProgress={progress}
      />

      <button
        type="submit"
        disabled={isUploading}
        className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {isUploading ? 'Uploading...' : 'Create Business Unit'}
      </button>
    </form>
  );
}

/**
 * Example 3: Edit Form with Existing File
 * 
 * Shows existing file and allows replacement
 */
export function EditFormExample() {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string>('');
  const [currentLogo, setCurrentLogo] = useState<string>('/storage/business-units/logo.png');
  
  const { uploadFile, progress, reset } = useFileUpload({
    onSuccess: (response) => {
      toast.success('Logo updated successfully!');
      setCurrentLogo(response.url);
      setSelectedFile(null);
      setPreview('');
    },
    onError: (error) => {
      toast.error(`Upload failed: ${error}`);
    },
  });

  const handleFileSelect = async (file: File | null) => {
    if (file) {
      setSelectedFile(file);
      
      // Create preview
      const reader = new FileReader();
      reader.onloadend = () => {
        setPreview(reader.result as string);
      };
      reader.readAsDataURL(file);
      
      // Upload immediately
      try {
        await uploadFile(file, '/api/admin/business-units/1/upload-logo');
      } catch (error) {
        // Error handled by hook
      }
    } else {
      setSelectedFile(null);
      setPreview('');
      reset();
    }
  };

  const handleRemove = async () => {
    // Call API to remove logo
    try {
      // await axios.delete('/api/admin/business-units/1/logo');
      setCurrentLogo('');
      toast.success('Logo removed successfully!');
    } catch (error) {
      toast.error('Failed to remove logo');
    }
  };

  return (
    <FileUpload
      label="Business Unit Logo"
      accept="image/jpeg,image/png,image/gif,image/svg+xml"
      maxSize={2 * 1024 * 1024}
      onFileSelect={handleFileSelect}
      preview={preview}
      currentFile={currentLogo}
      onRemove={handleRemove}
      uploadProgress={progress}
    />
  );
}

/**
 * Example 4: Multiple File Types
 * 
 * Accepts different file types with appropriate validation
 */
export function MultipleFileTypesExample() {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string>('');
  
  const { uploadFile, progress } = useFileUpload({
    onSuccess: (response) => {
      toast.success('Document uploaded successfully!');
    },
  });

  const handleFileSelect = async (file: File | null) => {
    if (file) {
      setSelectedFile(file);
      
      // Create preview for images only
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onloadend = () => {
          setPreview(reader.result as string);
        };
        reader.readAsDataURL(file);
      }
      
      // Upload
      try {
        await uploadFile(file, '/api/admin/documents/upload');
      } catch (error) {
        // Error handled by hook
      }
    } else {
      setSelectedFile(null);
      setPreview('');
    }
  };

  return (
    <FileUpload
      label="Supporting Document"
      accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx"
      maxSize={5 * 1024 * 1024} // 5MB
      onFileSelect={handleFileSelect}
      preview={preview}
      uploadProgress={progress}
    />
  );
}

/**
 * Example 5: With Form Validation
 * 
 * Integrates with React Hook Form
 */
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const formSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  logo: z.instanceof(File).optional(),
});

type FormData = z.infer<typeof formSchema>;

export function FormValidationExample() {
  const [preview, setPreview] = useState<string>('');
  
  const { uploadFile, progress } = useFileUpload();
  
  const { control, handleSubmit, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(formSchema),
  });

  const onSubmit = async (data: FormData) => {
    if (data.logo) {
      try {
        const response = await uploadFile(data.logo, '/api/admin/upload');
        console.log('Form data:', { ...data, logo_url: response.url });
      } catch (error) {
        // Error handled by hook
      }
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <Controller
        name="logo"
        control={control}
        render={({ field }) => (
          <FileUpload
            label="Logo"
            accept="image/*"
            maxSize={2 * 1024 * 1024}
            onFileSelect={(file) => {
              field.onChange(file);
              
              if (file) {
                const reader = new FileReader();
                reader.onloadend = () => {
                  setPreview(reader.result as string);
                };
                reader.readAsDataURL(file);
              } else {
                setPreview('');
              }
            }}
            preview={preview}
            uploadProgress={progress}
            error={errors.logo?.message}
          />
        )}
      />

      <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg">
        Submit
      </button>
    </form>
  );
}
