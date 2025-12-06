/**
 * Purok API Utility Module
 * Handles all API calls to the Node.js Express backend
 */

const PurokAPI = {
    // API base URL - adjust this to match your server configuration
    baseURL: 'http://localhost:3000/api',

    /**
     * Generic fetch wrapper with error handling
     */
    async fetchAPI(endpoint, options = {}) {
        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'API request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    /**
     * Get all puroks (no pagination)
     * @returns {Promise<Array>} Array of purok objects
     */
    async getAllPuroks() {
        const response = await this.fetchAPI('/puroks/all');
        return response.data || [];
    },

    /**
     * Get all puroks with pagination
     * @param {number} page - Page number (default: 1)
     * @param {number} perPage - Items per page (default: 10)
     * @returns {Promise<Object>} Object with data and pagination info
     */
    async getPuroksPaginated(page = 1, perPage = 10) {
        return await this.fetchAPI(`/puroks?page=${page}&per_page=${perPage}`);
    },

    /**
     * Get a single purok by ID
     * @param {number} purokID - Purok ID
     * @returns {Promise<Object>} Purok object
     */
    async getPurokById(purokID) {
        const response = await this.fetchAPI(`/puroks/${purokID}`);
        return response.data;
    },

    /**
     * Search puroks by name or code
     * @param {string} searchTerm - Search term
     * @returns {Promise<Array>} Array of matching puroks
     */
    async searchPuroks(searchTerm) {
        if (!searchTerm || !searchTerm.trim()) {
            return [];
        }
        const response = await this.fetchAPI(`/puroks/search?q=${encodeURIComponent(searchTerm)}`);
        return response.data || [];
    },

    /**
     * Verify a purok code
     * @param {string} purokCode - Purok code to verify
     * @returns {Promise<Object>} Verification result with purok data if valid
     */
    async verifyPurokCode(purokCode) {
        const response = await this.fetchAPI(`/puroks/verify/${encodeURIComponent(purokCode)}`);
        return response;
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PurokAPI;
}

