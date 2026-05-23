<script setup>
import { ref } from 'vue';
import { useAudio } from '@/composables/useAudio.js';

const { isMuted, volume, currentTrackName, isPlaying, toggleMute, setVolume } = useAudio();

const showSlider = ref(false);

const handleVolumeChange = (e) => {
    setVolume(parseFloat(e.target.value));
};
</script>

<template>
    <div class="audio-widget" @mouseenter="showSlider = true" @mouseleave="showSlider = false">
        <!-- Slider de volume (apparaît au survol) -->
        <transition name="slide-up">
            <div v-if="showSlider" class="volume-panel">
                <span class="track-name" v-if="currentTrackName">{{ currentTrackName }}</span>
                <div class="slider-wrap">
                    <input
                        type="range"
                        min="0"
                        max="1"
                        step="0.05"
                        :value="volume"
                        @input="handleVolumeChange"
                        class="volume-slider"
                    />
                    <div class="volume-fill" :style="{ height: (volume * 100) + '%' }"></div>
                </div>
                <span class="volume-pct">{{ Math.round(volume * 100) }}%</span>
            </div>
        </transition>

        <!-- Bouton principal mute/unmute -->
        <button @click="toggleMute" class="mute-btn" :class="{ muted: isMuted }" :title="isMuted ? 'Activer le son' : 'Couper le son'">
            <!-- Icône Son actif -->
            <svg v-if="!isMuted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="icon">
                <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.508c-1.141 0-2.318.664-2.66 1.905A9.76 9.76 0 001.5 12c0 .898.121 1.768.35 2.595.341 1.24 1.518 1.905 2.659 1.905h1.93l4.5 4.5c.945.945 2.561.276 2.561-1.06V4.06zM18.584 5.106a.75.75 0 011.06 0c3.808 3.807 3.808 9.98 0 13.788a.75.75 0 11-1.06-1.06 8.25 8.25 0 000-11.668.75.75 0 010-1.06z" />
                <path d="M15.932 7.757a.75.75 0 011.061 0 6 6 0 010 8.486.75.75 0 01-1.06-1.061 4.5 4.5 0 000-6.364.75.75 0 010-1.061z" />
            </svg>
            <!-- Icône Son coupé -->
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="icon">
                <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.508c-1.141 0-2.318.664-2.66 1.905A9.76 9.76 0 001.5 12c0 .898.121 1.768.35 2.595.341 1.24 1.518 1.905 2.659 1.905h1.93l4.5 4.5c.945.945 2.561.276 2.561-1.06V4.06zM17.78 9.22a.75.75 0 10-1.06 1.06L18.44 12l-1.72 1.72a.75.75 0 001.06 1.06L19.5 13.06l1.72 1.72a.75.75 0 101.06-1.06L20.56 12l1.72-1.72a.75.75 0 00-1.06-1.06L19.5 10.94l-1.72-1.72z" />
            </svg>
            <!-- Indicateur d'animation musique -->
            <span v-if="isPlaying && !isMuted" class="music-bars">
                <span></span><span></span><span></span>
            </span>
        </button>
    </div>
</template>

<style scoped>
.audio-widget {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    user-select: none;
}

/* ─── Bouton principal ─── */
.mute-btn {
    position: relative;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(28, 29, 36, 0.9);
    border: 2px solid rgba(47, 194, 118, 0.3);
    color: #2fc276;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    backdrop-filter: blur(12px);
    transition: all 0.25s ease;
    box-shadow: 0 0 20px rgba(47, 194, 118, 0.1), 0 4px 12px rgba(0,0,0,0.4);
}

.mute-btn:hover {
    border-color: rgba(47, 194, 118, 0.7);
    box-shadow: 0 0 30px rgba(47, 194, 118, 0.25), 0 4px 16px rgba(0,0,0,0.5);
    transform: scale(1.08);
}

.mute-btn.muted {
    color: #6b7280;
    border-color: rgba(107, 114, 128, 0.3);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.icon {
    width: 20px;
    height: 20px;
}

/* ─── Barres musicales animées ─── */
.music-bars {
    position: absolute;
    top: -2px;
    right: -2px;
    display: flex;
    align-items: flex-end;
    gap: 1.5px;
    height: 10px;
    background: rgba(13, 14, 18, 0.8);
    border-radius: 4px;
    padding: 1px 2px;
}

.music-bars span {
    display: block;
    width: 2.5px;
    background: #2fc276;
    border-radius: 2px;
    animation: music-bar 0.8s ease-in-out infinite alternate;
}
.music-bars span:nth-child(1) { height: 5px; animation-delay: 0s; }
.music-bars span:nth-child(2) { height: 8px; animation-delay: 0.2s; }
.music-bars span:nth-child(3) { height: 4px; animation-delay: 0.4s; }

@keyframes music-bar {
    from { transform: scaleY(0.4); }
    to   { transform: scaleY(1); }
}

/* ─── Panneau volume ─── */
.volume-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    background: rgba(13, 14, 18, 0.92);
    border: 1px solid rgba(47, 194, 118, 0.2);
    border-radius: 16px;
    padding: 0.75rem 0.6rem;
    backdrop-filter: blur(16px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    min-width: 56px;
}

.track-name {
    font-size: 8px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #2fc276;
    text-align: center;
    max-width: 56px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.volume-pct {
    font-size: 9px;
    font-weight: 900;
    color: #6b7280;
}

/* ─── Slider vertical ─── */
.slider-wrap {
    position: relative;
    width: 28px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.volume-slider {
    writing-mode: vertical-lr;
    direction: rtl;
    width: 4px;
    height: 80px;
    accent-color: #2fc276;
    cursor: pointer;
    background: transparent;
    position: relative;
    z-index: 2;
}

/* ─── Transition d'apparition ─── */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(8px) scale(0.95);
}
</style>
