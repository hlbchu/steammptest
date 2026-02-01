/**
 * SOUND MANAGER - Quản lý âm thanh cho website
 */

class SoundManager {
    constructor() {
        this.sounds = {};
        this.enabled = true;
        this.volume = 0.3;
        
        // Tạo âm thanh "ting" bằng Web Audio API
        this.createTingSound();
    }
    
    /**
     * Tạo âm thanh "ting" bằng Web Audio API
     */
    createTingSound() {
        this.sounds.ting = () => {
            if (!this.enabled) return;
            
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                
                // Create oscillator for "ting" sound
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();
                
                oscillator.connect(gain);
                gain.connect(audioContext.destination);
                
                // Set frequency for a pleasant "ting" sound
                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(600, audioContext.currentTime + 0.1);
                
                // Set gain envelope
                gain.gain.setValueAtTime(this.volume, audioContext.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (error) {
                console.warn('Could not play sound:', error);
            }
        };
    }
    
    /**
     * Load âm thanh từ file
     */
    loadSound(name, path) {
        this.sounds[name] = new Audio(path);
        this.sounds[name].volume = this.volume;
    }
    
    /**
     * Play âm thanh
     */
    play(soundName) {
        if (!this.enabled) return;
        
        if (typeof this.sounds[soundName] === 'function') {
            // Web Audio API sound
            this.sounds[soundName]();
        } else if (this.sounds[soundName]) {
            // HTML5 Audio
            const sound = this.sounds[soundName].cloneNode();
            sound.volume = this.volume;
            sound.play().catch(err => console.warn('Could not play sound:', err));
        }
    }
    
    /**
     * Bật/tắt âm thanh
     */
    toggle() {
        this.enabled = !this.enabled;
        return this.enabled;
    }
    
    /**
     * Set volume (0.0 - 1.0)
     */
    setVolume(vol) {
        this.volume = Math.max(0, Math.min(1, vol));
    }
}

// Global sound manager instance
const soundManager = new SoundManager();

// Load cart sound
soundManager.loadSound('cart', '/steamweb/public/assets/sounds/som_matricula-464025.mp3');
