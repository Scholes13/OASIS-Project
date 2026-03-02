import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/Card';
import { 
  Mail, 
  Server, 
  AtSign, 
  Settings, 
  Send, 
  CheckCircle, 
  XCircle, 
  Clock,
  BarChart3,
  Info,
  Eye,
  EyeOff
} from 'lucide-react';

// Types
interface NotificationSettings {
  smtp_host: string;
  smtp_port: number;
  smtp_username: string;
  smtp_password: string | null;
  smtp_encryption: 'tls' | 'ssl' | 'none';
  mail_from_address: string;
  mail_from_name: string;
  email_enabled: boolean;
  fallback_to_database: boolean;
  link_expiry_days: number;
  retry_failed_emails: boolean;
  total_sent: number;
  total_failed: number;
  last_email_sent_at: string | null;
}

interface Props {
  settings: NotificationSettings;
}

// Form Schema
const notificationSettingsSchema = z.object({
  smtp_host: z.string().min(1, 'SMTP host is required'),
  smtp_port: z.number().min(1).max(65535, 'Port must be between 1 and 65535'),
  smtp_username: z.string().min(1, 'SMTP username is required'),
  smtp_password: z.string().optional(),
  smtp_encryption: z.enum(['tls', 'ssl', 'none']),
  mail_from_address: z.string().email('Invalid email address'),
  mail_from_name: z.string().min(1, 'From name is required'),
  email_enabled: z.boolean(),
  fallback_to_database: z.boolean(),
  link_expiry_days: z.number().min(1).max(30),
  retry_failed_emails: z.boolean(),
});

type NotificationSettingsFormData = z.infer<typeof notificationSettingsSchema>;

function NotificationSettingsIndex({ settings }: Props) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSendingTest, setIsSendingTest] = useState(false);
  const [testEmail, setTestEmail] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
    watch,
  } = useForm<NotificationSettingsFormData>({
    resolver: zodResolver(notificationSettingsSchema),
    defaultValues: {
      smtp_host: settings.smtp_host,
      smtp_port: settings.smtp_port,
      smtp_username: settings.smtp_username,
      smtp_password: '',
      smtp_encryption: settings.smtp_encryption,
      mail_from_address: settings.mail_from_address,
      mail_from_name: settings.mail_from_name,
      email_enabled: settings.email_enabled,
      fallback_to_database: settings.fallback_to_database,
      link_expiry_days: settings.link_expiry_days,
      retry_failed_emails: settings.retry_failed_emails,
    },
  });

  const linkExpiryDays = watch('link_expiry_days');

  // Calculate success rate
  const total = settings.total_sent + settings.total_failed;
  const successRate = total > 0 ? Math.round((settings.total_sent / total) * 100 * 10) / 10 : 0;

  const onSubmit = (data: NotificationSettingsFormData) => {
    setIsSubmitting(true);

    // Remove password if empty (keep existing)
    const submitData = { ...data };
    if (!submitData.smtp_password) {
      delete submitData.smtp_password;
    }

    router.post(route('admin.notification-settings.update'), submitData, {
      onSuccess: () => {
        toast.success('Notification settings updated successfully');
        setIsSubmitting(false);
      },
      onError: () => {
        toast.error('Failed to update settings');
        setIsSubmitting(false);
      },
    });
  };

  const handleSendTest = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!testEmail) {
      toast.error('Please enter a test email address');
      return;
    }

    setIsSendingTest(true);

    router.post(
      route('admin.notification-settings.test'),
      { test_email: testEmail },
      {
        onSuccess: () => {
          toast.success('Test email sent successfully');
          setIsSendingTest(false);
        },
        onError: () => {
          toast.error('Failed to send test email');
          setIsSendingTest(false);
        },
      }
    );
  };

  return (
    <>
      <Head title="Notification Settings" />

      <div className="p-6 space-y-6">
        {/* Statistics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Card className="p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-blue-100 rounded-md p-3">
                <Mail className="h-6 w-6 text-blue-600" />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dt className="text-sm font-medium text-gray-500 truncate">Total Sent</dt>
                <dd className="text-2xl font-semibold text-gray-900">
                  {settings.total_sent.toLocaleString()}
                </dd>
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-red-100 rounded-md p-3">
                <XCircle className="h-6 w-6 text-red-600" />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dt className="text-sm font-medium text-gray-500 truncate">Failed</dt>
                <dd className="text-2xl font-semibold text-gray-900">
                  {settings.total_failed.toLocaleString()}
                </dd>
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-green-100 rounded-md p-3">
                <BarChart3 className="h-6 w-6 text-green-600" />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dt className="text-sm font-medium text-gray-500 truncate">Success Rate</dt>
                <dd className="text-2xl font-semibold text-gray-900">{successRate}%</dd>
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-primary rounded-md p-3">
                <Clock className="h-6 w-6 text-primary" />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dt className="text-sm font-medium text-gray-500 truncate">Last Sent</dt>
                <dd className="text-sm font-semibold text-gray-900">
                  {settings.last_email_sent_at
                    ? new Date(settings.last_email_sent_at).toLocaleDateString()
                    : 'Never'}
                </dd>
              </div>
            </div>
          </Card>
        </div>

        {/* Configuration Form */}
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          {/* SMTP Configuration */}
          <Card>
            <div className="p-6">
              <div className="flex items-center mb-4">
                <div className="flex-shrink-0 bg-purple-100 rounded-md p-2">
                  <Server className="h-5 w-5 text-purple-600" />
                </div>
                <h3 className="ml-3 text-lg font-semibold text-gray-900">
                  SMTP Server Configuration
                </h3>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* SMTP Host */}
                <div>
                  <Label htmlFor="smtp_host">
                    SMTP Host <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="smtp_host"
                    {...register('smtp_host')}
                    placeholder="smtp.gmail.com"
                    error={errors.smtp_host?.message}
                  />
                </div>

                {/* SMTP Port */}
                <div>
                  <Label htmlFor="smtp_port">
                    SMTP Port <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="smtp_port"
                    type="number"
                    {...register('smtp_port', { valueAsNumber: true })}
                    placeholder="587"
                    error={errors.smtp_port?.message}
                  />
                </div>

                {/* SMTP Username */}
                <div>
                  <Label htmlFor="smtp_username">
                    SMTP Username <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="smtp_username"
                    {...register('smtp_username')}
                    placeholder="your-email@werkudara.com"
                    error={errors.smtp_username?.message}
                  />
                </div>

                {/* SMTP Password */}
                <div>
                  <Label htmlFor="smtp_password">
                    SMTP Password{' '}
                    {settings.smtp_password ? (
                      <span className="text-gray-500 font-normal">
                        (Optional - leave blank to keep current)
                      </span>
                    ) : (
                      <span className="text-red-500">*</span>
                    )}
                  </Label>
                  <div className="relative">
                    <Input
                      id="smtp_password"
                      type={showPassword ? 'text' : 'password'}
                      {...register('smtp_password')}
                      placeholder={
                        settings.smtp_password
                          ? 'Enter new password to change'
                          : 'Enter SMTP password'
                      }
                      error={errors.smtp_password?.message}
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      {showPassword ? (
                        <EyeOff className="h-5 w-5 text-gray-400" />
                      ) : (
                        <Eye className="h-5 w-5 text-gray-400" />
                      )}
                    </button>
                  </div>
                  {settings.smtp_password && (
                    <p className="mt-1 text-xs text-green-600 flex items-center">
                      <CheckCircle className="h-4 w-4 mr-1" />
                      Password is currently set and encrypted
                    </p>
                  )}
                </div>

                {/* SMTP Encryption */}
                <div className="md:col-span-2">
                  <Label htmlFor="smtp_encryption">
                    Encryption Type <span className="text-red-500">*</span>
                  </Label>
                  <select
                    id="smtp_encryption"
                    {...register('smtp_encryption')}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                  >
                    <option value="tls">TLS (Recommended - Port 587)</option>
                    <option value="ssl">SSL (Port 465)</option>
                    <option value="none">⚠️ None - Unencrypted (Development Only)</option>
                  </select>
                  {errors.smtp_encryption && (
                    <p className="mt-1 text-sm text-red-600">{errors.smtp_encryption.message}</p>
                  )}
                  <p className="mt-2 text-sm text-amber-600 flex items-center">
                    <Info className="h-4 w-4 mr-1" />
                    <strong>Security Notice:</strong> Unencrypted SMTP sends credentials in
                    plaintext. Only use for local development or trusted internal networks.
                  </p>
                </div>
              </div>
            </div>
          </Card>

          {/* Email Settings */}
          <Card>
            <div className="p-6">
              <div className="flex items-center mb-4">
                <div className="flex-shrink-0 bg-blue-100 rounded-md p-2">
                  <AtSign className="h-5 w-5 text-blue-600" />
                </div>
                <h3 className="ml-3 text-lg font-semibold text-gray-900">
                  Email Sender Information
                </h3>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* From Address */}
                <div>
                  <Label htmlFor="mail_from_address">
                    From Email Address <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="mail_from_address"
                    type="email"
                    {...register('mail_from_address')}
                    placeholder="noreply@werkudara.com"
                    error={errors.mail_from_address?.message}
                  />
                </div>

                {/* From Name */}
                <div>
                  <Label htmlFor="mail_from_name">
                    From Name <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="mail_from_name"
                    {...register('mail_from_name')}
                    placeholder="Werkudara Group - Purchase Request"
                    error={errors.mail_from_name?.message}
                  />
                </div>
              </div>
            </div>
          </Card>

          {/* Notification Options */}
          <Card>
            <div className="p-6">
              <div className="flex items-center mb-4">
                <div className="flex-shrink-0 bg-green-100 rounded-md p-2">
                  <Settings className="h-5 w-5 text-green-600" />
                </div>
                <h3 className="ml-3 text-lg font-semibold text-gray-900">Notification Options</h3>
              </div>

              <div className="space-y-6">
                {/* Enable Email Notifications */}
                <div className="flex items-start">
                  <div className="flex items-center h-5">
                    <input
                      type="checkbox"
                      id="email_enabled"
                      {...register('email_enabled')}
                      className="rounded border-gray-300 text-primary focus:ring-primary"
                    />
                  </div>
                  <div className="ml-3">
                    <Label htmlFor="email_enabled" className="font-medium">
                      Enable Email Notifications
                    </Label>
                    <p className="text-sm text-gray-500">
                      Send email notifications to approvers and requestors
                    </p>
                  </div>
                </div>

                {/* Fallback to Database */}
                <div className="flex items-start">
                  <div className="flex items-center h-5">
                    <input
                      type="checkbox"
                      id="fallback_to_database"
                      {...register('fallback_to_database')}
                      className="rounded border-gray-300 text-primary focus:ring-primary"
                    />
                  </div>
                  <div className="ml-3">
                    <Label htmlFor="fallback_to_database" className="font-medium">
                      Enable Database Fallback
                    </Label>
                    <p className="text-sm text-gray-500">
                      Save notifications to database if email sending fails (Recommended)
                    </p>
                  </div>
                </div>

                {/* Link Expiry Days */}
                <div>
                  <Label htmlFor="link_expiry_days">
                    Public Link Expiry (Days) <span className="text-red-500">*</span>
                  </Label>
                  <div className="flex items-center space-x-4">
                    <input
                      type="range"
                      id="link_expiry_days"
                      min="1"
                      max="14"
                      {...register('link_expiry_days', { valueAsNumber: true })}
                      className="flex-1"
                    />
                    <span className="text-lg font-semibold text-gray-900 w-20 text-center">
                      {linkExpiryDays} days
                    </span>
                  </div>
                  <p className="mt-1 text-xs text-gray-500">
                    Public approval links will expire after this many days (1-14 days)
                  </p>
                  {errors.link_expiry_days && (
                    <p className="mt-1 text-sm text-red-600">{errors.link_expiry_days.message}</p>
                  )}
                </div>
              </div>
            </div>
          </Card>

          {/* Action Buttons */}
          <div className="flex justify-end">
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? 'Saving...' : 'Save Settings'}
            </Button>
          </div>
        </form>

        {/* Test Email Section */}
        <Card>
          <div className="p-6">
            <div className="flex items-center mb-4">
              <div className="flex-shrink-0 bg-yellow-100 rounded-md p-2">
                <Send className="h-5 w-5 text-yellow-600" />
              </div>
              <h3 className="ml-3 text-lg font-semibold text-gray-900">Send Test Email</h3>
            </div>

            <p className="text-sm text-gray-600 mb-4">
              Send a test email to verify your SMTP configuration is working correctly. Make sure
              to save your settings first.
            </p>

            <form onSubmit={handleSendTest} className="flex items-end space-x-4">
              <div className="flex-1">
                <Label htmlFor="test_email">Test Email Address</Label>
                <Input
                  id="test_email"
                  type="email"
                  value={testEmail}
                  onChange={(e) => setTestEmail(e.target.value)}
                  placeholder="your-email@example.com"
                  required
                />
              </div>
              <Button type="submit" variant="secondary" disabled={isSendingTest}>
                {isSendingTest ? 'Sending...' : 'Send Test'}
              </Button>
            </form>
          </div>
        </Card>

        {/* Info Box */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <Info className="h-5 w-5 text-blue-400" />
            </div>
            <div className="ml-3">
              <h3 className="text-sm font-medium text-blue-800">Important Notes</h3>
              <div className="mt-2 text-sm text-blue-700 space-y-1">
                <p>
                  • For Gmail: Use App Password (not your regular password). Enable 2FA first, then
                  generate App Password.
                </p>
                <p>
                  • TLS (Port 587) is recommended for most SMTP servers including Gmail, Outlook,
                  and Office 365.
                </p>
                <p>
                  • Database fallback ensures notifications are always saved even if email sending
                  fails.
                </p>
                <p>
                  • Test your configuration after making changes to ensure emails are delivered
                  correctly.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

export default NotificationSettingsIndex;
