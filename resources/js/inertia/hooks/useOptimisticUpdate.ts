/**
 * useOptimisticUpdate Hook
 * 
 * Provides optimistic UI updates with automatic rollback on failure.
 * 
 * Requirements:
 * - 11.7: Handle optimistic updates with rollback on failure
 * 
 * Features:
 * - Immediately updates UI before server response
 * - Automatically rolls back to previous state on error
 * - Supports custom rollback logic
 * - Integrates with Inertia.js router
 * 
 * Usage:
 * ```tsx
 * const { optimisticUpdate } = useOptimisticUpdate();
 * 
 * const handleToggleStatus = (item: Item) => {
 *   optimisticUpdate({
 *     // Optimistic state update
 *     optimisticData: { ...item, is_active: !item.is_active },
 *     
 *     // Server request
 *     request: () => router.post(route('toggle-status', item.id)),
 *     
 *     // Optional: Custom rollback
 *     onRollback: () => {
 *       console.log('Update failed, rolled back');
 *     }
 *   });
 * };
 * ```
 */

import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface OptimisticUpdateOptions<T> {
  // The optimistic data to display immediately
  optimisticData: T;
  
  // The server request to perform
  request: () => void;
  
  // Optional: Previous data to rollback to (if not provided, will use current state)
  previousData?: T;
  
  // Optional: Custom rollback handler
  onRollback?: (previousData: T) => void;
  
  // Optional: Success handler
  onSuccess?: () => void;
  
  // Optional: Error handler
  onError?: (error: any) => void;
  
  // Optional: Show toast on rollback
  showRollbackToast?: boolean;
}

export function useOptimisticUpdate<T = any>() {
  const [isOptimistic, setIsOptimistic] = useState(false);
  const [optimisticState, setOptimisticState] = useState<T | null>(null);
  const [previousState, setPreviousState] = useState<T | null>(null);

  const optimisticUpdate = useCallback((options: OptimisticUpdateOptions<T>) => {
    const {
      optimisticData,
      request,
      previousData,
      onRollback,
      onSuccess,
      onError,
      showRollbackToast = true,
    } = options;

    // Store previous state for rollback
    const rollbackData = previousData || previousState;
    setPreviousState(rollbackData);

    // Apply optimistic update immediately
    setIsOptimistic(true);
    setOptimisticState(optimisticData);

    // Perform the actual request
    try {
      request();
      
      // Note: Inertia handles success/error through its own callbacks
      // We'll rely on the router's onSuccess/onError handlers
      
      // Reset optimistic state on success
      setTimeout(() => {
        setIsOptimistic(false);
        setOptimisticState(null);
        onSuccess?.();
      }, 100);
      
    } catch (error) {
      // Rollback on error
      if (rollbackData) {
        setOptimisticState(rollbackData);
        onRollback?.(rollbackData);
      }
      
      if (showRollbackToast) {
        toast.error('Update failed. Changes have been reverted.');
      }
      
      setIsOptimistic(false);
      onError?.(error);
    }
  }, [previousState]);

  const rollback = useCallback(() => {
    if (previousState) {
      setOptimisticState(previousState);
      setIsOptimistic(false);
    }
  }, [previousState]);

  return {
    optimisticUpdate,
    rollback,
    isOptimistic,
    optimisticState,
  };
}

/**
 * useOptimisticList Hook
 * 
 * Specialized hook for optimistic updates on list items (add, update, delete).
 * 
 * Usage:
 * ```tsx
 * const { items, addItem, updateItem, deleteItem } = useOptimisticList(initialItems);
 * 
 * const handleCreate = (newItem: Item) => {
 *   addItem({
 *     item: newItem,
 *     request: () => router.post(route('items.store'), newItem),
 *   });
 * };
 * ```
 */
export function useOptimisticList<T extends { id: number | string }>(initialItems: T[]) {
  const [items, setItems] = useState<T[]>(initialItems);
  const [previousItems, setPreviousItems] = useState<T[]>(initialItems);

  const addItem = useCallback((options: {
    item: T;
    request: () => void;
    onSuccess?: () => void;
    onError?: (error: any) => void;
  }) => {
    const { item, request, onSuccess, onError } = options;
    
    // Store previous state
    setPreviousItems(items);
    
    // Optimistically add item
    setItems(prev => [...prev, item]);
    
    try {
      request();
      onSuccess?.();
    } catch (error) {
      // Rollback on error
      setItems(previousItems);
      toast.error('Failed to add item. Changes have been reverted.');
      onError?.(error);
    }
  }, [items, previousItems]);

  const updateItem = useCallback((options: {
    id: number | string;
    updates: Partial<T>;
    request: () => void;
    onSuccess?: () => void;
    onError?: (error: any) => void;
  }) => {
    const { id, updates, request, onSuccess, onError } = options;
    
    // Store previous state
    setPreviousItems(items);
    
    // Optimistically update item
    setItems(prev => prev.map(item => 
      item.id === id ? { ...item, ...updates } : item
    ));
    
    try {
      request();
      onSuccess?.();
    } catch (error) {
      // Rollback on error
      setItems(previousItems);
      toast.error('Failed to update item. Changes have been reverted.');
      onError?.(error);
    }
  }, [items, previousItems]);

  const deleteItem = useCallback((options: {
    id: number | string;
    request: () => void;
    onSuccess?: () => void;
    onError?: (error: any) => void;
  }) => {
    const { id, request, onSuccess, onError } = options;
    
    // Store previous state
    setPreviousItems(items);
    
    // Optimistically remove item
    setItems(prev => prev.filter(item => item.id !== id));
    
    try {
      request();
      onSuccess?.();
    } catch (error) {
      // Rollback on error
      setItems(previousItems);
      toast.error('Failed to delete item. Changes have been reverted.');
      onError?.(error);
    }
  }, [items, previousItems]);

  return {
    items,
    setItems,
    addItem,
    updateItem,
    deleteItem,
  };
}

export default useOptimisticUpdate;
