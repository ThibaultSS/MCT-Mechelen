<template>
    <div class="highscore-container">
        <h1 class="highscore-title">Highscores</h1>
        <div v-if="loading" class="loading">Loading...</div>
        <div v-else>
            <ul class="highscore-list">
                <li v-for="(score, index) in scores" :key="index" class="highscore-item">
                    <span class="player-name">{{ score.username }}</span>: <span class="player-score">{{ score.total_score }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const scores = ref([]);
const loading = ref(true);

const fetchScores = async () => {
    try {
        const response = await fetch('https://mct-mechelen.test/bubblesort');
        if (!response.ok) throw new Error('Network response was not ok');
        scores.value = await response.json();
    } catch (error) {
        console.error('Error fetching scores:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchScores();
});
</script>

<style scoped>
/* Page background */
body {
  margin: 0;
  min-height: 100vh;
  background: radial-gradient(
    circle at center,
    #1e6fc1 0%,
    #0f4fa0 45%,
    #081a4d 100%
  );
  font-family: 'Arial', sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Container */
.highscore-container {
  width: 500px;
  max-width: 90%;
  background: linear-gradient(
    to bottom,
    #ffb800 0%,
    #ff9800 100%
  );
  border-radius: 26px;
  padding: 32px 28px;

  box-shadow:
    0 10px 0 #e07c00,
    0 18px 30px rgba(0, 0, 0, 0.35);
}

/* Title */
.highscore-title {
  text-align: center;
  font-size: 36px;
  font-weight: 800;
  color: #0b1b3d;
  margin-bottom: 28px;
  text-transform: uppercase;
}

/* Loading */
.loading {
  text-align: center;
  font-size: 20px;
  font-weight: 600;
  color: #0b1b3d;
}

/* List */
.highscore-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

/* List item */
.highscore-item {
  display: flex;
  justify-content: space-between;
  align-items: center;

  background: rgba(255, 255, 255, 0.25);
  border-radius: 16px;
  padding: 14px 18px;
  margin-bottom: 14px;

  font-size: 18px;
  font-weight: 600;
  color: #0b1b3d;

  box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.35);
}

/* Player name */
.player-name {
  max-width: 65%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Score */
.player-score {
  font-size: 22px;
  font-weight: 800;
}

/* First place highlight */
.highscore-item:first-child {
  background: rgba(255, 255, 255, 0.45);
  box-shadow:
    0 0 15px rgba(255, 255, 255, 0.6),
    inset 0 2px 6px rgba(255, 255, 255, 0.5);
}

</style>