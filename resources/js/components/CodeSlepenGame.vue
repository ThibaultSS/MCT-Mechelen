<template>
    <div class="game-container">
        <!-- Start Screen -->
        <div v-if="!gameStarted" class="start-screen">
            <div class="start-content">
                <h1 class="start-title">Code Quest</h1>
                <p class="start-description">Categoriseer code en vind de fouten!</p>
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
                    <span class="game-title-text">Code Quest</span>
                </div>
                <div class="stat">
                    <span class="stat-label">Score</span>
                    <span class="stat-value">{{ score }}</span>
                </div>
            </div>

            <!-- Round Type Indicator -->
            <div class="round-type-indicator">
                <span class="round-type">{{ currentRoundType }}</span>
            </div>

            <!-- Loading Screen -->
            <div v-if="loadingRound" class="loading-screen">
                <div class="loading-spinner"></div>
                <p class="loading-text">Ronde laden...</p>
            </div>

            <!-- True/False Round -->
            <div v-else-if="isTrueFalseRound" class="categorizing-container">
                <div class="code-items-area">
                    <div class="code-items-label">Sleep de code snippets naar de juiste box:</div>
                    <div v-if="!allItemsCategorized" class="code-items-list">
                        <div
                            v-for="(item, index) in shuffledItems"
                            :key="'shuffled-' + index"
                            class="code-item"
                            :class="{ 'used': item.used }"
                            :draggable="!item.used && !showingResult"
                            @dragstart="onDragStart($event, item, index)"
                            @dragend="onDragEnd"
                        >
                            <code>{{ item.code }}</code>
                        </div>
                    </div>
                    <div v-else class="code-items-list code-items-list-empty">
                        <button 
                            class="btn-check btn-check-center" 
                            @click="checkRound"
                            :disabled="showingResult"
                        >
                            Controleer
                        </button>
                    </div>
                </div>

                <div class="categories-area">
                    <div class="categories-label">Categorieën:</div>
                    <div class="categories-list">
                        <div
                            v-for="category in categories"
                            :key="category.name"
                            class="category-drop-zone"
                            :class="{ 'drag-over': dragOverCategory === category.name }"
                            @dragover.prevent="onDragOver(category.name)"
                            @dragleave="onDragLeave"
                            @drop="onDropCategory(category.name)"
                        >
                            <div class="category-header">{{ category.label }}</div>
                            <div class="category-items">
                                <div
                                    v-for="(item, index) in category.items"
                                    :key="'cat-' + category.name + '-' + index"
                                    class="category-item"
                                    :class="{ 
                                        'correct': hasChecked && item.correct,
                                        'incorrect': hasChecked && !item.correct
                                    }"
                                >
                                    <code>{{ item.code }}</code>
                                    <button 
                                        v-if="!showingResult" 
                                        class="remove-btn"
                                        @click="removeFromCategory(category.name, index)"
                                        title="Verwijderen"
                                    >
                                        ×
                                    </button>
                                </div>
                                <div v-if="category.items.length === 0" class="category-empty">Sleep hier</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categorizing Round (HTML of CSS) -->
            <div v-else-if="isCategorizingRound" class="categorizing-container">
                <div class="code-items-area">
                    <div class="code-items-label">Sleep de code snippets naar de juiste categorie:</div>
                    <div v-if="!allItemsCategorized" class="code-items-list">
                        <div
                            v-for="(item, index) in shuffledItems"
                            :key="'shuffled-' + index"
                            class="code-item"
                            :class="{ 'used': item.used }"
                            :draggable="!item.used && !showingResult"
                            @dragstart="onDragStart($event, item, index)"
                            @dragend="onDragEnd"
                        >
                            <code>{{ item.code }}</code>
                        </div>
                    </div>
                    <div v-else class="code-items-list code-items-list-empty">
                        <button 
                            class="btn-check btn-check-center" 
                            @click="checkRound"
                            :disabled="showingResult"
                        >
                            Controleer
                        </button>
                    </div>
                </div>

                <div class="categories-area">
                    <div class="categories-label">Categorieën:</div>
                    <div class="categories-list">
                        <div
                            v-for="category in categories"
                            :key="category.name"
                            class="category-drop-zone"
                            :class="{ 'drag-over': dragOverCategory === category.name }"
                            @dragover.prevent="onDragOver(category.name)"
                            @dragleave="onDragLeave"
                            @drop="onDropCategory(category.name)"
                        >
                            <div class="category-header">{{ category.label }}</div>
                            <div class="category-items">
                                <div
                                    v-for="(item, index) in category.items"
                                    :key="'cat-' + category.name + '-' + index"
                                    class="category-item"
                                    :class="{ 
                                        'correct': hasChecked && item.correct,
                                        'incorrect': hasChecked && !item.correct
                                    }"
                                >
                                    <code>{{ item.code }}</code>
                                    <button 
                                        v-if="!showingResult" 
                                        class="remove-btn"
                                        @click="removeFromCategory(category.name, index)"
                                        title="Verwijderen"
                                    >
                                        ×
                                    </button>
                                </div>
                                <div v-if="category.items.length === 0" class="category-empty">Sleep hier</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result Overlay -->
            <div v-if="showingResult" class="result-overlay" :class="resultClass">
                <div class="result-content">
                    <div class="result-text">{{ resultMessage }}</div>
                    <div v-if="roundScore > 0" class="round-score">
                        +{{ roundScore }} punten!
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
                    Je hebt <strong>{{ score }}</strong> van <strong>{{ maxScore }}</strong> punten behaald!
                </p>
                <div class="modal-actions">
                    <form method="POST" action="/score/code-quest">
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

const gameStarted = ref(false);
const gameEnded = ref(false);
const currentRound = ref(0);
const totalRounds = ref(5);
const score = ref(0);
const maxScore = ref(100);
const showingResult = ref(false);
const resultMessage = ref('');
const resultClass = ref('');
const roundScore = ref(0);
const loadingRound = ref(false);
const hasChecked = ref(false);
const draggedItem = ref(null);
const draggedItemIndex = ref(null);
const dragOverCategory = ref(null);
const csrfToken = ref(document.querySelector('meta[name="csrf-token"]')?.content || '');

// Game state
const shuffledItems = ref([]);
const categories = ref([]);
const correctCategories = ref({});

// Code snippets database
const codeDatabase = {
    // Ronde 1: HTML Categoriseren
    htmlCategorize: {
        items: [
            { code: '<html>', category: 'structuur-layout' },
            { code: '<head>', category: 'structuur-layout' },
            { code: '<body>', category: 'structuur-layout' },
            { code: '<div class="container">', category: 'structuur-layout' },
            { code: '<section>', category: 'structuur-layout' },
            { code: '<form>', category: 'formulieren' },
            { code: '<input type="text">', category: 'formulieren' },
            { code: '<button>Klik</button>', category: 'formulieren' },
            { code: '<p>Tekst</p>', category: 'content' },
            { code: '<h1>Titel</h1>', category: 'content' }
        ],
        categories: ['structuur-layout', 'content', 'formulieren']
    },
    // Ronde 2: CSS Categoriseren
    cssCategorize: {
        items: [
            { code: 'color: red;', category: 'kleuren' },
            { code: 'background-color: blue;', category: 'kleuren' },
            { code: 'border-color: green;', category: 'kleuren' },
            { code: 'margin: 10px;', category: 'spacing-afmetingen' },
            { code: 'padding: 20px;', category: 'spacing-afmetingen' },
            { code: 'width: 100%;', category: 'spacing-afmetingen' },
            { code: 'height: 50px;', category: 'spacing-afmetingen' },
            { code: 'font-size: 16px;', category: 'typografie' },
            { code: 'font-weight: bold;', category: 'typografie' }
        ],
        categories: ['kleuren', 'spacing-afmetingen', 'typografie']
    },
    // Ronde 3: JavaScript Categoriseren
    jsCategorize: {
        items: [
            { code: 'let x = 5;', category: 'variabelen' },
            { code: 'const name = "test";', category: 'variabelen' },
            { code: 'var count = 0;', category: 'variabelen' },
            { code: 'if (x > 5) {', category: 'loops-condities' },
            { code: 'else {', category: 'loops-condities' },
            { code: 'for (let i = 0; i < 10; i++) {', category: 'loops-condities' },
            { code: 'while (x < 10) {', category: 'loops-condities' },
            { code: 'function test() {', category: 'functies-output' },
            { code: 'return x;', category: 'functies-output' },
            { code: 'console.log(x);', category: 'functies-output' }
        ],
        categories: ['variabelen', 'loops-condities', 'functies-output']
    },
    // Ronde 4: Juist/Fout mengeling HTML/CSS/JavaScript
    mixedTrueFalse: {
        items: [
            { code: '<div>Tekst</div>', correct: true },
            { code: '<p>Tekst<p>', correct: false }, // Fout: geen closing tag
            { code: 'color: red;', correct: true },
            { code: 'color red;', correct: false }, // Fout: geen dubbele punt
            { code: 'let x = 5;', correct: true },
            { code: 'let x = 5', correct: false }, // Fout: geen puntkomma
            { code: '<img src="image.jpg">', correct: true },
            { code: 'background-color: blue;', correct: true },
            { code: 'if (x > 5) {', correct: true },
            { code: 'if x > 5 {', correct: false } // Fout: geen haakjes
        ]
    },
    // Ronde 5: Wat is wat - HTML/CSS/JavaScript identificeren
    identifyLanguage: {
        items: [
            { code: '<div class="container">', category: 'html' },
            { code: '<h1>Titel</h1>', category: 'html' },
            { code: '<button onclick="click()">Klik</button>', category: 'html' },
            { code: '<img src="image.jpg">', category: 'html' },
            { code: 'color: red;', category: 'css' },
            { code: 'background-color: blue;', category: 'css' },
            { code: 'margin: 10px;', category: 'css' },
            { code: 'font-size: 16px;', category: 'css' },
            { code: 'let x = 5;', category: 'javascript' },
            { code: 'function test() {', category: 'javascript' },
            { code: 'console.log("Hello");', category: 'javascript' },
            { code: 'if (x > 5) {', category: 'javascript' }
        ],
        categories: ['html', 'css', 'javascript']
    }
};

const categoryLabels = {
    'formulieren': 'Formulieren',
    'structuur-layout': 'Structuur & Layout',
    'content': 'Inhoud',
    'kleuren': 'Kleuren',
    'spacing-afmetingen': 'Spacing & Afmetingen',
    'typografie': 'Typografie',
    'variabelen': 'Variabelen',
    'loops-condities': 'Loops & Condities',
    'functies-output': 'Functies & Output',
    'html': 'HTML',
    'css': 'CSS',
    'javascript': 'JavaScript',
    'juist': '✓ Juist',
    'fout': '✗ Fout'
};

const currentRoundType = computed(() => {
    if (currentRound.value === 1) {
        return 'Categoriseer de HTML code';
    } else if (currentRound.value === 2) {
        return 'Categoriseer de CSS code';
    } else if (currentRound.value === 3) {
        return 'Categoriseer de JavaScript code';
    } else if (currentRound.value === 4) {
        return 'Welke code is juist of fout?';
    } else {
        return 'Is dit HTML, CSS of JavaScript?';
    }
});

const isCategorizingRound = computed(() => {
    return currentRound.value === 1 || currentRound.value === 2 || currentRound.value === 3 || currentRound.value === 5;
});

const isTrueFalseRound = computed(() => {
    return currentRound.value === 4;
});

const allItemsCategorized = computed(() => {
    return shuffledItems.value.every(item => item.used);
});

const shuffleArray = (array) => {
    const shuffled = [...array];
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
};

const startGame = () => {
    cleanupTimers();
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
    loadingRound.value = true;
    showingResult.value = false;
    draggedItem.value = null;
    draggedItemIndex.value = null;
    dragOverCategory.value = null;
    roundScore.value = 0;
    hasChecked.value = false;

    cleanupTimers();
    loadingTimer = setTimeout(() => {
        if (currentRound.value === 1) {
            setupHtmlCategorizingRound();
        } else if (currentRound.value === 2) {
            setupCssCategorizingRound();
        } else if (currentRound.value === 3) {
            setupJsCategorizingRound();
        } else if (currentRound.value === 4) {
            setupMixedTrueFalseRound();
        } else if (currentRound.value === 5) {
            setupIdentifyLanguageRound();
        }
        loadingRound.value = false;
        draggedItem.value = null;
        draggedItemIndex.value = null;
        dragOverCategory.value = null;
    }, 300);
};

const setupCategorizingRound = (roundData) => {
    correctCategories.value = {};
    roundData.items.forEach(item => {
        const categoryName = item.correct !== undefined 
            ? (item.correct ? 'juist' : 'fout')
            : item.category;
        
        if (!correctCategories.value[categoryName]) {
            correctCategories.value[categoryName] = [];
        }
        correctCategories.value[categoryName].push(item.code);
    });
    
    shuffledItems.value = shuffleArray(roundData.items.map(item => ({
        ...item,
        used: false,
        correctCategory: item.correct !== undefined ? (item.correct ? 'juist' : 'fout') : undefined
    })));
    
    if (roundData.categories) {
        categories.value = roundData.categories.map(cat => ({
            name: cat,
            label: categoryLabels[cat] || cat,
            items: []
        }));
    } else {
        // Voor true/false rondes
        categories.value = [
            {
                name: 'juist',
                label: '✓ Juist',
                items: []
            },
            {
                name: 'fout',
                label: '✗ Fout',
                items: []
            }
        ];
    }
};

const setupHtmlCategorizingRound = () => {
    setupCategorizingRound(codeDatabase.htmlCategorize);
};

const setupCssCategorizingRound = () => {
    setupCategorizingRound(codeDatabase.cssCategorize);
};

const setupJsCategorizingRound = () => {
    setupCategorizingRound(codeDatabase.jsCategorize);
};

const setupMixedTrueFalseRound = () => {
    setupCategorizingRound(codeDatabase.mixedTrueFalse);
};

const setupIdentifyLanguageRound = () => {
    setupCategorizingRound(codeDatabase.identifyLanguage);
};

const onDragStart = (event, item, index) => {
    if (showingResult.value || item.used) {
        event.preventDefault();
        return;
    }
    draggedItem.value = item;
    draggedItemIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/html', item.code);
    event.target.style.opacity = '0.5';
};

const onDragEnd = (event) => {
    event.target.style.opacity = '1';
    dragOverCategory.value = null;
};

const onDragOver = (categoryName) => {
    if (draggedItem.value && !draggedItem.value.used) {
        dragOverCategory.value = categoryName;
    }
};

const onDragLeave = () => {
    dragOverCategory.value = null;
};

const onDropCategory = (categoryName) => {
    dragOverCategory.value = null;
    if (!draggedItem.value || draggedItem.value.used || showingResult.value) return;
    
    // Veiligheid: check of index geldig is
    if (draggedItemIndex.value === null || draggedItemIndex.value < 0 || draggedItemIndex.value >= shuffledItems.value.length) {
        draggedItem.value = null;
        draggedItemIndex.value = null;
        return;
    }
    
    const item = shuffledItems.value[draggedItemIndex.value];
    const category = categories.value.find(cat => cat.name === categoryName);
    
    if (category && item) {
        // Bepaal of het correct is: gebruik correctCategory voor true/false, anders category
        const isCorrect = item.correctCategory 
            ? item.correctCategory === categoryName 
            : (item.category === categoryName);
        
        category.items.push({
            code: item.code,
            category: item.correctCategory || item.category,
            correct: isCorrect
        });
        item.used = true;
    }
    
    shuffledItems.value.splice(draggedItemIndex.value, 1);
    draggedItem.value = null;
    draggedItemIndex.value = null;
};

const removeFromCategory = (categoryName, itemIndex) => {
    const category = categories.value.find(cat => cat.name === categoryName);
    if (!category || !category.items[itemIndex]) return;
    
    const item = category.items[itemIndex];
    
    // Vind het originele item in de database en voeg terug toe
    const originalItem = shuffledItems.value.find(i => i.code === item.code && i.used);
    if (originalItem) {
        originalItem.used = false;
    } else {
        // Als niet gevonden, maak nieuw item aan
        shuffledItems.value.push({
            code: item.code,
            category: item.category,
            used: false
        });
    }
    
    // Verwijder uit categorie
    category.items.splice(itemIndex, 1);
};

// Timers
let resultTimer = null;
let loadingTimer = null;

const cleanupTimers = () => {
    if (resultTimer) {
        clearTimeout(resultTimer);
        resultTimer = null;
    }
    if (loadingTimer) {
        clearTimeout(loadingTimer);
        loadingTimer = null;
    }
};

const checkRound = () => {
    hasChecked.value = true;
    let correct = 0;
    let total = 0;
    
    categories.value.forEach(category => {
        category.items.forEach(item => {
            total++;
            if (item.correct) correct++;
        });
    });
    
    // Bereken score proportioneel: (correct / total) * 20
    // Veiligheid: voorkom division by zero
    if (total === 0) {
        roundScore.value = 0;
    } else {
        roundScore.value = Math.round((correct / total) * 20);
    }
    score.value += roundScore.value;
    
    if (total === 0) {
        resultMessage.value = 'Geen items gecategoriseerd!';
        resultClass.value = 'partial';
    } else if (correct === total) {
        resultMessage.value = 'Perfect!';
        resultClass.value = 'correct';
    } else {
        resultMessage.value = `${correct}/${total} correct!`;
        resultClass.value = 'partial';
    }
    
    showingResult.value = true;
    
    cleanupTimers();
    resultTimer = setTimeout(() => {
        nextRound();
    }, 2000);
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

.game-header {
    display: flex;
    justify-content: space-around;
    align-items: center;
    margin-bottom: 20px;
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

.round-type-indicator {
    text-align: center;
    margin-bottom: 20px;
}

.round-type {
    font-size: 24px;
    font-weight: bold;
    color: #FCC600;
    background: rgba(252, 198, 0, 0.2);
    padding: 10px 30px;
    border-radius: 15px;
    border: 2px solid rgba(252, 198, 0, 0.5);
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

/* Categorizing Container */
.categorizing-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 30px;
    overflow-y: auto;
}

.code-items-area,
.categories-area {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.code-items-label,
.categories-label {
    font-size: 20px;
    font-weight: bold;
    color: #FCC600;
}

.code-items-list {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
    overflow-x: hidden;
    overflow-y: visible;
    padding: 10px 5px;
}

.code-items-list-empty {
    min-height: 100px;
    align-items: center;
    justify-content: center;
}

.code-item {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    padding: 15px 20px;
    cursor: grab;
    transition: all 0.2s;
    backdrop-filter: blur(10px);
    position: relative;
    box-sizing: border-box;
}

.code-item:hover:not(.used) {
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    cursor: grab;
    transform: translateY(-2px);
}

.code-item:active:not(.used) {
    cursor: grabbing;
}

.code-item.used {
    opacity: 0.3;
    cursor: not-allowed;
}

.code-item code {
    font-size: 16px;
    color: #fff;
    font-family: 'Courier New', monospace;
    background: rgba(0, 0, 0, 0.3);
    padding: 2px 6px;
    border-radius: 4px;
}

.remove-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(248, 113, 113, 0.8);
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    color: white;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    transition: all 0.2s;
    opacity: 0;
}

.category-item:hover .remove-btn {
    opacity: 1;
}

.remove-btn:hover {
    background: rgba(248, 113, 113, 1);
    transform: scale(1.1);
}

.categories-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.category-drop-zone {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    padding: 20px;
    min-height: 200px;
    backdrop-filter: blur(10px);
    cursor: pointer;
    transition: all 0.2s;
}

.category-drop-zone.drag-over {
    border-color: #FCC600;
    background: rgba(252, 198, 0, 0.2);
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(252, 198, 0, 0.4);
}

.category-header {
    font-size: 20px;
    font-weight: bold;
    color: #FCC600;
    margin-bottom: 15px;
    text-align: center;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.category-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 100px;
}

.category-item {
    position: relative;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 12px;
}

.category-item.correct {
    border-color: #4ade80;
    background: rgba(74, 222, 128, 0.2);
}

.category-item.incorrect {
    border-color: #f87171;
    background: rgba(248, 113, 113, 0.2);
}

.category-item code {
    font-size: 14px;
    color: #fff;
    font-family: 'Courier New', monospace;
    background: rgba(0, 0, 0, 0.3);
    padding: 2px 6px;
    border-radius: 4px;
}

.category-empty {
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
    text-align: center;
    padding: 20px;
}

.btn-check {
    padding: 20px 60px;
    font-size: 24px;
    font-weight: bold;
    color: #07103E;
    background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%);
    border: none;
    border-radius: 20px;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
    transition: all 0.2s;
    border: 3px solid rgba(255, 255, 255, 0.3);
    align-self: center;
    margin-top: 20px;
}

.btn-check-center {
    margin-top: 0;
    align-self: center;
}

.btn-check:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5);
}

.btn-check:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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
    z-index: 100;
}

.result-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.result-text {
    font-size: 48px;
    font-weight: bold;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.8);
    animation: popIn 0.3s ease-out;
    margin: 0;
}

.result-overlay.correct .result-text {
    color: #4ade80;
}

.result-overlay.partial .result-text {
    color: #FCC600;
}

.round-score {
    font-size: 24px;
    color: #FCC600;
    font-weight: bold;
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

    .game-title-text {
        font-size: 32px;
    }

    .stat {
        padding: 10px 20px;
    }

    .stat-value {
        font-size: 24px;
    }

    .categories-list {
        grid-template-columns: 1fr;
    }

    .code-item code,
    .category-item code {
        font-size: 14px;
    }
}
</style>

