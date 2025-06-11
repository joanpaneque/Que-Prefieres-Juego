<script setup>
import { onMounted } from 'vue'

onMounted(() => {
    if (window.adsbygoogle && Array.isArray(window.adsbygoogle)) {
        try {
            window.adsbygoogle.push({})
        } catch (e) {
            console.warn('Error cargando anuncio:', e)
        }
    }
})
</script>


<template>
    <ins class="adsbygoogle" style="display:inline-block;width:728px;height:90px"
        data-ad-client="ca-pub-6200122651048793" data-ad-slot="7868618207"></ins>
</template>
