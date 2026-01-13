<template>
    <div class="game-container">
        <!-- Start Screen -->
        <div v-if="!gameStarted" class="start-screen">
            <div class="start-content">
                <h1 class="start-title">Code Quest</h1>
                <p class="start-description">Categoriseer code, zet HTML in volgorde en vind de fouten!</p>
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

            <!-- Ordering Round (HTML, JavaScript, Mixed) -->
            <div v-else-if="isOrderingRound" class="ordering-container">
                <div class="code-items-area">
                    <div class="code-items-label">Klik op een code regel om te selecteren:</div>
                    <div class="code-items-list">
                        <div
                            v-for="(item, index) in shuffledItems"
                            :key="'shuffled-' + index"
                            class="code-item"
                            @click="selectItem(item, index)"
                            :class="{ 'selected': selectedItemIndex === index }"
                        >
                            <code>{{ item.code }}</code>
                        </div>
                    </div>
                </div>

                <div class="drop-zones-area">
                    <div class="drop-zones-list">
                        <div
                            v-for="(slot, index) in dropSlots"
                            :key="'slot-' + index"
                            class="drop-slot"
                            @click="placeInSlot(index)"
                            :class="{
                                'filled': slot !== null,
                                'correct': hasChecked && slot !== null && slot.correct,
                                'incorrect': hasChecked && slot !== null && !slot.correct,
                                'clickable': selectedItem !== null && slot === null
                            }"
                        >
                            <code v-if="slot">{{ slot.code }}</code>
                            <span v-else class="drop-hint">{{ selectedItem ? 'Klik om hier te plaatsen' : 'Klik om hier te plaatsen' }}</span>
                            <button 
                                v-if="slot && !showingResult" 
                                class="remove-btn"
                                @click.stop="removeFromSlot(index)"
                                title="Verwijderen"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                </div>

                <button 
                    v-if="allSlotsFilled" 
                    class="btn-check" 
                    @click="checkOrdering"
                    :disabled="showingResult"
                >
                    Controleer
                </button>
            </div>

            <!-- True/False Round -->
            <div v-else-if="isTrueFalseRound" class="categorizing-container">
                <div class="code-items-area">
                    <div class="code-items-label">Sleep de code snippets naar de juiste box:</div>
                    <div class="code-items-list">
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
                            @drop="onDrop(category.name)"
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

                <button 
                    v-if="allItemsCategorized" 
                    class="btn-check" 
                    @click="checkTrueFalse"
                    :disabled="showingResult"
                >
                    Controleer
                </button>
            </div>

            <!-- Categorizing Round (HTML of CSS) -->
            <div v-else-if="isCategorizingRound" class="categorizing-container">
                <div class="code-items-area">
                    <div class="code-items-label">Sleep de code snippets naar de juiste categorie:</div>
                    <div class="code-items-list">
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

                <button 
                    v-if="allItemsCategorized" 
                    class="btn-check" 
                    @click="checkCategorizing"
                    :disabled="showingResult"
                >
                    Controleer
                </button>
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
                    <a :href="`/score/code-quest/${score}`" class="btn btn-primary">
                        Volgende Challenge
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const gameStarted = ref(false);
const gameEnded = ref(false);
const currentRound = ref(0);
const totalRounds = ref(10);
const score = ref(0);
const maxScore = ref(100);
const showingResult = ref(false);
const resultMessage = ref('');
const resultClass = ref('');
const roundScore = ref(0);
const loadingRound = ref(false);
const selectedItem = ref(null);
const selectedItemIndex = ref(null);
const hasChecked = ref(false);
const draggedItem = ref(null);
const draggedItemIndex = ref(null);
const dragOverCategory = ref(null);

// Game state
const shuffledItems = ref([]);
const dropSlots = ref([]);
const categories = ref([]);
const correctOrder = ref([]);
const correctCategories = ref({});

// Code snippets database
const codeDatabase = {
    // Ronde 1: Categoriseren
    categorize: {
        items: [
            { code: '<html>', category: 'structuur' },
            { code: '<form>', category: 'formulieren' },
            { code: '<p>Tekst</p>', category: 'content' },
            { code: '<div class="container">', category: 'pagina-indeling' },
            { code: '<input type="text">', category: 'formulieren' }
        ],
        categories: ['formulieren', 'structuur', 'content', 'pagina-indeling']
    },
    // Ronde 2: HTML volgorde
    htmlOrder: {
        items: [
            { code: '<header>', order: 0 },
            { code: '<nav>', order: 1 },
            { code: '<main>', order: 2 },
            { code: '<section>', order: 3 },
            { code: '<footer>', order: 4 }
        ]
    },
    // Ronde 3: Juist/Fout
    trueFalse: {
        items: [
            { code: '<div>Tekst</div>', correct: true },
            { code: '<p>Tekst<p>', correct: false }, // Fout: geen closing tag
            { code: '<h1>Titel</h1>', correct: true },
            { code: '<img src="image.jpg">', correct: true },
            { code: '<a href="link">Link</a>', correct: true }
        ]
    },
    // Ronde 4: CSS Categoriseren - Kleuren & Spacing
    cssCategorize1: {
        items: [
            { code: 'color: red;', category: 'kleuren' },
            { code: 'background-color: blue;', category: 'kleuren' },
            { code: 'border-color: green;', category: 'kleuren' },
            { code: 'margin: 10px;', category: 'spacing' },
            { code: 'padding: 20px;', category: 'spacing' },
            { code: 'width: 100%;', category: 'afmetingen' },
            { code: 'height: 50px;', category: 'afmetingen' },
            { code: 'font-size: 16px;', category: 'typografie' }
        ],
        categories: ['kleuren', 'spacing', 'afmetingen', 'typografie']
    },
    // Ronde 5: CSS Ordenen
    cssOrder: {
        items: [
            { code: '.container {', order: 0 },
            { code: '  display: flex;', order: 1 },
            { code: '  justify-content: center;', order: 2 },
            { code: '}', order: 3 }
        ]
    },
    // Ronde 6: CSS Juist/Fout
    cssTrueFalse: {
        items: [
            { code: 'color: red;', correct: true },
            { code: 'color red;', correct: false }, // Fout: geen dubbele punt
            { code: 'background-color: blue;', correct: true },
            { code: 'margin: 10px;', correct: true },
            { code: 'padding 20px;', correct: false } // Fout: geen dubbele punt
        ]
    },
    // Ronde 7: JavaScript Categoriseren
    jsCategorize1: {
        items: [
            { code: 'let x = 5;', category: 'variabelen' },
            { code: 'const name = "test";', category: 'variabelen' },
            { code: 'if (x > 5) {', category: 'condities' },
            { code: 'for (let i = 0; i < 10; i++) {', category: 'loops' },
            { code: 'function test() {', category: 'functies' },
            { code: 'console.log(x);', category: 'output' },
            { code: 'return x;', category: 'functies' },
            { code: 'while (x < 10) {', category: 'loops' }
        ],
        categories: ['variabelen', 'condities', 'loops', 'functies', 'output']
    },
    // Ronde 8: JavaScript Ordenen
    jsOrder: {
        items: [
            { code: 'function calculate() {', order: 0 },
            { code: '  let result = 0;', order: 1 },
            { code: '  result = result + 5;', order: 2 },
            { code: '  return result;', order: 3 },
            { code: '}', order: 4 }
        ]
    },
    // Ronde 9: JavaScript Juist/Fout
    jsTrueFalse: {
        items: [
            { code: 'let x = 5;', correct: true },
            { code: 'let x = 5', correct: false }, // Fout: geen puntkomma
            { code: 'const name = "test";', correct: true },
            { code: 'if (x > 5) {', correct: true },
            { code: 'if x > 5 {', correct: false } // Fout: geen haakjes
        ]
    },
    // Ronde 10: HTML/CSS/JavaScript Identificeren
    identifyLanguage: {
        items: [
            { code: '<div class="container">', category: 'html' },
            { code: 'color: red;', category: 'css' },
            { code: 'let x = 5;', category: 'javascript' },
            { code: '<h1>Titel</h1>', category: 'html' },
            { code: 'background-color: blue;', category: 'css' },
            { code: 'function test() {', category: 'javascript' },
            { code: '<img src="image.jpg">', category: 'html' },
            { code: 'margin: 10px;', category: 'css' },
            { code: 'console.log("Hello");', category: 'javascript' },
            { code: '<button onclick="click()">Klik</button>', category: 'html' }
        ],
        categories: ['html', 'css', 'javascript']
    }
};

const categoryLabels = {
    'formulieren': 'Formulieren',
    'structuur': 'Structuur',
    'content': 'Content',
    'pagina-indeling': 'Pagina-indeling',
    'kleuren': 'Kleuren',
    'spacing': 'Spacing',
    'afmetingen': 'Afmetingen',
    'layout': 'Layout',
    'typografie': 'Typografie',
    'variabelen': 'Variabelen',
    'condities': 'Condities',
    'loops': 'Loops',
    'functies': 'Functies',
    'output': 'Output',
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
        return 'Zet HTML elementen in volgorde';
    } else if (currentRound.value === 3) {
        return 'Welke HTML lijn is fout?';
    } else if (currentRound.value === 4) {
        return 'Categoriseer CSS: Kleuren & Spacing';
    } else if (currentRound.value === 5) {
        return 'Zet CSS code in volgorde';
    } else if (currentRound.value === 6) {
        return 'Welke CSS lijn is fout?';
    } else if (currentRound.value === 7) {
        return 'Categoriseer JavaScript code';
    } else if (currentRound.value === 8) {
        return 'Zet JavaScript code in volgorde';
    } else if (currentRound.value === 9) {
        return 'Welke JavaScript lijn is fout?';
    } else {
        return 'Is dit HTML, CSS of JavaScript?';
    }
});

const isOrderingRound = computed(() => {
    return currentRound.value === 2 || currentRound.value === 5 || currentRound.value === 8;
});

const isCategorizingRound = computed(() => {
    return currentRound.value === 1 || currentRound.value === 4 || currentRound.value === 7 || currentRound.value === 10;
});

const isTrueFalseRound = computed(() => {
    return currentRound.value === 3 || currentRound.value === 6 || currentRound.value === 9;
});

const allSlotsFilled = computed(() => {
    return dropSlots.value.every(slot => slot !== null);
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
    selectedItem.value = null;
    selectedItemIndex.value = null;
    draggedItem.value = null;
    draggedItemIndex.value = null;
    dragOverCategory.value = null;
    roundScore.value = 0;
    hasChecked.value = false;

    setTimeout(() => {
        if (isCategorizingRound.value) {
            if (currentRound.value === 1) {
                setupCategorizingRound();
            } else if (currentRound.value === 4) {
                setupCssCategorizingRound(1);
            } else if (currentRound.value === 7) {
                setupJsCategorizingRound();
            } else if (currentRound.value === 10) {
                setupIdentifyLanguageRound();
            }
        } else if (isOrderingRound.value) {
            if (currentRound.value === 2) {
                setupOrderingRound();
            } else if (currentRound.value === 5) {
                setupCssOrderingRound();
            } else if (currentRound.value === 8) {
                setupJsOrderingRound();
            }
        } else if (isTrueFalseRound.value) {
            if (currentRound.value === 3) {
                setupTrueFalseRound();
            } else if (currentRound.value === 6) {
                setupCssTrueFalseRound();
            } else if (currentRound.value === 9) {
                setupJsTrueFalseRound();
            }
        }
        loadingRound.value = false;
        draggedItem.value = null;
        draggedItemIndex.value = null;
        dragOverCategory.value = null;
    }, 300);
};

const setupCategorizingRound = () => {
    const roundData = codeDatabase.categorize;
    
    correctCategories.value = {};
    roundData.items.forEach(item => {
        if (!correctCategories.value[item.category]) {
            correctCategories.value[item.category] = [];
        }
        correctCategories.value[item.category].push(item.code);
    });
    
    shuffledItems.value = shuffleArray(roundData.items.map(item => ({
        ...item,
        used: false
    })));
    
    categories.value = roundData.categories.map(cat => ({
        name: cat,
        label: categoryLabels[cat] || cat,
        items: []
    }));
};

const setupOrderingRound = () => {
    const roundData = codeDatabase.htmlOrder;
    
    correctOrder.value = roundData.items.sort((a, b) => a.order - b.order).map(item => item.code);
    shuffledItems.value = shuffleArray(roundData.items.map((item, index) => ({
        ...item,
        originalIndex: item.order
    })));
    
    dropSlots.value = new Array(roundData.items.length).fill(null);
};

const setupCssOrderingRound = () => {
    const roundData = codeDatabase.cssOrder;
    
    correctOrder.value = roundData.items.sort((a, b) => a.order - b.order).map(item => item.code);
    shuffledItems.value = shuffleArray(roundData.items.map((item, index) => ({
        ...item,
        originalIndex: item.order
    })));
    
    dropSlots.value = new Array(roundData.items.length).fill(null);
};

const setupTrueFalseRound = () => {
    const roundData = codeDatabase.trueFalse;
    
    correctCategories.value = {};
    roundData.items.forEach(item => {
        const categoryName = item.correct ? 'juist' : 'fout';
        if (!correctCategories.value[categoryName]) {
            correctCategories.value[categoryName] = [];
        }
        correctCategories.value[categoryName].push(item.code);
    });
    
    shuffledItems.value = shuffleArray(roundData.items.map(item => ({
        ...item,
        used: false,
        correctCategory: item.correct ? 'juist' : 'fout'
    })));
    
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
};

const setupCssCategorizingRound = (roundNumber) => {
    const roundData = codeDatabase.cssCategorize1;
    
    correctCategories.value = {};
    roundData.items.forEach(item => {
        if (!correctCategories.value[item.category]) {
            correctCategories.value[item.category] = [];
        }
        correctCategories.value[item.category].push(item.code);
    });
    
    shuffledItems.value = shuffleArray(roundData.items.map(item => ({
        ...item,
        used: false
    })));
    
    categories.value = roundData.categories.map(cat => ({
        name: cat,
        label: categoryLabels[cat] || cat,
        items: []
    }));
};

const setupCssTrueFalseRound = () => {
    const roundData = codeDatabase.cssTrueFalse;
    
    correctCategories.value = {};
    roundData.items.forEach(item => {
        const categoryName = item.correct ? 'juist' : 'fout';
        if (!correctCategories.value[categoryName]) {
            correctCategories.value[categoryName] = [];
        }
        correctCategories.value[categoryName].push(item.code);
    });
    
    shuffledItems.value = shuffleArray(roundData.items.map(item => ({
        ...item,
        used: false,
        correctCategory: item.correct ? 'juist' : 'fout'
    })));
    
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
};

const setupJsCategorizingRound = () => {
    const roundData = codeDatabase.jsCategorize1;
    
    correctCategories.value = {};
    roundData.items.forEach(item => {
        if (!correctCategories.value[item.category]) {
            correctCategories.value[item.category] = [];
        }
        correctCategories.value[item.category].push(item.code);
    });
    
    shuffledItems.value = shuffleArray(roundData.items.map(item => ({
        ...item,
        used: false
    })));
    
    categories.value = roundData.categories.map(cat => ({
        name: cat,
        label: categoryLabels[cat] || cat,
        items: []
    }));
};

const setupJsOrderingRound = () => {
    const roundData = codeDatabase.jsOrder;
    
    correctOrder.value = roundData.items.sort((a, b) => a.order - b.order).map(item => item.code);
    shuffledItems.value = shuffleArray(roundData.items.map((item, index) => ({
        ...item,
        originalIndex: item.order
    })));
    
    dropSlots.value = new Array(roundData.items.length).fill(null);
};

const setupJsTrueFalseRound = () => {
    const roundData = codeDatabase.jsTrueFalse;
    
    correctCategories.value = {};
    roundData.items.forEach(item => {
        const categoryName = item.correct ? 'juist' : 'fout';
        if (!correctCategories.value[categoryName]) {
            correctCategories.value[categoryName] = [];
        }
        correctCategories.value[categoryName].push(item.code);
    });
    
    shuffledItems.value = shuffleArray(roundData.items.map(item => ({
        ...item,
        used: false,
        correctCategory: item.correct ? 'juist' : 'fout'
    })));
    
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
};

const setupIdentifyLanguageRound = () => {
    const roundData = codeDatabase.identifyLanguage;
    
    correctCategories.value = {};
    roundData.items.forEach(item => {
        if (!correctCategories.value[item.category]) {
            correctCategories.value[item.category] = [];
        }
        correctCategories.value[item.category].push(item.code);
    });
    
    shuffledItems.value = shuffleArray(roundData.items.map(item => ({
        ...item,
        used: false
    })));
    
    categories.value = roundData.categories.map(cat => ({
        name: cat,
        label: categoryLabels[cat] || cat,
        items: []
    }));
};

const selectItem = (item, index) => {
    if (showingResult.value || item.used) return;
    selectedItem.value = item;
    selectedItemIndex.value = index;
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

const onDrop = (categoryName) => {
    dragOverCategory.value = null;
    if (!draggedItem.value || draggedItem.value.used || showingResult.value) return;
    
    const item = shuffledItems.value[draggedItemIndex.value];
    const category = categories.value.find(cat => cat.name === categoryName);
    
    if (category) {
        // Voor true/false ronde: gebruik correctCategory
        const isCorrect = item.correctCategory ? item.correctCategory === categoryName : (item.category === categoryName);
        
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

const onDropCategory = (categoryName) => {
    dragOverCategory.value = null;
    if (!draggedItem.value || draggedItem.value.used || showingResult.value) return;
    
    const item = shuffledItems.value[draggedItemIndex.value];
    const category = categories.value.find(cat => cat.name === categoryName);
    
    if (category) {
        // Voor categorizing rondes: gebruik category property
        const isCorrect = item.correctCategory ? item.correctCategory === categoryName : (item.category === categoryName);
        
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

const placeInSlot = (slotIndex) => {
    if (!selectedItem.value || dropSlots.value[slotIndex] !== null || showingResult.value) return;
    
    const item = shuffledItems.value[selectedItemIndex.value];
    dropSlots.value[slotIndex] = {
        code: item.code,
        originalIndex: item.originalIndex,
        correct: item.originalIndex === slotIndex
    };
    
    shuffledItems.value.splice(selectedItemIndex.value, 1);
    selectedItem.value = null;
    selectedItemIndex.value = null;
};

const placeInCategory = (categoryName) => {
    if (!selectedItem.value || selectedItem.value.used || showingResult.value) return;
    
    const item = shuffledItems.value[selectedItemIndex.value];
    const category = categories.value.find(cat => cat.name === categoryName);
    
    if (category) {
        // Voor true/false ronde: gebruik correctCategory
        const isCorrect = item.correctCategory ? item.correctCategory === categoryName : (item.category === categoryName);
        
        category.items.push({
            code: item.code,
            category: item.correctCategory || item.category,
            correct: isCorrect
        });
        item.used = true;
    }
    
    shuffledItems.value.splice(selectedItemIndex.value, 1);
    selectedItem.value = null;
    selectedItemIndex.value = null;
};

const removeFromSlot = (slotIndex) => {
    const slot = dropSlots.value[slotIndex];
    if (!slot) return;
    
    // Voeg het item terug toe aan shuffledItems
    shuffledItems.value.push({
        code: slot.code,
        originalIndex: slot.originalIndex
    });
    
    // Verwijder uit slot
    dropSlots.value[slotIndex] = null;
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


const checkCategorizing = () => {
    hasChecked.value = true;
    let correct = 0;
    let total = 0;
    
    categories.value.forEach(category => {
        category.items.forEach(item => {
            total++;
            if (item.correct) correct++;
        });
    });
    
    // Bereken score proportioneel: (correct / total) * 10
    roundScore.value = Math.round((correct / total) * 10);
    score.value += roundScore.value;
    
    if (correct === total) {
        resultMessage.value = 'Perfect!';
        resultClass.value = 'correct';
    } else {
        resultMessage.value = `${correct}/${total} correct!`;
        resultClass.value = 'partial';
    }
    
    showingResult.value = true;
    
    setTimeout(() => {
        nextRound();
    }, 2000);
};

const checkOrdering = () => {
    hasChecked.value = true;
    const correct = dropSlots.value.filter(slot => slot && slot.correct).length;
    const total = dropSlots.value.length;
    // Bereken score proportioneel: (correct / total) * 10
    roundScore.value = Math.round((correct / total) * 10);
    score.value += roundScore.value;
    
    if (correct === total) {
        resultMessage.value = 'Perfect!';
        resultClass.value = 'correct';
    } else {
        resultMessage.value = `${correct}/${total} correct!`;
        resultClass.value = 'partial';
    }
    
    showingResult.value = true;
    
    setTimeout(() => {
        nextRound();
    }, 2000);
};

const checkTrueFalse = () => {
    hasChecked.value = true;
    let correct = 0;
    let total = 0;
    
    categories.value.forEach(category => {
        category.items.forEach(item => {
            total++;
            if (item.correct) correct++;
        });
    });
    
    // Bereken score proportioneel: (correct / total) * 10
    roundScore.value = Math.round((correct / total) * 10);
    score.value += roundScore.value;
    
    if (correct === total) {
        resultMessage.value = 'Perfect!';
        resultClass.value = 'correct';
    } else {
        resultMessage.value = `${correct}/${total} correct!`;
        resultClass.value = 'partial';
    }
    
    showingResult.value = true;
    
    setTimeout(() => {
        nextRound();
    }, 2000);
};

const endGame = () => {
    gameEnded.value = true;
    gameStarted.value = false;
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

/* Ordering Container */
.ordering-container,
.categorizing-container,
.true-false-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 30px;
    overflow-y: auto;
}

.code-items-area,
.drop-zones-area,
.categories-area {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.code-items-label,
.drop-zones-label,
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

.code-item.selected {
    background: rgba(252, 198, 0, 0.3);
    border-color: #FCC600;
    transform: scale(1.05);
    box-shadow: 0 6px 25px rgba(252, 198, 0, 0.5);
    z-index: 10;
    position: relative;
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

.drop-zones-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.drop-slot {
    background: rgba(255, 255, 255, 0.05);
    border: 3px dashed rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    padding: 20px;
    min-height: 60px;
    display: flex;
    align-items: center;
    transition: all 0.2s;
}

.drop-slot.filled {
    border-style: solid;
    background: rgba(255, 255, 255, 0.1);
}

.drop-slot.correct {
    border-color: #4ade80;
    background: rgba(74, 222, 128, 0.2);
}

.drop-slot.incorrect {
    border-color: #f87171;
    background: rgba(248, 113, 113, 0.2);
}

.drop-slot code {
    font-size: 16px;
    color: #fff;
    font-family: 'Courier New', monospace;
    background: rgba(0, 0, 0, 0.3);
    padding: 2px 6px;
    border-radius: 4px;
}

.drop-hint {
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
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

.drop-slot:hover .remove-btn,
.category-item:hover .remove-btn {
    opacity: 1;
}

.remove-btn:hover {
    background: rgba(248, 113, 113, 1);
    transform: scale(1.1);
}

.drop-slot {
    position: relative;
    cursor: pointer;
}

.drop-slot.clickable {
    border-color: #FCC600;
    background: rgba(252, 198, 0, 0.1);
    border-style: dashed;
}

.drop-slot.clickable:hover {
    background: rgba(252, 198, 0, 0.2);
}

.category-item {
    position: relative;
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

.category-drop-zone.clickable {
    border-color: #FCC600;
    background: rgba(252, 198, 0, 0.1);
}

.category-drop-zone.clickable:hover {
    background: rgba(252, 198, 0, 0.2);
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

/* True/False Round */
.true-false-label {
    font-size: 20px;
    font-weight: bold;
    color: #FCC600;
    text-align: center;
    margin-bottom: 20px;
}

.true-false-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 800px;
    margin: 0 auto;
    width: 100%;
}

.true-false-item {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    padding: 25px;
    backdrop-filter: blur(10px);
    transition: all 0.3s;
}

.true-false-item.answered {
    border-color: rgba(252, 198, 0, 0.5);
}

.true-false-item.correct {
    border-color: #4ade80;
    background: rgba(74, 222, 128, 0.2);
}

.true-false-item.incorrect {
    border-color: #f87171;
    background: rgba(248, 113, 113, 0.2);
}

.true-false-code {
    margin-bottom: 20px;
    text-align: center;
}

.true-false-code code {
    font-size: 18px;
    color: #fff;
    font-family: 'Courier New', monospace;
    background: rgba(0, 0, 0, 0.4);
    padding: 10px 15px;
    border-radius: 8px;
    display: inline-block;
}

.true-false-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-true,
.btn-false {
    padding: 15px 40px;
    font-size: 18px;
    font-weight: bold;
    border: 3px solid;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    min-width: 150px;
}

.btn-true {
    background: rgba(74, 222, 128, 0.2);
    border-color: #4ade80;
    color: #4ade80;
}

.btn-true:hover:not(.disabled):not(.selected) {
    background: rgba(74, 222, 128, 0.3);
    transform: translateY(-2px);
}

.btn-true.selected {
    background: #4ade80;
    color: white;
    box-shadow: 0 4px 15px rgba(74, 222, 128, 0.4);
}

.btn-false {
    background: rgba(248, 113, 113, 0.2);
    border-color: #f87171;
    color: #f87171;
}

.btn-false:hover:not(.disabled):not(.selected) {
    background: rgba(248, 113, 113, 0.3);
    transform: translateY(-2px);
}

.btn-false.selected {
    background: #f87171;
    color: white;
    box-shadow: 0 4px 15px rgba(248, 113, 113, 0.4);
}

.btn-true.disabled,
.btn-false.disabled {
    opacity: 0.6;
    cursor: not-allowed;
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
    .drop-slot code,
    .category-item code {
        font-size: 14px;
    }
}
</style>

