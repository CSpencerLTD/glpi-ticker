<?php
function displayCriticalZone($title, $tickets) {
    if (empty($tickets)) return;
    echo "<h3>$title</h3>";
    echo "<table class='tab_cadre_fixe' style='margin-bottom: 20px'>";
    echo "<tr><th>ID</th><th>Title</th><th>Customer</th><th>Date Entered</th><th>Technician</th><th>Task Technician</th><th>Task Date</th><th>SLA</th><th>TTR</th><th>Action Time</th></tr>";

    foreach ($tickets as $row) {
        $colorStyle = "background-color: #d9534f;"; // Always red
        // Generate the link based on SLT type (existing logic)
        if (str_contains($row['slt'], 'Change')) {
            $link = "/front/change.form.php?id={$row['id']}";
        } elseif ($row['slt'] === 'Problem') {
            $link = "/front/problem.form.php?id={$row['id']}";
        } else {
            $link = "/front/ticket.form.php?id={$row['id']}";
        }

        echo "<tr style=\"$colorStyle\">";
        echo "<td>{$row['id']}</td>";
        echo "<td><a href='$link' target='_blank'>{$row['title']}</a></td>";
        echo "<td>{$row['customer_name']}</td>";
        echo "<td>{$row['date_entered']}</td>";
        echo "<td>{$row['technician']}</td>";
        echo "<td>{$row['task_technician']}</td>";
        echo "<td>{$row['task_date']}</td>";
        echo "<td>{$row['slt']}</td>";
        echo "<td>{$row['ttr']}</td>";
        echo "<td>{$row['action_time']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

function displayDynamicZone($title, $tickets, $zoneType = '') {
    if (empty($tickets)) {
        if ($zoneType === 'today') {
			echo "<h3>$title</h3>";
            echo "<p><i>There are no more tasks for today!</i></p>";
        } elseif ($zoneType === 'future') {
			echo "<h3>$title</h3>";
            echo "<p><i>Nothing else is planned for the future!</i></p>";
        }
        return;
    }

    echo "<h3>$title</h3>";
    echo "<table class='tab_cadre_fixe' style='margin-bottom: 20px'>";
    echo "<tr><th>ID</th><th>Title</th><th>Customer</th><th>Date Entered</th><th>Technician</th><th>Task Technician</th><th>Task Date</th><th>SLA</th><th>TTR</th><th>Action Time</th></tr>";

    $now = date("Y-m-d H:i:s");
    $quarterHour = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $halfHour = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    foreach ($tickets as $row) {
        if ($row['action_time'] < $now) {
            $colorStyle = "background-color: #d9534f;"; // Red for overdue
        } elseif ($row['action_time'] < $quarterHour) {
            $colorStyle = "background-color: #f2dede;"; // Light red
        } elseif ($row['action_time'] < $halfHour) {
            $colorStyle = "background-color: #fce4ec;"; // Pink
                } elseif ($row['task_state'] == "Unplanned") {
        $colorStyle = "background-color: #ffb38a;"; // Unplanned - Orange
                } elseif ($row['task_technician'] == "New") {
        $colorStyle = ""; // No color for new
        } else {
            $colorStyle = "background-color: #5cb85c;"; // Green
        }

        // Generate ticket link (existing logic)
        if (str_contains($row['slt'], 'Change')) {
            $link = "/front/change.form.php?id={$row['id']}";
        } elseif ($row['slt'] === 'Problem') {
            $link = "/front/problem.form.php?id={$row['id']}";
        } else {
            $link = "/front/ticket.form.php?id={$row['id']}";
        }

        echo "<tr style=\"$colorStyle\">";
        echo "<td>{$row['id']}</td>";
        echo "<td><a href='$link' target='_blank'>{$row['title']}</a></td>";
        echo "<td>{$row['customer_name']}</td>";
        echo "<td>{$row['date_entered']}</td>";
        echo "<td>{$row['technician']}</td>";
        echo "<td>{$row['task_technician']}</td>";
        echo "<td>{$row['task_date']}</td>";
        echo "<td>{$row['slt']}</td>";
        echo "<td>{$row['ttr']}</td>";
        echo "<td>{$row['action_time']}</td>";
        echo "</tr>";
    }
    echo "</table>";
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
displayCriticalZone("Tickets Past SLA. Don't leave them!", $slaZone);



//Task Red Zone
$ticketPastTasks = PluginTickerTickets::getTicketTasksPastActionTime();

$problemPastTasks = PluginTickerProblems::getProblemTasksPastActionTime();

$changePastTasks = PluginTickerChanges::getChangeTasksPastActionTime();

$taskZone = array_merge($ticketPastTasks, $problemPastTasks, $changePastTasks);
sortByActionTime($taskZone);

// Display Task Red tickets
displayCriticalZone("Someone has missed a task. Can you help?", $taskZone);

//New and unplanned Zone
$newTickets = PluginTickerTickets::getNewTickets();
$unplannedTickets = PluginTickerTickets::getUnplannedTickets();

$newProblems = PluginTickerProblems::getNewProblems();
$unplannedProblems = PluginTickerProblems::getUnplannedProblems();

$newChanges = PluginTickerChanges::getNewChanges();
$unplannedChanges = PluginTickerChanges::getUnplannedChanges();

$newUnplannedGroup = array_merge($newTickets, $unplannedTickets, $newProblems, $unplannedProblems, $newChanges, $unplannedChanges);
sortByActionTime($newUnplannedGroup);

displayDynamicZone("New/Unplanned", $newUnplannedGroup);

// Today's Section Zone
$todaysTicketTasks = PluginTickerTickets::getTodaysTicketTasks();
$todaysProblemTasks = PluginTickerProblems::getProblemTasksToday();
$todaysChangeTasks = PluginTickerChanges::getTodaysChangeTasks();

$todayGroup = array_merge($todaysTicketTasks, $todaysProblemTasks, $todaysChangeTasks);
sortByActionTime($todayGroup);

displayDynamicZone("Today's Tickets", $todayGroup, 'today');

// Divider
echo "<hr><p style='text-align:center; font-weight:bold;'>End of Today's Tasks</p><hr>";

// Future Tasks Zone
$futureTicketTasks = PluginTickerTickets::getFutureTicketTasks();
$futureProblemTasks = PluginTickerProblems::getFutureProblemTasks();
$futureChangeTasks = PluginTickerChanges::getFutureChangeTasks();

$futureGroup = array_merge($futureTicketTasks, $futureProblemTasks, $futureChangeTasks);

sortByActionTime($futureGroup);
displayDynamicZone("Future Tasks", $futureGroup, 'future');

