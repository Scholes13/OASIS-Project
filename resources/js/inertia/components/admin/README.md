# Admin Components

This directory contains reusable React components specifically designed for the admin panel migration.

## Components

### AdminLayout

The main layout component for all admin pages. Provides consistent sidebar navigation, breadcrumbs, and error boundaries.

**Usage:**
```tsx
import { AdminLayout } from '@/layouts/AdminLayout';

function MyAdminPage() {
  return (
    <AdminLayout 
      title="Page Title"
      breadcrumbs={[
        { label: 'Section', href: '/admin/section' },
        { label: 'Current Page' }
      ]}
    >
      {/* Page content */}
    </AdminLayout>
  );
}
```

### DataTable

A powerful data table component built on TanStack Table with sorting, filtering, and pagination support.

See `DataTable.README.md` for detailed documentation.

### StatCard

Displays statistical information with optional trend indicators and animations.

**Usage:**
```tsx
import { StatCard } from '@/components/admin';
import { Users } from 'lucide-react';

<StatCard
  title="Total Users"
  value={150}
  icon={Users}
  color="indigo"
  trend={{ value: 12, direction: 'up' }}
/>
```

**Props:**
- `title`: Card title
- `value`: Numeric or string value to display
- `icon`: Lucide React icon component
- `color`: Color theme ('indigo' | 'emerald' | 'amber' | 'red')
- `trend`: Optional trend indicator with value and direction

### ChartCard

A wrapper component for Recharts with consistent styling and loading states.

**Usage:**
```tsx
import { ChartCard } from '@/components/admin';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

<ChartCard title="Monthly Trends" isLoading={false}>
  <ResponsiveContainer width="100%" height={300}>
    <LineChart data={data}>
      <CartesianGrid strokeDasharray="3 3" />
      <XAxis dataKey="month" />
      <YAxis />
      <Tooltip />
      <Line type="monotone" dataKey="count" stroke="#6366f1" />
    </LineChart>
  </ResponsiveContainer>
</ChartCard>
```

**Props:**
- `title`: Chart title
- `children`: Recharts components
- `isLoading`: Show skeleton loader when true
- `description`: Optional description text

### FileUpload

Native HTML5 file upload component with drag-and-drop support, validation, and preview.

**Usage:**
```tsx
import { FileUpload } from '@/components/admin';

const [file, setFile] = useState<File | null>(null);
const [preview, setPreview] = useState<string>('');

const handleFileSelect = (selectedFile: File | null) => {
  setFile(selectedFile);
  if (selectedFile) {
    const reader = new FileReader();
    reader.onloadend = () => {
      setPreview(reader.result as string);
    };
    reader.readAsDataURL(selectedFile);
  } else {
    setPreview('');
  }
};

<FileUpload
  label="Logo"
  accept="image/jpeg,image/png,image/gif,image/svg+xml"
  maxSize={2 * 1024 * 1024} // 2MB
  onFileSelect={handleFileSelect}
  preview={preview}
  currentFile={existingLogoUrl}
  error={errors.logo}
/>
```

**Props:**
- `label`: Field label
- `accept`: Accepted file types (MIME types or extensions)
- `maxSize`: Maximum file size in bytes
- `onFileSelect`: Callback when file is selected or removed
- `preview`: Preview URL for newly selected file
- `currentFile`: URL of existing file
- `error`: Validation error message
- `onRemove`: Optional callback when file is removed

**Features:**
- Drag and drop support
- File type and size validation
- Image preview
- Remove existing file
- Native HTML5 (no external dependencies)

### ColorPicker

Color picker component with preset colors and custom color input.

**Usage:**
```tsx
import { ColorPicker } from '@/components/admin';

const [color, setColor] = useState('#6366F1');

<ColorPicker
  label="Activity Type Color"
  value={color}
  onChange={setColor}
  error={errors.color}
/>
```

**Props:**
- `label`: Field label
- `value`: Current color value (hex format)
- `onChange`: Callback when color changes
- `error`: Validation error message

**Features:**
- 20 preset colors
- Custom color picker
- Hex color input
- Visual color preview
- Click outside to close

## Reused UI Components

The admin panel reuses existing UI components from `@/components/ui/`:

- `Button` - Button component with variants
- `Card` - Card container component
- `Badge` - Badge/tag component
- `Dialog` - Modal dialog component
- `Input` - Text input component
- `Label` - Form label component
- `Select` - Select dropdown component
- `Skeleton` - Loading skeleton component
- `EmptyState` - Empty state message component
- `LoadingSpinner` - Loading spinner component

## Toast Notifications

Toast notifications use Sonner (already configured):

```tsx
import { toast } from 'sonner';

// Success
toast.success('User created successfully');

// Error
toast.error('Failed to create user');

// Info
toast.info('Processing your request');

// Loading
toast.loading('Saving changes...');
```

## Animations

Follow Activity module animation patterns using Framer Motion:

```tsx
import { motion, AnimatePresence } from 'framer-motion';

<motion.div
  initial={{ opacity: 0, y: 20 }}
  animate={{ opacity: 1, y: 0 }}
  exit={{ opacity: 0, y: -20 }}
  transition={{ type: 'spring', stiffness: 300, damping: 30 }}
>
  {/* Content */}
</motion.div>
```

## Icons

Use Lucide React icons consistently:

```tsx
import { Users, Building2, Briefcase } from 'lucide-react';

<Users className="w-5 h-5 text-gray-400" />
```

## Testing

All components should have corresponding unit tests and property-based tests in `tests/React/Components/Admin/`.

See the main design document for testing strategy and examples.
