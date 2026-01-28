/**
 * Logout Store
 * 
 * Manages logout state and farewell messages.
 */

import { create } from 'zustand';

// Farewell messages - random selection on each logout
const farewellMessages = [
    { title: 'Goodbye!', subtitle: 'Have a wonderful day ahead! 🌟' },
    { title: 'See you soon!', subtitle: 'Take care and stay awesome! ✨' },
    { title: 'Until next time!', subtitle: 'Keep up the great work! 💪' },
    { title: 'Bye for now!', subtitle: 'Wishing you a productive day! 🚀' },
    { title: 'Take care!', subtitle: 'See you on your next adventure! 🎯' },
    { title: 'Farewell!', subtitle: 'May your day be filled with success! 🏆' },
    { title: 'Catch you later!', subtitle: 'Stay positive and keep smiling! 😊' },
    { title: 'So long!', subtitle: 'Remember, you\'re doing amazing! 💫' },
    { title: 'Adios!', subtitle: 'Have a fantastic rest of your day! 🌈' },
    { title: 'Cheerio!', subtitle: 'Keep being brilliant! 🌟' },
    { title: 'Peace out!', subtitle: 'You\'ve got this! 💪' },
    { title: 'Later!', subtitle: 'Make today count! ⭐' },
];

interface LogoutState {
    isLoggingOut: boolean;
    userName: string;
    message: { title: string; subtitle: string };
    startLogout: (userName: string) => void;
    completeLogout: () => void;
    reset: () => void;
}

export const useLogoutStore = create<LogoutState>((set) => ({
    isLoggingOut: false,
    userName: '',
    message: farewellMessages[0],
    
    startLogout: (userName: string) => {
        // Pick random farewell message
        const randomMessage = farewellMessages[Math.floor(Math.random() * farewellMessages.length)];
        set({ 
            isLoggingOut: true, 
            userName,
            message: randomMessage,
        });
    },
    
    completeLogout: () => {
        // This will be called after animation completes
        set({ isLoggingOut: false });
    },
    
    reset: () => {
        set({ 
            isLoggingOut: false, 
            userName: '',
            message: farewellMessages[0],
        });
    },
}));

// Selectors for optimized re-renders
export const useIsLoggingOut = () => useLogoutStore((state) => state.isLoggingOut);
export const useLogoutUserName = () => useLogoutStore((state) => state.userName);
export const useLogoutMessage = () => useLogoutStore((state) => state.message);
