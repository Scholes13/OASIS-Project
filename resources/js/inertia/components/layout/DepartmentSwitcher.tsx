/**
 * DepartmentSwitcher Component
 * 
 * Department switcher for users with multiple departments in current business unit.
 * Shows: Department Code + Name
 * Only renders if user has multiple departments.
 */

import { useState, useRef, useEffect, useMemo } from 'react';
import { motion, AnimatePresence, type Variants } from 'framer-motion';
import { ChevronUp, Building2 } from 'lucide-react';
import { router, usePage } from '@inertiajs/react';
import { showToast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';

interface Department {
    id: number;
    code: string;
    name: string;
    parent_department_id?: number | null;
}

interface DepartmentTreeNode extends Department {
    children?: Department[];
}

interface PagePropsWithDepartment {
    currentDepartment: (Department & { parent?: Department | null }) | null;
    availableDepartments: DepartmentTreeNode[];
    [key: string]: unknown;
}

// Animation variants
const dropdownVariants: Variants = {
    hidden: { 
        opacity: 0, 
        y: 10, 
        scale: 0.95,
    },
    visible: { 
        opacity: 1, 
        y: 0, 
        scale: 1,
        transition: { 
            duration: 0.2, 
            ease: [0.16, 1, 0.3, 1] as const,
        }
    },
    exit: { 
        opacity: 0, 
        y: 10, 
        scale: 0.95,
        transition: { duration: 0.15 }
    }
};

// Department icon component
function DepartmentIcon({ 
    department, 
    size = 'md',
}: { 
    department: Department; 
    size?: 'sm' | 'md' | 'lg';
}) {
    const sizeClasses = {
        sm: 'w-8 h-8 text-xs',
        md: 'w-10 h-10 text-sm',
        lg: 'w-12 h-12 text-base',
    };
    
    const gradients = [
        'from-blue-600 to-blue-800',
        'from-purple-600 to-purple-800',
        'from-teal-600 to-teal-800',
        'from-rose-600 to-rose-800',
        'from-amber-600 to-amber-800',
    ];
    const gradientIndex = department.code.charCodeAt(0) % gradients.length;
    
    return (
        <div 
            className={cn(
                "flex items-center justify-center font-bold text-white bg-gradient-to-br rounded-full flex-shrink-0",
                sizeClasses[size],
                gradients[gradientIndex]
            )}
        >
            {department.code.substring(0, 2)}
        </div>
    );
}

// Smooth checkmark
function SmoothCheck({ isVisible }: { isVisible: boolean }) {
    return (
        <AnimatePresence>
            {isVisible && (
                <motion.div
                    initial={{ scale: 0, opacity: 0 }}
                    animate={{ scale: 1, opacity: 1 }}
                    exit={{ scale: 0, opacity: 0 }}
                    transition={{ 
                        type: 'spring',
                        stiffness: 300,
                        damping: 20,
                    }}
                    className="flex-shrink-0 text-primary"
                >
                    <svg 
                        className="w-6 h-6" 
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor"
                        strokeWidth={2.5}
                    >
                        <motion.path 
                            strokeLinecap="round" 
                            strokeLinejoin="round" 
                            d="M5 13l4 4L19 7"
                            initial={{ pathLength: 0 }}
                            animate={{ pathLength: 1 }}
                            transition={{ duration: 0.3, delay: 0.1 }}
                        />
                    </svg>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

// Department item in dropdown
function DepartmentMenuItem({
    department,
    isActive,
    isSubDepartment,
    onClick,
}: {
    department: Department;
    isActive: boolean;
    isSubDepartment?: boolean;
    onClick: () => void;
}) {
    return (
        <button
            onClick={onClick}
            disabled={isActive}
            className={cn(
                "w-full flex items-center gap-3 px-4 py-3 text-left transition-colors",
                isSubDepartment && "pl-10",
                isActive
                    ? "bg-primary cursor-default"
                    : "hover:bg-gray-50 active:bg-gray-100"
            )}
        >
            <DepartmentIcon department={department} size="md" />

            <div className="flex-1 min-w-0">
                <span className={cn(
                    "font-semibold text-base block",
                    isActive ? "text-gray-900" : "text-gray-900"
                )}>
                    {department.code}
                </span>
                <p className="text-sm text-gray-500 truncate">{department.name}</p>
            </div>

            <SmoothCheck isVisible={isActive} />
        </button>
    );
}

export function DepartmentSwitcher() {
    const [isOpen, setIsOpen] = useState(false);
    const [isSwitching, setIsSwitching] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const { currentDepartment, availableDepartments } = usePage<PagePropsWithDepartment>().props;
    
    // Flatten tree to a list of switchable depts (root + their children),
    // preserving hierarchy via the optional parent_department_id field.
    const flatDepartments = useMemo<Department[]>(() => {
        const out: Department[] = [];
        for (const root of availableDepartments) {
            out.push({
                id: root.id,
                code: root.code,
                name: root.name,
                parent_department_id: root.parent_department_id ?? null,
            });
            for (const child of root.children ?? []) {
                out.push({
                    id: child.id,
                    code: child.code,
                    name: child.name,
                    parent_department_id: child.parent_department_id ?? root.id,
                });
            }
        }
        return out;
    }, [availableDepartments]);

    // Only render if user has multiple departments (across roots + children)
    const hasMultipleDepartments = flatDepartments.length > 1;
    
    // Close on outside click
    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);
    
    // Close on escape
    useEffect(() => {
        function handleEscape(event: KeyboardEvent) {
            if (event.key === 'Escape') setIsOpen(false);
        }
        document.addEventListener('keydown', handleEscape);
        return () => document.removeEventListener('keydown', handleEscape);
    }, []);
    
    const handleSwitch = async (departmentId: number) => {
        // Don't switch if already on this department or already switching
        if (currentDepartment?.id === departmentId || isSwitching) {
            return;
        }

        setIsOpen(false);
        setIsSwitching(true);

        // Validate user has access to this department
        const targetDepartment = flatDepartments.find(dept => dept.id === departmentId);
        if (!targetDepartment) {
            showToast.error('You do not have access to this department');
            setIsSwitching(false);
            return;
        }

        // Use Inertia router.post - automatically handles CSRF token
        router.post(
            '/api/department/switch',
            { department_id: departmentId },
            {
                preserveState: false,
                preserveScroll: false,
                onSuccess: () => {
                    showToast.success(`Switched to ${targetDepartment.name}`);
                },
                onError: (errors) => {
                    const message = errors.message || 'Failed to switch department';
                    showToast.error(message);
                },
                onFinish: () => {
                    setIsSwitching(false);
                },
            }
        );
    };
    
    // Don't render if no current department or only one department
    if (!currentDepartment || !hasMultipleDepartments) {
        return null;
    }
    
    // Multiple departments - show clickable button with dropdown
    return (
        <div ref={dropdownRef} className="relative">
            {/* Trigger - Icon + Department Name */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                disabled={isSwitching}
                className={cn(
                    "flex h-10 items-center gap-2 rounded-lg border px-3 transition-all",
                    isOpen 
                        ? "border-primary bg-white shadow-sm" 
                        : "border-gray-200 bg-white hover:border-gray-300",
                    "focus:outline-none focus:border-primary",
                    isSwitching && "opacity-70 cursor-wait"
                )}
            >
                <Building2 className="w-5 h-5 text-gray-500" />
                
                <span className="hidden max-w-48 truncate text-sm font-medium text-slate-900 sm:block">
                    {currentDepartment.name}
                </span>
                
                <motion.div
                    animate={{ rotate: isOpen ? 180 : 0 }}
                    transition={{ duration: 0.2 }}
                >
                    <ChevronUp className="w-5 h-5 text-gray-400" />
                </motion.div>
            </button>
            
            {/* Dropdown */}
            <AnimatePresence>
                {isOpen && (
                    <motion.div
                        variants={dropdownVariants}
                        initial="hidden"
                        animate="visible"
                        exit="exit"
                        className="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden z-50"
                    >
                        {/* Header */}
                        <div className="px-4 py-3 border-b border-gray-100">
                            <h3 className="text-base font-semibold text-gray-900">
                                Switch Department
                            </h3>
                            <p className="text-sm text-gray-500">
                                Select a department to switch context
                            </p>
                        </div>
                        
                        {/* List */}
                        <div className="max-h-80 overflow-y-auto divide-y divide-gray-100">
                            {flatDepartments.map((dept) => (
                                <DepartmentMenuItem
                                    key={dept.id}
                                    department={dept}
                                    isActive={dept.id === currentDepartment.id}
                                    isSubDepartment={Boolean(dept.parent_department_id)}
                                    onClick={() => handleSwitch(dept.id)}
                                />
                            ))}
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}

export default DepartmentSwitcher;
