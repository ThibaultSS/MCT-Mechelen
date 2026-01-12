<template>
    <div class="game-container">
        <!-- Start Screen -->
        <div v-if="!gameStarted" class="start-screen">
            <div class="start-content">
                <h1 class="start-title">Code Quest</h1>
                <p class="start-description">Sleep de code regels naar de juiste volgorde of categoriseer ze correct!</p>
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
                    <div class="drop-zones-label">Juiste volgorde:</div>
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

            <!-- Categorizing Round (CSS) -->
            <div v-else-if="isCategorizingRound" class="categorizing-container">
                <div class="code-items-area">
                    <div class="code-items-label">Klik op een code snippet om te selecteren:</div>
                    <div class="code-items-list">
                        <div
                            v-for="(item, index) in shuffledItems"
                            :key="'shuffled-' + index"
                            class="code-item"
                            @click="selectItem(item, index)"
                            :class="{ 'selected': selectedItemIndex === index, 'used': item.used }"
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
                            @click="placeInCategory(category.name)"
                            :class="{ 'clickable': selectedItem !== null }"
                        >
                            <div class="category-header">{{ category.label }}</div>
                            <div class="category-items">
                                <div
                                    v-for="(item, index) in category.items"
                                    :key="'cat-' + category.name + '-' + index"
                                    class="category-item"
                                    :class="{ 'correct': hasChecked && item.correct }"
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
                                <div v-if="category.items.length === 0" class="category-empty">Drop hier</div>
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

// Game state
const shuffledItems = ref([]);
const dropSlots = ref([]);
const categories = ref([]);
const correctOrder = ref([]);
const correctCategories = ref({});

// Code snippets database
const codeDatabase = {
    html: {
        easy: [
            {
                items: [
                    { code: '<div>' },
                    { code: '  <p>Tekst</p>' },
                    { code: '</div>' }
                ]
            },
            {
                items: [
                    { code: '<h1>' },
                    { code: '  Titel' },
                    { code: '</h1>' }
                ]
            },
            {
                items: [
                    { code: '<ul>' },
                    { code: '  <li>Item 1</li>' },
                    { code: '</ul>' }
                ]
            }
        ],
        medium: [
            {
                items: [
                    { code: '<div class="container">' },
                    { code: '  <header>' },
                    { code: '    <h1>Titel</h1>' },
                    { code: '  </header>' },
                    { code: '</div>' }
                ]
            },
            {
                items: [
                    { code: '<form>' },
                    { code: '  <input type="text">' },
                    { code: '  <button>Verzend</button>' },
                    { code: '</form>' }
                ]
            }
        ],
        hard: [
            {
                items: [
                    { code: '<!DOCTYPE html>' },
                    { code: '<html>' },
                    { code: '  <head>' },
                    { code: '    <title>Pagina</title>' },
                    { code: '  </head>' },
                    { code: '  <body>' },
                    { code: '    <h1>Content</h1>' },
                    { code: '  </body>' },
                    { code: '</html>' }
                ]
            }
        ]
    },
    css: {
        easy: [
            {
                items: [
                    { code: 'color: red;', category: 'colors' },
                    { code: 'background: blue;', category: 'colors' },
                    { code: 'margin: 10px;', category: 'layout' },
                    { code: 'padding: 5px;', category: 'layout' }
                ],
                categories: ['colors', 'layout']
            },
            {
                items: [
                    { code: 'font-size: 16px;', category: 'typography' },
                    { code: 'font-weight: bold;', category: 'typography' },
                    { code: 'width: 100%;', category: 'layout' },
                    { code: 'height: 50px;', category: 'layout' }
                ],
                categories: ['typography', 'layout']
            }
        ],
        medium: [
            {
                items: [
                    { code: 'display: flex;', category: 'layout' },
                    { code: 'justify-content: center;', category: 'layout' },
                    { code: 'color: #333;', category: 'colors' },
                    { code: 'font-family: Arial;', category: 'typography' },
                    { code: 'border: 1px solid black;', category: 'layout' }
                ],
                categories: ['layout', 'colors', 'typography']
            },
            {
                items: [
                    { code: 'background-color: white;', category: 'colors' },
                    { code: 'margin-top: 20px;', category: 'layout' },
                    { code: 'text-align: center;', category: 'typography' },
                    { code: 'padding: 15px;', category: 'layout' },
                    { code: 'font-size: 18px;', category: 'typography' }
                ],
                categories: ['colors', 'layout', 'typography']
            }
        ],
        hard: [
            {
                items: [
                    { code: 'display: grid;', category: 'layout' },
                    { code: 'grid-template-columns: 1fr 1fr;', category: 'layout' },
                    { code: 'color: rgba(0,0,0,0.8);', category: 'colors' },
                    { code: 'font-weight: 700;', category: 'typography' },
                    { code: 'border-radius: 10px;', category: 'layout' },
                    { code: 'box-shadow: 0 2px 5px;', category: 'layout' }
                ],
                categories: ['layout', 'colors', 'typography']
            }
        ]
    },
    javascript: {
        easy: [
            {
                items: [
                    { code: 'let x = 5;' },
                    { code: 'x = x + 1;' },
                    { code: 'console.log(x);' }
                ]
            },
            {
                items: [
                    { code: 'const name = "test";' },
                    { code: 'name = name.toUpperCase();' },
                    { code: 'console.log(name);' }
                ]
            },
            {
                items: [
                    { code: 'let arr = [];' },
                    { code: 'arr.push(1);' },
                    { code: 'console.log(arr);' }
                ]
            }
        ],
        medium: [
            {
                items: [
                    { code: 'function calculate() {' },
                    { code: '  let result = 0;' },
                    { code: '  result = result + 5;' },
                    { code: '  return result;' },
                    { code: '}' }
                ]
            },
            {
                items: [
                    { code: 'if (x > 5) {' },
                    { code: '  console.log("Groter");' },
                    { code: '} else {' },
                    { code: '  console.log("Kleiner");' },
                    { code: '}' }
                ]
            }
        ],
        hard: [
            {
                items: [
                    { code: 'let data = [1, 2, 3];' },
                    { code: 'let sum = 0;' },
                    { code: 'for (let i = 0; i < data.length; i++) {' },
                    { code: '  sum += data[i];' },
                    { code: '}' },
                    { code: 'console.log(sum);' }
                ]
            }
        ]
    },
    mixed: {
        final: [
            {
                items: [
                    { code: '<div class="container">', type: 'html' },
                    { code: '  <h1>Titel</h1>', type: 'html' },
                    { code: '</div>', type: 'html' },
                    { code: '.container {', type: 'css' },
                    { code: '  display: flex;', type: 'css' },
                    { code: '}', type: 'css' },
                    { code: 'let title = document.querySelector("h1");', type: 'javascript' },
                    { code: 'title.textContent = "Nieuw";', type: 'javascript' }
                ],
                correctOrder: [0, 1, 2, 3, 4, 5, 6, 7]
            }
        ]
    }
};

const categoryLabels = {
    'colors': 'Kleuren',
    'layout': 'Layout',
    'typography': 'Typografie'
};

const currentRoundType = computed(() => {
    if (currentRound.value <= 3) {
        return '📝 HTML Code Ordenen';
    } else if (currentRound.value <= 6) {
        return '🏷️ CSS Code Categoriseren';
    } else if (currentRound.value <= 9) {
        return '📝 JavaScript Code Ordenen';
    } else {
        return '🎯 Gemengde Challenge (HTML/CSS/JS)';
    }
});

const isOrderingRound = computed(() => {
    return currentRound.value <= 3 || (currentRound.value >= 7 && currentRound.value <= 9) || currentRound.value === 10;
});

const isCategorizingRound = computed(() => {
    return currentRound.value >= 4 && currentRound.value <= 6;
});

const allSlotsFilled = computed(() => {
    return dropSlots.value.every(slot => slot !== null);
});

const allItemsCategorized = computed(() => {
    return shuffledItems.value.every(item => item.used);
});

const getDifficulty = () => {
    if (currentRound.value <= 2) return 'easy';
    if (currentRound.value <= 5) return 'medium';
    return 'hard';
};

const getRoundCategory = () => {
    if (currentRound.value <= 3) return 'html';
    if (currentRound.value <= 6) return 'css';
    if (currentRound.value <= 9) return 'javascript';
    return 'mixed';
};

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
    roundScore.value = 0;
    hasChecked.value = false;

    setTimeout(() => {
        if (isOrderingRound.value) {
            setupOrderingRound();
        } else if (isCategorizingRound.value) {
            setupCategorizingRound();
        }
        loadingRound.value = false;
    }, 300);
};

const setupOrderingRound = () => {
    const category = getRoundCategory();
    const difficulty = getDifficulty();
    
    if (category === 'mixed') {
        // Speciale finale ronde
        const roundData = codeDatabase.mixed.final[0];
        correctOrder.value = roundData.correctOrder.map(idx => roundData.items[idx].code);
        // Maak items met correcte originalIndex gebaseerd op correctOrder
        shuffledItems.value = shuffleArray(roundData.items.map((item, index) => {
            const correctIndex = roundData.correctOrder.indexOf(index);
            return {
                ...item,
                originalIndex: correctIndex
            };
        }));
        dropSlots.value = new Array(roundData.items.length).fill(null);
    } else {
        const rounds = codeDatabase[category][difficulty];
        const roundData = rounds[Math.floor(Math.random() * rounds.length)];
        
        correctOrder.value = roundData.items.map(item => item.code);
        shuffledItems.value = shuffleArray(roundData.items.map((item, index) => ({
            ...item,
            originalIndex: index
        })));
        
        dropSlots.value = new Array(roundData.items.length).fill(null);
    }
};

const setupCategorizingRound = () => {
    const difficulty = getDifficulty();
    const rounds = codeDatabase.css[difficulty];
    const roundData = rounds[Math.floor(Math.random() * rounds.length)];
    
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
        category.items.push({
            code: item.code,
            category: item.category,
            correct: item.category === categoryName
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

const checkOrdering = () => {
    hasChecked.value = true;
    const correct = dropSlots.value.filter(slot => slot && slot.correct).length;
    const total = dropSlots.value.length;
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
.categorizing-container {
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
    cursor: pointer;
    transition: all 0.2s;
    backdrop-filter: blur(10px);
    position: relative;
    box-sizing: border-box;
}

.code-item:hover:not(.used):not(.selected) {
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    cursor: pointer;
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

