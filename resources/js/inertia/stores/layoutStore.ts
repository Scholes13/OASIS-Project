import { create } from 'zustand';

export interface BusinessUnit {
    id: number;
    code: string;
    name: string;
    logo: string | null;
}

interface LayoutState {
    currentBusinessUnit: BusinessUnit | null;
    availableBusinessUnits: BusinessUnit[];
    isSwitchingBU: boolean;
    sidebarOpen: boolean;
}

interface LayoutActions {
    setCurrentBusinessUnit: (bu: BusinessUnit | null) => void;
    setAvailableBusinessUnits: (bus: BusinessUnit[]) => void;
    setSwitchingBU: (switching: boolean) => void;
    toggleSidebar: () => void;
    setSidebarOpen: (open: boolean) => void;
}

type LayoutStore = LayoutState & LayoutActions;

export const useLayoutStore = create<LayoutStore>((set) => ({
    // State
    currentBusinessUnit: null,
    availableBusinessUnits: [],
    isSwitchingBU: false,
    sidebarOpen: true,

    // Actions
    setCurrentBusinessUnit: (bu) => set({ currentBusinessUnit: bu }),
    setAvailableBusinessUnits: (bus) => set({ availableBusinessUnits: bus }),
    setSwitchingBU: (switching) => set({ isSwitchingBU: switching }),
    toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
    setSidebarOpen: (open) => set({ sidebarOpen: open }),
}));

// Selectors for better performance
export const useCurrentBusinessUnit = () => useLayoutStore((state) => state.currentBusinessUnit);
export const useAvailableBusinessUnits = () => useLayoutStore((state) => state.availableBusinessUnits);
export const useIsSwitchingBU = () => useLayoutStore((state) => state.isSwitchingBU);
export const useSidebarOpen = () => useLayoutStore((state) => state.sidebarOpen);
