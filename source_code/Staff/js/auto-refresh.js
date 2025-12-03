/**
 * Auto-refresh functionality for Staff Dashboard
 * This script provides automatic data refresh capabilities for the BPMS Staff system
 */

class AutoRefresh {
    constructor(options = {}) {
        // Default settings
        this.settings = {
            enabled: true,
            interval: options.interval || 30000, // Default: 30 seconds
            refreshFunctions: options.refreshFunctions || [],
            statusElementId: options.statusElementId || null,
            debugMode: options.debugMode || false
        };

        this.timerId = null;
        this.lastRefreshTime = null;
        this.initialize();
    }

    initialize() {
        // Create status indicator if element ID is provided
        if (this.settings.statusElementId) {
            this.createStatusIndicator();
        } else {
            // Create a floating status indicator
            this.createFloatingIndicator();
        }

        // Start the refresh timer
        this.startTimer();

        // Add event listeners for page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                this.log('Page became visible, refreshing data immediately');
                this.refreshData();
                this.startTimer(); // Restart the timer
            } else {
                this.log('Page hidden, pausing auto-refresh');
                this.stopTimer(); // Pause the timer when page is not visible
            }
        });

        // Log initialization
        this.log('Auto-refresh initialized with interval: ' + (this.settings.interval / 1000) + ' seconds');
    }

    createStatusIndicator() {
        const statusElement = document.getElementById(this.settings.statusElementId);
        if (statusElement) {
            statusElement.innerHTML = `
                <div class="auto-refresh-status">
                    <span class="status-indicator"></span>
                    <span class="status-text">Auto-refresh active</span>
                </div>
            `;
            this.statusElement = statusElement.querySelector('.auto-refresh-status');
            this.statusIndicator = statusElement.querySelector('.status-indicator');
            this.statusText = statusElement.querySelector('.status-text');
        }
    }

    createFloatingIndicator() {
        // Create a floating status indicator
        const indicator = document.createElement('div');
        indicator.className = 'auto-refresh-floating';
        indicator.innerHTML = `
            <div class="auto-refresh-status">
                <span class="status-indicator"></span>
                <span class="status-text">Auto-refresh active</span>
                <span class="last-updated"></span>
            </div>
        `;
        
        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .auto-refresh-floating {
                position: fixed;
                bottom: 10px;
                right: 10px;
                background-color: rgba(255, 255, 255, 0.9);
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 5px 10px;
                font-size: 12px;
                z-index: 9999;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
                transition: opacity 0.3s ease;
                opacity: 0.7;
            }
            .auto-refresh-floating:hover {
                opacity: 1;
            }
            .auto-refresh-status {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .status-indicator {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background-color: #4CAF50;
                display: inline-block;
            }
            .status-indicator.refreshing {
                background-color: #FFC107;
                animation: pulse 1s infinite;
            }
            .status-indicator.disabled {
                background-color: #F44336;
            }
            .status-indicator.error {
                background-color: #F44336;
            }
            .last-updated {
                margin-left: 5px;
                color: #666;
                font-size: 10px;
            }
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(indicator);
        
        this.statusElement = indicator.querySelector('.auto-refresh-status');
        this.statusIndicator = indicator.querySelector('.status-indicator');
        this.statusText = indicator.querySelector('.status-text');
        this.lastUpdatedElement = indicator.querySelector('.last-updated');
        
        // Add toggle functionality
        this.statusElement.addEventListener('click', () => {
            this.toggleRefresh();
        });
    }

    startTimer() {
        if (this.settings.enabled && !this.timerId) {
            this.timerId = setInterval(() => {
                this.refreshData();
            }, this.settings.interval);
            
            this.updateStatus('active');
        }
    }

    stopTimer() {
        if (this.timerId) {
            clearInterval(this.timerId);
            this.timerId = null;
            this.updateStatus('disabled');
        }
    }

    toggleRefresh() {
        if (this.settings.enabled) {
            this.settings.enabled = false;
            this.stopTimer();
            this.updateStatus('disabled');
            this.statusText.textContent = 'Auto-refresh disabled';
        } else {
            this.settings.enabled = true;
            this.refreshData(); // Refresh immediately
            this.startTimer();
            this.updateStatus('active');
            this.statusText.textContent = 'Auto-refresh active';
        }
    }

    refreshData() {
        if (!this.settings.enabled) return;
        
        this.updateStatus('refreshing');
        this.statusText.textContent = 'Refreshing...';
        
        // Execute all refresh functions
        const promises = this.settings.refreshFunctions.map(fn => {
            try {
                const result = fn();
                // If the function returns a promise, return it; otherwise, wrap in a resolved promise
                return result instanceof Promise ? result : Promise.resolve(result);
            } catch (error) {
                this.log('Error in refresh function: ' + error.message);
                return Promise.reject(error);
            }
        });
        
        // Wait for all refresh functions to complete
        Promise.all(promises)
            .then(() => {
                this.lastRefreshTime = new Date();
                this.updateStatus('active');
                this.statusText.textContent = 'Auto-refresh active';
                if (this.lastUpdatedElement) {
                    this.lastUpdatedElement.textContent = 'Updated: ' + this.formatTime(this.lastRefreshTime);
                }
                this.log('Data refreshed successfully');
            })
            .catch(error => {
                this.updateStatus('error');
                this.statusText.textContent = 'Refresh error';
                this.log('Error refreshing data: ' + error.message);
                // Continue with auto-refresh despite errors
                setTimeout(() => {
                    if (this.settings.enabled) {
                        this.updateStatus('active');
                        this.statusText.textContent = 'Auto-refresh active';
                    }
                }, 3000);
            });
    }

    updateStatus(status) {
        if (!this.statusIndicator) return;
        
        // Remove all status classes
        this.statusIndicator.classList.remove('active', 'refreshing', 'disabled', 'error');
        
        // Add the appropriate class
        this.statusIndicator.classList.add(status);
    }

    formatTime(date) {
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        const seconds = date.getSeconds().toString().padStart(2, '0');
        return `${hours}:${minutes}:${seconds}`;
    }

    log(message) {
        if (this.settings.debugMode) {
            console.log(`[AutoRefresh] ${message}`);
        }
    }

    // Add a new refresh function
    addRefreshFunction(fn) {
        if (typeof fn === 'function') {
            this.settings.refreshFunctions.push(fn);
        }
    }

    // Set refresh interval
    setInterval(milliseconds) {
        this.settings.interval = milliseconds;
        if (this.timerId) {
            this.stopTimer();
            this.startTimer();
        }
    }
}

// Helper function to create an auto-refresh instance with common settings
function initializeAutoRefresh(refreshFunctions, interval = 30000) {
    return new AutoRefresh({
        interval: interval,
        refreshFunctions: refreshFunctions,
        debugMode: true
    });
}
