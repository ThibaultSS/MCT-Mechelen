<template>
    <div class="game-container">
        <!-- Start Screen -->
        <div v-if="!gameStarted" class="start-screen">
            <div class="start-content">
                <h1 class="start-title">Kometen Typen</h1>
                <p class="start-description">Typ de woorden voordat ze de grond raken!</p>
                <button class="btn-start" @click="handleStartGame" :disabled="countdown > 0">
                    START
                </button>
            </div>
        </div>

        <!-- Countdown Overlay -->
        <div v-if="countdown > 0" class="countdown-overlay">
            <div class="countdown-content">
                <div class="countdown-number">{{ countdown }}</div>
                <div class="countdown-text">Klaar?</div>
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
            :class="{ 'fast-comet': comet.isFast }"
            :style="{
                left: `${comet.left}px`,
                top: `${comet.top}px`,
                transform: `rotate(${comet.rotation}deg)`,
            }"
        >
            <!-- Komeet SVG -->
            <svg 
                class="comet-svg" 
                :style="{ width: comet.size + 'px', height: comet.size + 'px' }"
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
                        <stop offset="0%" :style="`stop-color:${comet.isFast ? '#FF0000' : '#FCC600'};stop-opacity:1`" />
                        <stop offset="40%" :style="`stop-color:${comet.isFast ? '#FF4444' : '#FB6E00'};stop-opacity:0.9`" />
                        <stop offset="100%" :style="`stop-color:${comet.isFast ? '#FF6666' : '#FB6E00'};stop-opacity:0.5`" />
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
                @input="checkInputMatch"
                autofocus
            />
        </div>

        <!-- Game Over Modal -->
        <div
            v-if="gameEnded"
            class="modal-overlay"
        >
            <div class="modal-content">
                <h2 class="modal-title">Challenge voltooid!</h2>
                <p class="modal-text">
                    Je hebt <strong>{{ score }}</strong> van <strong>{{ TOTAL_WORDS }}</strong> juist!
                </p>
                <div class="modal-actions">
                    <form method="POST" action="/score/comet-typing">
                        <input type="hidden" name="score" :value="score">
                        <input type="hidden" name="_token" :value="csrfToken">

                        <button type="submit" class="btn btn-primary">
                            Volgende Challenge
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';

// Constants
const TOTAL_WORDS = 50;
const DIFFICULTY_INCREASE_INTERVAL = 8; // Elke 8 woorden (was 10) - moeilijkheid stijgt sneller
const INITIAL_SPAWN_INTERVAL = 1800; // milliseconds (was 2200) - start sneller met spawnen
const MIN_SPAWN_INTERVAL = 600; // milliseconds (was 700) - kan nog sneller worden
const MIN_SPAWN_MARGIN = 150; // Minimale afstand van de rand
const FAST_COMET_CHANCE = 0.25; // 25% kans op een snelle komeet (was 18%)
const FAST_COMET_SPEED_PPS = 150; // Snelle kometen: 150 pixels per seconde (was 135)
const MULTIPLE_COMET_CHANCE = 0.55; // 55% kans op meerdere kometen tegelijk (was 45%)
const TARGET_FPS = 60;
const FRAME_INTERVAL = 1000 / TARGET_FPS; // ~16.67ms voor 60 fps
const MAX_DELTA_TIME = 100; // Max delta time in ms om spikes te voorkomen
const MIN_DELTA_TIME = 1; // Min delta time om te voorkomen dat het te snel gaat
const csrfToken = ref(document.querySelector('meta[name="csrf-token"]')?.content || '');

// Eenvoudige woorden (30)
const EASY_WORDS = [
    'appel', 'MCT', 'steen', 'vis', 'honderd', 'typen', 'tempel', 'krab',
    'woord', 'nagel', 'check', 'boom', 'huis', 'fiets', 'water', 'tafel',
    'stoel', 'brood', 'zon', 'maan', 'hand', 'boek', 'pen', 'kaart',
    'klok', 'school', 'raam', 'vloer', 'licht', 'straat'
];

// Moeilijkere woorden (10)
const HARD_WORDS = [
    'embrio', 'nagelbijter', 'Unity', 'patient', 'kerstkaartjes',
    'serrien', 'verantwoord', 'onomkeerbaar', 'communicatie', 'karakters'
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
const speed = ref(1.8); // Start snelheid (pixels per frame bij originele 30fps, wordt omgezet naar PPS)
const spawnInterval = ref(INITIAL_SPAWN_INTERVAL);
const currentDifficulty = ref(0); // Huidige moeilijkheidsniveau
const lastFrameTime = ref(0); // Laatste frame tijd voor delta time berekening
const countdown = ref(0); // Countdown timer (0 = geen countdown)

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
let animationTimer = null;
let countdownTimer = null;

// ID counters
let cometIdCounter = 0;
let explosionIdCounter = 0;

// Track laatste komeet type om snelle kometen na elkaar te voorkomen
let lastCometWasFast = false;

// Game logic
const createComet = () => {
    if (gameEnded.value || wordsSeen.value >= TOTAL_WORDS) return;

    // Bepaal hoeveel kometen tegelijk spawnen (50% kans op meerdere)
    const spawnMultiple = Math.random() < MULTIPLE_COMET_CHANCE;
    const cometCount = spawnMultiple ? 2 : 1; // 1 of 2 kometen

    for (let i = 0; i < cometCount; i++) {
        if (wordsSeen.value >= TOTAL_WORDS) break;

        // Bepaal of dit een snelle komeet is
        // Geen snelle komeet als de laatste ook snel was, of als dit niet de eerste is in een batch
        let isFast = false;
        if (!lastCometWasFast && i === 0) {
            // Alleen eerste komeet in batch kan snel zijn
            isFast = Math.random() < FAST_COMET_CHANCE;
        }
        
        lastCometWasFast = isFast;

        // Snelle kometen krijgen altijd makkelijke woorden
        const word = isFast 
            ? EASY_WORDS[Math.floor(Math.random() * EASY_WORDS.length)]
            : getWordForDifficulty();
        
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
            isFast: isFast, // Snelle komeet flag
        };

        comets.value.push(comet);
        wordsSeen.value++;
    }
};

const moveComets = () => {
    if (gameEnded.value) return;

    const currentTime = performance.now();
    let deltaTime = lastFrameTime.value > 0 ? currentTime - lastFrameTime.value : FRAME_INTERVAL;
    lastFrameTime.value = currentTime;

    // Clamp delta time om spikes te voorkomen (bijvoorbeeld bij tab switching)
    deltaTime = Math.max(MIN_DELTA_TIME, Math.min(deltaTime, MAX_DELTA_TIME));
    
    // Converteer naar seconden voor tijd-gebaseerde beweging
    const deltaSeconds = deltaTime / 1000;
    
    // Converteer snelheid naar pixels per seconde (basis snelheid was voor 30fps, dus * 30)
    const baseSpeedPPS = speed.value * 30;

    comets.value.forEach(comet => {
        // Snelle kometen hebben een vaste snelheid, normale kometen worden sneller met moeilijkheid
        const cometSpeedPPS = comet.isFast ? FAST_COMET_SPEED_PPS : baseSpeedPPS;
        comet.top += cometSpeedPPS * deltaSeconds; // Tijd-gebaseerde beweging
        comet.rotation += 0.15 * deltaSeconds * 30; // Rotatie ook tijd-gebaseerd (0.15 per frame bij 30fps = 4.5 per seconde)
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

    // Clean up old explosions - tijd-gebaseerd (750ms duur)
    explosions.value = explosions.value.filter(explosion => {
        explosion.age = (explosion.age || 0) + deltaTime;
        return explosion.age < 750; // 750ms duur
    });

    // Check if game should end (all words spawned and no comets left)
    if (wordsSeen.value >= TOTAL_WORDS && comets.value.length === 0 && !gameEnded.value) {
        endGame();
        return;
    }

    animationTimer = setTimeout(moveComets, FRAME_INTERVAL);
};

const createExplosion = (x, y) => {
    explosions.value.push({
        id: explosionIdCounter++,
        x,
        y,
        age: 0,
    });
};

const destroyComet = (cometId) => {
    const index = comets.value.findIndex(c => c.id === cometId);
    if (index === -1) return;
    
    const comet = comets.value[index];
    createExplosion(comet.left, comet.top);
    comets.value.splice(index, 1);
    score.value++;
};

const checkInputMatch = () => {
    if (gameEnded.value || !inputValue.value.trim()) return;

    const input = inputValue.value.trim().toLowerCase();
    const matchingComet = comets.value.find(
        comet => comet.word.toLowerCase() === input
    );

    if (matchingComet) {
        destroyComet(matchingComet.id);
        inputValue.value = '';
    }
};

const increaseDifficulty = () => {
    if (gameEnded.value) return;
    
    const newDifficulty = Math.floor(wordsSeen.value / DIFFICULTY_INCREASE_INTERVAL);
    
    if (newDifficulty > currentDifficulty.value) {
        currentDifficulty.value = newDifficulty;
        speed.value += 0.7; // Snelheidsverhoging per niveau (was 0.5) - stijgt sneller

        if (spawnInterval.value > MIN_SPAWN_INTERVAL) {
            clearInterval(spawnTimer);
            spawnInterval.value -= 150; // Spawn verhoging (was 120) - daalt sneller
            spawnTimer = setInterval(createComet, spawnInterval.value);
        }
    }
};

const cleanupTimers = () => {
    if (spawnTimer) {
        clearInterval(spawnTimer);
        spawnTimer = null;
    }
    if (difficultyTimer) {
        clearInterval(difficultyTimer);
        difficultyTimer = null;
    }
    if (animationTimer) {
        clearTimeout(animationTimer);
        animationTimer = null;
    }
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
};

const endGame = () => {
    gameEnded.value = true;
    comets.value = [];
    cleanupTimers();
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
    speed.value = 2.2; // Start snelheid (was 1.8) - start sneller
    spawnInterval.value = INITIAL_SPAWN_INTERVAL;
    currentDifficulty.value = 0;
    cometIdCounter = 0;
    explosionIdCounter = 0;
    lastCometWasFast = false; // Reset snelle komeet tracking
    lastFrameTime.value = performance.now(); // Reset frame time voor delta time
    countdown.value = 0; // Reset countdown

    // Clear any existing timers (behalve countdown timer die al gestopt is)
    if (spawnTimer) clearInterval(spawnTimer);
    if (difficultyTimer) clearInterval(difficultyTimer);
    if (animationTimer) clearTimeout(animationTimer);

    // Start timers
    spawnTimer = setInterval(createComet, spawnInterval.value);
    difficultyTimer = setInterval(increaseDifficulty, 500);

    // Start animation loop
    moveComets();
};

const handleStartGame = () => {
    if (countdown.value > 0) return; // Voorkom dubbele starts
    
    // Start countdown
    countdown.value = 5;
    gameStarted.value = true;
    
    countdownTimer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(countdownTimer);
            countdownTimer = null;
            countdown.value = 0;
            startGame();
        }
    }, 1000);
};

onUnmounted(() => {
    cleanupTimers();
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

.fast-comet .comet-svg {
    filter: drop-shadow(0 0 20px #FF0000) drop-shadow(0 0 35px #FF4444) drop-shadow(0 0 50px #FF6666);
    animation: comet-pulse-fast 0.8s ease-in-out infinite;
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

@keyframes comet-pulse-fast {
    0%, 100% {
        opacity: 0.9;
        transform: translateX(-50%) scale(1);
    }
    50% {
        opacity: 1;
        transform: translateX(-50%) scale(1.15);
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

.modal-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1.5rem;
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

.btn-start:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Countdown Overlay */
.countdown-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(7, 16, 62, 0.95);
    z-index: 200;
    backdrop-filter: blur(10px);
}

.countdown-content {
    text-align: center;
    animation: countdownPulse 0.5s ease-out;
}

.countdown-number {
    font-size: 12rem;
    font-weight: 700;
    color: #FCC600;
    text-shadow: 
        0 0 30px rgba(252, 198, 0, 0.8),
        0 0 60px rgba(251, 110, 0, 0.6),
        0 0 90px rgba(252, 198, 0, 0.4);
    line-height: 1;
    margin-bottom: 1rem;
    animation: countdownScale 1s ease-out;
}

.countdown-text {
    font-size: 2rem;
    color: #147ED8;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.2em;
}

@keyframes countdownPulse {
    0% {
        opacity: 0;
        transform: scale(0.5);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes countdownScale {
    0% {
        transform: scale(1.5);
        opacity: 0.5;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
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
