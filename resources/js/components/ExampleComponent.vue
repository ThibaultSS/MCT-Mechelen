<template>
    <div class="p-4 bg-white rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-4">Vue Component Example</h2>
        <p class="text-gray-600 mb-4">This is a Vue component working with Laravel and Vite!</p>
        <button 
            @click="increment" 
            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
        >
            Clicked {{ count }} times
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const count = ref(0);

const increment = () => {
    count.value++;
};
</script>

