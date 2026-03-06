<?php
/**
 * Driver Sender - Laptop 1 (SENDER)
 * Makikinig sa request mula sa Laptop 2 bago mag-send.
 */

// Configuration
$receiver_ip = '10.225.157.136'; // Palitan ito ng IP ng Laptop 2
$receiver_port = '3000';
$receiver_url = "http://{$receiver_ip}:{$receiver_port}/receive.php";

$status_message = "Ready at naghihintay ng request mula sa Laptop 2...";
$status_type = "info";

// Logic: Kapag pinindot ang button, i-check kung may active request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_request') {
    
    // 1. Alamin kung may "Request" signal sa Receiver
    $check_request_url = "http://{$receiver_ip}:{$receiver_port}/receive.php?check_request=1";
    $request_status = @file_get_contents($check_request_url);
    $req_data = json_decode($request_status, true);

    if ($req_data && $req_data['has_request'] === true) {
        // 2. Kung may request, ipadala na ang data
        $driver_name = $_POST['driver_name'] ?? 'Unknown Driver';
        $license_number = $_POST['license_number'] ?? 'N/A';
        
        $post_data = [
            'driver_name' => $driver_name,
            'license_number' => $license_number,
            'sent_from' => gethostname(),
            'sent_at' => date('Y-m-d H:i:s'),
            'clear_request' => true // Sabihan ang receiver na tapos na ang request
        ];

        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($post_data),
                'timeout' => 5
            ]
        ];

        $context  = stream_context_create($options);
        $response = @file_get_contents($receiver_url, false, $context);
        
        if ($response) {
            $status_message = "✓ Request Natanggap! Data naipadala na sa Laptop 2.";
            $status_type = "success";
        } else {
            $status_message = "✗ Error: Hindi makakonekta sa Laptop 2.";
            $status_type = "error";
        }
    } else {
        $status_message = "⚠ Paalala: Wala pang request mula sa Laptop 2. Pindutin muna ang 'Request Data' doon.";
        $status_type = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laptop 1 - Sender</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding: 50px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 400px; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9em; }
        .info { background: #e3f2fd; color: #0d47a1; }
        .success { background: #e8f5e9; color: #1b5e20; }
        .error { background: #ffebee; color: #b71c1c; }
        .warning { background: #fff3e0; color: #e65100; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #5a6fd6; }
    </style>
</head>
<body>
    <div class="card">
        <h2>📤 Laptop 1 (Sender)</h2>
        <div class="alert <?php echo $status_type; ?>">
            <?php echo $status_message; ?>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="process_request">
            <label>Pangalan ng Driver:</label>
            <input type="text" name="driver_name" value="Juan Dela Cruz" required>
            <label>License Number:</label>
            <input type="text" name="license_number" value="L12-34-56789" required>
            <button type="submit">I-send kapag may Request na</button>
        </form>
        <p style="font-size: 0.8em; color: #888; margin-top: 15px;">
            Dapat mag-click muna ng "Request" sa Laptop 2 bago gumana ang Send button na ito.
        </p>
    </div>
</body>
</html>