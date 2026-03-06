<?php
/**
 * Driver Sender - Laptop 1 (SENDER)
 * Sends driver information to Laptop 2 via network
 */

// Configuration
$receiver_ip = '10.225.157.110';
$receiver_port = '3000';
$receiver_url = "http://{$receiver_ip}:{$receiver_port}/receive.php";
$timeout = 10;

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_name = $_POST['driver_name'] ?? '';
    $license_number = $_POST['license_number'] ?? '';
    
    if (empty($driver_name) || empty($license_number)) {
        $result = [
            'status' => 'error',
            'message' => 'Driver name and license number are required'
        ];
    } else {
        // Prepare data
        $data = [
            'driver_name' => $driver_name,
            'license_number' => $license_number,
            'sent_from' => gethostname() ?: 'Laptop1',
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
        // Setup POST request
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
                'timeout' => $timeout,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($receiver_url, false, $context);
        
        if ($response === false) {
            $error = error_get_last()['message'] ?? 'Unknown error';
            $result = [
                'status' => 'error',
                'message' => "Failed to connect to {$receiver_ip}:{$receiver_port}",
                'error' => $error
            ];
        } else {
            $result = json_decode($response, true);
            if (!$result) {
                $result = [
                    'status' => 'error',
                    'message' => 'Invalid response from receiver',
                    'response' => substr($response, 0, 200)
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Sender - Laptop 1</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        
        .header p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .header .info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85em;
            color: #333;
            text-align: left;
            line-height: 1.6;
        }
        
        .info-item {
            margin-bottom: 8px;
        }
        
        .info-label {
            color: #667eea;
            font-weight: bold;
        }
        
        .panel {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .panel h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
        }
        
        button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-send {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-clear {
            background: #f0f0f0;
            color: #666;
        }
        
        .btn-clear:hover {
            background: #e0e0e0;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }
        
        .alert strong {
            display: block;
            margin-bottom: 8px;
            font-size: 1.1em;
        }
        
        .response-details {
            background: rgba(0, 0, 0, 0.1);
            padding: 12px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85em;
            margin-top: 10px;
            color: inherit;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📤 Driver Sender</h1>
            <p>Laptop 1 - Send drivers to Laptop 2</p>
            
            <div class="info">
                <div class="info-item">
                    <span class="info-label">📍 Target:</span> <?php echo $receiver_url; ?>
                </div>
                <div class="info-item">
                    <span class="info-label">🖥️ Computer:</span> <?php echo gethostname() ?: 'Laptop1'; ?>
                </div>
                <div class="info-item">
                    <span class="info-label">⏱️ Timeout:</span> <?php echo $timeout; ?> seconds
                </div>
            </div>
        </div>
        
        <!-- Results -->
        <?php if (isset($result)): ?>
            <?php if ($result['status'] === 'success'): ?>
                <div class="alert success">
                    <strong>✓ Success!</strong>
                    Driver information sent successfully to Laptop 2
                    <div class="response-details">
                        <strong>Driver:</strong> <?php echo htmlspecialchars($data['driver_name']); ?><br>
                        <strong>License:</strong> <?php echo htmlspecialchars($data['license_number']); ?><br>
                        <strong>Sent:</strong> <?php echo $data['sent_at']; ?><br>
                        <strong>ID:</strong> <?php echo isset($result['data']['id']) ? substr($result['data']['id'], -8) : 'N/A'; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert error">
                    <strong>✗ <?php echo $result['message']; ?></strong>
                    <?php if (isset($result['error'])): ?>
                        <div class="response-details">
                            <?php echo $result['error']; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Send Form -->
        <div class="panel">
            <h2>📝 Send Driver Information</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="driver_name">Driver Name</label>
                    <input type="text" id="driver_name" name="driver_name" placeholder="e.g., Juan Dela Cruz" required>
                </div>
                
                <div class="form-group">
                    <label for="license_number">License Number</label>
                    <input type="text" id="license_number" name="license_number" placeholder="e.g., ABC123" required>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn-send">Send to Laptop 2</button>
                    <button type="reset" class="btn-clear">Clear</button>
                </div>
            </form>
        </div>
        
        <!-- Help Section -->
        <div class="panel" style="background: #f0f8ff; border: 2px solid #667eea;">
            <h2>❓ Make Sure Laptop 2 is Running</h2>
            <p style="color: #333; line-height: 1.6;">
                <strong>On Laptop 2:</strong><br>
                1. Open CMD/PowerShell<br>
                2. Go to the project folder<br>
                3. Run: <code style="background: #fff; padding: 2px 6px; border-radius: 3px; font-family: monospace;">php -S 10.225.157.110:3000</code><br>
                4. You should see: "listening on http://10.225.157.110:3000"
            </p>
        </div>
    </div>
</body>
</html>