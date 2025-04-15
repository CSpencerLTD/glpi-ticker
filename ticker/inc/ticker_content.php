<?php
global $DB;

$sql = "
           SELECT
              gt.id,
              IF (gt.name LIKE '%Leaver%', 'Equipment Request', gt.name) AS title,
              COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gtuu.alternative_email) AS customer_name,
              COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
              COALESCE(CONCAT(gtu.firstname, ' ', gtu.realname), 'New') AS task_technician,
              COALESCE(gs.name, 'Request') AS slt,
              gt.date AS date_entered,
              COALESCE(gtt.end, 'Action Required') AS task_date,
              gt.time_to_resolve AS ttr
           FROM glpi_tickets gt
           LEFT JOIN glpi_tickets_users AS gtut ON gtut.tickets_id = gt.id AND gtut.type = 2
           LEFT JOIN glpi_tickets_users AS gtuu ON gtuu.tickets_id = gt.id AND gtuu.type = 1
           LEFT JOIN glpi_users AS gut ON gut.id = gtut.users_id
           LEFT JOIN glpi_users AS guu ON guu.id = gtuu.users_id
           LEFT JOIN glpi_slas AS gs ON gs.id = gt.slas_id_ttr
           LEFT JOIN glpi_tickettasks AS gtt ON gtt.tickets_id = gt.id
           LEFT JOIN glpi_users AS gtu ON gtu.id = gtt.users_id_tech
           WHERE
              gt.status NOT IN (4,6)
              AND gt.time_to_resolve < NOW()
              AND gt.is_deleted = 0
           GROUP BY gt.id
           ORDER BY gtt.end, gs.name
        ";

// Fetch the result set
        $result = $DB->request($sql);

// Convert result into an array
$red_tickets = [];
foreach ($result as $row) {
    $red_tickets[] = $row;
}

// Display as a table
echo "<h3>Red Tickets (Past SLA)</h3>";

if (count($red_tickets)) {
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
