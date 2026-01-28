import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Building2, CheckCircle2, AlertTriangle, Info, Save } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/Card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BusinessUnit } from '@/types';

interface SlaSettingData {
  id?: number;
  business_unit_id: number;
  followup_sla_hours: number;
  completion_sla_hours: number;
  email_alerts_enabled: boolean;
  updated_at?: string;
}

interface SlaStatistics {
  business_unit_id: number;
  compliance_rate: number;
  average_completion_time: number;
  overdue_count: number;
}

interface SlaFormData {
  business_unit_id: number;
  followup_sla_hours: number;
  completion_sla_hours: number;
  email_alerts_enabled: boolean;
}

interface Props {
  businessUnits: BusinessUnit[];
  slaSettings: Record<number, SlaSettingData>;
  statistics?: Record<number, SlaStatistics>;
}

function Index({ businessUnits, slaSettings, statistics = {} }: Props) {
  return (
    <>
      <Head title="SLA Settings" />

      <div className="p-6 space-y-6">
        {/* Info Box */}
        <Card className="bg-blue-50 border-blue-200">
          <div className="flex gap-3 p-4">
            <Info className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
            <div>
              <h3 className="text-sm font-medium text-blue-900 mb-2">About SLA Settings</h3>
              <div className="text-sm text-blue-800 space-y-1">
                <p>
                  <strong>Follow-up SLA:</strong> Maximum time allowed from task entry to when admin starts working (Pending → In Progress)
                </p>
                <p>
                  <strong>Completion SLA:</strong> Maximum time allowed from start to completion (In Progress → Done)
                </p>
                <p>
                  <strong>Email Alerts:</strong> When enabled, system will send email notifications to assigned admin and department manager when tasks exceed SLA targets
                </p>
                <p>Configure SLA targets for each business unit independently</p>
              </div>
            </div>
          </div>
        </Card>

        {/* SLA Settings for Each Business Unit */}
        <div className="space-y-6">
          {businessUnits.map((businessUnit) => {
            const settings = slaSettings[businessUnit.id];
            const stats = statistics[businessUnit.id];
            const hasSettings = !!settings;

            return (
              <BusinessUnitSlaCard
                key={businessUnit.id}
                businessUnit={businessUnit}
                settings={settings}
                statistics={stats}
                hasSettings={hasSettings}
              />
            );
          })}
        </div>

        {/* Quick Reference Guide */}
        <Card className="bg-gray-50 border-gray-200">
          <div className="flex gap-3 p-4">
            <Info className="w-5 h-5 text-gray-600 flex-shrink-0 mt-0.5" />
            <div>
              <h3 className="text-sm font-medium text-gray-900 mb-2">Recommended SLA Values</h3>
              <div className="text-sm text-gray-700 space-y-1">
                <p>
                  <strong>Standard:</strong> Follow-up: 24 hours, Completion: 72 hours
                </p>
                <p>
                  <strong>Urgent:</strong> Follow-up: 4 hours, Completion: 24 hours
                </p>
                <p>
                  <strong>Relaxed:</strong> Follow-up: 48 hours, Completion: 168 hours (1 week)
                </p>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </>
  );
}

interface BusinessUnitSlaCardProps {
  businessUnit: BusinessUnit;
  settings?: SlaSettingData;
  statistics?: SlaStatistics;
  hasSettings: boolean;
}

function BusinessUnitSlaCard({
  businessUnit,
  settings,
  statistics,
  hasSettings,
}: BusinessUnitSlaCardProps) {
  const { data, setData, post, processing, errors } = useForm<SlaFormData>({
    business_unit_id: businessUnit.id,
    followup_sla_hours: settings?.followup_sla_hours ?? 24,
    completion_sla_hours: settings?.completion_sla_hours ?? 72,
    email_alerts_enabled: settings?.email_alerts_enabled ?? true,
  });

  const [showConfirmation, setShowConfirmation] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Validate follow-up < completion
    if (data.followup_sla_hours >= data.completion_sla_hours) {
      toast.error('Follow-up time must be less than completion time');
      return;
    }

    // Show confirmation if disabling email alerts
    if (hasSettings && settings?.email_alerts_enabled && !data.email_alerts_enabled) {
      setShowConfirmation(true);
      return;
    }

    submitForm();
  };

  const submitForm = () => {
    post(route('admin.sla-settings.update'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(`SLA settings updated successfully for ${businessUnit.name}`);
        setShowConfirmation(false);
      },
      onError: (errors) => {
        if (Object.keys(errors).length > 0) {
          toast.error('Please fix the validation errors');
        } else {
          toast.error('Failed to update SLA settings');
        }
      },
    });
  };

  return (
    <Card>
      {/* Header */}
      <div className="px-5 py-4 border-b border-gray-100 bg-gray-50">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="flex-shrink-0 bg-indigo-100 rounded-md p-2">
              <Building2 className="w-5 h-5 text-indigo-600" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-gray-900">{businessUnit.name}</h3>
              <p className="text-sm text-gray-500">{businessUnit.code}</p>
            </div>
          </div>
          {hasSettings ? (
            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
              <CheckCircle2 className="w-3.5 h-3.5" />
              Configured
            </span>
          ) : (
            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
              <AlertTriangle className="w-3.5 h-3.5" />
              Not Configured
            </span>
          )}
        </div>
      </div>

      {/* Form */}
      <form onSubmit={handleSubmit} className="p-6 space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Follow-up SLA Hours */}
          <div className="space-y-2">
            <Label htmlFor={`followup_sla_hours_${businessUnit.id}`}>
              Follow-up SLA (Hours) <span className="text-red-500">*</span>
            </Label>
            <Input
              id={`followup_sla_hours_${businessUnit.id}`}
              type="number"
              min={1}
              max={720}
              value={data.followup_sla_hours}
              onChange={(e) => setData('followup_sla_hours', parseInt(e.target.value) || 1)}
              placeholder="24"
              required
              className={errors.followup_sla_hours ? 'border-red-500' : ''}
            />
            <p className="text-xs text-gray-500">
              Maximum time from task entry to start (1-720 hours)
            </p>
            {errors.followup_sla_hours && (
              <p className="text-sm text-red-600">{errors.followup_sla_hours}</p>
            )}
          </div>

          {/* Completion SLA Hours */}
          <div className="space-y-2">
            <Label htmlFor={`completion_sla_hours_${businessUnit.id}`}>
              Completion SLA (Hours) <span className="text-red-500">*</span>
            </Label>
            <Input
              id={`completion_sla_hours_${businessUnit.id}`}
              type="number"
              min={1}
              max={720}
              value={data.completion_sla_hours}
              onChange={(e) => setData('completion_sla_hours', parseInt(e.target.value) || 1)}
              placeholder="72"
              required
              className={errors.completion_sla_hours ? 'border-red-500' : ''}
            />
            <p className="text-xs text-gray-500">
              Maximum time from start to completion (1-720 hours)
            </p>
            {errors.completion_sla_hours && (
              <p className="text-sm text-red-600">{errors.completion_sla_hours}</p>
            )}
          </div>
        </div>

        {/* Email Alerts Toggle */}
        <div className="flex items-start gap-3">
          <input
            type="checkbox"
            id={`email_alerts_enabled_${businessUnit.id}`}
            checked={data.email_alerts_enabled}
            onChange={(e) => setData('email_alerts_enabled', e.target.checked)}
            className="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          />
          <div className="flex-1">
            <Label htmlFor={`email_alerts_enabled_${businessUnit.id}`} className="font-medium">
              Enable Email Alerts
            </Label>
            <p className="text-sm text-gray-500">
              Send email notifications when tasks exceed SLA targets
            </p>
          </div>
        </div>

        {/* Current Settings Display (if exists) */}
        {hasSettings && settings && (
          <div className="pt-6 border-t border-gray-200">
            <h4 className="text-sm font-medium text-gray-700 mb-3">Current Settings</h4>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="bg-gray-50 rounded-lg p-3">
                <p className="text-xs text-gray-500">Follow-up SLA</p>
                <p className="text-lg font-semibold text-gray-900">
                  {settings.followup_sla_hours} hours
                </p>
              </div>
              <div className="bg-gray-50 rounded-lg p-3">
                <p className="text-xs text-gray-500">Completion SLA</p>
                <p className="text-lg font-semibold text-gray-900">
                  {settings.completion_sla_hours} hours
                </p>
              </div>
              <div className="bg-gray-50 rounded-lg p-3">
                <p className="text-xs text-gray-500">Email Alerts</p>
                <p
                  className={`text-lg font-semibold ${
                    settings.email_alerts_enabled ? 'text-emerald-600' : 'text-gray-400'
                  }`}
                >
                  {settings.email_alerts_enabled ? 'Enabled' : 'Disabled'}
                </p>
              </div>
            </div>
            {settings.updated_at && (
              <p className="mt-2 text-xs text-gray-500">
                Last updated: {new Date(settings.updated_at).toLocaleString()}
              </p>
            )}
          </div>
        )}

        {/* Compliance Statistics (if available) */}
        {statistics && (
          <div className="pt-6 border-t border-gray-200">
            <h4 className="text-sm font-medium text-gray-700 mb-3">SLA Compliance Statistics</h4>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="bg-blue-50 rounded-lg p-3">
                <p className="text-xs text-blue-600">Compliance Rate</p>
                <p className="text-lg font-semibold text-blue-900">
                  {statistics.compliance_rate.toFixed(1)}%
                </p>
              </div>
              <div className="bg-emerald-50 rounded-lg p-3">
                <p className="text-xs text-emerald-600">Avg. Completion Time</p>
                <p className="text-lg font-semibold text-emerald-900">
                  {statistics.average_completion_time.toFixed(1)} hours
                </p>
              </div>
              <div className="bg-amber-50 rounded-lg p-3">
                <p className="text-xs text-amber-600">Overdue Tasks</p>
                <p className="text-lg font-semibold text-amber-900">
                  {statistics.overdue_count}
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Confirmation Dialog */}
        {showConfirmation && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            className="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            onClick={() => setShowConfirmation(false)}
          >
            <motion.div
              initial={{ scale: 0.95 }}
              animate={{ scale: 1 }}
              className="bg-white rounded-lg p-6 max-w-md mx-4"
              onClick={(e) => e.stopPropagation()}
            >
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                Disable Email Alerts?
              </h3>
              <p className="text-sm text-gray-600 mb-4">
                Are you sure you want to disable email alerts for {businessUnit.name}? 
                Admins and managers will no longer receive notifications when tasks exceed SLA targets.
              </p>
              <div className="flex gap-3 justify-end">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setShowConfirmation(false)}
                >
                  Cancel
                </Button>
                <Button
                  type="button"
                  onClick={submitForm}
                  disabled={processing}
                >
                  {processing ? 'Saving...' : 'Confirm'}
                </Button>
              </div>
            </motion.div>
          </motion.div>
        )}

        {/* Action Button */}
        <div className="flex justify-end">
          <Button type="submit" disabled={processing}>
            <Save className="w-4 h-4 mr-2" />
            {processing ? 'Saving...' : hasSettings ? 'Update Settings' : 'Save Settings'}
          </Button>
        </div>
      </form>
    </Card>
  );
}

export default Index;
