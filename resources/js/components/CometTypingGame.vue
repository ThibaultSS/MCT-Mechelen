<template>
    <div class="game-container">
        <!-- Start Screen -->
        <div v-if="!gameStarted" class="start-screen">
            <div class="start-content">
                <h1 class="start-title">Kometen Typen</h1>
                <p class="start-description">Typ de woorden voordat ze de grond raken!</p>
                <button class="btn-start" @click="handleStartGame">
                    START
                </button>
            </div>
        </div>

        <!-- Game UI -->
        <div v-if="gameStarted" class="game-header">
            <div class="stat">
                <span class="stat-label">Score</span>
                <span class="stat-value">{{ score }}</span>
            </div>
            <div class="stat">
                <span class="stat-label">Woorden</span>
                <span class="stat-value">{{ remainingWords }}</span>
            </div>
            <div class="stat">
                <span class="stat-label">Gemist</span>
                <span class="stat-value">{{ missed }}</span>
            </div>
        </div>

        <!-- Comets -->
        <div
            v-if="gameStarted"
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
            <svg 
                class="comet-svg" 
                :style="{ width: (comet.size || 120) + 'px', height: (comet.size || 120) + 'px' }"
                viewBox="0 0 100 100" 
                xmlns="http://www.w3.org/2000/svg"
            >
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
                    :font-size="Math.max(10, 18 - (comet.word.length - 4) * 0.6)"
                    font-weight="bold"
                    fill="#07103E"
                    style="pointer-events: none;"
                >{{ comet.word }}</text>
            </svg>
        </div>

        <!-- Explosion effects -->
        <div
            v-if="gameStarted"
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

        <!-- Ground -->
        <div class="ground-container">
            <div class="ground"></div>
        </div>

        <!-- Input -->
        <div v-if="gameStarted" class="input-container">
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
                    Je hebt <strong>{{ score }}</strong> van <strong>{{ TOTAL_WORDS }}</strong> woorden getypt!
                </p>
                <p class="modal-text" v-if="missed > 0">
                    <strong>{{ missed }}</strong> kometen gemist
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
const TOTAL_WORDS = 100;
const DIFFICULTY_INCREASE_INTERVAL = 10; // Elke 10 woorden
const INITIAL_SPAWN_INTERVAL = 2000; // milliseconds
const MIN_SPAWN_INTERVAL = 500; // milliseconds - veel sneller
const MIN_SPAWN_MARGIN = 150; // Minimale afstand van de rand

// Eenvoudige woorden
const EASY_WORDS = [
    'appel', 'banaan', 'citroen', 'druif', 'eik', 'fiets', 'gitaar', 'huis',
    'ijs', 'jas', 'kast', 'lamp', 'muziek', 'noot', 'olifant', 'piano',
    'quiz', 'raket', 'ster', 'tafel', 'ui', 'vogel', 'water', 'xylofoon',
    'yoga', 'zon', 'auto', 'boek', 'code', 'dans', 'eten', 'feest',
    'goud', 'hond', 'kaas', 'liefde', 'maan', 'nacht', 'oog', 'paard',
    'regen', 'sneeuw', 'tijd', 'uur', 'vriend', 'winter', 'zomer', 'lente'
];

// Moeilijkere woorden
const HARD_WORDS = [
    'computer', 'elektronisch', 'programmeur', 'ontwikkelaar', 'applicatie',
    'technologie', 'innovatie', 'processor', 'besturingssysteem', 'software',
    'hardware', 'netwerk', 'database', 'server', 'framework', 'interface',
    'algoritme', 'programmeren', 'codeer', 'syntaxis', 'variabele', 'functie',
    'parameter', 'iteratie', 'recursie', 'abstractie', 'encapsulatie', 'polymorfisme',
    'gebeurtenis', 'callback', 'asynchroon', 'promise', 'component', 'modulariteit',
    'optimalisatie', 'debuggen', 'testen', 'validatie', 'authenticatie', 'autorisatie'
];

// State
const gameStarted = ref(false);
const comets = ref([]);
const explosions = ref([]);
const inputValue = ref('');
const wordsSeen = ref(0); // Totaal aantal woorden dat verschenen is
const score = ref(0);
const missed = ref(0); // Kometen die de bodem raakten
const gameEnded = ref(false);
const speed = ref(1.2);
const spawnInterval = ref(INITIAL_SPAWN_INTERVAL);
const currentDifficulty = ref(0); // Huidige moeilijkheidsniveau

// Computed
const remainingWords = computed(() => TOTAL_WORDS - wordsSeen.value);

// Functie om woorden te krijgen op basis van moeilijkheid
const getWordForDifficulty = () => {
    // Bij hogere moeilijkheid, gebruik veel meer moeilijke woorden (agressiever)
    const difficultyRatio = currentDifficulty.value / 10;
    // Sneller naar 100% moeilijke woorden (bij difficulty 10 = 100%)
    const hardWordChance = Math.min(difficultyRatio * 0.7, 1.0); // Max 100% moeilijke woorden
    
    const useHardWord = Math.random() < hardWordChance && HARD_WORDS.length > 0;
    const wordPool = useHardWord ? HARD_WORDS : EASY_WORDS;
    
    return wordPool[Math.floor(Math.random() * wordPool.length)];
};

// Timers
let spawnTimer = null;
let difficultyTimer = null;
let animationFrame = null;

// ID counters
let cometIdCounter = 0;
let explosionIdCounter = 0;

// Game logic
const createComet = () => {
    if (gameEnded.value || wordsSeen.value >= TOTAL_WORDS) return;

    const word = getWordForDifficulty();
    // Bereken grootte op basis van woord lengte (minimum 120px, +8px per extra letter)
    const baseSize = 120;
    const sizeIncrement = 8;
    const cometSize = baseSize + (word.length * sizeIncrement);

    const comet = {
        id: cometIdCounter++,
        word,
        left: Math.random() * (window.innerWidth - MIN_SPAWN_MARGIN * 2) + MIN_SPAWN_MARGIN,
        top: -100,
        rotation: Math.random() * 10 - 5, // Start rotatie tussen -5 en 5 graden
        size: cometSize, // Sla grootte op
    };

    comets.value.push(comet);
    wordsSeen.value++;
};

const moveComets = () => {
    if (gameEnded.value) return;

    comets.value.forEach(comet => {
        comet.top += speed.value;
        comet.rotation += 0.15; // Langzamere rotatie tijdens vallen
    });

    // Remove comets that are off screen and count missed ones
    const offScreenComets = comets.value.filter(
        comet => comet.top >= window.innerHeight + 150
    );
    
    if (offScreenComets.length > 0) {
        missed.value += offScreenComets.length;
    }
    
    comets.value = comets.value.filter(
        comet => comet.top < window.innerHeight + 150
    );

    // Clean up old explosions
    explosions.value = explosions.value.filter(explosion => {
        explosion.age = (explosion.age || 0) + 1;
        return explosion.age < 25;
    });

    // Check if game should end (all words spawned and no comets left)
    if (wordsSeen.value >= TOTAL_WORDS && comets.value.length === 0 && !gameEnded.value) {
        endGame();
        return;
    }

    if (!gameEnded.value) {
        animationFrame = requestAnimationFrame(moveComets);
    }
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
    
    const newDifficulty = Math.floor(wordsSeen.value / DIFFICULTY_INCREASE_INTERVAL);
    
    if (newDifficulty > currentDifficulty.value) {
        currentDifficulty.value = newDifficulty;
        speed.value += 0.4;

        if (spawnInterval.value > MIN_SPAWN_INTERVAL) {
            clearInterval(spawnTimer);
            spawnInterval.value -= 150;
            spawnTimer = setInterval(createComet, spawnInterval.value);
        }
    }
};

const endGame = () => {
    gameEnded.value = true;
    comets.value = [];

    if (spawnTimer) clearInterval(spawnTimer);
    if (difficultyTimer) clearInterval(difficultyTimer);
    if (animationFrame) cancelAnimationFrame(animationFrame);
};

const startGame = () => {
    // Reset state
    gameEnded.value = false;
    comets.value = [];
    explosions.value = [];
    inputValue.value = '';
    wordsSeen.value = 0;
    score.value = 0;
    missed.value = 0;
    speed.value = 1.2;
    spawnInterval.value = INITIAL_SPAWN_INTERVAL;
    currentDifficulty.value = 0;
    cometIdCounter = 0;
    explosionIdCounter = 0;

    // Clear any existing timers
    if (spawnTimer) clearInterval(spawnTimer);
    if (difficultyTimer) clearInterval(difficultyTimer);
    if (animationFrame) cancelAnimationFrame(animationFrame);

    // Start timers
    spawnTimer = setInterval(createComet, spawnInterval.value);
    difficultyTimer = setInterval(increaseDifficulty, 500);

    // Start animation loop
    moveComets();
};

const handleStartGame = () => {
    gameStarted.value = true;
    startGame();
};

const restartGame = () => {
    gameStarted.value = false;
    endGame();
};

const cleanup = () => {
    if (spawnTimer) clearInterval(spawnTimer);
    if (difficultyTimer) clearInterval(difficultyTimer);
    if (animationFrame) cancelAnimationFrame(animationFrame);
};

// Game starts when user clicks START button

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

/* Ground */
.ground-container {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60px;
    z-index: 5;
    pointer-events: none;
    overflow: hidden;
}

.ground {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: linear-gradient(to top, #2d4a22 0%, #3a5a2a 50%, #4a6a3a 100%);
    clip-path: polygon(
        0% 60%, 
        5% 55%, 
        10% 58%, 
        15% 52%, 
        20% 56%, 
        25% 54%, 
        30% 57%, 
        35% 51%, 
        40% 55%, 
        45% 53%, 
        50% 56%, 
        55% 54%, 
        60% 57%, 
        65% 52%, 
        70% 55%, 
        75% 53%, 
        80% 56%, 
        85% 54%, 
        90% 57%, 
        95% 52%, 
        100% 60%, 
        100% 100%, 
        0% 100%
    );
    box-shadow: 
        0 -5px 15px rgba(0, 0, 0, 0.3),
        inset 0 5px 10px rgba(0, 0, 0, 0.2);
}

/* Input */
.input-container {
    position: absolute;
    bottom: 100px;
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

/* Start Screen */
.start-screen {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, #07103E 0%, #0a1128 100%);
    z-index: 100;
}

.start-content {
    text-align: center;
    padding: 3rem;
}

.start-title {
    font-size: 3.5rem;
    font-weight: 700;
    color: #FCC600;
    margin-bottom: 1rem;
    text-shadow: 
        0 0 20px rgba(252, 198, 0, 0.5),
        0 0 40px rgba(251, 110, 0, 0.3);
    animation: pulse 2s ease-in-out infinite;
}

.start-description {
    font-size: 1.25rem;
    color: #147ED8;
    margin-bottom: 3rem;
    font-weight: 500;
}

.btn-start {
    padding: 1.25rem 4rem;
    font-size: 1.75rem;
    font-weight: 700;
    color: #07103E;
    background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%);
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-radius: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 
        0 10px 30px rgba(0, 0, 0, 0.3),
        0 0 40px rgba(252, 198, 0, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.btn-start:hover {
    transform: translateY(-5px) scale(1.05);
    box-shadow: 
        0 15px 40px rgba(0, 0, 0, 0.4),
        0 0 60px rgba(252, 198, 0, 0.6);
    background: linear-gradient(135deg, #FCC600 0%, #FB6E00 100%);
}

.btn-start:active {
    transform: translateY(-2px) scale(1.02);
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.9;
        transform: scale(1.02);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .start-title {
        font-size: 2.5rem;
    }
    
    .start-description {
        font-size: 1rem;
    }
    
    .btn-start {
        padding: 1rem 3rem;
        font-size: 1.5rem;
    }

    .ground-container {
        height: 50px;
    }

    .ground {
        height: 50px;
    }

    .input-container {
        bottom: 80px;
    }
}

.btn-primary:active {
    transform: translateY(0);
}
</style>
