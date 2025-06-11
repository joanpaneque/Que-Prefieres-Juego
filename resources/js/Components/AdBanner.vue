<script setup>
import { onMounted, ref } from 'vue'

const showFallback = ref(false)

onMounted(() => {
    // Esperar un poco para que se cargue el script de AdSense
    setTimeout(() => {
        if (window.adsbygoogle && Array.isArray(window.adsbygoogle)) {
            try {
                window.adsbygoogle.push({})

                // Verificar si el anuncio se cargó después de un tiempo
                setTimeout(() => {
                    const adElement = document.querySelector('.adsbygoogle')
                    if (adElement && adElement.innerHTML.trim() === '') {
                        showFallback.value = true
                    }
                }, 2000)
            } catch (e) {
                console.warn('Error cargando anuncio:', e)
                showFallback.value = true
            }
        } else {
            showFallback.value = true
        }
    }, 1000)
})
</script>


<template>
    <div class="ad-container">
        <ins v-if="!showFallback"
             class="adsbygoogle"
             style="display:inline-block;width:728px;height:90px"
             data-ad-client="ca-pub-6200122651048793"
             data-ad-slot="7868618207">
        </ins>

                        <div v-if="showFallback"
             class="ad-fallback bg-gradient-to-br from-gray-700 to-gray-800 rounded-2xl flex flex-col items-center justify-center text-center text-white shadow-lg border border-gray-600 hover:from-gray-600 hover:to-gray-700 transition-all duration-300 cursor-pointer"
             style="width:728px;height:90px;padding:10px;box-sizing:border-box;">
            <div class="text-sm font-semibold">Pon tu anuncio aquí (+1000 visitas mensuales)</div>
            <div class="text-xs text-gray-300 mt-1">joanpd0@gmail.com</div>
        </div>
    </div>
</template>
