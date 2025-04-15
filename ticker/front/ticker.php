<?php
include_once __DIR__ . '/../inc/ticket.class.php';

echo "<h3>Red Tickets (Past SLA)</h3>";

if (!empty($red_tickets)) {
    echo "<table class='tab_cadrehov'>";
    echo "<tr><th>Item</th><th>Customer Name</th><th>Technician</th><th>Task Technician</th><th>SLA</th><th>Date Entered</th><th>Task Date</th><th>Time to Resolve</th></tr>";
    foreach ($red_tickets as $ticket) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($ticket['title']) . "</td>";
        echo "<td>" . htmlspecialchars($ticket['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($ticket['technician']) . "</td>";
        echo "<td>" . htmlspecialchars($ticket['task_technician']) . "</td>";
        echo "<td>" . htmlspecialchars($ticket['slt']) . "</td>";
        echo "<td>" . htmlspecialchars($ticket['date_entered']) . "</td>";
        echo "<td>" . htmlspecialchars($ticket['task_date']) . "</td>";
        echo "<td>" . htmlspecialchars($ticket['ttr']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No red tickets found.</p>";
}

