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
                    <span class="stat-value">{{ currentRound }}/{{ totalRounds }}</span>
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

        <!-- Game Over Screen -->
        <div v-if="gameEnded" class="game-over-screen">
            <div class="game-over-content">
                <h1 class="game-over-title">GAME OVER</h1>
                <div class="final-stats">
                    <div class="final-stat">
                        <span class="final-stat-label">Score</span>
                        <span class="final-stat-value">{{ score }}/{{ totalRounds }}</span>
                    </div>
                    <div class="final-stat">
                        <span class="final-stat-label">Percentage</span>
                        <span class="final-stat-value">{{ Math.round((score / totalRounds) * 100) }}%</span>
                    </div>
                </div>
                <div class="game-over-actions">
                    <button class="btn-primary" @click="restartGame">
                        Opnieuw Spelen
                    </button>
                    <a href="/" class="btn-secondary">
                        Terug naar Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const gameStarted = ref(false);
const gameEnded = ref(false);
const currentRound = ref(0);
const totalRounds = ref(10);
const score = ref(0);
const currentImages = ref([]); // Array met 2 afbeeldingen [AI_1, Not_1]
const showingResult = ref(false);
const resultMessage = ref('');
const resultClass = ref('');
const lastSelectedImage = ref(null);
const lastChoiceWasCorrect = ref(false);
const loadingImages = ref(false);

// Helper functie om pad van AI afbeelding te krijgen
const getAIImagePath = (number) => {
    const extension = (number === 1 || number === 2 || number === 5 || number === 7) ? 'jpeg' : 'jpg';
    return `/images/AI_Notai/AI_${number}.${extension}`;
};

// Helper functie om pad van Not AI afbeelding te krijgen
const getNotAIImagePath = (number) => {
    return `/images/AI_Notai/Not_${number}.jpg`;
};

const startGame = () => {
    gameStarted.value = true;
    gameEnded.value = false;
    currentRound.value = 0;
    score.value = 0;
    showingResult.value = false;
    currentImages.value = [];
    nextRound();
};

const nextRound = () => {
    if (currentRound.value >= totalRounds.value) {
        endGame();
        return;
    }

    currentRound.value++;
    loadingImages.value = true;
    showingResult.value = false;
    lastSelectedImage.value = null;
    currentImages.value = [];
    
    // Gebruik het ronde nummer direct als paar nummer (1-10)
    const pairNumber = currentRound.value;
    
    // Maak de twee afbeeldingen
    const aiImage = {
        path: getAIImagePath(pairNumber),
        isAI: true,
        name: `AI_${pairNumber}`,
        pairNumber: pairNumber
    };
    
    const notAIImage = {
        path: getNotAIImagePath(pairNumber),
        isAI: false,
        name: `Not_${pairNumber}`,
        pairNumber: pairNumber
    };
    
    // Zet ze in willekeurige volgorde (links/rechts)
    const imagesToLoad = Math.random() < 0.5 ? [aiImage, notAIImage] : [notAIImage, aiImage];
    
    // Laad beide afbeeldingen voordat we ze tonen
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
    
    // Zoek de geselecteerde afbeelding voor visuele feedback
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
    
    // Wacht 2 seconden voordat we naar de volgende ronde gaan
    setTimeout(() => {
        nextRound();
    }, 2000);
};

const endGame = () => {
    gameEnded.value = true;
    gameStarted.value = false;
};

const restartGame = () => {
    gameStarted.value = false;
    gameEnded.value = false;
    currentRound.value = 0;
    score.value = 0;
    showingResult.value = false;
    currentImages.value = [];
    lastSelectedImage.value = null;
    loadingImages.value = false;
};
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
    border: none;
    border-radius: 30px;
    cursor: pointer;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 4px solid rgba(255, 255, 255, 0.3);
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
    margin-bottom: 30px;
    gap: 40px;
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

.controls {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-bottom: 30px;
}

.btn-choice {
    padding: 25px 80px;
    font-size: 36px;
    font-weight: bold;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
    border: 4px solid rgba(255, 255, 255, 0.3);
}

.btn-choice:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-ai {
    background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%);
    color: #07103E;
}

.btn-not {
    background: linear-gradient(135deg, #147ED8 0%, #07103E 100%);
    color: white;
}

.btn-choice:hover:not(:disabled) {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5);
}

.btn-choice:active:not(:disabled) {
    transform: translateY(-2px);
}

/* Game Over Screen */
.game-over-screen {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #07103E 0%, #147ED8 100%);
}

.game-over-content {
    text-align: center;
    padding: 50px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 30px;
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.2);
    max-width: 600px;
    width: 90%;
}

.game-over-title {
    font-size: 56px;
    font-weight: bold;
    margin-bottom: 40px;
    color: #FCC600;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.final-stats {
    display: flex;
    justify-content: space-around;
    margin-bottom: 50px;
    gap: 30px;
}

.final-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.final-stat-label {
    font-size: 20px;
    opacity: 0.9;
    margin-bottom: 10px;
}

.final-stat-value {
    font-size: 48px;
    font-weight: bold;
    color: #FCC600;
}

.game-over-actions {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.btn-primary, .btn-secondary {
    padding: 18px 50px;
    font-size: 24px;
    font-weight: bold;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
    border: 3px solid rgba(255, 255, 255, 0.3);
    display: inline-block;
}

.btn-primary {
    background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%);
    color: #07103E;
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.btn-primary:hover, .btn-secondary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5);
}

.btn-primary:active, .btn-secondary:active {
    transform: translateY(-1px);
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

    .game-over-title {
        font-size: 36px;
    }

    .final-stat-value {
        font-size: 32px;
    }

    .btn-primary, .btn-secondary {
        font-size: 20px;
        padding: 15px 35px;
    }
}
</style>

