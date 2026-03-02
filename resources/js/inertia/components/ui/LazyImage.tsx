import React, { useState, useEffect, useRef } from 'react';
import { cn } from '../../lib/utils';

interface LazyImageProps extends React.ImgHTMLAttributes<HTMLImageElement> {
    src: string;
    alt: string;
    className?: string;
    placeholderClassName?: string;
    fallbackSrc?: string;
    threshold?: number;
    rootMargin?: string;
}

/**
 * LazyImage Component
 * 
 * Implements lazy loading for images with:
 * - Intersection Observer API for viewport detection
 * - Blur-up placeholder effect
 * - Error handling with fallback image
 * - Responsive image support
 * - Performance optimization
 * 
 * @example
 * <LazyImage
 *   src="/path/to/image.jpg"
 *   alt="Description"
 *   className="w-32 h-32 rounded-lg"
 * />
 */
export const LazyImage: React.FC<LazyImageProps> = ({
    src,
    alt,
    className,
    placeholderClassName,
    fallbackSrc = '/images/placeholder.png',
    threshold = 0.1,
    rootMargin = '50px',
    ...props
}) => {
    const [isLoaded, setIsLoaded] = useState(false);
    const [isInView, setIsInView] = useState(false);
    const [hasError, setHasError] = useState(false);
    const imgRef = useRef<HTMLImageElement>(null);

    useEffect(() => {
        // Check if Intersection Observer is supported
        if (!('IntersectionObserver' in window)) {
            // Fallback: load image immediately if IO not supported
            setIsInView(true);
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        setIsInView(true);
                        // Stop observing once image is in view
                        if (imgRef.current) {
                            observer.unobserve(imgRef.current);
                        }
                    }
                });
            },
            {
                threshold,
                rootMargin,
            }
        );

        if (imgRef.current) {
            observer.observe(imgRef.current);
        }

        return () => {
            if (imgRef.current) {
                observer.unobserve(imgRef.current);
            }
        };
    }, [threshold, rootMargin]);

    const handleLoad = () => {
        setIsLoaded(true);
    };

    const handleError = () => {
        setHasError(true);
        setIsLoaded(true);
    };

    const imageSrc = hasError ? fallbackSrc : src;

    return (
        <div className={cn('relative overflow-hidden', className)}>
            {/* Placeholder - shown while loading */}
            {!isLoaded && (
                <div
                    className={cn(
                        'absolute inset-0 bg-gray-200 animate-pulse',
                        placeholderClassName
                    )}
                />
            )}

            {/* Actual Image */}
            <img
                ref={imgRef}
                src={isInView ? imageSrc : undefined}
                alt={alt}
                onLoad={handleLoad}
                onError={handleError}
                className={cn(
                    'transition-opacity duration-300',
                    isLoaded ? 'opacity-100' : 'opacity-0',
                    className
                )}
                loading="lazy"
                decoding="async"
                {...props}
            />
        </div>
    );
};

/**
 * LazyAvatar Component
 * 
 * Specialized lazy image for user avatars with initials fallback
 */
interface LazyAvatarProps {
    src?: string | null;
    alt: string;
    name: string;
    className?: string;
    size?: 'sm' | 'md' | 'lg';
}

export const LazyAvatar: React.FC<LazyAvatarProps> = ({
    src,
    alt,
    name,
    className,
    size = 'md',
}) => {
    const getInitials = (fullName: string) => {
        return fullName
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const sizeClasses = {
        sm: 'w-6 h-6 text-xs',
        md: 'w-8 h-8 text-sm',
        lg: 'w-12 h-12 text-base',
    };

    if (!src) {
        return (
            <div
                className={cn(
                    'rounded-full bg-primary flex items-center justify-center',
                    sizeClasses[size],
                    className
                )}
            >
                <span className="font-medium text-white">{getInitials(name)}</span>
            </div>
        );
    }

    return (
        <LazyImage
            src={src}
            alt={alt}
            className={cn('rounded-full object-cover', sizeClasses[size], className)}
        />
    );
};

/**
 * LazyLogo Component
 * 
 * Specialized lazy image for business unit logos with fallback
 */
interface LazyLogoProps {
    src?: string | null;
    alt: string;
    className?: string;
    fallbackText?: string;
}

export const LazyLogo: React.FC<LazyLogoProps> = ({
    src,
    alt,
    className,
    fallbackText,
}) => {
    if (!src) {
        return (
            <div
                className={cn(
                    'rounded bg-gray-200 flex items-center justify-center',
                    className
                )}
            >
                {fallbackText && (
                    <span className="text-xs font-medium text-gray-600">
                        {fallbackText}
                    </span>
                )}
            </div>
        );
    }

    return (
        <LazyImage
            src={src}
            alt={alt}
            className={cn('rounded object-cover', className)}
        />
    );
};
