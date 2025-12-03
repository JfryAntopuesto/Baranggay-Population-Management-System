class WebSocketClient {
    constructor(userId) {
        this.userId = userId;
        this.ws = null;
        this.eventHandlers = {};
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000; // Start with 1 second delay
        this.isReconnecting = false;
        this.connect();
    }

    connect() {
        // Use secure WebSocket if the page is served over HTTPS
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.hostname}:8080`;
        
        this.ws = new WebSocket(wsUrl);

        this.ws.onopen = () => {
            console.log('WebSocket connected');
            this.reconnectAttempts = 0;
            this.reconnectDelay = 1000;
            this.isReconnecting = false;
            
            // Send user ID to server
            this.send({
                type: 'register',
                userId: this.userId
            });

            // Trigger connect event
            if (this.eventHandlers['connect']) {
                this.eventHandlers['connect'].forEach(handler => handler());
            }
        };

        this.ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                console.log('Received WebSocket message:', data);
                
                if (data.type && this.eventHandlers[data.type]) {
                    this.eventHandlers[data.type].forEach(handler => handler(data));
                }
            } catch (error) {
                console.error('Error parsing WebSocket message:', error);
            }
        };

        this.ws.onclose = () => {
            console.log('WebSocket disconnected');
            
            // Only trigger disconnect event if we're not attempting to reconnect
            if (!this.isReconnecting && this.eventHandlers['disconnect']) {
                this.eventHandlers['disconnect'].forEach(handler => handler());
            }
            
            this.attemptReconnect();
        };

        this.ws.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
    }

    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.isReconnecting = true;
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
            
            setTimeout(() => {
                this.connect();
            }, this.reconnectDelay);
            
            // Exponential backoff
            this.reconnectDelay *= 2;
        } else {
            this.isReconnecting = false;
            console.log('Max reconnection attempts reached');
            if (this.eventHandlers['disconnect']) {
                this.eventHandlers['disconnect'].forEach(handler => handler());
            }
        }
    }

    on(event, handler) {
        if (!this.eventHandlers[event]) {
            this.eventHandlers[event] = [];
        }
        this.eventHandlers[event].push(handler);
    }

    off(event, handler) {
        if (this.eventHandlers[event]) {
            this.eventHandlers[event] = this.eventHandlers[event].filter(h => h !== handler);
        }
    }

    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        } else {
            console.error('WebSocket is not connected');
        }
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }
} 