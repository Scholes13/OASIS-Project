/**
 * BusinessUnitSwitcher Component
 * 
 * Clean business unit switcher matching the design reference.
 * Shows: Logo + Code + Name (no role/staff text)
 */

import { useState, useRef, useEffect, useMemo } from 'react';
import { motion, AnimatePresence, type Variants } from 'framer-motion';
import { ChevronUp } from 'lucide-react';
import { useBusinessUnit, BusinessUnit } from '@/hooks/useBusinessUnit';
import { cn } from '@/lib/utils';

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

// Logo component - rounded rectangle to prevent logo cropping
function BuLogo({ 
    bu, 
    size = 'md',
}: { 
    bu: BusinessUnit; 
    size?: 'sm' | 'md' | 'lg';
}) {
    const sizeClasses = {
        sm: 'w-8 h-8 text-xs',
        md: 'w-10 h-10 text-sm',
        lg: 'w-12 h-12 text-base',
    };
    
    const gradients = [
        'from-indigo-600 to-indigo-800',
        'from-emerald-600 to-emerald-800',
        'from-orange-600 to-orange-800',
        'from-blue-600 to-blue-800',
        'from-pink-600 to-pink-800',
    ];
    const gradientIndex = bu.code.charCodeAt(0) % gradients.length;
    
    if (bu.logo) {
        return (
            <div className={cn("flex-shrink-0 rounded-xl overflow-hidden bg-gray-50 p-1", sizeClasses[size])}>
                <img 
                    src={`/storage/${bu.logo}`} 
                    alt={bu.code} 
                    className="w-full h-full object-contain"
                />
            </div>
        );
    }
    
    return (
        <div 
            className={cn(
                "flex items-center justify-center font-bold text-white bg-gradient-to-br rounded-xl flex-shrink-0",
                sizeClasses[size],
                gradients[gradientIndex]
            )}
        >
            {bu.code.substring(0, 2)}
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

// BU item in dropdown - enterprise modern SaaS styling
function BuMenuItem({ 
    bu, 
    isActive, 
    onClick,
}: { 
    bu: BusinessUnit; 
    isActive: boolean; 
    onClick: () => void;
}) {
    return (
        <button
            onClick={onClick}
            disabled={isActive}
            className={cn(
                "w-full flex items-center gap-3 px-4 py-3 text-left transition-all duration-150",
                isActive 
                    ? "bg-primary/[0.06] border-l-[3px] border-l-primary cursor-default" 
                    : "hover:bg-gray-50 active:bg-gray-100 border-l-[3px] border-l-transparent"
            )}
        >
            <BuLogo bu={bu} size="md" />
            
            <div className="flex-1 min-w-0">
                <span className={cn(
                    "font-semibold text-base block",
                    isActive ? "text-primary" : "text-gray-900"
                )}>
                    {bu.code}
                </span>
                <p className={cn(
                    "text-sm truncate",
                    isActive ? "text-primary/70" : "text-gray-500"
                )}>
                    {bu.name}
                </p>
            </div>
            
            <SmoothCheck isVisible={isActive} />
        </button>
    );
}

export function BusinessUnitSwitcher() {
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const { currentBusinessUnit, availableBusinessUnits, switchBusinessUnit, isSwitching } = useBusinessUnit();
    
    // Sort: active BU first
    const sortedBusinessUnits = useMemo(() => {
        if (!currentBusinessUnit || !availableBusinessUnits.length) return availableBusinessUnits;
        
        return [...availableBusinessUnits].sort((a, b) => {
            if (a.id === currentBusinessUnit.id) return -1;
            if (b.id === currentBusinessUnit.id) return 1;
            return a.code.localeCompare(b.code);
        });
    }, [availableBusinessUnits, currentBusinessUnit]);
    
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
    
    const handleSwitch = async (buId: number) => {
        setIsOpen(false);
        await switchBusinessUnit(buId);
    };
    
    if (!currentBusinessUnit) return null;
    
    const hasMultipleBUs = availableBusinessUnits.length > 1;
    
    // Single BU - just display
    if (!hasMultipleBUs) {
        return (
            <div className="flex h-10 items-center gap-2 px-2">
                <BuLogo bu={currentBusinessUnit} size="sm" />
                <span className="hidden text-sm font-medium text-slate-700 sm:block">
                    {currentBusinessUnit.name}
                </span>
            </div>
        );
    }
    
    return (
        <div ref={dropdownRef} className="relative">
            {/* Trigger - Logo + Full Name (rounded-xl, not too curved) */}
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
                <BuLogo bu={currentBusinessUnit} size="sm" />
                
                <span className="hidden max-w-56 truncate text-sm font-medium text-slate-900 sm:block">
                    {currentBusinessUnit.name}
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
                                Switch Business Unit
                            </h3>
                            <p className="text-sm text-gray-500">
                                Select a business unit to switch context
                            </p>
                        </div>
                        
                        {/* List */}
                        <div className="max-h-80 overflow-y-auto divide-y divide-gray-100">
                            {sortedBusinessUnits.map((bu) => (
                                <BuMenuItem
                                    key={bu.id}
                                    bu={bu}
                                    isActive={bu.id === currentBusinessUnit.id}
                                    onClick={() => handleSwitch(bu.id)}
                                />
                            ))}
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}

export default BusinessUnitSwitcher;
