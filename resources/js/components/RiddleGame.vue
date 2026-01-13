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
                            [`color-${index}`]: true,
                            'selected': selectedAnswer === index,
                            'correct': showingResult && answer.correct,
                            'incorrect': showingResult && selectedAnswer === index && !answer.correct,
                            'disabled': showingResult
                        }"
                        @click="selectAnswer(index)"
                        :disabled="showingResult"
                    >
                        <span class="answer-icon" v-html="getIcon(index)"></span>
                        <span class="answer-text">{{ answer.text }}</span>
                    </button>
                </div>

                <!-- Result Pop-up -->
                <div v-if="showingResult" class="result-popup-overlay">
                    <div class="result-popup" :class="resultClass">
                        <h3 class="result-popup-title">{{ resultMessage }}</h3>
                        <p v-if="!selectedAnswerCorrect" class="correct-answer-text">
                            Het juiste antwoord is: <strong>{{ correctAnswerText }}</strong>
                        </p>
                        <div class="result-popup-actions">
                            <button class="btn-next" @click="nextRound" v-if="currentRound < totalRounds">
                                Volgende Ronde
                            </button>
                            <button class="btn-next" @click="endGame" v-else>
                                Resultaten Bekijken
                            </button>
                        </div>
                    </div>
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
                    <form method="POST" action="/score/riddle">
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
import { ref, computed, onMounted } from 'vue';

const gameStarted = ref(false);
const gameEnded = ref(false);
const currentRound = ref(1);
const totalRounds = ref(5);
const score = ref(0);
const selectedAnswer = ref(null);
const showingResult = ref(false);
const resultMessage = ref('');
const resultClass = ref('');
const selectedAnswerCorrect = ref(false);
const correctAnswerText = ref('');
const csrfToken = ref(document.querySelector('meta[name="csrf-token"]')?.content || '');

const riddles = [
    {
        question: 'Ik vergelijk twee namen.\nDe eerste begint met K en eindigt met G.\nDe tweede start met T en eindigt met M.\nWat ertussen zit, maakt het verschil duidelijk:\nde eerste wint altijd van de tweede.',
        answers: [
            { text: 'KDG is beter dan TSM', correct: true },
            { text: 'TSM is beter dan KDG', correct: false },
            { text: 'KDG en TSM zijn gelijk', correct: false },
            { text: 'Geen van beide', correct: false }
        ],
        firstRoundAnswers: [
            { text: 'KDG is beter dan TSM', correct: true },
            { text: 'KDG is beter dan TSM', correct: true },
            { text: 'KDG is beter dan TSM', correct: true },
            { text: 'KDG is beter dan TSM', correct: true }
        ]
    },
    {
        question: 'Ik reageer niet op woorden, maar op druk en balans.\nMijn ritme bepaalt of jij punten scoort.\nZonder zadel en teugels ben ik moeilijk te sturen,\nmaar zonder mij sta jij stil in de ring.\nWat ben ik?',
        answers: [
            { text: 'Een ruiter', correct: false },
            { text: 'Een paard', correct: true },
            { text: 'Een hoefsmid', correct: false },
            { text: 'Een rijbaan', correct: false }
        ],
        firstRoundAnswers: [
            { text: 'Een paard', correct: true },
            { text: 'Een paard', correct: true },
            { text: 'Een paard', correct: true },
            { text: 'Een paard', correct: true }
        ]
    },
    {
        question: 'Ik bereikte de macht zonder politieke ervaring.\nMijn naam werd een merk en een werkwoord.\nIk verdeelde meningen meer dan stemmen.\nWie past het best bij deze beschrijving?',
        answers: [
            { text: 'Joe Biden', correct: false },
            { text: 'Elon Musk', correct: false },
            { text: 'Donald Trump', correct: true },
            { text: 'Ronald Reagan', correct: false }
        ],
        firstRoundAnswers: [
            { text: 'Donald Trump', correct: true },
            { text: 'Donald Trump', correct: true },
            { text: 'Donald Trump', correct: true },
            { text: 'Donald Trump', correct: true }
        ]
    },
    {
        question: 'Ik ben vloeibaar, maar geen natuurproduct.\nMijn prik verdwijnt als ik te lang wacht.\nSuiker maakt mij populair,\nmaar water blijft gezonder dan ik.\nWat ben ik?',
        answers: [
            { text: 'Mineraalwater', correct: false },
            { text: 'Vruchtensap', correct: false },
            { text: 'Frisdrank', correct: true },
            { text: 'Thee', correct: false }
        ],
        firstRoundAnswers: [
            { text: 'Frisdrank', correct: true },
            { text: 'Frisdrank', correct: true },
            { text: 'Frisdrank', correct: true },
            { text: 'Frisdrank', correct: true }
        ]
    },
    {
        question: 'Ik ben bijna onzichtbaar voor het blote oog.\nIk overleef extreme kou, hitte en zelfs vacuüm.\nIk leef wanneer er water is, maar kan bijna alles verdragen.\nWat ben ik?',
        answers: [
            { text: 'Een bacterie', correct: false },
            { text: 'Een waterbeertje', correct: true },
            { text: 'Een amoebe', correct: false },
            { text: 'Een insect', correct: false }
        ],
        firstRoundAnswers: [
            { text: 'Een waterbeertje', correct: true },
            { text: 'Een waterbeertje', correct: true },
            { text: 'Een waterbeertje', correct: true },
            { text: 'Een waterbeertje', correct: true }
        ]
    }
];

const riddle = ref('');
const answers = ref([]);

const startGame = () => {
    gameStarted.value = true;
    resetRound();
};

const getCurrentRiddle = () => {
    // Gebruik elk raadsel voor één ronde: 1, 2, 3, 4, 5
    if (currentRound.value === 1) return riddles[0];
    if (currentRound.value === 2) return riddles[1];
    if (currentRound.value === 3) return riddles[2];
    if (currentRound.value === 4) return riddles[3];
    return riddles[4]; // Ronde 5 = raadsel 5
};

const resetRound = () => {
    selectedAnswer.value = null;
    showingResult.value = false;
    resultMessage.value = '';
    resultClass.value = '';
    selectedAnswerCorrect.value = false;
    correctAnswerText.value = '';
    
    const currentRiddleData = getCurrentRiddle();
    riddle.value = currentRiddleData.question;
    
    // Alleen voor de eerste ronde: alle antwoorden zijn hetzelfde (grappig!)
    if (currentRound.value === 1) {
        answers.value = currentRiddleData.firstRoundAnswers.map(a => ({ ...a }));
    } else {
        // Voor de andere rondes: normale antwoorden
        answers.value = currentRiddleData.answers.map(a => ({ ...a }));
    }
};

const selectAnswer = (index) => {
    if (showingResult.value) return;
    
    selectedAnswer.value = index;
    const answer = answers.value[index];
    
    // Direct resultaat tonen
    showingResult.value = true;
    
    const currentRiddleData = getCurrentRiddle();
    const correctAnswer = currentRiddleData.answers.find(a => a.correct);
    correctAnswerText.value = correctAnswer.text;
    
    if (answer.correct) {
        score.value++;
        resultMessage.value = 'Correct! 🎉';
        resultClass.value = 'correct';
        selectedAnswerCorrect.value = true;
    } else {
        resultMessage.value = 'Helaas, dat is niet correct.';
        resultClass.value = 'incorrect';
        selectedAnswerCorrect.value = false;
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

const getIcon = (index) => {
    const icons = [
        '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="5" width="30" height="30" stroke="currentColor" stroke-width="3" fill="none"/></svg>', // Vierkant
        '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="15" stroke="currentColor" stroke-width="3" fill="none"/></svg>', // Cirkel
        '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 5 L35 35 L5 35 Z" stroke="currentColor" stroke-width="3" fill="none"/></svg>', // Driehoek
        '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 5 L24 15 L35 15 L26 22 L30 32 L20 25 L10 32 L14 22 L5 15 L16 15 Z" stroke="currentColor" stroke-width="2" fill="currentColor"/></svg>' // Ster
    ];
    return icons[index] || icons[0];
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
    padding: 15px 30px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    backdrop-filter: blur(10px);
    margin-bottom: 20px;
    flex-shrink: 0;
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
    gap: 30px;
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    overflow-y: auto;
}

.riddle-question {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 30px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    width: 100%;
    text-align: center;
    max-width: 900px;
}

.question-text {
    font-size: 24px;
    line-height: 1.6;
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
    border: none;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 20px;
    font-weight: bold;
    color: white;
    text-align: left;
    position: relative;
    min-height: 90px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

/* Kahoot kleuren */
.answer-btn.color-0 {
    background: #DB5246; /* Rood */
}

.answer-btn.color-1 {
    background: #1EA1F1; /* Blauw */
}

.answer-btn.color-2 {
    background: #FCC600; /* Geel */
    color: #07103E;
}

.answer-btn.color-3 {
    background: #5AC467; /* Groen */
}

.answer-btn:hover:not(.disabled) {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    filter: brightness(1.1);
}

.answer-btn.selected {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5);
    filter: brightness(1.15);
}

.answer-btn.correct {
    background: #5AC467 !important;
    animation: correctPulse 0.5s;
    box-shadow: 0 0 30px rgba(90, 196, 103, 0.6);
}

.answer-btn.incorrect {
    background: #DB5246 !important;
    animation: incorrectShake 0.5s;
    box-shadow: 0 0 30px rgba(219, 82, 70, 0.6);
}

.answer-btn.disabled {
    cursor: not-allowed;
    opacity: 0.8;
}

.answer-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 12px;
    flex-shrink: 0;
    color: white;
}

.answer-btn.color-2 .answer-icon {
    background: rgba(7, 16, 62, 0.25);
    color: #07103E;
}

.answer-btn.selected .answer-icon {
    background: rgba(255, 255, 255, 0.4);
    transform: scale(1.1);
}

.answer-btn.correct .answer-icon {
    background: rgba(255, 255, 255, 0.4);
    color: white;
}

.answer-btn.incorrect .answer-icon {
    background: rgba(255, 255, 255, 0.4);
    color: white;
}

.answer-text {
    flex: 1;
    font-weight: 600;
    font-size: 22px;
    line-height: 1.4;
}

/* Result Pop-up */
.result-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.8);
    display: flex !important;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(5px);
}

.result-popup {
    background: linear-gradient(135deg, #07103E 0%, #147ED8 100%);
    border-radius: 20px;
    padding: 40px;
    max-width: 500px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    border: 3px solid rgba(255, 255, 255, 0.3);
    animation: popupSlideIn 0.3s ease-out;
}

.result-popup.correct {
    border-color: #00ff00;
    box-shadow: 0 20px 60px rgba(0, 255, 0, 0.3);
}

.result-popup.incorrect {
    border-color: #ff0000;
    box-shadow: 0 20px 60px rgba(255, 0, 0, 0.3);
}

.result-popup-title {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 20px;
    color: white;
}

.result-popup.correct .result-popup-title {
    color: #00ff00;
}

.result-popup.incorrect .result-popup-title {
    color: #ff0000;
}

.correct-answer-text {
    font-size: 18px;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 25px;
    line-height: 1.5;
    padding: 15px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.correct-answer-text strong {
    color: #FCC600;
    font-size: 20px;
}

.result-popup-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
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

@keyframes popupSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
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

