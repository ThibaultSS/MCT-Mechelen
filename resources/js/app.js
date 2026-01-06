import './bootstrap';
import { createApp } from 'vue';
import ExampleComponent from './components/ExampleComponent.vue';

// Maak de Vue app aan
const app = createApp({});

// Registreer globale componenten
app.component('example-component', ExampleComponent);

// Mount de app op een element in je Blade template
// Voeg <div id="app"></div> toe aan je Blade template en gebruik <example-component></example-component>
const appElement = document.getElementById('app');
if (appElement) {
    app.mount('#app');
}
