<?php
/**
 * Admin Dashboard - View bookings
 * URL: http://localhost:8000/admin.php
 */

require_once 'config.php';
require_once 'lib/helpers.php';

// Database connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_error) {
    die('Database connection failed: ' . $db->connect_error);
}

// Get upcoming bookings
$result = $db->query("
    SELECT * FROM bookings 
    WHERE date >= CURDATE() 
    ORDER BY date ASC, time ASC
    LIMIT 100
");

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salon Bookings Dashboard</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card .number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-card .label {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .bookings-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .table-header {
            background: #f5f5f5;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .table-header h2 {
            color: #333;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f9f9f9;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #eee;
            font-size: 14px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #333;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-completed {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            color: #999;
        }

        .empty-state svg {
            width: 60px;
            height: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: white;
            font-size: 12px;
        }

        .date-badge {
            background: #667eea;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .time-badge {
            background: #764ba2;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💇 Salon Booking Dashboard</h1>
            <p>WhatsApp Booking System - Real-time Updates</p>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="number"><?php echo count($bookings); ?></div>
                    <div class="label">Upcoming Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo date('H:i'); ?></div>
                    <div class="label">Current Time</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo date('l'); ?></div>
                    <div class="label">Today</div>
                </div>
            </div>
        </div>

        <div class="bookings-table">
            <div class="table-header">
                <h2>📅 Upcoming Appointments</h2>
            </div>
            
            <?php if (count($bookings) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Booked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($booking['name']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($booking['phone']); ?></code></td>
                                <td><?php echo htmlspecialchars($booking['service']); ?></td>
                                <td><span class="date-badge"><?php echo date('M d, Y', strtotime($booking['date'])); ?></span></td>
                                <td><span class="time-badge"><?php echo date('g:i A', strtotime($booking['time'])); ?></span></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>📭 No bookings yet</p>
                    <p>Bookings will appear here when customers book via WhatsApp</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>Auto-refreshing in 10 seconds... • Last updated: <?php echo date('H:i:s'); ?></p>
            <meta http-equiv="refresh" content="10">
        </div>
    </div>
</body>
</html>