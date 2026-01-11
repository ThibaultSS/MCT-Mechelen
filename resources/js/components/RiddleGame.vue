<template>
    <div class="game-container">
        <!-- Start Screen -->
        <div v-if="!gameStarted" class="start-screen">
            <div class="start-content">
                <h1 class="start-title">Raadsel Challenge</h1>
                <p class="start-description">Los het raadsel op door het juiste antwoord te kiezen!</p>
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
                    <span class="game-title-text">Raadsel Challenge</span>
                </div>
                <div class="stat">
                    <span class="stat-label">Score</span>
                    <span class="stat-value">{{ score }}</span>
                </div>
            </div>

            <div class="riddle-container">
                <div class="riddle-question">
                    <h2 class="question-text">{{ riddle }}</h2>
                </div>

                <div class="answers-grid">
                    <button
                        v-for="(answer, index) in answers"
                        :key="index"
                        class="answer-btn"
                        :class="{
                            'selected': selectedAnswer === index,
                            'correct': showingResult && answer.correct,
                            'incorrect': showingResult && selectedAnswer === index && !answer.correct,
                            'disabled': showingResult
                        }"
                        @click="selectAnswer(index)"
                        :disabled="showingResult"
                    >
                        <span class="answer-letter">{{ String.fromCharCode(65 + index) }}</span>
                        <span class="answer-text">{{ answer.text }}</span>
                    </button>
                </div>

                <div v-if="showingResult" class="result-message" :class="resultClass">
                    <p>{{ resultMessage }}</p>
                    <button class="btn-next" @click="nextRound" v-if="currentRound < totalRounds">
                        Volgende Ronde
                    </button>
                    <button class="btn-next" @click="endGame" v-else>
                        Resultaten Bekijken
                    </button>
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
                    Je hebt <strong>{{ score }}</strong> van <strong>{{ totalRounds }}</strong> juist!
                </p>
                <div class="modal-actions">
                    <a href="/" class="btn btn-primary">
                        Terug naar Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const gameStarted = ref(false);
const gameEnded = ref(false);
const currentRound = ref(1);
const totalRounds = ref(10);
const score = ref(0);
const selectedAnswer = ref(null);
const showingResult = ref(false);
const resultMessage = ref('');
const resultClass = ref('');

const riddle = ref('Ik vergelijk twee namen.\nDe eerste begint met K en eindigt met G.\nDe tweede start met T en eindigt met M.\nWat ertussen zit, maakt het verschil duidelijk:\nde eerste wint altijd van de tweede.');

const answers = ref([
    { text: 'KDG is beter dan TSM', correct: true },
    { text: 'KDG is beter dan TSM', correct: true },
    { text: 'KDG is beter dan TSM', correct: true },
    { text: 'KDG is beter dan TSM', correct: true }
]);

const normalAnswers = [
    { text: 'KDG is beter dan TSM', correct: true },
    { text: 'TSM is beter dan KDG', correct: false },
    { text: 'KDG en TSM zijn gelijk', correct: false },
    { text: 'Geen van beide', correct: false }
];

const startGame = () => {
    gameStarted.value = true;
    resetRound();
};

const resetRound = () => {
    selectedAnswer.value = null;
    showingResult.value = false;
    resultMessage.value = '';
    resultClass.value = '';
    
    // Voor de eerste ronde: alle antwoorden zijn hetzelfde (grappig!)
    if (currentRound.value === 1) {
        answers.value = [
            { text: 'KDG is beter dan TSM', correct: true },
            { text: 'KDG is beter dan TSM', correct: true },
            { text: 'KDG is beter dan TSM', correct: true },
            { text: 'KDG is beter dan TSM', correct: true }
        ];
    } else {
        // Voor de andere rondes: normale antwoorden
        answers.value = [...normalAnswers];
    }
};

const selectAnswer = (index) => {
    if (showingResult.value) return;
    
    selectedAnswer.value = index;
    const answer = answers.value[index];
    
    // Direct resultaat tonen
    showingResult.value = true;
    
    if (answer.correct) {
        score.value++;
        resultMessage.value = 'Correct! 🎉';
        resultClass.value = 'correct';
    } else {
        resultMessage.value = 'Helaas, dat is niet correct. Het juiste antwoord is: KDG is beter dan TSM';
        resultClass.value = 'incorrect';
    }
};

const nextRound = () => {
    if (currentRound.value < totalRounds.value) {
        currentRound.value++;
        resetRound();
    } else {
        endGame();
    }
};

const endGame = () => {
    gameEnded.value = true;
};
</script>

<style scoped>
.game-container {
    width: 100%;
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
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #07103E 0%, #147ED8 100%);
}

.start-content {
    text-align: center;
    padding: 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 30px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    max-width: 600px;
}

.start-title {
    font-size: 48px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #FCC600;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.start-description {
    font-size: 20px;
    margin-bottom: 40px;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
}

.btn-start {
    padding: 20px 60px;
    font-size: 28px;
    font-weight: bold;
    color: #07103E;
    background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%);
    border: none;
    border-radius: 20px;
    cursor: pointer;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-start:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
}

.btn-start:active {
    transform: translateY(-1px);
}

/* Game Screen */
.game-screen {
    width: 100%;
    height: 100vh;
    display: flex;
    flex-direction: column;
    padding: 20px;
    box-sizing: border-box;
}

.game-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    backdrop-filter: blur(10px);
    margin-bottom: 30px;
}

.stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.stat-label {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #FCC600;
}

.game-title {
    flex: 1;
    text-align: center;
}

.game-title-text {
    font-size: 36px;
    font-weight: bold;
    color: #FCC600;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

/* Riddle Container */
.riddle-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 40px;
    padding: 40px;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}

.riddle-question {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 40px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    width: 100%;
    text-align: center;
}

.question-text {
    font-size: 28px;
    line-height: 1.8;
    color: white;
    white-space: pre-line;
    margin: 0;
}

/* Answers Grid */
.answers-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    width: 100%;
    max-width: 1000px;
}

.answer-btn {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 25px 30px;
    background: rgba(255, 255, 255, 0.1);
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.3s;
    backdrop-filter: blur(10px);
    font-size: 20px;
    color: white;
    text-align: left;
    position: relative;
}

.answer-btn:hover:not(.disabled) {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
}

.answer-btn.selected {
    background: rgba(252, 198, 0, 0.3);
    border-color: #FCC600;
    box-shadow: 0 6px 25px rgba(252, 198, 0, 0.5);
}

.answer-btn.correct {
    background: rgba(0, 255, 0, 0.3);
    border-color: #00ff00;
    animation: correctPulse 0.5s;
}

.answer-btn.incorrect {
    background: rgba(255, 0, 0, 0.3);
    border-color: #ff0000;
    animation: incorrectShake 0.5s;
}

.answer-btn.disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

.answer-letter {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    font-weight: bold;
    font-size: 24px;
    flex-shrink: 0;
}

.answer-btn.selected .answer-letter {
    background: #FCC600;
    color: #07103E;
}

.answer-btn.correct .answer-letter {
    background: #00ff00;
    color: #07103E;
}

.answer-btn.incorrect .answer-letter {
    background: #ff0000;
    color: white;
}

.answer-text {
    flex: 1;
    font-weight: 500;
}

/* Result Message */
.result-message {
    text-align: center;
    padding: 30px;
    border-radius: 15px;
    backdrop-filter: blur(10px);
    width: 100%;
    max-width: 600px;
}

.result-message.correct {
    background: rgba(0, 255, 0, 0.2);
    border: 2px solid #00ff00;
}

.result-message.incorrect {
    background: rgba(255, 0, 0, 0.2);
    border: 2px solid #ff0000;
}

.result-message p {
    font-size: 24px;
    margin-bottom: 20px;
    font-weight: bold;
}

.btn-next {
    padding: 15px 40px;
    font-size: 20px;
    font-weight: bold;
    color: #07103E;
    background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%);
    border: none;
    border-radius: 15px;
    cursor: pointer;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-next:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
}

/* Animations */
@keyframes correctPulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes incorrectShake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-10px);
    }
    75% {
        transform: translateX(10px);
    }
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(5px);
}

.modal-content {
    background: linear-gradient(135deg, #07103E 0%, #147ED8 100%);
    border-radius: 20px;
    padding: 50px;
    max-width: 500px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    border: 3px solid rgba(252, 198, 0, 0.5);
}

.modal-title {
    font-size: 36px;
    font-weight: bold;
    color: #FCC600;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.modal-text {
    font-size: 20px;
    color: white;
    margin-bottom: 30px;
    line-height: 1.6;
}

.modal-text strong {
    color: #FCC600;
    font-size: 24px;
}

.modal-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.btn {
    padding: 15px 40px;
    font-size: 20px;
    font-weight: bold;
    border: none;
    border-radius: 15px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%);
    color: #07103E;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .start-title {
        font-size: 36px;
    }

    .start-description {
        font-size: 18px;
    }

    .btn-start {
        padding: 15px 40px;
        font-size: 24px;
    }

    .game-header {
        padding: 15px 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .game-title-text {
        font-size: 24px;
        order: -1;
        width: 100%;
    }

    .stat-value {
        font-size: 24px;
    }

    .question-text {
        font-size: 20px;
    }

    .answers-grid {
        grid-template-columns: 1fr;
    }

    .answer-btn {
        padding: 20px;
        font-size: 18px;
    }
}
</style>

