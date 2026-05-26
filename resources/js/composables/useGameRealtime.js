import { ref, onUnmounted } from 'vue';

export function useGameRealtime(sessionToken, currentUserId) {
    const lockedRiddles      = ref({});
    const realtimeAttempts   = ref([]);
    const realtimePlayers    = ref([]);
    const sessionEnded       = ref(false);
    const riddleNotification = ref(null);

    let channel = null;

    function subscribe() {
        if (!window.Echo) {
            console.warn('[Realtime] Laravel Echo non disponible.');
            return;
        }

        channel = window.Echo.channel(`game.${sessionToken}`);

        // Verrouillage instantané (avant même la réponse)
        channel.listen('.riddle.locked', (e) => {
            if (e.locked_by_user_id === currentUserId) return;

            lockedRiddles.value[e.game_riddle_id] = {
                locked_by_user_id: e.locked_by_user_id,
                locked_by_name:    e.locked_by_name,
                locked_at:         e.locked_at,
            };

            riddleNotification.value = {
                game_riddle_id: e.game_riddle_id,
                message: `🔒 ${e.locked_by_name} vient de verrouiller cette énigme !`,
                type:    'lock',
            };
        });

        // Mise à jour complète après une réponse
        channel.listen('.App\\Events\\GameUpdated', (e) => {
            if (!e.session) return;

            if (e.session.attempts)    realtimeAttempts.value = e.session.attempts;
            if (e.session.players)     realtimePlayers.value  = e.session.players;

            if (e.session.gameRiddles) {
                e.session.gameRiddles.forEach((gr) => {
                    if (gr.statut === 'verrouille') {
                        lockedRiddles.value[gr.id] = {
                            locked_by_player_id: gr.locked_by_player_id,
                            locked_by_name:      gr.locked_by_name,
                        };
                    }
                });
            }

            if (e.session.statut === 'termine') {
                sessionEnded.value = true;
            }
        });
    }

    function unsubscribe() {
        if (window.Echo && channel) {
            window.Echo.leave(`game.${sessionToken}`);
            channel = null;
        }
    }

    onUnmounted(unsubscribe);

    return {
        lockedRiddles,
        realtimeAttempts,
        realtimePlayers,
        sessionEnded,
        riddleNotification,
        subscribe,
        unsubscribe,
    };
}