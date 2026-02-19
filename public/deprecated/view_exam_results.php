<?php
session_start();
include('../config/db_config.php');

// Get exam results
$results = [];
$filter_session = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT er.*, es.session_name, a.full_name, a.admission_id, a.email as admission_email
         FROM exam_registrations er
         LEFT JOIN exam_sessions es ON er.exam_session_id = es.id
         LEFT JOIN admissions a ON er.admission_id = a.id
         WHERE 1=1";

if ($filter_session > 0) {
    $query .= " AND er.exam_session_id = " . intval($filter_session);
}

if (!empty($filter_status)) {
    $query .= " AND er.result = '" . $conn->real_escape_string($filter_status) . "'";
}

$query .= " ORDER BY er.result_date DESC LIMIT 1000";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
}

// Get sessions for filter dropdown
$sessions = [];
$session_result = $conn->query("SELECT id, session_name FROM exam_sessions ORDER BY exam_date DESC");
if ($session_result) {
    while ($row = $session_result->fetch_assoc()) {
        $sessions[] = $row;
    }
}

// Calculate statistics
$total_registrations = count($results);
$passed = 0;
$failed = 0;
$average_score = 0;
$total_score = 0;

foreach ($results as $r) {
    if ($r['result'] === 'Passed') {
        $passed++;
    } else if ($r['result'] === 'Failed') {
        $failed++;
    }
    if ($r['score'] !== null) {
        $total_score += $r['score'];
    }
}

if ($total_registrations > 0) {
    $average_score = round($total_score / $total_registrations, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - PTC SIAMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #999;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
        }

        .stat-card.passed .value {
            color: #4caf50;
        }

        .stat-card.failed .value {
            color: #f44336;
        }

        .stat-card.total .value {
            color: #2196f3;
        }

        .stat-card.average .value {
            color: #ff9800;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-group button {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .filter-group button:hover {
            background: #764ba2;
        }

        .results-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-passed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-failed {
            background: #ffebee;
            color: #c62828;
        }

        .no-results {
            padding: 40px;
            text-align: center;
            color: #999;
        }

        .export-btn {
            padding: 10px 20px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin-left: auto;
        }

        .export-btn:hover {
            background: #45a049;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group button, .export-btn {
                width: 100%;
            }

            th, td {
                padding: 10px 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Exam Results</h1>
            <p>View and analyze student exam results</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card total">
                <h3>Total Registrations</h3>
                <div class="value"><?php echo $total_registrations; ?></div>
            </div>
            <div class="stat-card passed">
                <h3>Passed</h3>
                <div class="value"><?php echo $passed; ?></div>
            </div>
            <div class="stat-card failed">
                <h3>Failed</h3>
                <div class="value"><?php echo $failed; ?></div>
            </div>
            <div class="stat-card average">
                <h3>Average Score</h3>
                <div class="value"><?php echo $average_score; %></div>
            </div>
        </div>

        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; width: 100%;">
                <div class="filter-group">
                    <label>Exam Session</label>
                    <select name="session_id">
                        <option value="">All Sessions</option>
                        <?php foreach ($sessions as $session): ?>
                            <option value="<?php echo $session['id']; ?>" <?php echo $filter_session == $session['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($session['session_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Result Status</label>
                    <select name="status">
                        <option value="">All Results</option>
                        <option value="Passed" <?php echo $filter_status === 'Passed' ? 'selected' : ''; ?>>Passed</option>
                        <option value="Failed" <?php echo $filter_status === 'Failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>

                <div class="filter-group" style="margin-left: auto;">
                    <button type="submit">Apply Filters</button>
                </div>
            </form>
        </div>

        <div class="results-table">
            <?php if (count($results) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Admission ID</th>
                            <th>Student Name</th>
                            <th>Session</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Result</th>
                            <th>Attendance</th>
                            <th>Submission Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['admission_id'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['session_name'] ?: 'N/A'); ?></td>
                                <td><?php echo $row['score'] !== null ? $row['score'] : 'N/A'; ?></td>
                                <td><?php echo $row['score_percentage'] !== null ? round($row['score_percentage'], 2) . '%' : 'N/A'; ?></td>
                                <td>
                                    <?php if ($row['result']): ?>
                                        <span class="status-badge status-<?php echo strtolower($row['result']); ?>">
                                            <?php echo htmlspecialchars($row['result']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo ucfirst($row['attendance_status'] ?: 'pending'); ?></td>
                                <td><?php echo $row['result_date'] ? (new DateTime($row['result_date']))->format('M d, Y h:i A') : 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <p>No exam results found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
