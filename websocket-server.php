<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/database/database-connection.php';
require_once __DIR__ . '/database/database-operations.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Http\Server as ReactHttpServer;
use React\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use React\Socket\Server as SocketServer;

class WebSocketServer implements \Ratchet\MessageComponentInterface {
    protected $clients;
    protected $userConnections;
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->db = new DatabaseOperations($GLOBALS['conn']);
        echo "WebSocket server started\n";
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        echo "Received message from {$from->resourceId}: " . $msg . "\n";
        $data = json_decode($msg, true);

        if ($data === null) {
            echo "Failed to decode JSON message from {$from->resourceId}\n";
            return;
        }

        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'register':
                    $this->handleRegistration($from, $data);
                    break;
                    
                case 'notification_read':
                    $this->handleNotificationRead($from, $data);
                    break;
                    
                default:
                    echo "Unknown message type from {$from->resourceId}: {$data['type']}\n";
            }
        } else {
            echo "Message missing type from {$from->resourceId}: " . json_encode($data) . "\n";
        }
    }
    
    protected function handleRegistration(\Ratchet\ConnectionInterface $from, $data) {
        if (isset($data['userId'])) {
            $userId = $data['userId'];
            $this->userConnections[$userId] = $from;
            $from->userId = $userId; // Store user ID on the connection object
            echo "User {$userId} registered on connection {$from->resourceId}\n";
            
            // Send a simple response back to the client to confirm registration
            $from->send(json_encode(['type' => 'registration_success', 'message' => 'Registration successful']));
            echo "Sent registration success message to {$from->resourceId}\n";

            // **Fetch and send initial dashboard data to the newly registered client**
            try {
                // Get user notifications
                $userNotifications = $this->getUserNotifications($userId);
                if (!empty($userNotifications)) {
                    $from->send(json_encode([
                        'type' => 'notification_update', 
                        'notifications' => $userNotifications
                    ]));
                }
                
                $dashboardData = [
                    'pendingRequests' => $this->db->getRequestCountByStatus('pending'),
                    'pendingAppointments' => $this->db->getUpcomingAppointmentsCount(),
                    'pendingComplaints' => $this->db->getRequestCountByStatus('pending'),
                    'recentAnnouncements' => $this->db->getAnnouncements(3)
                ];
                $from->send(json_encode(['type' => 'dashboard_update', 'data' => $dashboardData]));
                echo "Sent initial dashboard data to user {$userId} on connection {$from->resourceId}\n";
            } catch (Exception $e) {
                echo "Error sending initial dashboard data to user {$userId}: {$e->getMessage()}\n";
                error_log("WebSocket Error sending initial data to user {$userId}: " . $e->getMessage());
            }
        } else {
            echo "Registration message missing userId from {$from->resourceId}\n";
        }
    }
    
    protected function handleNotificationRead(\Ratchet\ConnectionInterface $from, $data) {
        if (!isset($data['notifID']) || !isset($data['userId'])) {
            echo "Notification read message missing required fields from {$from->resourceId}\n";
            return;
        }
        
        $notifID = $data['notifID'];
        $userId = $data['userId'];
        
        echo "User {$userId} marked notification {$notifID} as read\n";
        
        try {
            // Get updated notifications for this user
            $updatedNotifications = $this->getUserNotifications($userId);
            
            // Send updated notifications back to the user who made the change
            $this->sendToUser($userId, [
                'type' => 'notification_update',
                'notifications' => $updatedNotifications
            ]);
            
            echo "Sent updated notifications to user {$userId}\n";
        } catch (Exception $e) {
            echo "Error updating notifications for user {$userId}: {$e->getMessage()}\n";
            error_log("WebSocket Error updating notifications: " . $e->getMessage());
        }
    }
    
    protected function getUserNotifications($userId) {
        try {
            // Get both unread and read notifications
            $unreadNotifications = $this->db->getUserNotifications($userId);
            $readNotifications = $this->db->getReadNotifications($userId);
            
            // Combine notifications and mark their status
            $allNotifications = array_merge(
                array_map(function($n) { 
                    $n['is_read'] = false; 
                    return $n; 
                }, $unreadNotifications),
                array_map(function($n) { 
                    $n['is_read'] = true; 
                    return $n; 
                }, $readNotifications)
            );
            
            // Sort by datetime, most recent first
            usort($allNotifications, function($a, $b) {
                return strtotime($b['datetime']) - strtotime($a['datetime']);
            });
            
            return $allNotifications;
        } catch (Exception $e) {
            error_log("Error getting user notifications: " . $e->getMessage());
            return [];
        }
    }

    public function onClose(\Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        // Remove user connection using the stored userId if available
        if (isset($conn->userId)) {
            unset($this->userConnections[$conn->userId]);
            echo "User {$conn->userId} on connection {$conn->resourceId} disconnected\n";
        } else {
            echo "Connection {$conn->resourceId} has disconnected (user ID not set)\n";
        }
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred on connection {$conn->resourceId}: {$e->getMessage()}\n";
        // Log stack trace for detailed debugging
        error_log("WebSocket Error on connection {$conn->resourceId}: {$e->getMessage()}\nStack trace:\n" . $e->getTraceAsString());
        $conn->close();
    }

    public function broadcast($message) {
        foreach ($this->clients as $client) {
            $client->send(json_encode($message));
        }
    }

    public function sendToUser($userId, $message) {
        if (isset($this->userConnections[$userId])) {
            $this->userConnections[$userId]->send(json_encode($message));
        }
    }
}

// Create event loop
$loop = Factory::create();

// Create WebSocket server
$webSocket = new WebSocketServer();

// Create server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $webSocket
        )
    ),
    8080,
    '0.0.0.0',
    $loop
);

// Create HTTP server for notifications
$httpServer = new ReactHttpServer(function (ServerRequestInterface $request) use ($webSocket) {
    if ($request->getMethod() === 'POST') {
        $data = json_decode((string) $request->getBody(), true);
        
        // Broadcast to all connected clients
        $webSocket->broadcast($data);
        
        return new Response(200, ['Content-Type' => 'application/json'], json_encode(['success' => true]));
    }
    
    return new Response(404, ['Content-Type' => 'application/json'], json_encode(['error' => 'Not found']));
});

// Start HTTP server
$socket = new SocketServer('0.0.0.0:8081', $loop);
$httpServer->listen($socket);

echo "WebSocket server started on port 8080\n";
echo "HTTP server started on port 8081\n";

// Run the server
$server->run(); 