<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';  

const alreadyPlayedPreferences = ref([]);
const currentPreference = ref(null);
const showVotes = ref(false);
const canVote = ref(true);
const showNextButtonDelayed = ref(false);

// Refs for animated percentages
const animatedPreference1Percentage = ref(0);
const animatedPreference2Percentage = ref(0);

// Computed properties for percentages
const totalVotes = computed(() => {
    if (!currentPreference.value || !showVotes.value) return 0;
    // Ensure votes are numbers
    const votes1 = Number(currentPreference.value.preference1_votes) || 0;
    const votes2 = Number(currentPreference.value.preference2_votes) || 0;
    return votes1 + votes2;
});

const preference1Percentage = computed(() => {
    if (totalVotes.value === 0) return 0;
    const votes1 = Number(currentPreference.value.preference1_votes) || 0;
    return Math.round((votes1 / totalVotes.value) * 100);
});

const preference2Percentage = computed(() => {
    if (totalVotes.value === 0) return 0;
    const votes2 = Number(currentPreference.value.preference2_votes) || 0;
    return Math.round((votes2 / totalVotes.value) * 100);
});

function getNewPreference() {
    // Don't hide votes immediately
    // showVotes.value = false; 
    canVote.value = true;
    showNextButtonDelayed.value = false; // Hide button immediately
    
    // It's okay to reset animated percentages here, they won't be shown until next vote
    animatedPreference1Percentage.value = 0; 
    animatedPreference2Percentage.value = 0;

    axios.get('/api/get-new-preference', {
        params: {
            preferencesToSkip: alreadyPlayedPreferences.value
        }
    }).then(response => {
        // Hide old stats *just before* assigning new preference
        showVotes.value = false; 
        currentPreference.value = response.data;
        alreadyPlayedPreferences.value.push(currentPreference.value.id);
    });
}

function saveVote(vote) {
    // vote has to be preference1 or preference2
    if (vote !== 'preference1' && vote !== 'preference2') {
        return;
    }

    axios.post('/api/vote', {
        preference_id: currentPreference.value.id,
        vote: vote
    }).then(response => {
        console.log(response.data);
    });
}

function vote(vote) {
    if (!canVote.value) return;

    canVote.value = false;

    // Increment local vote count immediately
    if (currentPreference.value) {
        if (vote === 'preference1') {
            currentPreference.value.preference1_votes++;
        } else if (vote === 'preference2') {
            currentPreference.value.preference2_votes++;
        }
    }

    // Show votes *after* incrementing
    showVotes.value = true;

    // Persist vote in backend (no need to wait for response to show votes)
    saveVote(vote);

    // Consider adding a delay or button before fetching the next preference
    // setTimeout(getNewPreference, 2000); // Example: Fetch next after 2s
}

// Animation function
function animatePercentage(targetRef, finalValue, duration = 800) { // Increased duration slightly
    const startValue = 0; // Always start from 0 for the count-up effect
    const range = finalValue - startValue;
    let startTime = null;

    function step(currentTime) {
        if (!startTime) startTime = currentTime;
        const elapsedTime = currentTime - startTime;
        const progress = Math.min(elapsedTime / duration, 1);

        targetRef.value = Math.round(startValue + range * progress);

        if (progress < 1) {
            requestAnimationFrame(step);
        }
    }
    // Ensure the ref is reset before starting animation
    targetRef.value = 0;
    requestAnimationFrame(step);
}

// Watcher to trigger animation and delayed button
watch(showVotes, (newValue) => {
    if (newValue && currentPreference.value) {
        // Start percentage animation immediately
        animatePercentage(animatedPreference1Percentage, preference1Percentage.value);
        animatePercentage(animatedPreference2Percentage, preference2Percentage.value);
        
        // Schedule the "Siguiente" button to appear after 1 second
        setTimeout(() => {
            // Check if votes are still shown (user might have clicked quickly)
            if (showVotes.value) { 
                showNextButtonDelayed.value = true;
            }
        }, 1000); // 1000ms delay
    } 
});

onMounted(() => {
    getNewPreference();
});
</script>

<template>
    <div class="h-screen w-screen overflow-hidden bg-zinc-200 grid grid-rows-2 p-4 gap-4 relative">
        <div
            :class="[
                'bg-red-500 rounded-2xl flex text-center items-center p-4 gap-4 justify-center text-white text-4xl flex-col transition-colors duration-200',
                canVote ? 'cursor-pointer hover:bg-red-600' : 'cursor-not-allowed'
            ]"
            @click="canVote && vote('preference1')"
        >
            <h1>{{ currentPreference?.preference1 }}</h1>
            <div v-show="showVotes">
                <h1 class="text-6xl font-bold">{{ animatedPreference1Percentage }}%</h1>
                <span class="text-lg">({{ currentPreference?.preference1_votes }} votos)</span>
            </div>
        </div>
        <div
            :class="[
                'bg-sky-600 rounded-2xl flex items-center text-center p-4 gap-4 justify-center text-white text-4xl flex-col transition-colors duration-200',
                canVote ? 'cursor-pointer hover:bg-sky-700' : 'cursor-not-allowed'
            ]"
            @click="canVote && vote('preference2')"
        >
            <h1>{{ currentPreference?.preference2 }}</h1>
            <div v-show="showVotes">
                <h1 class="text-6xl font-bold">{{ animatedPreference2Percentage }}%</h1>
                 <span class="text-lg">({{ currentPreference?.preference2_votes }} votos)</span>
            </div>
        </div>
        
        <!-- "Siguiente" Button wrapped in transition -->
        <transition name="fade">
            <div 
                v-show="showNextButtonDelayed" 
                @click="getNewPreference"
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white text-zinc-800 px-8 py-4 rounded-lg shadow-lg cursor-pointer text-2xl font-semibold hover:bg-zinc-100 z-10"
            >
                Siguiente pregunta
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* Fade transition for the "Siguiente" button */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease-in-out; /* Duration and easing */
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0; /* Start invisible */
}
</style>
