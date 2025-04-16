<?php
//include_once("../inc/ticket.class.php");

function displayTicketTable($title, $tickets) {
   echo "<h3>$title</h3>";
   if (empty($tickets)) {
      echo "<p><i>No tickets found.</i></p>";
      return;
   }

   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><th>ID</th><th>Title</th><th>Customer</th><th>Technician</th><th>Task Date</th><th>TTR</th></tr>";
   foreach ($tickets as $row) {
      echo "<tr>";
      echo "<td>{$row['id']}</td>";
      echo "<td>{$row['title']}</td>";
      echo "<td>{$row['customer_name']}</td>";
      echo "<td>{$row['technician']}</td>";
      echo "<td>{$row['task_date']}</td>";
      echo "<td>{$row['ttr']}</td>";
      echo "</tr>";
   }
   echo "</table><br>";
}

// 🟥 Red Zone
$pastSLA = PluginTickerTicket::getTicketsPastSLA();
$pastTasks = PluginTickerTicket::getTasksPastActionTime();
$redZone = array_merge($pastSLA, $pastTasks);

// Display Red tickets
displayTicketTable("Tickets Past SLA or Task Due", $redZone);

// 📌 Today's Section
$newTickets = PluginTickerTicket::getNewTickets();
$unplanned = PluginTickerTicket::getUnplannedTickets();
$todaysTasks = PluginTickerTicket::getTodaysTasks();
$todayGroup = array_merge($newTickets, $unplanned, $todaysTasks);

displayTicketTable("Today's Tickets", $todayGroup);

// Divider
echo "<hr><p style='text-align:center; font-weight:bold;'>End of Today's Tasks</p><hr>";

// 📅 Future Tasks
$futureTasks = PluginTickerTicket::getFutureTasks();
displayTicketTable("Future Tasks", $futureTasks);

