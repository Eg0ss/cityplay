import { reactive } from 'vue';

export const userStatsStore = reactive({
    points: 0,
    levelName: 'BRONZE I',
    initialized: false,
    
    initialize(initialPoints, initialLevel) {
        this.points = initialPoints || 0;
        this.levelName = initialLevel || 'BRONZE I';
        this.initialized = true;
    },
    
    addPoints(pts) {
        this.points += pts;
        // Recalculer le niveau dynamiquement en front
        if (this.points < 500) {
            this.levelName = 'BRONZE I';
        } else if (this.points < 1500) {
            this.levelName = 'BRONZE II';
        } else if (this.points < 3000) {
            this.levelName = 'ARGENT I';
        } else {
            this.levelName = 'OR I';
        }
    }
});
