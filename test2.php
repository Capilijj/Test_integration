<?php
/**
 * Driver Receiver - Laptop 2 (RECEIVER)
 * Added Feature: Information Request System
 */

// Create data directory if it doesn't exist
$data_dir = __DIR__ . '/drivers_data';
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0777, true);
}

$drivers_file = $data_dir . '/drivers.json';
$log_file = $data_dir . '/receive_log.txt';
$request_file = $data_dir . '/pending_request.json'; // New file for requests

// Initialize files if they don't exist
if (!file_exists($drivers_file)) file_put_contents($drivers_file, json_encode([]));
if (!file_exists($request_file)) file_put_contents($request_file, json_encode(['pending' => false]));

function load_drivers() {
    global $drivers_file;
    return json_decode(file_get_contents($drivers_file), true) ?? [];
}

function save_drivers($drivers) {
    global $drivers_file;
    file_put_contents($drivers_file, json_encode($drivers, JSON_PRETTY_PRINT));
}

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// --- NEW FEATURE: HANDLE REQUEST ACTIONS ---
if (isset($_POST['action']) && $_POST['action'] === 'create_request') {
    $request_data = [
        'pending' => true,
        'message' => htmlspecialchars($_POST['request_msg']),
        'requested_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($request_file, json_encode($request_data));
    log_message("REQUEST SENT: " . $_POST['request_msg']);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
    exit;
}

// --- NEW FEATURE: API FOR SENDER TO CHECK REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_request'])) {
    header('Content-Type: application/json');
    $req = json_decode(file_get_contents($request_file), true);
    echo json_encode($req);
    
    // Once the sender reads it, we could mark it as not pending, 
    // but usually, we wait for the sender to "fulfill" it.
    exit;
}

// Handle API POST from Sender (Existing code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $body = file_get_contents('php://input');
    $input = json_decode($body, true);
    
    if ($input) {
        $driver = [
            'id' => uniqid('DRV_', true),
            'driver_name' => htmlspecialchars($input['driver_name']),
            'license_number' => htmlspecialchars($input['license_number']),
            'sent_from' => htmlspecialchars($input['sent_from'] ?? 'Unknown'),
            'sent_at' => $input['sent_at'] ?? date('Y-m-d H:i:s'),
            'received_at' => date('Y-m-d H:i:s'),
            'received_ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ];
        
        $drivers = load_drivers();
        array_unshift($drivers, $driver);
        save_drivers(array_slice($drivers, 0, 100));

        // Auto-clear pending request once sender sends info
        file_put_contents($request_file, json_encode(['pending' => false]));
        
        echo json_encode(['status' => 'success', 'data' => $driver]);
        exit;
    }
}

// UI Data
$drivers = load_drivers();
$request_status = json_decode(file_get_contents($request_file), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Receiver - Laptop 2</title>
    <style>
        /* ... Keep your existing CSS ... */
        body { font-family: sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .btn-blue { background: #3498db; }
        .request-box { background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin-bottom: 20px; }
        input[type="text"] { width: 70%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .status-badge { padding: 5px 10px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .bg-pending { background: #ffc107; color: #856404; }
        .bg-clear { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📥 Driver Receiver</h1>

        <div class="card">
            <h3>📢 Request Info from Sender</h3>
            <p style="font-size: 0.9em; color: #666;">Utusan ang Laptop 1 na mag-send ng data.</p>
            <form method="POST" style="margin-top: 15px;">
                <input type="hidden" name="action" value="create_request">
                <input type="text" name="request_msg" placeholder="Anong info ang kailangan? (e.g. Send latest driver)" required>
                <button type="submit" class="btn btn-blue">Send Request</button>
            </form>

            <?php if ($request_status['pending']): ?>
                <div class="request-box" style="margin-top: 15px;">
                    <strong>Current Pending Request:</strong><br>
                    "<?php echo $request_status['message']; ?>" 
                    <span class="status-badge bg-pending">Waiting for Sender...</span>
                </div>
            <?php else: ?>
                <p style="margin-top: 10px;"><span class="status-badge bg-clear">No Pending Requests</span></p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>📦 Received Drivers (Total: <?php echo count($drivers); ?>)</h2>
            <hr>
            <?php foreach ($drivers as $d): ?>
                <div style="border-bottom: 1px solid #eee; padding: 10px 0;">
                    <strong>🚗 <?php echo $d['driver_name']; ?></strong> - <?php echo $d['license_number']; ?><br>
                    <small>From: <?php echo $d['sent_from']; ?> | Rec: <?php echo $d['received_at']; ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Refresh every 10 seconds to see if Laptop 1 responded
        setTimeout(() => { location.reload(); }, 10000);
    </script>
</body>
</html>