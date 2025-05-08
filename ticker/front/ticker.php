<?php
// DEV only - remove or disable in production
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1) Bootstrap GLPI
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 3));
}
require_once GLPI_ROOT . '/inc/includes.php';

// 2) ACL: ensure user sees the central dashboard
//Session::checkRight('central', 'r');

// 3) Load your plugin classes (so all get…() methods exist)
require_once GLPI_ROOT . '/plugins/ticker/inc/changes.class.php';
require_once GLPI_ROOT . '/plugins/ticker/inc/problems.class.php';
require_once GLPI_ROOT . '/plugins/ticker/inc/tickets.class.php';

// 4) Compute the web-accessible AJAX URL
global $CFG_GLPI;
$baseUrl = rtrim($CFG_GLPI['url_base'], '/');
$ajaxUrl = $baseUrl . '/plugins/ticker/front/ticker.php';

// at top of the file, make sure session is started
if (session_status() !== PHP_SESSION_ACTIVE) {
   session_start();
}
// GLPI stores the active user profile ID in this session key:
//$currentUser = intval($_SESSION['glpiactiveprofileid'] ?? 0);
$currentUser = \Session::getLoginUserID();


echo '<div id="tickerFullScreenWrapper"'
   .' data-ajax-url="'. $ajaxUrl .'"'
   .' data-current-user-id="'. $currentUser .'"'
   .' style="position:relative;">';

// Print the control-panel:
echo '<div id="tickerControls">'
  .  '<label><input type="checkbox" id="personalViewToggle"> My Tickets</label>'
  .  '<label><input type="checkbox" id="pauseRefreshToggle"> Pause</label>'
  .  '<button id="fullScreenToggle" class="ticker-fullscreen-btn">⛶</button>'
  .'</div>';

// 5) Helper: sort rows by action_time
function sortByActionTime(array &$items) {
    usort($items, function($a, $b) {
        $ta = strtotime($a['action_time'] ?? '9999-12-31 23:59:59');
        $tb = strtotime($b['action_time'] ?? '9999-12-31 23:59:59');
        return $ta <=> $tb;
    });
}

// 6) Helper: render a "zone" table only if it's always-shown or non-empty
function displayZone(string $title, array $rows, string $zoneType) {
    $conditional = ['slaZone','taskZone','newUnplannedZone'];
    $always      = ['todayZone','futureZone'];

    // Skip empty conditional zones:
    if (empty($rows) && in_array($zoneType, $conditional, true)) {
        return;
    }

    echo "<h3>{$title}</h3>";

    // Show "no items" for empty always-shown zones:
    if (empty($rows) && in_array($zoneType, $always, true)) {
        $msg = $zoneType==='todayZone'
             ? '<i>No tasks for today!</i>'
             : '<i>Nothing planned for the future!</i>';
        echo "<p>{$msg}</p>";
        return;
    }

    // Table header:
    echo '<div style="overflow-x:auto;">'
       .   '<table id="'. $zoneType .'" style="margin-bottom:20px;" '
       .        'class="tab_cadre_fixe ticker">'
       .   '<colgroup>'
       .     '<col class="id-col">'
       .     '<col class="title-col">'
       .     '<col class="customer-col">'
       .     '<col class="date-col">'
       .     '<col class="tech-col">'
       .     '<col class="task-tech-col">'
       .     '<col class="task-date-col">'
       .     '<col class="sla-col">'
       .     '<col class="ttr-col">'
       .     '<col class="action-time-col">'
       .   '</colgroup>'
       .   '<thead><tr>'
       .     '<th>ID</th><th>Title</th><th>Customer</th>'
       .     '<th>Date Entered</th><th>Technician</th>'
       .     '<th>Task Tech</th><th>Task Date</th>'
       .     '<th>SLA</th><th>TTR</th><th>Action Time</th>'
       .   '</tr></thead>'
       .   '<tbody>';

    // Row loop: only ONE foreach, on $rows
    $now      = date('Y-m-d H:i:s');
    $qtr      = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $halfHour = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    foreach ($rows as $r) {
        // decide background color
        if ($r['action_time'] < $now)          { $bg = '#d9534f'; }
        elseif ($r['action_time'] < $qtr)      { $bg = '#f2dede'; }
        elseif ($r['action_time'] < $halfHour) { $bg = '#fce4ec'; }
        elseif ($r['task_state']==='Unplanned'){ $bg = '#ffb38a'; }
        else                                   { $bg = '#5cb85c'; }

        // build the ticket/problem/change link
        $link = str_contains($r['slt'],'Change') 
              ? "/front/change.form.php?id={$r['id']}"
              : ($r['slt']==='Problem'
                 ? "/front/problem.form.php?id={$r['id']}"
                 : "/front/ticket.form.php?id={$r['id']}");

        // collect all the user-IDs for filtering
$userIds = array_filter([
    $r['ticket_task_technician_id']   ?? null,
    $r['ticket_assigned_id']          ?? null,
    $r['problem_task_technician_id']  ?? null,
    $r['problem_assigned_id']         ?? null,
    $r['change_task_technician_id']   ?? null,
    $r['change_assigned_id']          ?? null,
]);

        // emit the row
        echo '<tr style="background:'. $bg .';"'
           .' data-user-ids="'. implode(',', $userIds) .'">'
           .  "<td>{$r['id']}</td>"
           .  "<td><a href=\"{$link}\" target=\"_blank\">{$r['title']}</a></td>"
           .  "<td>{$r['customer_name']}</td>"
           .  "<td>{$r['date_entered']}</td>"
           .  "<td>{$r['technician']}</td>"
           .  "<td>{$r['task_technician']}</td>"
           .  "<td>{$r['task_date']}</td>"
           .  "<td>{$r['slt']}</td>"
           .  "<td>{$r['ttr']}</td>"
           .  "<td>{$r['action_time']}</td>"
           .'</tr>';
    }

    // close table
    echo '</tbody></table></div>';
}

// - SLA past-due
$sla = array_merge(
    PluginTickerTickets::getTicketsPastSLA(),
    PluginTickerProblems::getProblemsPastSLA(),
    PluginTickerChanges::getChangesPastSLA()
);
sortByActionTime($sla);
displayZone("Tickets Past SLA - don't leave them!", $sla, 'slaZone');

// - Tasks past-due
$tasks = array_merge(
    PluginTickerTickets::getTicketTasksPastActionTime(),
    PluginTickerProblems::getProblemTasksPastActionTime(),
    PluginTickerChanges::getChangeTasksPastActionTime()
);
sortByActionTime($tasks);
displayZone("Missed Tasks - can you help?", $tasks, 'taskZone');

// - New/Unplanned
$newup = array_merge(
    PluginTickerTickets::getNewTickets(),
    PluginTickerTickets::getUnplannedTickets(),
    PluginTickerProblems::getNewProblems(),
    PluginTickerProblems::getUnplannedProblems(),
    PluginTickerChanges::getNewChanges(),
    PluginTickerChanges::getUnplannedChanges()
);
sortByActionTime($newup);
displayZone("New / Unplanned Items", $newup, 'newUnplannedZone');

// - Today's tasks
$today = array_merge(
    PluginTickerTickets::getTodaysTicketTasks(),
    PluginTickerProblems::getProblemTasksToday(),
    PluginTickerChanges::getTodaysChangeTasks()
);
sortByActionTime($today);
displayZone("Today's Tasks", $today, 'todayZone');

// - Divider
echo "<hr><p style='text-align:center;'>End of Today</p><hr>";

// - Future tasks
$future = array_merge(
    PluginTickerTickets::getFutureTicketTasks(),
    PluginTickerProblems::getFutureProblemTasks(),
    PluginTickerChanges::getFutureChangeTasks()
);
sortByActionTime($future);
displayZone("Future Tasks", $future, 'futureZone');



echo '</div>';

