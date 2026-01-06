import './bootstrap';
import { createApp } from 'vue';
import ExampleComponent from './components/ExampleComponent.vue';
import CometTypingGame from './components/CometTypingGame.vue';

// Wacht tot DOM geladen is
document.addEventListener('DOMContentLoaded', () => {
    // Maak de Vue app aan
    const app = createApp({});

    // Registreer globale componenten
    app.component('example-component', ExampleComponent);
    app.component('comet-typing-game', CometTypingGame);

    // Mount de app op een element in je Blade template
    const appElement = document.getElementById('app');
    if (appElement) {
        try {
            app.mount('#app');
        } catch (error) {
            // Silently fail in production
            if (process.env.NODE_ENV === 'development') {
                console.error('Error mounting Vue app:', error);
            }
        }
    }
});
