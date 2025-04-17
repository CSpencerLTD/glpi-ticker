<?php
function displayTicketTable($title, $tickets) {
   echo "<h3>$title</h3>";
   if (empty($tickets)) {
      echo "<p><i>Keep up the good work!</i></p>";
      return;
   }

   echo "<table class='tab_cadre_fixe' style='margin-bottom: 20px'>";
   echo "<tr><th>ID</th><th>Title</th><th>Customer</th><th>Date Entered</th><th>Technician</th><th>Task Technician</th><th>Task Date</th><th>SLA</th><th>TTR</th><th>Next Action</th></tr>";
   foreach ($tickets as $row) {
      $displayTitle = $row['title'];
      $id = $row['id'];

      if (str_contains($row['slt'], 'Change')) {
         $link = "/front/change.form.php?id=$id";
      } elseif ($row['slt'] === 'Problem') {
         $link = "/front/problem.form.php?id=$id";
      } else {
         $link = "/front/ticket.form.php?id=$id";
      }

      echo "<tr>";
      echo "<td>{$row['id']}</td>";
      echo "<td><a href='$link' target='_blank'>$displayTitle</a></td>";
      echo "<td>{$row['customer_name']}</td>";
	  echo "<td>{$row['date_entered']}</td>";
      echo "<td>{$row['technician']}</td>";
	  echo "<td>{$row['task_technician']}</td>";
      echo "<td>{$row['task_date']}</td>";
      echo "<td>{$row['ttr']}</td>";
	  echo "<td>{$row['action_time']}</td>";
      echo "</tr>";
   }
   echo "</table><br>";
}
function sortByActionTime(&$array) {
    usort($array, function ($a, $b) {
        $a_time = isset($a['action_time']) ? strtotime($a['action_time']) : PHP_INT_MAX;
        $b_time = isset($b['action_time']) ? strtotime($b['action_time']) : PHP_INT_MAX;
        return $a_time <=> $b_time;
    });
}


//SLA Red Zone
$ticketPastSLA = PluginTickerTickets::getTicketsPastSLA();

$problemPastSLA = PluginTickerProblems::getProblemsPastSLA();

$changePastSLA = PluginTickerChanges::getChangesPastSLA();

$slaZone = array_merge($ticketPastSLA, $problemPastSLA, $changePastSLA);
sortByActionTime($slaZone);

// Display Red SLA tickets
displayTicketTable("Tickets Past SLA. Don't leave them!", $slaZone);



//Task Red Zone
$ticketPastTasks = PluginTickerTickets::getTicketTasksPastActionTime();

$problemPastTasks = PluginTickerProblems::getProblemTasksPastActionTime();

$changePastTasks = PluginTickerChanges::getChangeTasksPastActionTime();

$taskZone = array_merge($ticketPastTasks, $problemPastTasks, $changePastTasks);
sortByActionTime($taskZone);

// Display Task Red tickets
displayTicketTable("Someone has missed a task. Can you help?", $taskZone);


// Today's Section
$newTickets = PluginTickerTickets::getNewTickets();
$unplannedTickets = PluginTickerTickets::getUnplannedTickets();
$todaysTicketTasks = PluginTickerTickets::getTodaysTicketTasks();

$newProblems = PluginTickerProblems::getNewProblems();
$unplannedProblems = PluginTickerProblems::getUnplannedProblems();
$todaysProblemTasks = PluginTickerProblems::getProblemTasksToday();

$newChanges = PluginTickerChanges::getNewChanges();
$unplannedChanges = PluginTickerChanges::getUnplannedChanges();
$todaysChangeTasks = PluginTickerChanges::getTodaysChangeTasks();

$todayGroup = array_merge($newTickets, $unplannedTickets, $todaysTicketTasks, $newProblems, $unplannedProblems, $todaysProblemTasks, $newChanges, $unplannedChanges, $todaysChangeTasks);
sortByActionTime($todayGroup);

displayTicketTable("Today's Tickets", $todayGroup);

// Divider
echo "<hr><p style='text-align:center; font-weight:bold;'>End of Today's Tasks</p><hr>";

// 📅 Future Tasks
$futureTicketTasks = PluginTickerTickets::getFutureTicketTasks();
$futureProblemTasks = PluginTickerProblems::getFutureProblemTasks();
$futureChangeTasks = PluginTickerChanges::getFutureChangeTasks();

$futureGroup = array_merge($futureTicketTasks, $futureProblemTasks, $futureChangeTasks);

sortByActionTime($futureGroup);
displayTicketTable("Future Tasks", $futureGroup);