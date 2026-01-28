import React from 'react';
import { Link } from '@inertiajs/react';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import { Toaster } from '@/components/ui/toast';
import { ChevronRight } from 'lucide-react';

export interface Breadcrumb {
  label: string;
  href?: string;
}

interface AdminLayoutProps {
  children: React.ReactNode;
  title: string;
  breadcrumbs?: Breadcrumb[];
}

export function AdminLayout({ children, title, breadcrumbs = [] }: AdminLayoutProps) {
  return (
    <ErrorBoundary>
      {/* Toast Notifications */}
      <Toaster position="top-right" richColors closeButton duration={5000} />
      
      {/* Skip to main content link for keyboard users */}
      <a
        href="#main-content"
        className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
      >
        Skip to main content
      </a>
      
      <div className="min-h-screen bg-gray-50">
        {/* Header with Breadcrumbs */}
        <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
          <div className="px-6 py-4">
            {/* Breadcrumbs */}
            {breadcrumbs.length > 0 && (
              <nav className="flex items-center space-x-2 text-sm text-gray-500 mb-2" aria-label="Breadcrumb">
                <Link href="/admin" className="hover:text-gray-700 transition-colors">
                  Admin
                </Link>
                {breadcrumbs.map((crumb, index) => (
                  <React.Fragment key={index}>
                    <ChevronRight className="w-4 h-4" aria-hidden="true" />
                    {crumb.href ? (
                      <Link href={crumb.href} className="hover:text-gray-700 transition-colors">
                        {crumb.label}
                      </Link>
                    ) : (
                      <span className="text-gray-900 font-medium" aria-current="page">{crumb.label}</span>
                    )}
                  </React.Fragment>
                ))}
              </nav>
            )}
            
            {/* Page Title */}
            <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
          </div>
        </header>

        {/* Page Content */}
        <main id="main-content" className="p-6" role="main">
          {children}
        </main>
      </div>
    </ErrorBoundary>
  );
}
