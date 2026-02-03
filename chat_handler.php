<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$current_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'send':
        $message = $_POST['message'] ?? '';
        $recipient_id = $_POST['recipient_id'] ?? 18; // Default to an admin ID if needed
        
        if (!empty($message)) {
            $sql = "INSERT INTO chat_messages (sender_id, recipient_id, message, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $current_id, $recipient_id, $message);
            echo json_encode(['success' => $stmt->execute()]);
        }
        break;

    case 'fetch_messages':
        // Students can fetch messages where they are either sender OR recipient
        $sql = "SELECT *, DATE_FORMAT(created_at, '%h:%i %p') as formatted_time 
                FROM chat_messages 
                WHERE sender_id = ? OR recipient_id = ? 
                ORDER BY created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $current_id, $current_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $class = ($row['sender_id'] == $current_id) ? 'sent' : 'received';
            echo "<div class='msg $class'>" . htmlspecialchars($row['message']) . 
                 "<div class='msg-time'>{$row['formatted_time']}</div></div>";
        }
        break;

    case 'list_users':
        // ONLY ADMINS can see the list of all users
        if ($user_role !== 'admin' && $user_role !== 'super_admin') {
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $sql = "SELECT id, name FROM users WHERE role = 'user'";
        $res = $conn->query($sql);
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;
}