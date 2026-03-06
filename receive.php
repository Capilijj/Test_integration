<?php
/**
 * Driver Receiver - Laptop 2 (RECEIVER)
 * Receives and saves driver information from Laptop 1
 */

// Create data directory if it doesn't exist
$data_dir = __DIR__ . '/drivers_data';
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0777, true);
}

$drivers_file = $data_dir . '/drivers.json';
$log_file = $data_dir . '/receive_log.txt';

// Initialize drivers file if it doesn't exist
if (!file_exists($drivers_file)) {
    file_put_contents($drivers_file, json_encode([]));
}

// Function to load drivers
function load_drivers() {
    global $drivers_file;
    $content = file_get_contents($drivers_file);
    return json_decode($content, true) ?? [];
}

// Function to save drivers
function save_drivers($drivers) {
    global $drivers_file;
    file_put_contents($drivers_file, json_encode($drivers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Function to log messages
function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Handle API requests (JSON POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    // Get request body
    $body = file_get_contents('php://input');
    
    // Log the incoming request
    log_message("Incoming request: " . substr($body, 0, 100));
    
    // Decode JSON
    $input = json_decode($body, true);
    
    if (!$input) {
        log_message("ERROR: Invalid JSON received");
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'code' => 400,
            'message' => 'Invalid JSON'
        ]);
        exit;
    }
    
    $errors = [];
    
    // Validate input
    if (empty($input['driver_name'])) {
        $errors[] = 'Driver name is required';
    } elseif (strlen($input['driver_name']) < 2) {
        $errors[] = 'Driver name must be at least 2 characters';
    }
    
    if (empty($input['license_number'])) {
        $errors[] = 'License number is required';
    } elseif (strlen($input['license_number']) < 3) {
        $errors[] = 'License number must be at least 3 characters';
    }
    
    if (!empty($errors)) {
        log_message("Validation error: " . implode(', ', $errors));
        http_response_code(422);
        echo json_encode([
            'status' => 'error',
            'code' => 422,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Create driver record
    $driver = [
        'id' => uniqid('DRV_', true),
        'driver_name' => htmlspecialchars($input['driver_name']),
        'license_number' => htmlspecialchars($input['license_number']),
        'sent_from' => htmlspecialchars($input['sent_from'] ?? 'Unknown'),
        'sent_at' => $input['sent_at'] ?? date('Y-m-d H:i:s'),
        'received_at' => date('Y-m-d H:i:s'),
        'received_ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'timestamp' => time()
    ];
    
    // Load existing drivers
    $drivers = load_drivers();
    
    // Add new driver to the beginning
    array_unshift($drivers, $driver);
    
    // Keep only last 100 drivers
    if (count($drivers) > 100) {
        $drivers = array_slice($drivers, 0, 100);
    }
    
    // Save drivers
    save_drivers($drivers);
    
    // Log success
    log_message("SUCCESS: Driver received from {$input['sent_from']} - {$driver['driver_name']} ({$driver['license_number']})");
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'code' => 200,
        'message' => 'Driver information received successfully',
        'data' => $driver
    ]);
    exit;
}

// Handle web interface (GET)
$drivers = load_drivers();
$total_received = count($drivers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Receiver - Laptop 2</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 20px;
        }
        
        .status-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .status-item {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .status-item .number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .status-item .label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .main-panel {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        
        .main-panel h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .control-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .control-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-refresh {
            background: #f5576c;
            color: white;
        }
        
        .btn-refresh:hover {
            background: #f093fb;
        }
        
        .btn-clear {
            background: #f0f0f0;
            color: #666;
        }
        
        .btn-clear:hover {
            background: #e0e0e0;
        }
        
        .drivers-list {
            max-height: 800px;
            overflow-y: auto;
        }
        
        .driver-card {
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);
            border-left: 4px solid #f5576c;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: start;
            transition: all 0.3s;
        }
        
        .driver-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.2);
        }
        
        .driver-info {
            flex: 1;
        }
        
        .driver-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .driver-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 8px;
        }
        
        .driver-field {
            color: #666;
            font-size: 0.95em;
        }
        
        .driver-field .label {
            color: #f5576c;
            font-weight: 600;
        }
        
        .driver-meta {
            text-align: right;
            padding: 15px;
            background: white;
            border-radius: 6px;
            font-size: 0.85em;
            border: 1px solid #f0f0f0;
        }
        
        .driver-id {
            color: #999;
            font-family: monospace;
            font-size: 0.8em;
            margin-bottom: 8px;
        }
        
        .driver-source {
            color: #f5576c;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            color: #999;
            padding: 60px 20px;
        }
        
        .empty-state .emoji {
            font-size: 4em;
            margin-bottom: 15px;
        }
        
        .info-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .info-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .info-box {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85em;
            color: #333;
            line-height: 1.6;
            margin-bottom: 15px;
            word-break: break-all;
        }
        
        .info-box:last-child {
            margin-bottom: 0;
        }
        
        .drivers-list::-webkit-scrollbar {
            width: 8px;
        }
        
        .drivers-list::-webkit-scrollbar-track {
            background: #f0f0f0;
        }
        
        .drivers-list::-webkit-scrollbar-thumb {
            background: #f5576c;
            border-radius: 10px;
        }
        
        .drivers-list::-webkit-scrollbar-thumb:hover {
            background: #f093fb;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📥 Driver Receiver</h1>
            <p>Laptop 2 - Receiving drivers from Laptop 1</p>
            
            <div class="status-bar">
                <div class="status-item">
                    <div class="number"><?php echo $total_received; ?></div>
                    <div class="label">Total Received</div>
                </div>
                <div class="status-item">
                    <div class="number"><?php echo date('H:i:s'); ?></div>
                    <div class="label">Server Time</div>
                </div>
                <div class="status-item">
                    <div class="number"><?php echo gethostname(); ?></div>
                    <div class="label">Computer Name</div>
                </div>
            </div>
        </div>
        
        <!-- Main Panel -->
        <div class="main-panel">
            <h2>📦 Received Drivers</h2>
            
            <div class="control-buttons">
                <button class="btn-refresh" onclick="location.reload()">🔄 Refresh</button>
                <button class="btn-clear" onclick="if(confirm('Delete all drivers?')) { window.location.href='?clear=1'; }">🗑️ Clear All</button>
            </div>
            
            <div class="drivers-list">
                <?php if (empty($drivers)): ?>
                    <div class="empty-state">
                        <div class="emoji">📭</div>
                        <p style="font-size: 1.2em; margin-bottom: 10px;">No drivers received yet</p>
                        <p>Waiting for Laptop 1 to send driver information...</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($drivers as $driver): ?>
                        <div class="driver-card">
                            <div class="driver-info">
                                <div class="driver-name">🚗 <?php echo $driver['driver_name']; ?></div>
                                <div class="driver-row">
                                    <div class="driver-field">
                                        <span class="label">📄 License:</span><br><?php echo $driver['license_number']; ?>
                                    </div>
                                    <div class="driver-field">
                                        <span class="label">💻 From:</span><br><?php echo $driver['sent_from']; ?>
                                    </div>
                                </div>
                                <div class="driver-row">
                                    <div class="driver-field">
                                        <span class="label">📤 Sent:</span><br><?php echo $driver['sent_at']; ?>
                                    </div>
                                    <div class="driver-field">
                                        <span class="label">📥 Received:</span><br><?php echo $driver['received_at']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="driver-meta">
                                <div class="driver-id">ID:<br><?php echo substr($driver['id'], -12); ?></div>
                                <div class="driver-source">✓ Received</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Info Section -->
        <div class="info-section">
            <h3>🔧 Server Information</h3>
            <div class="info-box">
                <strong>📂 Data Directory:</strong><br><?php echo realpath($data_dir); ?>
            </div>
            <div class="info-box">
                <strong>💾 Storage Method:</strong><br>File-based JSON (drivers_data/drivers.json)
            </div>
            <div class="info-box">
                <strong>📊 Total Records:</strong><br><?php echo $total_received; ?> drivers stored
            </div>
            <div class="info-box">
                <strong>🖥️ This Server:</strong><br>
                Computer: <?php echo gethostname(); ?><br>
                IP: <?php echo $_SERVER['SERVER_ADDR'] ?? getenv('SERVER_ADDR') ?? 'N/A'; ?><br>
                Port: <?php echo $_SERVER['SERVER_PORT']; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 5 seconds
        setTimeout(() => location.reload(), 5000);
    </script>
</body>
</html>

<?php
// Handle clear action
if (isset($_GET['clear']) && $_GET['clear'] == 1) {
    file_put_contents($drivers_file, json_encode([]));
    log_message("All drivers cleared by user");
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
