<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';  

// Recibir categorías del controlador
const props = defineProps({
  categories: Array
});

const alreadyPlayedPreferences = ref([]);
const currentPreference = ref(null);
const showVotes = ref(false);
const canVote = ref(true);
const showNextButtonDelayed = ref(false);

// Refs for animated percentages
const animatedPreference1Percentage = ref(0);
const animatedPreference2Percentage = ref(0);

// Variable para la categoría seleccionada (inicialmente la primera o null)
const selectedCategory = ref(props.categories && props.categories.length > 0 ? props.categories[0] : null);

// Nuevo ref para el mensaje
const noMorePreferencesMessage = ref('');

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
    canVote.value = true;
    showNextButtonDelayed.value = false;
    animatedPreference1Percentage.value = 0;
    animatedPreference2Percentage.value = 0;
    noMorePreferencesMessage.value = ''; // Limpiar mensaje anterior
    currentPreference.value = null; // Ocultar preferencias anteriores mientras carga

    const params = {
        preferencesToSkip: alreadyPlayedPreferences.value
    };
    if (selectedCategory.value) {
        params.categoryId = selectedCategory.value.id;
    }

    axios.get('/api/get-new-preference', { params })
        .then(response => {
            showVotes.value = false; // Asegurarse de ocultar votos viejos
            
            if (response.data.preference) {
                currentPreference.value = response.data.preference;
                alreadyPlayedPreferences.value.push(currentPreference.value.id);
            } else {
                // No se encontró preferencia
                currentPreference.value = null;
                // Mostrar el mensaje personalizado
                noMorePreferencesMessage.value = '¡No quedan más preguntas en esta categoría! Puedes elegir otra categoría o volver a jugar las mismas reiniciando la lista.'; 
                // Opcional: Botón para reiniciar lista (vaciar alreadyPlayedPreferences)
                console.warn(response.data.message || "No preference found.");
            }
        }).catch(error => {
            console.error("Error fetching new preference:", error);
            noMorePreferencesMessage.value = 'Error al cargar la siguiente pregunta.'; // Mensaje de error genérico
            currentPreference.value = null;
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

const showCategoriesMenu = ref(false);

onMounted(() => {
    // Obtener preferencia inicial (considerar filtrar por categoría seleccionada si es necesario)
    getNewPreference(); 
});

// Función para seleccionar una categoría
function selectCategory(category) {
    if (selectedCategory.value?.id === category?.id) { // Evita recargar si es la misma categoría
        showCategoriesMenu.value = false;
        return;
    }
    selectedCategory.value = category;
    showCategoriesMenu.value = false;
    // Limpiar lista de preferencias jugadas al cambiar de categoría
    alreadyPlayedPreferences.value = []; 
    // Asegurarse de limpiar el mensaje de "no quedan preguntas"
    noMorePreferencesMessage.value = ''; 
    getNewPreference(); 
}

// Función para reiniciar preguntas de la categoría actual
function resetPreferences() {
    alreadyPlayedPreferences.value = [];
    noMorePreferencesMessage.value = ''; // Ocultar mensaje
    getNewPreference(); // Obtener una nueva (ahora debería encontrar)
}
</script>

<template>
    <Head>
        <title>Juego ¿Qué Prefieres? Online Gratis</title>
        <meta name="description" content="Juega gratis al popular juego '¿Qué prefieres?' online. Enfrenta divertidos y difíciles dilemas, vota por tu opción preferida y descubre al instante qué elige la mayoría. ¡Horas de diversión garantizadas!">
        <meta name="keywords" content="qué prefieres, que prefieres, juego, jugar, online, gratis, preguntas, dilemas, elecciones, votar, comparar, popular, divertido">
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="https://queprefieresjuego.com/">
        <meta property="og:title" content="Juego ¿Qué Prefieres? Online Gratis">
        <meta property="og:description" content="Juega gratis al popular juego '¿Qué prefieres?' online. Enfrenta divertidos y difíciles dilemas, vota por tu opción preferida y descubre al instante qué elige la mayoría.">
        <!-- <meta property="og:image" content="https://queprefieresjuego.com/path/to/your/image.jpg"> --> <!-- ¡Descomenta y pon una imagen atractiva! -->

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="https://queprefieresjuego.com/">
        <meta property="twitter:title" content="Juego ¿Qué Prefieres? Online Gratis">
        <meta property="twitter:description" content="Juega gratis al popular juego '¿Qué prefieres?' online. Enfrenta divertidos y difíciles dilemas, vota por tu opción preferida y descubre al instante qué elige la mayoría.">
        <!-- <meta property="twitter:image" content="https://queprefieresjuego.com/path/to/your/image.jpg"> --> <!-- ¡Descomenta y pon una imagen atractiva! -->

        <!-- Canonical URL -->
        <link rel="canonical" href="https://queprefieresjuego.com/" />
    </Head>
    <div class="h-dvh w-screen overflow-hidden bg-gray-900 grid grid-rows-2 p-4 gap-4 relative">
        <div class="absolute top-0 left-[50%] -translate-x-1/2 text-center text-white bg-gray-900 px-4 py-2 rounded-b-2xl w-fit whitespace-nowrap z-20">
            <h1 class="text-2xl font-bold">¿Qué Prefieres?</h1>
        </div>
        <div 
            class="absolute z-50 text-center bottom-0 w-[calc(100%-32px)] select-none left-[50%] bg-gray-900/80 transition-all duration-300 ease-in-out -translate-x-1/2 text-white px-4 py-2 rounded-t-2xl whitespace-nowrap"
            :class="{
                'h-[calc(100%-48px-16px)] max-w-full overflow-y-auto': showCategoriesMenu, // Añadido overflow
                'h-12 max-w-fit cursor-pointer': !showCategoriesMenu // Añadido cursor
            }"
            @click="!showCategoriesMenu && (showCategoriesMenu = true)"
        >
            <h1 
                class="text-2xl font-bold inline-flex items-center gap-2 justify-center cursor-pointer mb-4" 
                @click.stop="showCategoriesMenu = !showCategoriesMenu"
            >
                {{ selectedCategory ? selectedCategory.name : 'Preguntas generales' }}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" 
                     class="w-5 h-5 ml-1 transition-transform duration-300 ease-in-out" 
                     :class="{ 'rotate-180': showCategoriesMenu }">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                </svg>
            </h1>
            
            <!-- Lista de categorías (solo visible si showCategoriesMenu es true) -->
            <div v-if="showCategoriesMenu" class="mt-4 space-y-2">
                <div 
                    v-for="category in categories" 
                    :key="category.id" 
                    @click="selectCategory(category)" 
                    class="text-xl p-2 rounded hover:bg-gray-700 cursor-pointer"
                    :class="{'bg-gray-600 font-semibold': selectedCategory && selectedCategory.id === category.id}"
                >
                    {{ category.name }}
                </div>
                 <div v-if="!categories || categories.length === 0" class="text-gray-400">
                    No hay categorías disponibles.
                 </div>
                 <!-- Botón para cerrar -->
                 <button @click.stop="showCategoriesMenu = false" class="mt-4 text-sm text-gray-400 hover:text-white">
                    Cerrar
                 </button>
            </div>
        </div>
        
        <!-- Mensaje de No más Preferencias (Overlay) -->
        <div v-if="noMorePreferencesMessage" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900 bg-opacity-90 z-30 p-8 text-center">
             <p class="text-white text-2xl font-semibold mb-6">{{ noMorePreferencesMessage }}</p>
             <div class="flex flex-col sm:flex-row gap-4">
                 <button 
                     @click="resetPreferences"
                     class="px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-lg"
                 >
                     Volver a jugar esta categoría
                 </button>
                 <button 
                     @click="showCategoriesMenu = true"
                     class="px-6 py-3 bg-gray-600 text-white rounded hover:bg-gray-700 transition text-lg"
                 >
                     Cambiar de categoría
                 </button>
             </div>
        </div>
        
        <!-- Contenedor Principal de Preferencias (solo si no hay mensaje overlay) -->
        <template v-if="!noMorePreferencesMessage">
            <!-- Estado de Carga (solo si no hay preferencia Y el menú de categorías está CERRADO) -->
            <template v-if="!currentPreference && !showCategoriesMenu"> 
                 <div class="bg-gray-800 rounded-2xl flex items-center justify-center text-gray-500 text-2xl">Cargando pregunta...</div> 
                 <div class="bg-gray-800 rounded-2xl"></div> <!-- Placeholder para la segunda mitad -->
            </template>
            
            <!-- Preferencias Cargadas (o si el menú está abierto aunque no haya preferencia aún) -->
            <template v-else-if="currentPreference">
                <!-- Preferencia 1 -->
                <div
                    :class="[
                        'bg-gradient-to-br from-pink-700 to-red-500 rounded-2xl flex text-center items-center p-4 gap-4 justify-center text-white text-3xl flex-col transition-all duration-300',
                        canVote ? 'cursor-pointer hover:from-pink-600 hover:to-red-400' : 'cursor-not-allowed',
                        showCategoriesMenu ? 'opacity-50 pointer-events-none' : '' // Mantenemos la opacidad si el menú está abierto
                    ]"
                    @click="canVote && vote('preference1')"
                >
                    <div>{{ currentPreference?.preference1 }}</div>
                    <div v-show="showVotes">
                        <div class="text-5xl font-bold">{{ animatedPreference1Percentage }}%</div>
                        <span class="text-lg">({{ currentPreference?.preference1_votes }} votos)</span>
                    </div>
                </div>
                <!-- Preferencia 2 -->
                <div
                    :class="[
                        'bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center text-center p-4 gap-4 justify-center text-white text-3xl flex-col transition-all duration-300',
                        canVote ? 'cursor-pointer hover:from-blue-400 hover:to-indigo-500' : 'cursor-not-allowed',
                        showCategoriesMenu ? 'opacity-50 pointer-events-none' : '' // Mantenemos la opacidad si el menú está abierto
                    ]"
                    @click="canVote && vote('preference2')"
                >
                    <div>{{ currentPreference?.preference2 }}</div>
                    <div v-show="showVotes">
                        <div class="text-5xl font-bold">{{ animatedPreference2Percentage }}%</div>
                         <span class="text-lg">({{ currentPreference?.preference2_votes }} votos)</span>
                    </div>
                </div>
            </template>
            <!-- Añadimos un else para el caso en que no hay preferencia pero el menú sí está abierto -->
            <!-- Así evitamos que se muestre el loading, pero también evitamos un hueco vacío si no hay currentPreference -->
            <template v-else-if="showCategoriesMenu && !currentPreference">
                 <div class="bg-gray-800 rounded-2xl opacity-50"></div> <!-- Placeholder opaco 1 -->
                 <div class="bg-gray-800 rounded-2xl opacity-50"></div> <!-- Placeholder opaco 2 -->
            </template>
        </template>
        
        <!-- "Siguiente" Button (solo si hay preferencia actual y no hay mensaje) -->
        <transition name="fade">
            <div 
                v-if="currentPreference && !noMorePreferencesMessage" 
                v-show="showNextButtonDelayed" 
                @click="getNewPreference"
                class="text-center whitespace-nowrap absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white text-zinc-800 px-8 py-4 rounded-2xl shadow-lg cursor-pointer text-2xl font-semibold hover:bg-zinc-100 z-10"
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
