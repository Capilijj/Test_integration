<?php
/**
 * Driver Receiver - Laptop 2 (RECEIVER)
 * Magpapadala ng request signal bago tumanggap ng data.
 */

$data_dir = __DIR__ . '/drivers_data';
if (!is_dir($data_dir)) mkdir($data_dir, 0777, true);

$drivers_file = $data_dir . '/drivers.json';
$request_file = $data_dir . '/request_signal.txt';

// Logic 1: API Check para sa Sender
if (isset($_GET['check_request'])) {
    header('Content-Type: application/json');
    $has_request = file_exists($request_file);
    echo json_encode(['has_request' => $has_request]);
    exit;
}

// Logic 2: Paggawa ng Request (kapag pinindot ang button sa UI)
if (isset($_GET['make_request'])) {
    file_put_contents($request_file, 'PENDING_' . time());
    header('Location: receive.php?status=requested');
    exit;
}

// Logic 3: Pag-clear ng lahat (Reset)
if (isset($_GET['clear'])) {
    if (file_exists($request_file)) unlink($request_file);
    file_put_contents($drivers_file, json_encode([]));
    header('Location: receive.php');
    exit;
}

// Logic 4: Pag-receive ng Data via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $input = json_decode($body, true);

    if ($input) {
        $drivers = file_exists($drivers_file) ? json_decode(file_get_contents($drivers_file), true) : [];
        $new_entry = [
            'driver_name' => htmlspecialchars($input['driver_name']),
            'license_number' => htmlspecialchars($input['license_number']),
            'received_at' => date('H:i:s'),
            'from' => $input['sent_from']
        ];
        array_unshift($drivers, $new_entry);
        file_put_contents($drivers_file, json_encode($drivers));

        // Burahin ang request signal dahil nasagot na
        if (file_exists($request_file)) unlink($request_file);
        
        echo json_encode(['status' => 'success']);
    }
    exit;
}

$drivers = file_exists($drivers_file) ? json_decode(file_get_contents($drivers_file), true) : [];
$is_waiting = file_exists($request_file);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laptop 2 - Receiver</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #e0e5ec; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; }
        .panel { background: #f0f2f5; padding: 25px; border-radius: 15px; box-shadow: 8px 8px 15px #b8b9be, -8px -8px 15px #ffffff; text-align: center; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; margin: 5px; }
        .btn-req { background: #4CAF50; color: white; }
        .btn-clear { background: #f44336; color: white; }
        .status-box { margin: 20px 0; padding: 15px; border-radius: 10px; font-weight: bold; }
        .waiting { background: #fff9c4; color: #f57f17; border: 1px solid #fbc02d; }
        .idle { background: #e8e8e8; color: #757575; }
        .list { text-align: left; margin-top: 20px; background: white; border-radius: 10px; padding: 15px; }
        .item { border-bottom: 1px solid #eee; padding: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="panel">
            <h1>📥 Laptop 2 (Receiver)</h1>
            
            <?php if ($is_waiting): ?>
                <div class="status-box waiting">⌛ Kasalukuyang naghihintay ng data mula sa Laptop 1...</div>
            <?php else: ?>
                <div class="status-box idle">😴 Idle (Walang active request)</div>
            <?php endif; ?>

            <div class="controls">
                <a href="?make_request=1"><button class="btn btn-req">Pindutin para mag-Request sa Laptop 1</button></a>
                <a href="?clear=1"><button class="btn btn-clear">Clear All</button></a>
            </div>

            <div class="list">
                <h3>Mga Natanggap na Driver:</h3>
                <?php if (empty($drivers)): ?>
                    <p style="color:#999">Wala pang record.</p>
                <?php else: ?>
                    <?php foreach ($drivers as $d): ?>
                        <div class="item">
                            <strong>🚗 <?php echo $d['driver_name']; ?></strong><br>
                            <small>License: <?php echo $d['license_number']; ?> | Oras: <?php echo $d['received_at']; ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        // Auto refresh para makita ang bagong data
        setTimeout(() => { if(!window.location.search.includes('status=requested')) location.reload(); }, 3000);
    </script>
</body>
</html>