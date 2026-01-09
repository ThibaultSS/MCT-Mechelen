import './bootstrap';
import { createApp } from 'vue';
import ExampleComponent from './components/ExampleComponent.vue';
import CometTypingGame from './components/CometTypingGame.vue';
import AiOrNotGame from './components/AiOrNotGame.vue';
import HexaGuessGame from './components/HexaGuessGame.vue';

// Wacht tot DOM geladen is
document.addEventListener('DOMContentLoaded', () => {
    // Maak de Vue app aan
    const app = createApp({});

    // Registreer globale componenten
    app.component('example-component', ExampleComponent);
    app.component('comet-typing-game', CometTypingGame);
    app.component('ai-or-not-game', AiOrNotGame);
    app.component('hexa-guess-game', HexaGuessGame);

    // Mount de app op een element in je Blade template
    const appElement = document.getElementById('app');
    if (appElement) {
        try {
            app.mount('#app');
        } catch (error) {
            console.error('Error mounting Vue app:', error);
        }
    } else {
        console.error('App element (#app) not found in DOM');
    }
});
