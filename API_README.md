# BPMS Puroks API

A Node.js + Express REST API for fetching puroks from the database, replacing XML-based data retrieval.

## Setup

### 1. Install Dependencies

```bash
npm install
```

### 2. Configure Environment Variables

Create a `.env` file in the root directory with the following variables:

```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=031405
DB_NAME=baranggay_population_management
PORT=3000
```

### 3. Start the Server

```bash
# Production mode
npm start

# Development mode (with auto-reload)
npm run dev
```

The server will start on `http://localhost:3000` (or the port specified in `.env`).

## API Endpoints

### Base URL
```
http://localhost:3000/api
```

### 1. Get All Puroks (Paginated)
```
GET /api/puroks?page=1&per_page=10
```

**Query Parameters:**
- `page` (optional, default: 1) - Page number
- `per_page` (optional, default: 10) - Items per page

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "purokID": 1,
      "purok_name": "Purok 1",
      "araw": "2025-01-15",
      "purok_pres": "John Doe",
      "purok_code": "PUR001"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 25,
    "total_pages": 3
  }
}
```

### 2. Get All Puroks (No Pagination)
```
GET /api/puroks/all
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "count": 25
}
```

### 3. Get Purok by ID
```
GET /api/puroks/:id
```

**Example:**
```
GET /api/puroks/1
```

**Response:**
```json
{
  "success": true,
  "data": {
    "purokID": 1,
    "purok_name": "Purok 1",
    "araw": "2025-01-15",
    "purok_pres": "John Doe",
    "purok_code": "PUR001"
  }
}
```

### 4. Search Puroks
```
GET /api/puroks/search?q=searchTerm
```

**Query Parameters:**
- `q` (required) - Search term (searches in purok_name and purok_code)

**Example:**
```
GET /api/puroks/search?q=Purok
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "count": 5,
  "search_term": "Purok"
}
```

### 5. Verify Purok Code
```
GET /api/puroks/verify/:code
```

**Example:**
```
GET /api/puroks/verify/PUR001
```

**Response (Valid):**
```json
{
  "success": true,
  "valid": true,
  "data": {
    "purokID": 1,
    "purok_name": "Purok 1",
    "purok_code": "PUR001"
  }
}
```

**Response (Invalid):**
```json
{
  "success": false,
  "valid": false,
  "message": "Invalid purok code"
}
```

### 6. Health Check
```
GET /api/health
```

**Response:**
```json
{
  "success": true,
  "message": "API is running",
  "timestamp": "2025-01-15T10:30:00.000Z"
}
```

## Usage Examples

### JavaScript (Fetch API)

```javascript
// Get all puroks
fetch('http://localhost:3000/api/puroks/all')
  .then(response => response.json())
  .then(data => {
    console.log(data.data);
  })
  .catch(error => console.error('Error:', error));

// Get purok by ID
fetch('http://localhost:3000/api/puroks/1')
  .then(response => response.json())
  .then(data => {
    console.log(data.data);
  });

// Search puroks
fetch('http://localhost:3000/api/puroks/search?q=Purok')
  .then(response => response.json())
  .then(data => {
    console.log(data.data);
  });

// Verify purok code
fetch('http://localhost:3000/api/puroks/verify/PUR001')
  .then(response => response.json())
  .then(data => {
    if (data.valid) {
      console.log('Valid code:', data.data);
    } else {
      console.log('Invalid code');
    }
  });
```

### jQuery

```javascript
// Get all puroks
$.ajax({
  url: 'http://localhost:3000/api/puroks/all',
  method: 'GET',
  success: function(data) {
    console.log(data.data);
  },
  error: function(error) {
    console.error('Error:', error);
  }
});
```

### PHP (cURL)

```php
$ch = curl_init('http://localhost:3000/api/puroks/all');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
print_r($data['data']);
```

## Error Responses

All endpoints return errors in the following format:

```json
{
  "success": false,
  "error": "Error message",
  "message": "Detailed error message"
}
```

Common HTTP status codes:
- `200` - Success
- `400` - Bad Request (invalid parameters)
- `404` - Not Found (purok not found)
- `500` - Internal Server Error

## CORS

The API has CORS enabled, allowing requests from any origin. This can be configured in `server.js` if you need to restrict access.

## Notes

- The API uses connection pooling for efficient database connections
- All dates are returned in the format stored in the database
- Search is case-insensitive and uses LIKE queries
- The API returns JSON responses only (no XML)

