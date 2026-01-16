<template>
    <div class="game-container">
        <!-- Start Screen -->
        <div v-if="!gameStarted" class="start-screen">
            <div class="start-content">
                <h1 class="start-title">AI OR NOT</h1>
                <p class="start-description">Kies welke van de twee afbeeldingen door AI is gemaakt!</p>
                <button class="btn-start" @click="startGame">
                    START
                </button>
            </div>
        </div>

        <!-- Game Screen -->
        <div v-if="gameStarted && !gameEnded" class="game-screen">
            <div class="game-header">
                <div class="stat">
                    <span class="stat-label">Ronde</span>
                    <span class="stat-value">{{ currentRound }}/{{ TOTAL_ROUNDS }}</span>
                </div>
                <div v-if="!showingResult && !loadingImages" class="instruction-text-header">
                    Klik op wat AI is
                </div>
                <div class="stat">
                    <span class="stat-label">Score</span>
                    <span class="stat-value">{{ score }}</span>
                </div>
            </div>

            <!-- Loading Screen -->
            <div v-if="loadingImages" class="loading-screen">
                <div class="loading-spinner"></div>
                <p class="loading-text">Afbeeldingen laden...</p>
            </div>

            <!-- Images Wrapper (only shown when loaded) -->
            <div v-else class="images-wrapper" :class="{ 'showing-result': showingResult }">
                <div 
                    v-for="(image, index) in currentImages" 
                    :key="index"
                    class="image-choice-container"
                    @click="makeChoice(image.isAI)"
                    :class="{ 
                        'disabled': showingResult,
                        'correct-choice': showingResult && image.isAI && lastChoiceWasCorrect,
                        'incorrect-choice': showingResult && !image.isAI && lastSelectedImage === image
                    }"
                >
                    <img 
                        :src="image.path" 
                        :alt="image.name"
                        class="game-image"
                    />
                    <div v-if="showingResult && image.isAI" class="ai-label">AI</div>
                </div>
                <div v-if="showingResult" class="result-overlay" :class="resultClass">
                    <div class="result-text">{{ resultMessage }}</div>
                </div>
            </div>
        </div>

        <!-- Game Over Modal -->
        <div
            v-if="gameEnded"
            class="modal-overlay"
        >
            <div class="modal-content">
                <h2 class="modal-title">Challenge voltooid!</h2>
                <p class="modal-text">
                    Je hebt <strong>{{ score }}</strong> van <strong>{{ TOTAL_ROUNDS }}</strong> juist!
                </p>
                <div class="modal-actions">
                    <form method="POST" action="/score/ai-or-not">
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
import { ref, onUnmounted } from 'vue';

// Constants
const TOTAL_ROUNDS = 10;
const RESULT_DISPLAY_TIME = 2000; // 2 seconden

// State
const gameStarted = ref(false);
const gameEnded = ref(false);
const currentRound = ref(0);
const score = ref(0);
const currentImages = ref([]);
const showingResult = ref(false);
const resultMessage = ref('');
const resultClass = ref('');
const lastSelectedImage = ref(null);
const lastChoiceWasCorrect = ref(false);
const loadingImages = ref(false);
const csrfToken = ref(document.querySelector('meta[name="csrf-token"]')?.content || '');

// Timers
let resultTimer = null;

// Helper functie om pad van AI afbeelding te krijgen
const getAIImagePath = (number) => {
    const extension = (number === 1 || number === 2 || number === 5 || number === 7) ? 'jpeg' : 'jpg';
    return `/images/AI_Notai/AI_${number}.${extension}`;
};

// Helper functie om pad van Not AI afbeelding te krijgen
const getNotAIImagePath = (number) => {
    return `/images/AI_Notai/Not_${number}.jpg`;
};

const cleanupTimers = () => {
    if (resultTimer) {
        clearTimeout(resultTimer);
        resultTimer = null;
    }
};

const startGame = () => {
    cleanupTimers();
    gameStarted.value = true;
    gameEnded.value = false;
    currentRound.value = 0;
    score.value = 0;
    showingResult.value = false;
    currentImages.value = [];
    lastSelectedImage.value = null;
    nextRound();
};

const nextRound = () => {
    cleanupTimers();
    
    if (currentRound.value >= TOTAL_ROUNDS) {
        endGame();
        return;
    }

    currentRound.value++;
    loadingImages.value = true;
    showingResult.value = false;
    lastSelectedImage.value = null;
    currentImages.value = [];
    
    const pairNumber = currentRound.value;
    
    const aiImage = {
        path: getAIImagePath(pairNumber),
        isAI: true,
        name: `AI_${pairNumber}`
    };
    
    const notAIImage = {
        path: getNotAIImagePath(pairNumber),
        isAI: false,
        name: `Not_${pairNumber}`
    };
    
    // Willekeurige volgorde (links/rechts)
    const imagesToLoad = Math.random() < 0.5 ? [aiImage, notAIImage] : [notAIImage, aiImage];
    
    loadImages(imagesToLoad);
};

const loadImages = (images) => {
    let loadedCount = 0;
    const totalImages = images.length;
    
    images.forEach(image => {
        const img = new Image();
        img.onload = () => {
            loadedCount++;
            if (loadedCount === totalImages) {
                // Beide afbeeldingen zijn geladen
                currentImages.value = images;
                loadingImages.value = false;
            }
        };
        img.onerror = () => {
            // Als een afbeelding niet laadt, ga toch verder
            loadedCount++;
            if (loadedCount === totalImages) {
                currentImages.value = images;
                loadingImages.value = false;
            }
        };
        img.src = image.path;
    });
};

const makeChoice = (selectedImageIsAI) => {
    if (!currentImages.value.length || showingResult.value) return;
    
    const isCorrect = selectedImageIsAI === true;
    
    lastSelectedImage.value = currentImages.value.find(img => img.isAI === selectedImageIsAI);
    lastChoiceWasCorrect.value = isCorrect;
    
    if (isCorrect) {
        score.value++;
        resultMessage.value = 'GOED!';
        resultClass.value = 'correct';
    } else {
        resultMessage.value = 'FOUT!';
        resultClass.value = 'incorrect';
    }
    
    showingResult.value = true;
    
    resultTimer = setTimeout(() => {
        nextRound();
    }, RESULT_DISPLAY_TIME);
};

const endGame = () => {
    cleanupTimers();
    gameEnded.value = true;
    gameStarted.value = false;
};

onUnmounted(() => {
    cleanupTimers();
});
</script>

<style scoped>
.game-container {
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background: linear-gradient(135deg, #07103E 0%, #147ED8 100%);
    position: relative;
    font-family: Arial, sans-serif;
    color: white;
}

/* Start Screen */
.start-screen {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #07103E 0%, #147ED8 100%);
}

.start-content {
    text-align: center;
    padding: 40px;
}

.start-title {
    font-size: 64px;
    font-weight: bold;
    margin-bottom: 20px;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    color: #FCC600;
}

.start-description {
    font-size: 24px;
    margin-bottom: 40px;
    opacity: 0.9;
}

.btn-start {
    padding: 20px 60px;
    font-size: 32px;
    font-weight: bold;
    color: #07103E;
    background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%);
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-radius: 30px;
    cursor: pointer;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-start:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
}

.btn-start:active {
    transform: translateY(-1px);
}

/* Loading Screen */
.loading-screen {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
}

.loading-spinner {
    width: 80px;
    height: 80px;
    border: 8px solid rgba(255, 255, 255, 0.2);
    border-top: 8px solid #FCC600;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-text {
    font-size: 24px;
    opacity: 0.9;
    color: #FCC600;
}

/* Game Screen */
.game-screen {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    padding: 20px;
    box-sizing: border-box;
}

.game-header {
    display: flex;
    justify-content: space-around;
    align-items: center;
    margin-bottom: 30px;
    gap: 20px;
}

.stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 15px 30px;
    border-radius: 15px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.stat-label {
    font-size: 18px;
    opacity: 0.9;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #FCC600;
}

.instruction-text-header {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 600;
    color: #FCC600;
    text-align: center;
    text-shadow: 
        0 2px 10px rgba(0, 0, 0, 0.5),
        0 0 20px rgba(252, 198, 0, 0.3);
    padding: 15px 40px;
    background: rgba(7, 16, 62, 0.7);
    border-radius: 15px;
    border: 2px solid rgba(252, 198, 0, 0.3);
    backdrop-filter: blur(5px);
    white-space: nowrap;
    animation: instructionPulse 2s ease-in-out infinite;
    flex: 1;
    max-width: 400px;
}

.images-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 30px;
    position: relative;
    margin: 20px 0;
    min-height: 400px;
    padding: 20px;
}

.image-choice-container {
    flex: 1;
    max-width: 45%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
    transition: all 0.3s;
    border-radius: 20px;
    padding: 20px;
    background: rgba(0, 0, 0, 0.2);
    border: 4px solid rgba(255, 255, 255, 0.2);
}

.image-choice-container:hover:not(.disabled) {
    transform: scale(1.05);
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.image-choice-container.disabled {
    cursor: not-allowed;
}

.image-choice-container.correct-choice {
    border-color: #4ade80;
    background: rgba(74, 222, 128, 0.2);
    box-shadow: 0 0 30px rgba(74, 222, 128, 0.5);
}

.image-choice-container.incorrect-choice {
    border-color: #f87171;
    background: rgba(248, 113, 113, 0.2);
    box-shadow: 0 0 30px rgba(248, 113, 113, 0.5);
}

.game-image {
    width: 100%;
    height: auto;
    max-height: 500px;
    object-fit: contain;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    transition: transform 0.3s, filter 0.3s;
}

.images-wrapper.showing-result .game-image {
    filter: brightness(0.8);
}

.image-choice-container.correct-choice .game-image,
.image-choice-container.incorrect-choice .game-image {
    filter: brightness(1);
}

@keyframes instructionPulse {
    0%, 100% {
        opacity: 0.9;
        transform: scale(1);
    }
    50% {
        opacity: 1;
        transform: scale(1.02);
    }
}

.ai-label {
    position: absolute;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%);
    color: #07103E;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: bold;
    font-size: 24px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    animation: popIn 0.3s ease-out;
}

.result-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    padding: 30px 60px;
    border-radius: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    z-index: 10;
}

.result-text {
    font-size: 72px;
    font-weight: bold;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.8);
    animation: popIn 0.3s ease-out;
}

.result-overlay.correct .result-text {
    color: #4ade80;
}

.result-overlay.incorrect .result-text {
    color: #f87171;
}

@keyframes popIn {
    0% {
        transform: scale(0.5);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
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

.modal-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1.5rem;
}

.btn {
    padding: 0.75rem 2rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
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

/* Responsive */
@media (max-width: 768px) {
    .start-title {
        font-size: 42px;
    }

    .start-description {
        font-size: 18px;
    }

    .btn-start {
        padding: 15px 40px;
        font-size: 24px;
    }

    .stat {
        padding: 10px 20px;
    }

    .stat-label {
        font-size: 14px;
    }

    .stat-value {
        font-size: 24px;
    }

    .instruction-text-header {
        font-size: 16px;
        padding: 10px 15px;
        white-space: normal;
        max-width: 200px;
    }

    .images-wrapper {
        flex-direction: column;
        gap: 20px;
        min-height: auto;
        padding: 10px;
    }

    .image-choice-container {
        max-width: 90%;
        padding: 15px;
    }


    .game-image {
        max-height: 300px;
    }

    .result-text {
        font-size: 48px;
        padding: 20px 40px;
    }

    .ai-label {
        font-size: 18px;
        padding: 8px 15px;
        top: 10px;
        right: 10px;
    }
}
</style>

