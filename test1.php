<?php
/**
 * Driver Sender - Laptop 1 (SENDER)
 * Updated with Request Monitor Feature
 */

// Configuration - Siguraduhing tama ang IP ng Laptop 2 (Receiver)
$receiver_ip = '10.225.157.136'; 
$receiver_port = '3000';
// Note: Sa receiver script mo, "receive.php" ba ang filename o "receiver.php"? 
// Pakisigurado na tugma ito sa baba:
$receiver_url = "http://{$receiver_ip}:{$receiver_port}/test2.php";
$timeout = 10;

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_name = $_POST['driver_name'] ?? '';
    $license_number = $_POST['license_number'] ?? '';
    
    if (empty($driver_name) || empty($license_number)) {
        $result = ['status' => 'error', 'message' => 'Driver name and license number are required'];
    } else {
        $data = [
            'driver_name' => $driver_name,
            'license_number' => $license_number,
            'sent_from' => gethostname() ?: 'Laptop1',
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
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
            $result = ['status' => 'error', 'message' => "Failed to connect to Laptop 2 at {$receiver_url}"];
        } else {
            $result = json_decode($response, true);
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
        /* ... Keeping your original CSS ... */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; }
        .header, .panel { background: white; border-radius: 12px; padding: 30px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .info { background: #f0f0f0; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 0.85em; text-align: left; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; }
        .btn-send { width: 100%; padding: 12px; border: none; border-radius: 6px; background: #764ba2; color: white; font-weight: bold; cursor: pointer; }
        
        /* NEW STYLES FOR REQUEST NOTIFICATION */
        .request-notification {
            display: none;
            background: #fff3cd;
            border: 2px solid #ffeeba;
            color: #856404;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="requestBox" class="request-notification">
            <h3 style="margin-bottom: 10px;">🔔 Laptop 2 is requesting Info!</h3>
            <p id="requestMsg">"Loading request..."</p>
            <div style="margin-top: 10px; font-size: 0.8em; font-weight: bold;">
                Status: PENDING REQUEST FROM RECEIVER
            </div>
        </div>

        <div class="header">
            <h1>📤 Driver Sender</h1>
            <p>Laptop 1 - Sending to Laptop 2</p>
            <div class="info">
                📍 Target: <?php echo $receiver_url; ?><br>
                🖥️ My Hostname: <?php echo gethostname(); ?>
            </div>
        </div>

        <?php if (isset($result)): ?>
            <div style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: <?php echo $result['status'] === 'success' ? '#d4edda' : '#f8d7da'; ?>">
                <strong><?php echo $result['status'] === 'success' ? '✓ Success' : '✗ Error'; ?>:</strong> 
                <?php echo $result['message']; ?>
            </div>
        <?php endif; ?>

        <div class="panel">
            <h2>� Request Status from Receiver (Test 2)</h2>
            <div id="requestStatus">
                <p>No pending requests from Laptop 2.</p>
            </div>
        </div>

        <div class="panel">
            <h2>�📝 Driver Information</h2>
            <form method="POST" id="driverForm">
                <div class="form-group">
                    <label>Driver Name</label>
                    <input type="text" name="driver_name" id="driver_name" placeholder="Enter Name" required>
                </div>
                <div class="form-group">
                    <label>License Number</label>
                    <input type="text" name="license_number" id="license_number" placeholder="Enter License" required>
                </div>
                <button type="submit" class="btn-send">Send Data Now</button>
            </form>
        </div>
    </div>

    <script>
        // FUNCTION TO CHECK REQUESTS FROM LAPTOP 2
        async function checkIncomingRequests() {
            const receiverUrl = "<?php echo $receiver_url; ?>?check_request=1";
            
            try {
                const response = await fetch(receiverUrl);
                if (!response.ok) return;
                
                const data = await response.json();
                
                const box = document.getElementById('requestBox');
                const msg = document.getElementById('requestMsg');
                const statusDiv = document.getElementById('requestStatus');
                
                if (data.pending === true) {
                    box.style.display = 'block';
                    msg.innerText = `Message: "${data.message}" (Requested at: ${data.requested_at})`;
                    statusDiv.innerHTML = `<p style="color: #856404; font-weight: bold;">DISPLAYING REQUEST FROM TEST 2:</p><p>"${data.message}"</p><p><small>Requested at: ${data.requested_at}</small></p>`;
                    
                    // Optional: Highlight the inputs if they are empty
                    if(document.getElementById('driver_name').value === "") {
                        document.getElementById('driver_name').style.borderColor = "#ffc107";
                    }
                } else {
                    box.style.display = 'none';
                    statusDiv.innerHTML = '<p>No pending requests from Laptop 2.</p>';
                }
            } catch (error) {
                console.log("Waiting for Laptop 2 to be online...");
            }
        }

        // Check for requests every 3 seconds
        setInterval(checkIncomingRequests, 3000);
        
        // Initial check
        checkIncomingRequests();
    </script>
</body>
</html>