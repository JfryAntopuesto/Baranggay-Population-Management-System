const express = require('express');
const mysql = require('mysql2/promise');
const cors = require('cors');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Database configuration - matching database-connection.php
const dbConfig = {
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || 'root',
    database: process.env.DB_NAME || 'baranggay_population_management',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    charset: 'utf8mb4',
    // Additional options for better compatibility
    multipleStatements: false,
    timezone: 'local'
};

// Create connection pool
const pool = mysql.createPool(dbConfig);

// Test database connection
pool.getConnection()
    .then(connection => {
        console.log('Database connected successfully');
        connection.release();
    })
    .catch(err => {
        console.error('Database connection failed:', err.message);
    });

// Helper function to execute queries
const executeQuery = async (query, params = []) => {
    try {
        const [results] = await pool.execute(query, params);
        return results;
    } catch (error) {
        console.error('Database query error:', error);
        throw error;
    }
};


app.get('/api/puroks', async (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const perPage = parseInt(req.query.per_page) || 10;
        const offset = (page - 1) * perPage;

        // Ensure values are valid integers
        const limitValue = Math.max(1, Math.min(100, parseInt(perPage))); // Clamp between 1 and 100
        const offsetValue = Math.max(0, parseInt(offset)); // Ensure non-negative

        // Allowed puroks list (must match config/barangay-config.php)
        const allowedPuroks = [
            'Tamia 1', 'Tamia 2', 'Marba', 'Magay', '8-7',
            'Centro 1', 'Centro 2', 'Centro 3', 'Caguisocan',
            'Bagnan', 'Doña Concepcion', 'Tulay', 'Dalaguit', 'Cuanas'
        ];

        // Get total count (only allowed puroks)
        const placeholders = allowedPuroks.map(() => '?').join(',');
        const countQuery = `SELECT COUNT(*) as total FROM puroks WHERE purok_name IN (${placeholders})`;
        const [countResult] = await pool.execute(countQuery, allowedPuroks);
        const total = countResult[0].total;
        const totalPages = Math.ceil(total / limitValue);

        // Get puroks with pagination (only allowed puroks)
        // Note: LIMIT and OFFSET must be integers, not placeholders in MySQL
        const query = `
            SELECT purokID, purok_name, araw, purok_pres, purok_code 
            FROM puroks 
            WHERE purok_name IN (${placeholders})
            ORDER BY purok_name 
            LIMIT ${limitValue} OFFSET ${offsetValue}
        `;
        const puroks = await executeQuery(query, allowedPuroks);

        res.json({
            success: true,
            data: puroks,
            pagination: {
                page,
                per_page: perPage,
                total,
                total_pages: totalPages
            }
        });
    } catch (error) {
        console.error('Error fetching puroks:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch puroks',
            message: error.message
        });
    }
});


app.get('/api/puroks/all', async (req, res) => {
    try {
        // Allowed puroks list (must match config/barangay-config.php)
        const allowedPuroks = [
            'Tamia 1', 'Tamia 2', 'Marba', 'Magay', '8-7',
            'Centro 1', 'Centro 2', 'Centro 3', 'Caguisocan',
            'Bagnan', 'Doña Concepcion', 'Tulay', 'Dalaguit', 'Cuanas'
        ];
        
        const placeholders = allowedPuroks.map(() => '?').join(',');
        const query = `
            SELECT purokID, purok_name, araw, purok_pres, purok_code 
            FROM puroks 
            WHERE purok_name IN (${placeholders})
            ORDER BY purok_name
        `;
        const puroks = await executeQuery(query, allowedPuroks);

        res.json({
            success: true,
            data: puroks,
            count: puroks.length
        });
    } catch (error) {
        console.error('Error fetching all puroks:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch puroks',
            message: error.message
        });
    }
});

/**
 * GET /api/puroks/:id
 * Get a single purok by ID
 */
app.get('/api/puroks/:id', async (req, res) => {
    try {
        const purokID = parseInt(req.params.id);

        if (isNaN(purokID)) {
            return res.status(400).json({
                success: false,
                error: 'Invalid purok ID'
            });
        }

        const query = `
            SELECT purokID, purok_name, araw, purok_pres, purok_code 
            FROM puroks 
            WHERE purokID = ?
        `;
        const results = await executeQuery(query, [purokID]);

        if (results.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Purok not found'
            });
        }

        res.json({
            success: true,
            data: results[0]
        });
    } catch (error) {
        console.error('Error fetching purok:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch purok',
            message: error.message
        });
    }
});

/**
 * GET /api/puroks/search?q=searchTerm
 * Search puroks by name or code (only allowed puroks)
 */
app.get('/api/puroks/search', async (req, res) => {
    try {
        const searchTerm = req.query.q || '';

        if (!searchTerm.trim()) {
            return res.status(400).json({
                success: false,
                error: 'Search term is required'
            });
        }

        // Allowed puroks list (must match config/barangay-config.php)
        const allowedPuroks = [
            'Tamia 1', 'Tamia 2', 'Marba', 'Magay', '8-7',
            'Centro 1', 'Centro 2', 'Centro 3', 'Caguisocan',
            'Bagnan', 'Doña Concepcion', 'Tulay', 'Dalaguit', 'Cuanas'
        ];

        const placeholders = allowedPuroks.map(() => '?').join(',');
        const searchPattern = `%${searchTerm}%`;
        const query = `
            SELECT purokID, purok_name, araw, purok_pres, purok_code 
            FROM puroks 
            WHERE purok_name IN (${placeholders})
            AND (purok_name LIKE ? OR purok_code LIKE ?)
            ORDER BY purok_name
        `;
        const puroks = await executeQuery(query, [...allowedPuroks, searchPattern, searchPattern]);

        res.json({
            success: true,
            data: puroks,
            count: puroks.length,
            search_term: searchTerm
        });
    } catch (error) {
        console.error('Error searching puroks:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to search puroks',
            message: error.message
        });
    }
});

/**
 * GET /api/puroks/verify/:code
 * Verify a purok code and return purok information
 */
app.get('/api/puroks/verify/:code', async (req, res) => {
    try {
        const purokCode = req.params.code;

        if (!purokCode) {
            return res.status(400).json({
                success: false,
                error: 'Purok code is required'
            });
        }

        const query = `
            SELECT purokID, purok_name, purok_code 
            FROM puroks 
            WHERE purok_code = ?
        `;
        const results = await executeQuery(query, [purokCode]);

        if (results.length === 0) {
            return res.json({
                success: false,
                valid: false,
                message: 'Invalid purok code'
            });
        }

        res.json({
            success: true,
            valid: true,
            data: results[0]
        });
    } catch (error) {
        console.error('Error verifying purok code:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to verify purok code',
            message: error.message
        });
    }
});

// Health check endpoint
app.get('/api/health', (req, res) => {
    res.json({
        success: true,
        message: 'API is running',
        timestamp: new Date().toISOString()
    });
});

// Root endpoint
app.get('/', (req, res) => {
    res.json({
        message: 'Barangay Population Management System API',
        version: '1.0.0',
        endpoints: {
            puroks: {
                getAll: 'GET /api/puroks?page=1&per_page=10',
                getAllWithoutPagination: 'GET /api/puroks/all',
                getById: 'GET /api/puroks/:id',
                search: 'GET /api/puroks/search?q=searchTerm',
                verifyCode: 'GET /api/puroks/verify/:code'
            },
            health: 'GET /api/health'
        }
    });
});

// Error handling middleware
app.use((err, req, res, next) => {
    console.error('Error:', err);
    res.status(500).json({
        success: false,
        error: 'Internal server error',
        message: err.message
    });
});

// 404 handler
app.use((req, res) => {
    res.status(404).json({
        success: false,
        error: 'Endpoint not found'
    });
});

// Start server
app.listen(PORT, () => {
    console.log(`🚀 Server is running on http://localhost:${PORT}`);
    console.log(`📡 API endpoints available at http://localhost:${PORT}/api`);
});

module.exports = app;

