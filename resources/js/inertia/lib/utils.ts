import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Utility function to merge Tailwind CSS classes with clsx
 * Combines clsx for conditional classes with tailwind-merge for deduplication
 * 
 * @example
 * cn('px-2 py-1', condition && 'bg-blue-500', 'px-4')
 * // Result: 'py-1 px-4 bg-blue-500' (px-4 overrides px-2)
 */
export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}
