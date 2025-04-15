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

$result = $DB->request($sql);

$red_tickets = [];
foreach ($result as $row) {
    $red_tickets[] = $row;
}

