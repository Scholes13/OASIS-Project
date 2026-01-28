/**
 * Login Store
 * 
 * Manages login state and welcome messages.
 */

import { create } from 'zustand';

// Welcome messages - random selection on each login
const welcomeMessages = [
    { title: 'Welcome back!', subtitle: 'Great to see you again! 🎉' },
    { title: 'Hello there!', subtitle: 'Ready to be productive today? 🚀' },
    { title: 'Good to see you!', subtitle: 'Let\'s make today count! 💪' },
    { title: 'Welcome!', subtitle: 'Your workspace is ready! ✨' },
    { title: 'Hey there!', subtitle: 'Time to get things done! 🎯' },
    { title: 'Nice to see you!', subtitle: 'Let\'s achieve great things! 🏆' },
    { title: 'Welcome aboard!', subtitle: 'Your dashboard awaits! 📊' },
    { title: 'Hello again!', subtitle: 'Ready for a productive day? ⭐' },
    { title: 'Great to have you!', subtitle: 'Let\'s make it happen! 💫' },
    { title: 'Welcome back!', subtitle: 'Success starts here! 🌟' },
];

interface LoginState {
    isLoggingIn: boolean;
    userName: string;
    message: { title: string; subtitle: string };
    startLogin: (userName: string) => void;
    completeLogin: () => void;
    reset: () => void;
}

export const useLoginStore = create<LoginState>((set) => ({
    isLoggingIn: false,
    userName: '',
    message: welcomeMessages[0],
    
    startLogin: (userName: string) => {
        // Pick random welcome message
        const randomMessage = welcomeMessages[Math.floor(Math.random() * welcomeMessages.length)];
        set({ 
            isLoggingIn: true, 
            userName,
            message: randomMessage,
        });
    },
    
    completeLogin: () => {
        set({ isLoggingIn: false });
    },
    
    reset: () => {
        set({ 
            isLoggingIn: false, 
            userName: '',
            message: welcomeMessages[0],
        });
    },
}));

// Selectors for optimized re-renders
export const useIsLoggingIn = () => useLoginStore((state) => state.isLoggingIn);
export const useLoginUserName = () => useLoginStore((state) => state.userName);
export const useLoginMessage = () => useLoginStore((state) => state.message);
