import { ref, readonly } from 'vue';

// ─── Singleton partagé entre tous les composants ───────────────────────────
const isMuted = ref(false);
const volume = ref(0.5);
const currentTrackName = ref('');
const isPlaying = ref(false);

let audioCtx = null;
let bgAudio = null; // Élément <audio> pour la musique de fond

// ─── Initialiser le contexte Web Audio (nécessite un geste utilisateur) ────
const initAudioContext = () => {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    return audioCtx;
};

// ─── Générateur de son synthétique ─────────────────────────────────────────
const playTone = (frequency, duration, type = 'sine', gainValue = 0.3, delay = 0) => {
    if (isMuted.value) return;
    const ctx = initAudioContext();
    if (!ctx) return;

    const gainNode = ctx.createGain();
    gainNode.gain.setValueAtTime(0, ctx.currentTime + delay);
    gainNode.gain.linearRampToValueAtTime(gainValue * volume.value, ctx.currentTime + delay + 0.01);
    gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + duration);
    gainNode.connect(ctx.destination);

    const oscillator = ctx.createOscillator();
    oscillator.type = type;
    oscillator.frequency.setValueAtTime(frequency, ctx.currentTime + delay);
    oscillator.connect(gainNode);
    oscillator.start(ctx.currentTime + delay);
    oscillator.stop(ctx.currentTime + delay + duration);
};

// ─── Sons d'interface ───────────────────────────────────────────────────────

/** Clic sur un bouton */
const playClick = () => {
    playTone(800, 0.08, 'sine', 0.2);
};

/** Frappe clavier (input texte) */
const playKey = () => {
    playTone(1200, 0.04, 'triangle', 0.08);
};

/** Tick de métronome (dernières secondes du chrono) */
const playTick = () => {
    playTone(1000, 0.07, 'square', 0.15);
};

/** Bip d'urgence (chrono < 5s) */
const playCountdown = () => {
    playTone(1400, 0.12, 'sawtooth', 0.25);
};

/** Notification (nouveau joueur rejoint) */
const playNotification = () => {
    playTone(880, 0.1, 'sine', 0.2);
    playTone(1100, 0.15, 'sine', 0.2, 0.12);
};

// ─── Sons de résultat ───────────────────────────────────────────────────────

/** Fanfare de victoire : Do-Mi-Sol montant */
const playWin = () => {
    stopBackgroundMusic();
    const notes = [523, 659, 784, 1047]; // C5 E5 G5 C6
    notes.forEach((freq, i) => {
        playTone(freq, 0.3, 'sine', 0.4, i * 0.18);
    });
    // Shimmer final
    setTimeout(() => {
        playTone(1568, 0.5, 'triangle', 0.2);
    }, notes.length * 180 + 50);
};

/** Son d'échec : descente grave */
const playLose = () => {
    stopBackgroundMusic();
    const notes = [392, 330, 262, 196]; // G4 E4 C4 G3
    notes.forEach((freq, i) => {
        playTone(freq, 0.4, 'sawtooth', 0.3, i * 0.2);
    });
};

/** Son énigme déjà résolue : bip neutre */
const playAlreadySolved = () => {
    playTone(440, 0.2, 'sine', 0.2);
    playTone(440, 0.2, 'sine', 0.2, 0.3);
};

/** Son de démarrage de partie */
const playGameStart = () => {
    const notes = [262, 330, 392, 523]; // C4 E4 G4 C5
    notes.forEach((freq, i) => {
        playTone(freq, 0.25, 'sine', 0.35, i * 0.12);
    });
};

// ─── Musique de fond (HTML Audio) ──────────────────────────────────────────

const TRACKS = {
    lobby: {
        // Musique d'ambiance légère — libre de droits (Pixabay)
        url: 'https://cdn.pixabay.com/download/audio/2022/03/15/audio_8cb22f9a6f.mp3',
        name: '🎵 Ambiance Lobby',
    },
    game: {
        // Musique de jeu tendue — libre de droits (Pixabay)
        url: 'https://cdn.pixabay.com/download/audio/2023/06/08/audio_0e8c49e2e5.mp3',
        name: '🎮 Thème de Jeu',
    },
};

const playBackgroundMusic = (trackKey) => {
    if (isMuted.value) return;

    const track = TRACKS[trackKey];
    if (!track) return;

    // Arrêter l'éventuelle musique précédente
    stopBackgroundMusic();

    bgAudio = new Audio(track.url);
    bgAudio.loop = true;
    bgAudio.volume = Math.min(volume.value * 0.6, 0.6); // Musique plus douce que les effets
    bgAudio.preload = 'auto';

    bgAudio.play().catch(() => {
        // L'autoplay peut être bloqué avant interaction — on ignore silencieusement
    });

    currentTrackName.value = track.name;
    isPlaying.value = true;
};

const stopBackgroundMusic = () => {
    if (bgAudio) {
        bgAudio.pause();
        bgAudio.currentTime = 0;
        bgAudio = null;
    }
    isPlaying.value = false;
    currentTrackName.value = '';
};

const pauseBackgroundMusic = () => {
    if (bgAudio && !bgAudio.paused) {
        bgAudio.pause();
        isPlaying.value = false;
    }
};

const resumeBackgroundMusic = () => {
    if (bgAudio && bgAudio.paused && !isMuted.value) {
        bgAudio.play().catch(() => {});
        isPlaying.value = true;
    }
};

// ─── Contrôles globaux ──────────────────────────────────────────────────────

const toggleMute = () => {
    isMuted.value = !isMuted.value;
    if (bgAudio) {
        bgAudio.muted = isMuted.value;
    }
};

const setVolume = (val) => {
    volume.value = Math.max(0, Math.min(1, val));
    if (bgAudio) {
        bgAudio.volume = Math.min(volume.value * 0.6, 0.6);
    }
};

// ─── Export du composable ───────────────────────────────────────────────────
export function useAudio() {
    return {
        // État (readonly pour éviter les mutations externes)
        isMuted: readonly(isMuted),
        volume: readonly(volume),
        currentTrackName: readonly(currentTrackName),
        isPlaying: readonly(isPlaying),

        // Sons d'interface
        playClick,
        playKey,
        playTick,
        playCountdown,
        playNotification,

        // Sons de résultat
        playWin,
        playLose,
        playAlreadySolved,
        playGameStart,

        // Musique de fond
        playBackgroundMusic,
        stopBackgroundMusic,
        pauseBackgroundMusic,
        resumeBackgroundMusic,

        // Contrôles
        toggleMute,
        setVolume,
        initAudioContext,
    };
}
