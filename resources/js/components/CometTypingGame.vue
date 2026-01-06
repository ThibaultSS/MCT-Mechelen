<template>
    <div class="game-container">
        <!-- Game UI -->
        <div class="game-header">
            <div class="stat">
                <span class="stat-label">Score</span>
                <span class="stat-value">{{ score }}</span>
            </div>
            <div class="stat">
                <span class="stat-label">Tijd</span>
                <span class="stat-value">{{ remainingTime }}s</span>
            </div>
        </div>

        <!-- Comets -->
        <div
            v-for="comet in comets"
            :key="comet.id"
            class="comet-wrapper"
            :style="{
                left: `${comet.left}px`,
                top: `${comet.top}px`,
                transform: `rotate(${comet.rotation}deg)`,
            }"
        >
            <!-- Komeet SVG -->
            <svg class="comet-svg" width="120" height="120" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <filter :id="'glow-' + comet.id">
                        <feGaussianBlur stdDeviation="5" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                    <radialGradient :id="'cometGradient-' + comet.id" cx="50%" cy="50%">
                        <stop offset="0%" style="stop-color:#FCC600;stop-opacity:1" />
                        <stop offset="40%" style="stop-color:#FB6E00;stop-opacity:0.9" />
                        <stop offset="100%" style="stop-color:#FB6E00;stop-opacity:0.5" />
                    </radialGradient>
                </defs>
                <!-- Komeet kern (groter) -->
                <circle cx="50" cy="50" r="35" :fill="'url(#cometGradient-' + comet.id + ')'" :filter="'url(#glow-' + comet.id + ')'"/>
                <circle cx="50" cy="50" r="30" fill="#FCC600" opacity="0.95"/>
                <circle cx="50" cy="50" r="24" fill="#fff" opacity="0.9"/>
                <circle cx="50" cy="50" r="18" fill="#FCC600" opacity="0.8"/>
                <!-- Tekst in het midden van de komeet -->
                <text 
                    x="50" 
                    y="50" 
                    text-anchor="middle" 
                    dominant-baseline="central"
                    font-family="Arial, sans-serif"
                    font-size="16"
                    font-weight="bold"
                    fill="#07103E"
                    style="pointer-events: none;"
                >{{ comet.word }}</text>
            </svg>
        </div>

        <!-- Explosion effects -->
        <div
            v-for="explosion in explosions"
            :key="explosion.id"
            class="explosion"
            :style="{
                left: `${explosion.x}px`,
                top: `${explosion.y}px`,
            }"
        >
            <div
                v-for="i in 8"
                :key="i"
                class="explosion-particle"
                :style="{ '--index': i }"
            ></div>
        </div>

        <!-- Input -->
        <div class="input-container">
            <input
                v-model="inputValue"
                type="text"
                class="game-input"
                placeholder="Typ het woord..."
                @keydown.enter="handleInput"
                autofocus
            />
        </div>

        <!-- Game Over Modal -->
        <div
            v-if="gameEnded"
            class="modal-overlay"
            @click.self="restartGame"
        >
            <div class="modal-content">
                <h2 class="modal-title">Tijd voorbij!</h2>
                <p class="modal-text">
                    Je hebt <strong>{{ score }}</strong> kometen vernietigd
                </p>
                <button
                    class="btn btn-primary"
                    @click="restartGame"
                >
                    Opnieuw spelen
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

// Constants
const GAME_DURATION = 60; // seconds
const DIFFICULTY_INTERVAL = 10000; // milliseconds
const INITIAL_SPAWN_INTERVAL = 2000; // milliseconds
const MIN_SPAWN_INTERVAL = 800; // milliseconds

const WORDS = [
    'appel', 'banaan', 'citroen', 'druif', 'eik', 'fiets', 'gitaar', 'huis',
    'ijs', 'jas', 'kast', 'lamp', 'muziek', 'noot', 'olifant', 'piano',
    'quiz', 'raket', 'ster', 'tafel', 'ui', 'vogel', 'water', 'xylofoon',
    'yoga', 'zon', 'auto', 'boek', 'code', 'dans', 'eten', 'feest',
    'goud', 'hond', 'kaas', 'liefde', 'maan', 'nacht', 'oog', 'paard'
];

// State
const comets = ref([]);
const explosions = ref([]);
const inputValue = ref('');
const gameTime = ref(0);
const score = ref(0);
const gameEnded = ref(false);
const speed = ref(1.2);
const spawnInterval = ref(INITIAL_SPAWN_INTERVAL);

// Computed
const remainingTime = computed(() => GAME_DURATION - gameTime.value);

// Timers
let spawnTimer = null;
let gameTimer = null;
let difficultyTimer = null;
let animationFrame = null;

// ID counters
let cometIdCounter = 0;
let explosionIdCounter = 0;

// Game logic
const createComet = () => {
    if (gameEnded.value) return;

    const comet = {
        id: cometIdCounter++,
        word: WORDS[Math.floor(Math.random() * WORDS.length)],
        left: Math.random() * Math.max(100, window.innerWidth - 200),
        top: -100,
        rotation: Math.random() * 10 - 5, // Start rotatie tussen -5 en 5 graden
    };

    comets.value.push(comet);
};

const moveComets = () => {
    if (gameEnded.value) return;

    comets.value.forEach(comet => {
        comet.top += speed.value;
        comet.rotation += 0.15; // Langzamere rotatie tijdens vallen
    });

    // Remove comets that are off screen
    comets.value = comets.value.filter(
        comet => comet.top < window.innerHeight + 150
    );

    // Clean up old explosions
    explosions.value = explosions.value.filter(explosion => {
        explosion.age = (explosion.age || 0) + 1;
        return explosion.age < 25;
    });

    animationFrame = requestAnimationFrame(moveComets);
};

const createExplosion = (x, y) => {
    explosions.value.push({
        id: explosionIdCounter++,
        x,
        y,
        age: 0,
    });
};

const destroyComet = (index) => {
    const comet = comets.value[index];
    createExplosion(comet.left, comet.top);
    comets.value.splice(index, 1);
    score.value++;
};

const handleInput = () => {
    if (gameEnded.value || !inputValue.value.trim()) return;

    const input = inputValue.value.trim().toLowerCase();
    const index = comets.value.findIndex(
        comet => comet.word.toLowerCase() === input
    );

    if (index !== -1) {
        destroyComet(index);
    }

    inputValue.value = '';
};

const increaseDifficulty = () => {
    if (gameEnded.value) return;

    speed.value += 0.2;

    if (spawnInterval.value > MIN_SPAWN_INTERVAL) {
        clearInterval(spawnTimer);
        spawnInterval.value -= 100;
        spawnTimer = setInterval(createComet, spawnInterval.value);
    }
};

const checkGameTime = () => {
    if (gameEnded.value) return;

    gameTime.value++;

    if (gameTime.value >= GAME_DURATION) {
        endGame();
    }
};

const endGame = () => {
    gameEnded.value = true;
    comets.value = [];

    if (spawnTimer) clearInterval(spawnTimer);
    if (gameTimer) clearInterval(gameTimer);
    if (difficultyTimer) clearInterval(difficultyTimer);
    if (animationFrame) cancelAnimationFrame(animationFrame);
};

const startGame = () => {
    // Reset state
    gameEnded.value = false;
    comets.value = [];
    explosions.value = [];
    inputValue.value = '';
    gameTime.value = 0;
    score.value = 0;
    speed.value = 1.2;
    spawnInterval.value = INITIAL_SPAWN_INTERVAL;
    cometIdCounter = 0;
    explosionIdCounter = 0;

    // Clear any existing timers
    if (spawnTimer) clearInterval(spawnTimer);
    if (gameTimer) clearInterval(gameTimer);
    if (difficultyTimer) clearInterval(difficultyTimer);
    if (animationFrame) cancelAnimationFrame(animationFrame);

    // Start timers
    spawnTimer = setInterval(createComet, spawnInterval.value);
    gameTimer = setInterval(checkGameTime, 1000);
    difficultyTimer = setInterval(increaseDifficulty, DIFFICULTY_INTERVAL);

    // Start animation loop
    moveComets();
};

const restartGame = () => {
    endGame();
    setTimeout(startGame, 100);
};

const cleanup = () => {
    if (spawnTimer) clearInterval(spawnTimer);
    if (gameTimer) clearInterval(gameTimer);
    if (difficultyTimer) clearInterval(difficultyTimer);
    if (animationFrame) cancelAnimationFrame(animationFrame);
};

onMounted(() => {
    startGame();
});

onUnmounted(() => {
    cleanup();
});
</script>

<style scoped>
.game-container {
    position: relative;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background: linear-gradient(180deg, #07103E 0%, #0a1128 100%);
    color: #fff;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.game-header {
    position: absolute;
    top: 1.5rem;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    padding: 0 2rem;
    z-index: 10;
}

.stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #147ED8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #FCC600;
}

/* Komeet styling */
.comet-wrapper {
    position: absolute;
    user-select: none;
    pointer-events: none;
    will-change: transform;
    transform-origin: center center;
}

.comet-svg {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 120px;
    height: 120px;
    filter: drop-shadow(0 0 15px #FCC600) drop-shadow(0 0 25px #FB6E00);
    z-index: 1;
    animation: comet-pulse 1.2s ease-in-out infinite;
    pointer-events: none;
    display: block;
}

@keyframes comet-pulse {
    0%, 100% {
        opacity: 0.8;
        transform: translateX(-50%) scale(1);
    }
    50% {
        opacity: 1;
        transform: translateX(-50%) scale(1.1);
    }
}

/* Explosion */
.explosion {
    position: absolute;
    pointer-events: none;
    width: 0;
    height: 0;
}

.explosion-particle {
    position: absolute;
    width: 8px;
    height: 8px;
    background: radial-gradient(circle, #FCC600 0%, #FB6E00 100%);
    border-radius: 50%;
    opacity: 0;
    box-shadow: 0 0 10px #FCC600;
    animation: explode 0.6s ease-out forwards;
    animation-delay: calc(var(--index) * 0.05s);
}

@keyframes explode {
    0% {
        transform: translate(0, 0) scale(1);
        opacity: 1;
    }
    100% {
        transform: translate(var(--x, 0), var(--y, 0)) scale(0);
        opacity: 0;
    }
}

.explosion-particle:nth-child(1) { --x: 0px; --y: -60px; }
.explosion-particle:nth-child(2) { --x: 42px; --y: -42px; }
.explosion-particle:nth-child(3) { --x: 60px; --y: 0px; }
.explosion-particle:nth-child(4) { --x: 42px; --y: 42px; }
.explosion-particle:nth-child(5) { --x: 0px; --y: 60px; }
.explosion-particle:nth-child(6) { --x: -42px; --y: 42px; }
.explosion-particle:nth-child(7) { --x: -60px; --y: 0px; }
.explosion-particle:nth-child(8) { --x: -42px; --y: -42px; }

/* Input */
.input-container {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
}

.game-input {
    width: 20rem;
    max-width: 90vw;
    padding: 0.875rem 1.25rem;
    font-size: 1.125rem;
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid #FCC600;
    border-radius: 0.5rem;
    color: #07103E;
    outline: none;
    transition: all 0.2s;
}

.game-input:focus {
    border-color: #FB6E00;
    box-shadow: 
        0 0 0 3px rgba(252, 198, 0, 0.2),
        0 0 15px rgba(252, 198, 0, 0.3);
}

.game-input::placeholder {
    color: #94a3b8;
}

/* Modal */
.modal-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(7, 16, 62, 0.9);
    z-index: 50;
}

.modal-content {
    background: #07103E;
    border: 2px solid #147ED8;
    border-radius: 0.75rem;
    padding: 2.5rem;
    text-align: center;
    max-width: 28rem;
    width: 90%;
    box-shadow: 
        0 20px 25px -5px rgba(0, 0, 0, 0.3),
        0 0 30px rgba(20, 126, 216, 0.2);
}

.modal-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #FCC600;
}

.modal-text {
    font-size: 1.125rem;
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}

.modal-text strong {
    color: #FCC600;
    font-weight: 700;
}

.btn {
    padding: 0.75rem 2rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #FCC600;
    color: #07103E;
}

.btn-primary:hover {
    background: #FB6E00;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(251, 110, 0, 0.4);
}

.btn-primary:active {
    transform: translateY(0);
}
</style>
