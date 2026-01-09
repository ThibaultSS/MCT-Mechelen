<template>
    <div class="game-container">
        <!-- Start Screen -->
        <div v-if="!gameStarted" class="start-screen">
            <div class="start-content">
                <h1 class="start-title">HexaGuess</h1>
                <p class="start-description">Raad welke primaire kleur (rood, groen of blauw) het meest aanwezig is in de hexadecimale kleur!</p>
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
                <div class="game-title">
                    <span class="game-title-text">HexaGuess</span>
                </div>
                <div class="stat">
                    <span class="stat-label">Score</span>
                    <span class="stat-value">{{ score }}</span>
                </div>
            </div>
            
            <!-- Tip Container (rechtsonder) -->
            <div class="tip-container">
                <button class="tip-icon-btn" @click="toggleTip">
                    <span class="tip-icon">💡</span>
                </button>
                <div v-if="tipOpen" class="tip-banner" :class="{ 'tip-open': tipOpen }">
                    <p class="tip-text">💡 Tip: #RRGGBB betekent Rood-Groen-Blauw. De hoogste waarde (FF = 255) is de dominante kleur!</p>
                </div>
            </div>

            <!-- Loading Screen -->
            <div v-if="loadingColor" class="loading-screen">
                <div class="loading-spinner"></div>
                <p class="loading-text">Kleur laden...</p>
            </div>

            <!-- Color Display -->
            <div v-else class="color-display-wrapper">
                <div class="hex-code-display">
                    <div class="hex-code">{{ currentColor.hex }}</div>
                </div>
                
                <div class="color-choice-buttons">
                    <button 
                        class="color-btn btn-red" 
                        @click="makeChoice('red')"
                        :disabled="showingResult"
                    >
                        ROOD
                    </button>
                    <button 
                        class="color-btn btn-green" 
                        @click="makeChoice('green')"
                        :disabled="showingResult"
                    >
                        GROEN
                    </button>
                    <button 
                        class="color-btn btn-blue" 
                        @click="makeChoice('blue')"
                        :disabled="showingResult"
                    >
                        BLAUW
                    </button>
                </div>

                <!-- Result Overlay -->
                <div v-if="showingResult" class="result-overlay" :class="resultClass">
                    <div class="result-content">
                        <div class="result-text">{{ resultMessage }}</div>
                        <div v-if="!lastChoiceWasCorrect" class="correct-answer-info">
                            <p class="correct-answer-text">Het was: <strong>{{ getColorName(currentColor.dominantColor) }}</strong></p>
                            <div class="color-preview" :style="{ backgroundColor: currentColor.hex }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Over Screen -->
        <div v-if="gameEnded" class="game-over-screen">
            <div class="game-over-content">
                <h1 class="game-over-title">Challenge voltooid!</h1>
                <p class="result-text">
                    Je hebt <strong>{{ score }}</strong> van <strong>{{ totalRounds }}</strong> juist!
                </p>
                <div class="game-over-actions">
                    <a href="/" class="btn-primary">
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
const currentColor = ref({ hex: '', dominantColor: '' });
const showingResult = ref(false);
const resultMessage = ref('');
const resultClass = ref('');
const loadingColor = ref(false);
const lastChoiceWasCorrect = ref(false);
const tipOpen = ref(false);

// Genereer een willekeurige hex kleur waar één primaire kleur dominant is
const generateColor = () => {
    const colors = ['red', 'green', 'blue'];
    const dominantColor = colors[Math.floor(Math.random() * colors.length)];
    
    let r, g, b;
    
    // Zorg dat de dominante kleur hoog is (200-255) en de andere laag (0-100)
    switch (dominantColor) {
        case 'red':
            r = Math.floor(Math.random() * 56) + 200; // 200-255
            g = Math.floor(Math.random() * 101); // 0-100
            b = Math.floor(Math.random() * 101); // 0-100
            break;
        case 'green':
            r = Math.floor(Math.random() * 101); // 0-100
            g = Math.floor(Math.random() * 56) + 200; // 200-255
            b = Math.floor(Math.random() * 101); // 0-100
            break;
        case 'blue':
            r = Math.floor(Math.random() * 101); // 0-100
            g = Math.floor(Math.random() * 101); // 0-100
            b = Math.floor(Math.random() * 56) + 200; // 200-255
            break;
    }
    
    // Converteer naar hex
    const hex = '#' + 
        r.toString(16).padStart(2, '0').toUpperCase() + 
        g.toString(16).padStart(2, '0').toUpperCase() + 
        b.toString(16).padStart(2, '0').toUpperCase();
    
    return {
        hex,
        dominantColor
    };
};

const startGame = () => {
    gameStarted.value = true;
    gameEnded.value = false;
    currentRound.value = 0;
    score.value = 0;
    showingResult.value = false;
    nextRound();
};

const nextRound = () => {
    if (currentRound.value >= totalRounds.value) {
        endGame();
        return;
    }

    currentRound.value++;
    loadingColor.value = true;
    showingResult.value = false;
    
    // Simuleer een korte laadtijd voor consistentie
    setTimeout(() => {
        currentColor.value = generateColor();
        loadingColor.value = false;
    }, 500);
};

const getColorName = (color) => {
    const names = {
        'red': 'ROOD',
        'green': 'GROEN',
        'blue': 'BLAUW'
    };
    return names[color] || color.toUpperCase();
};

const makeChoice = (userChoice) => {
    if (!currentColor.value.hex || showingResult.value) return;
    
    const isCorrect = currentColor.value.dominantColor === userChoice;
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
    
    // Wacht 2.5 seconden voordat we naar de volgende ronde gaan (langer voor fout antwoord met extra info)
    setTimeout(() => {
        nextRound();
    }, 2500);
};

const endGame = () => {
    gameEnded.value = true;
    gameStarted.value = false;
};

const toggleTip = () => {
    tipOpen.value = !tipOpen.value;
};

const restartGame = () => {
    gameStarted.value = false;
    gameEnded.value = false;
    currentRound.value = 0;
    score.value = 0;
    showingResult.value = false;
    currentColor.value = { hex: '', dominantColor: '' };
    loadingColor.value = false;
    tipOpen.value = false;
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
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
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

/* Game Screen */
.game-screen {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    padding: 20px;
    box-sizing: border-box;
}

.tip-container {
    position: fixed;
    bottom: 30px;
    right: 30px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    z-index: 1000;
}

.tip-icon-btn {
    background: rgba(252, 198, 0, 0.3);
    border: 2px solid rgba(252, 198, 0, 0.6);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.tip-icon-btn:hover {
    background: rgba(252, 198, 0, 0.5);
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
}

.tip-icon {
    font-size: 28px;
    line-height: 1;
}

.tip-banner {
    position: absolute;
    bottom: 70px;
    right: 0;
    background: rgba(252, 198, 0, 0.2);
    border: 2px solid rgba(252, 198, 0, 0.5);
    border-radius: 15px;
    padding: 15px 30px;
    text-align: center;
    backdrop-filter: blur(10px);
    min-width: 350px;
    max-width: 400px;
    animation: slideUp 0.3s ease-out;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
}

.tip-banner.tip-open {
    display: block;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tip-text {
    font-size: 18px;
    color: #FCC600;
    margin: 0;
    font-weight: 500;
    line-height: 1.5;
}

.game-header {
    display: flex;
    justify-content: space-around;
    align-items: center;
    margin-bottom: 30px;
    gap: 20px;
}

.game-title {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
}

.game-title-text {
    font-size: 48px;
    font-weight: bold;
    color: #FCC600;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
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

/* Color Display */
.color-display-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 40px;
    position: relative;
    margin: 20px 0;
}

.hex-code-display {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.hex-code {
    font-size: 72px;
    font-weight: bold;
    color: #FCC600;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.8);
    background: rgba(0, 0, 0, 0.6);
    padding: 40px 60px;
    border-radius: 20px;
    backdrop-filter: blur(10px);
    font-family: 'Courier New', monospace;
    border: 4px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    letter-spacing: 4px;
}

.color-choice-buttons {
    display: flex;
    gap: 30px;
    justify-content: center;
}

.color-btn {
    padding: 25px 60px;
    font-size: 28px;
    font-weight: bold;
    border: none;
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
    border: 4px solid rgba(255, 255, 255, 0.3);
    text-transform: uppercase;
    min-width: 180px;
}

.color-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-red {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
}

.btn-green {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    color: white;
}

.btn-blue {
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    color: white;
}

.color-btn:hover:not(:disabled) {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5);
}

.color-btn:active:not(:disabled) {
    transform: translateY(-2px);
}

/* Result Overlay */
.result-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(10px);
    padding: 40px 60px;
    border-radius: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    z-index: 10;
}

.result-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.result-text {
    font-size: 72px;
    font-weight: bold;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.8);
    animation: popIn 0.3s ease-out;
    margin: 0;
}

.result-overlay.correct .result-text {
    color: #4ade80;
}

.result-overlay.incorrect .result-text {
    color: #f87171;
}

.correct-answer-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    margin-top: 10px;
}

.correct-answer-text {
    font-size: 24px;
    color: #cbd5e1;
    margin: 0;
}

.correct-answer-text strong {
    color: #FCC600;
    font-weight: 700;
}

.color-preview {
    width: 80px;
    height: 80px;
    border-radius: 15px;
    border: 4px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    animation: popIn 0.3s ease-out 0.2s both;
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
    margin-bottom: 30px;
    color: #FCC600;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.result-text {
    font-size: 32px;
    color: #cbd5e1;
    margin-bottom: 40px;
    line-height: 1.6;
}

.result-text strong {
    color: #FCC600;
    font-weight: 700;
}

.game-over-actions {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.btn-primary {
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
    background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%);
    color: #07103E;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5);
}

.btn-primary:active {
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

    .game-title-text {
        font-size: 32px;
    }

    .tip-container {
        bottom: 20px;
        right: 20px;
    }

    .tip-banner {
        bottom: 60px;
        min-width: 280px;
        max-width: 90%;
        padding: 12px 20px;
    }

    .hex-code {
        font-size: 42px;
        padding: 25px 40px;
        letter-spacing: 2px;
    }

    .color-choice-buttons {
        flex-direction: column;
        gap: 20px;
        width: 100%;
        padding: 0 20px;
    }

    .color-btn {
        width: 100%;
        padding: 20px;
        font-size: 24px;
    }

    .result-text {
        font-size: 48px;
        padding: 20px 40px;
    }

    .game-over-title {
        font-size: 36px;
    }

    .result-text {
        font-size: 24px;
    }

    .btn-primary {
        font-size: 20px;
        padding: 15px 35px;
    }

    .tip-icon-btn {
        width: 45px;
        height: 45px;
    }

    .tip-icon {
        font-size: 24px;
    }

    .tip-banner {
        top: 55px;
        min-width: 90%;
        padding: 12px 20px;
    }

    .tip-text {
        font-size: 14px;
    }
}
</style>

