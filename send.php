<?php
/**
 * Driver Sender - Laptop 1 (SENDER)
 * Sends driver information to Laptop 2
 */

// Configuration - UPDATE THIS IP/PORT TO MATCH LAPTOP 2
$receiver_url = 'http://10.225.157.110:3000/receiver.php';
$timeout = 10; // seconds

// Parse URL to get host and port for testing
$url_parts = parse_url($receiver_url);
$host = $url_parts['host'];
$port = $url_parts['port'] ?? 80;

// Test connection function
function testConnection($host, $port, $timeout = 5) {
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$fp) {
        return [
            'connected' => false,
            'error' => $errstr,
            'errno' => $errno
        ];
    }
    fclose($fp);
    return ['connected' => true];
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_name = $_POST['driver_name'] ?? '';
    $license_number = $_POST['license_number'] ?? '';
    
    // Prepare data to send
    $data = [
        'driver_name' => $driver_name,
        'license_number' => $license_number,
        'sent_from' => gethostname(),
        'sent_at' => date('Y-m-d H:i:s'),
        'timestamp' => time()
    ];
    
    // Test connection first
    $conn_test = testConnection($host, $port, 5);
    
    if (!$conn_test['connected']) {
        $result = [
            'status' => 'error',
            'message' => "Cannot connect to Laptop 2 at $host:$port",
            'error' => $conn_test['error'],
            'connection_failed' => true
        ];
    } else {
        // Setup POST request with timeout and error handling
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\nUser-Agent: DriverSender/1.0\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
                'timeout' => $timeout,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        
        // Try to send
        $response = @file_get_contents($receiver_url, false, $context);
        
        if ($response === false) {
            $error = error_get_last()['message'] ?? 'Unknown error';
            $result = [
                'status' => 'error',
                'message' => 'Failed to send data to Laptop 2',
                'error' => $error,
                'timeout' => $timeout . ' seconds'
            ];
        } else {
            $result = json_decode($response, true);
            if (!$result) {
                $result = [
                    'status' => 'error',
                    'message' => 'Invalid response from receiver',
                    'response' => $response
                ];
            }
        }
    }
}

// Get diagnostic info
$local_ip = gethostbyname(gethostname());
$server_ip = getenv('SERVER_ADDR') ?: 'N/A';
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
        
        .header .info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85em;
            color: #666;
            margin-top: 15px;
            text-align: left;
            line-height: 1.6;
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
        
        .form-group input::placeholder {
            color: #999;
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
        
        .alert.warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }
        
        .alert.info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #17a2b8;
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
            max-height: 200px;
            overflow-y: auto;
        }
        
        .steps {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        
        .steps strong {
            color: #0066cc;
            display: block;
            margin-bottom: 10px;
        }
        
        .steps ol {
            margin-left: 20px;
            line-height: 1.8;
            font-size: 0.95em;
            color: #333;
        }
        
        .steps li {
            margin-bottom: 8px;
        }
        
        .steps code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            color: #d63384;
        }
        
        .test-section {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            border: 1px solid #ddd;
        }
        
        .test-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.95em;
        }
        
        .test-item:last-child {
            margin-bottom: 0;
        }
        
        .test-status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.8em;
        }
        
        .test-status.ok {
            background: #28a745;
        }
        
        .test-status.fail {
            background: #dc3545;
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
                <div><span class="info-label">Receiver URL:</span><br><?php echo $receiver_url; ?></div>
                <div style="margin-top: 10px;"><span class="info-label">Target:</span> <?php echo $host; ?>:<?php echo $port; ?></div>
                <div style="margin-top: 10px;"><span class="info-label">From:</span> <?php echo gethostname(); ?></div>
                <div style="margin-top: 10px;"><span class="info-label">Local IP:</span> <?php echo $local_ip; ?></div>
            </div>
        </div>
        
        <!-- Results -->
        <?php if (isset($result)): ?>
            <?php if ($result['status'] === 'success'): ?>
                <div class="alert success">
                    <strong>✓ Success!</strong>
                    Driver information sent successfully to Laptop 2
                    <div class="response-details">
                        Driver: <?php echo $data['driver_name']; ?><br>
                        License: <?php echo $data['license_number']; ?><br>
                        Sent at: <?php echo $data['sent_at']; ?><br>
                        ID: <?php echo substr($result['data']['id'] ?? 'N/A', -8); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert error">
                    <strong>✗ Error!</strong>
                    <?php echo $result['message']; ?>
                    <?php if (isset($result['error'])): ?>
                        <div class="response-details">
                            Error: <?php echo $result['error']; ?><br>
                            <?php if (isset($result['timeout'])): ?>
                            Timeout: <?php echo $result['timeout']; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Troubleshooting for connection errors -->
                <?php if (isset($result['connection_failed']) && $result['connection_failed']): ?>
                    <div class="alert warning">
                        <strong>🔧 Connection Failed - Troubleshooting Steps:</strong>
                        
                        <div class="steps">
                            <strong>Step 1: Verify Laptop 2 is Running</strong>
                            <ol>
                                <li>On Laptop 2, open CMD/PowerShell</li>
                                <li>Run: <code>php -S 192.168.1.20:3000</code></li>
                                <li>You should see: "listening on http://192.168.1.20:3000"</li>
                            </ol>
                        </div>
                        
                        <div class="steps">
                            <strong>Step 2: Verify the IP Address</strong>
                            <ol>
                                <li>On Laptop 2, run: <code>ipconfig</code></li>
                                <li>Find "IPv4 Address" in your WiFi/Network connection</li>
                                <li>If different from 192.168.1.20, update the URL above</li>
                            </ol>
                        </div>
                        
                        <div class="steps">
                            <strong>Step 3: Test Network Connection</strong>
                            <ol>
                                <li>On Laptop 1, open CMD/PowerShell</li>
                                <li>Run: <code>ping 192.168.1.20</code></li>
                                <li>If "Request timed out", check firewall or WiFi connection</li>
                            </ol>
                        </div>
                        
                        <div class="steps">
                            <strong>Step 4: Check Firewall</strong>
                            <ol>
                                <li>On Laptop 2, go to Windows Defender Firewall</li>
                                <li>Click "Allow an app through firewall"</li>
                                <li>Add PHP to allowed apps, or disable firewall momentarily for testing</li>
                            </ol>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Send Form -->
        <div class="panel">
            <h2>📝 Send Driver</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="driver_name">Driver Name</label>
                    <input type="text" id="driver_name" name="driver_name" placeholder="e.g., John Doe" required>
                </div>
                
                <div class="form-group">
                    <label for="license_number">License Number</label>
                    <input type="text" id="license_number" name="license_number" placeholder="e.g., DL12345678" required>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn-send">Send to Laptop 2</button>
                    <button type="reset" class="btn-clear">Clear</button>
                </div>
            </form>
        </div>
        
        <!-- Quick Connection Test -->
        <div class="panel">
            <h2>🔗 Connection Status</h2>
            <div class="test-section">
                <div class="test-item">
                    <div class="test-status <?php echo $conn_test['connected'] ? 'ok' : 'fail'; ?>">
                        <?php echo $conn_test['connected'] ? '✓' : '✗'; ?>
                    </div>
                    <span>
                        <strong><?php echo $host; ?>:<?php echo $port; ?></strong>
                        <?php if ($conn_test['connected']): ?>
                            - Connected ✓
                        <?php else: ?>
                            - Failed (<?php echo $conn_test['error']; ?>)
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

