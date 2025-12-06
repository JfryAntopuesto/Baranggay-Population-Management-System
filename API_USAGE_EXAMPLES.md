# API Usage Examples for Frontend

This document provides practical examples of how to use the Purok API in your frontend code.

## Basic Setup

### Include the API Utility

```html
<!-- In your HTML file -->
<script src="../js/purok-api.js"></script>
```

### Configure API Base URL (if needed)

```javascript
// In purok-api.js, update if your API runs on a different port/domain
const PurokAPI = {
    baseURL: 'http://localhost:3000/api', // Change this if needed
    // ...
};
```

## Example 1: Populate Dropdown with Puroks

```html
<select id="purokSelect">
    <option value="">Loading...</option>
</select>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const select = document.getElementById('purokSelect');
    
    try {
        const puroks = await PurokAPI.getAllPuroks();
        
        select.innerHTML = '<option value="">-- Select Purok --</option>';
        puroks.forEach(purok => {
            const option = document.createElement('option');
            option.value = purok.purokID;
            option.textContent = purok.purok_name;
            select.appendChild(option);
        });
    } catch (error) {
        select.innerHTML = '<option value="">Error loading puroks</option>';
        console.error('Error:', error);
    }
});
</script>
```

## Example 2: Display Purok Details

```html
<div id="purokDetails">
    <div class="loading">Loading...</div>
</div>

<script>
async function loadPurokDetails(purokID) {
    const container = document.getElementById('purokDetails');
    
    try {
        const purok = await PurokAPI.getPurokById(purokID);
        
        container.innerHTML = `
            <h2>${escapeHtml(purok.purok_name)}</h2>
            <p><strong>Code:</strong> ${escapeHtml(purok.purok_code)}</p>
            <p><strong>President:</strong> ${escapeHtml(purok.purok_pres)}</p>
            <p><strong>Date:</strong> ${formatDate(purok.araw)}</p>
        `;
    } catch (error) {
        container.innerHTML = `
            <div class="error">
                Failed to load purok: ${error.message}
            </div>
        `;
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString();
}

// Usage
loadPurokDetails(1);
</script>
```

## Example 3: Search Puroks

```html
<input type="text" id="searchInput" placeholder="Search puroks...">
<div id="searchResults"></div>

<script>
let searchTimeout;

document.getElementById('searchInput').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    // Debounce search
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        if (query.length < 2) {
            document.getElementById('searchResults').innerHTML = '';
            return;
        }
        
        try {
            const results = await PurokAPI.searchPuroks(query);
            displaySearchResults(results);
        } catch (error) {
            console.error('Search error:', error);
        }
    }, 300);
});

function displaySearchResults(puroks) {
    const container = document.getElementById('searchResults');
    
    if (puroks.length === 0) {
        container.innerHTML = '<p>No puroks found</p>';
        return;
    }
    
    const html = puroks.map(purok => `
        <div class="purok-item">
            <h3>${escapeHtml(purok.purok_name)}</h3>
            <p>Code: ${escapeHtml(purok.purok_code)}</p>
        </div>
    `).join('');
    
    container.innerHTML = html;
}
</script>
```

## Example 4: Verify Purok Code

```html
<input type="text" id="purokCode" placeholder="Enter purok code">
<button onclick="verifyCode()">Verify</button>
<div id="verificationResult"></div>

<script>
async function verifyCode() {
    const code = document.getElementById('purokCode').value;
    const resultDiv = document.getElementById('verificationResult');
    
    if (!code) {
        resultDiv.innerHTML = '<p class="error">Please enter a code</p>';
        return;
    }
    
    resultDiv.innerHTML = '<p>Verifying...</p>';
    
    try {
        const verification = await PurokAPI.verifyPurokCode(code);
        
        if (verification.valid) {
            resultDiv.innerHTML = `
                <div class="success">
                    <p>✓ Valid code!</p>
                    <p>Purok: ${escapeHtml(verification.data.purok_name)}</p>
                </div>
            `;
        } else {
            resultDiv.innerHTML = '<p class="error">Invalid purok code</p>';
        }
    } catch (error) {
        resultDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
    }
}
</script>
```

## Example 5: Paginated Purok List

```html
<div id="purokList"></div>
<div id="pagination"></div>

<script>
let currentPage = 1;
const perPage = 10;

async function loadPuroks(page = 1) {
    const listDiv = document.getElementById('purokList');
    listDiv.innerHTML = '<p>Loading...</p>';
    
    try {
        const response = await PurokAPI.getPuroksPaginated(page, perPage);
        
        // Display puroks
        if (response.data.length === 0) {
            listDiv.innerHTML = '<p>No puroks found</p>';
            return;
        }
        
        const html = response.data.map(purok => `
            <div class="purok-card">
                <h3>${escapeHtml(purok.purok_name)}</h3>
                <p>Code: ${escapeHtml(purok.purok_code)}</p>
            </div>
        `).join('');
        
        listDiv.innerHTML = html;
        
        // Display pagination
        renderPagination(response.pagination);
        
    } catch (error) {
        listDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
    }
}

function renderPagination(pagination) {
    const paginationDiv = document.getElementById('pagination');
    
    let html = '<div class="pagination">';
    
    // Previous button
    if (pagination.page > 1) {
        html += `<button onclick="loadPuroks(${pagination.page - 1})">Previous</button>`;
    }
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        html += `<button 
            onclick="loadPuroks(${i})" 
            class="${i === pagination.page ? 'active' : ''}"
        >${i}</button>`;
    }
    
    // Next button
    if (pagination.page < pagination.total_pages) {
        html += `<button onclick="loadPuroks(${pagination.page + 1})">Next</button>`;
    }
    
    html += '</div>';
    paginationDiv.innerHTML = html;
}

// Load first page on page load
loadPuroks(1);
</script>
```

## Example 6: Complete Purok Management Component

```html
<div class="purok-manager">
    <h2>Purok Management</h2>
    
    <!-- Search -->
    <input type="text" id="search" placeholder="Search puroks...">
    
    <!-- Purok List -->
    <div id="purokList"></div>
    
    <!-- Selected Purok Details -->
    <div id="purokDetails" style="display: none;">
        <h3 id="purokName"></h3>
        <p id="purokCode"></p>
        <p id="purokPresident"></p>
    </div>
</div>

<script>
let allPuroks = [];
let selectedPurok = null;

// Load all puroks on page load
document.addEventListener('DOMContentLoaded', async function() {
    await loadAllPuroks();
    setupSearch();
});

async function loadAllPuroks() {
    try {
        allPuroks = await PurokAPI.getAllPuroks();
        renderPurokList(allPuroks);
    } catch (error) {
        document.getElementById('purokList').innerHTML = 
            `<p class="error">Failed to load puroks: ${error.message}</p>`;
    }
}

function renderPurokList(puroks) {
    const container = document.getElementById('purokList');
    
    if (puroks.length === 0) {
        container.innerHTML = '<p>No puroks found</p>';
        return;
    }
    
    const html = puroks.map(purok => `
        <div class="purok-item" onclick="selectPurok(${purok.purokID})">
            <h4>${escapeHtml(purok.purok_name)}</h4>
            <p>Code: ${escapeHtml(purok.purok_code)}</p>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

async function selectPurok(purokID) {
    try {
        selectedPurok = await PurokAPI.getPurokById(purokID);
        displayPurokDetails(selectedPurok);
    } catch (error) {
        console.error('Error loading purok details:', error);
    }
}

function displayPurokDetails(purok) {
    document.getElementById('purokName').textContent = purok.purok_name;
    document.getElementById('purokCode').textContent = `Code: ${purok.purok_code}`;
    document.getElementById('purokPresident').textContent = `President: ${purok.purok_pres}`;
    document.getElementById('purokDetails').style.display = 'block';
}

function setupSearch() {
    const searchInput = document.getElementById('search');
    let searchTimeout;
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = e.target.value.trim();
            
            if (query) {
                PurokAPI.searchPuroks(query)
                    .then(results => renderPurokList(results))
                    .catch(error => console.error('Search error:', error));
            } else {
                renderPurokList(allPuroks);
            }
        }, 300);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
```

## Error Handling Best Practices

```javascript
async function safeLoadPuroks() {
    try {
        const puroks = await PurokAPI.getAllPuroks();
        return { success: true, data: puroks };
    } catch (error) {
        // Log error for debugging
        console.error('Failed to load puroks:', error);
        
        // Return error info for UI
        return {
            success: false,
            error: error.message || 'Unknown error occurred'
        };
    }
}

// Usage
const result = await safeLoadPuroks();
if (result.success) {
    // Use result.data
} else {
    // Show error message to user
    showError(result.error);
}
```

## Loading States

```javascript
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    element.innerHTML = '<div class="loading">Loading...</div>';
}

function hideLoading(elementId, content) {
    const element = document.getElementById(elementId);
    element.innerHTML = content;
}

// Usage
showLoading('purokList');
const puroks = await PurokAPI.getAllPuroks();
hideLoading('purokList', renderPurokList(puroks));
```

## Notes

- Always handle errors gracefully
- Show loading states during API calls
- Use `escapeHtml()` to prevent XSS attacks
- Debounce search inputs for better performance
- Cache data when appropriate to reduce API calls

