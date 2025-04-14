class LoadingScreen {
    constructor() {
        this.loadingScreen = document.getElementById('loading-screen');
        this.minimumDisplayTime = 800; // Slightly longer minimum time
        this.startTime = Date.now();
        
        // Show immediately
        this.show();
        this.init();
    }
    
    init() {
        // Hide when everything is loaded
        window.addEventListener('load', () => {
            const elapsed = Date.now() - this.startTime;
            const remainingTime = Math.max(0, this.minimumDisplayTime - elapsed);
            
            setTimeout(() => {
                this.hide();
            }, remainingTime);
        });
        
        // Fallback in case load event doesn't fire
        setTimeout(() => {
            if (this.loadingScreen.style.display !== 'none') {
                this.hide();
            }
        }, 5000); // Extended timeout to 5 seconds
    }
    
    show() {
        if (this.loadingScreen) {
            this.loadingScreen.style.display = 'flex';
            this.loadingScreen.style.opacity = '1';
            document.body.style.overflow = 'hidden';
        }
    }
    
    hide() {
        if (this.loadingScreen) {
            this.loadingScreen.style.opacity = '0';
            setTimeout(() => {
                this.loadingScreen.style.display = 'none';
                document.body.style.overflow = '';
            }, 500);
        }
    }
}

// Initialize immediately (don't wait for DOMContentLoaded)
window.loadingScreen = new LoadingScreen();