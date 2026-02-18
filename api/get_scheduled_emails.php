<?php
// Get and manage scheduled emails

header('Content-Type: application/json');

$scheduledDir = __DIR__ . '/../storage/scheduled_emails';

// Handle GET requests - list all scheduled emails
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $schedules = [];
    $stats = [
        'pending' => 0,
        'sent' => 0,
        'failed' => 0
    ];

    if (is_dir($scheduledDir)) {
        $files = glob($scheduledDir . '/batch_*.json');

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if ($data) {
                $schedules[] = [
                    'batch_id' => $data['batch_id'],
                    'scheduled_time' => $data['scheduled_time'],
                    'created_at' => $data['created_at'],
                    'sent_at' => isset($data['sent_at']) ? $data['sent_at'] : null,
                    'status' => $data['status'],
                    'email_count' => count($data['emails']),
                    'emails' => $data['emails']
                ];

                // Count by status
                if ($data['status'] === 'pending') {
                    $stats['pending']++;
                } elseif ($data['status'] === 'sent') {
                    $stats['sent']++;
                } elseif ($data['status'] === 'failed') {
                    $stats['failed']++;
                }
            }
        }

        // Sort by scheduled time (most recent first)
        usort($schedules, function($a, $b) {
            return strtotime($b['scheduled_time']) - strtotime($a['scheduled_time']);
        });
    }

    echo json_encode([
        'success' => true,
        'schedules' => $schedules,
        'stats' => $stats
    ]);
    exit;
}

// Handle POST requests - delete scheduled emails
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'delete') {
        $batchId = isset($_POST['batch_id']) ? $_POST['batch_id'] : '';

        if (empty($batchId)) {
            echo json_encode(['success' => false, 'message' => 'Batch ID required']);
            exit;
        }

        // Validate batch ID format to prevent directory traversal
        if (!preg_match('/^batch_[0-9]{14}_[a-z0-9]{13}$/', $batchId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid batch ID format']);
            exit;
        }

        $file = $scheduledDir . '/' . $batchId . '.json';

        if (file_exists($file)) {
            if (unlink($file)) {
                echo json_encode(['success' => true, 'message' => 'Scheduled email batch deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete scheduled emails']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Batch not found']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
