<?php

class PluginTickerTickets {

   static function getTicketsPastSLA() {
      global $DB;

      $sql = "
         SELECT
			gt.id,
			IF (`gt`.`name` LIKE '%Leaver%', 'Equipment Request', `gt`.`name`) AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gtuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'New') AS task_technician,
			COALESCE(`gut`.`id`, '0') AS assigned_id,
			COALESCE(`gs`.`name`, 'Request') AS `slt`,
			gt.date AS date_entered,
			COALESCE(`gtt`.`id`, '0') AS task_id,
			COALESCE(`gtt`.`state`, '0') AS task_state,
			COALESCE(`gtt`.`end`,'Action Required') AS task_date,
			gt.status,
			gt.time_to_resolve AS ttr,
			COALESCE(gtt.end,gt.time_to_resolve)   AS action_time
		FROM
			glpi_tickets gt
			LEFT JOIN glpi_tickets_users AS gtut ON gtut.tickets_id = gt.id AND gtut.type = 2
			LEFT JOIN glpi_tickets_users AS gtuu ON gtuu.tickets_id = gt.id AND gtuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gtut.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gtuu.users_id
			LEFT JOIN glpi_slas AS gs ON gs.id = gt.slas_id_ttr
			LEFT JOIN glpi_tickettasks AS gtt ON gtt.tickets_id = gt.id
			LEFT JOIN glpi_users AS gtu ON gtu.id = gtt.users_id_tech
		WHERE
			gt.status NOT IN (4,6) AND gt.time_to_resolve < NOW()
			AND gt.is_deleted = 0
			GROUP BY gt.id
			ORDER BY end, slt";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));

   }

   static function getTicketTasksPastActionTime() {
      global $DB;

      $sql = "
         SELECT
            gt.id,
            IF (gt.name LIKE '%Leaver%', 'Equipment Request', gt.name) AS title,
            COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gtuu.alternative_email) AS customer_name,
            COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
            COALESCE(CONCAT(gtu.firstname, ' ', gtu.realname), 'New') AS task_technician,
            COALESCE(gut.id, '0') AS assigned_id,
            COALESCE(gs.name, 'Request') AS slt,
            gt.date AS date_entered,
            COALESCE(gtt.id, '0') AS task_id,
            COALESCE(gtt.state, '0') AS task_state,
            COALESCE(gtt.end, 'Action Required') AS task_date,
            gt.status,
            COALESCE(gt.time_to_resolve, 'TTR Missing') AS ttr,
            COALESCE(gtt.end, 'Unplanned') AS action_time
         FROM glpi_tickets gt
         LEFT JOIN glpi_tickets_users AS gtut ON gtut.tickets_id = gt.id AND gtut.type = 2
         LEFT JOIN glpi_tickets_users AS gtuu ON gtuu.tickets_id = gt.id AND gtuu.type = 1
         LEFT JOIN glpi_users AS gut ON gut.id = gtut.users_id
         LEFT JOIN glpi_users AS guu ON guu.id = gtuu.users_id
         LEFT JOIN glpi_slas AS gs ON gs.id = gt.slas_id_ttr
         LEFT JOIN glpi_tickettasks AS gtt ON gtt.tickets_id = gt.id
         LEFT JOIN glpi_users AS gtu ON gtu.id = gtt.users_id_tech
         WHERE
            gt.status NOT IN (6) AND gtt.state = 1 AND gtt.end < NOW()
            AND gtt.id != 0
            AND gt.is_deleted = 0
         ORDER BY gtt.end, gs.name";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getNewTickets() {
      global $DB;

      $sql = "
         SELECT
            gt.id,
            IF (gt.name LIKE '%Leaver%', 'Equipment Request', gt.name) AS title,
            COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gtuu.alternative_email) AS customer_name,
            COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
            COALESCE(CONCAT(gtu.firstname, ' ', gtu.realname), 'New') AS task_technician,
            COALESCE(gut.id, '0') AS assigned_id,
            COALESCE(gs.name, 'Request') AS slt,
            gt.date AS date_entered,
            COALESCE(gtt.id, '0') AS task_id,
            COALESCE(gtt.state, '0') AS task_state,
            COALESCE(gtt.end, 'Action Required') AS task_date,
            gt.status,
            gt.time_to_resolve AS ttr,
            COALESCE(gt.time_to_own, gt.date + INTERVAL 30 MINUTE) AS action_time
         FROM glpi_tickets gt
         LEFT JOIN glpi_tickets_users AS gtut ON gtut.tickets_id = gt.id AND gtut.type = 2
         LEFT JOIN glpi_tickets_users AS gtuu ON gtuu.tickets_id = gt.id AND gtuu.type = 1
         LEFT JOIN glpi_users AS gut ON gut.id = gtut.users_id
         LEFT JOIN glpi_users AS guu ON guu.id = gtuu.users_id
         LEFT JOIN glpi_slas AS gs ON gs.id = gt.slas_id_ttr
         LEFT JOIN glpi_tickettasks AS gtt ON gtt.tickets_id = gt.id
         LEFT JOIN glpi_users AS gtu ON gtu.id = gtt.users_id_tech
         WHERE
            gt.status != 6 AND (gtt.state = 1 OR gtt.state IS NULL)
            AND gtu.firstname IS NULL
            AND gt.is_deleted = 0
         GROUP BY gt.id
         ORDER BY action_time";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getUnplannedTickets() {
      global $DB;

      $sql = "
         SELECT DISTINCT
            gt.id,
            IF (gt.name LIKE '%Leaver%', 'Equipment Request', gt.name) AS title,
            COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gtuu.alternative_email) AS customer_name,
            COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
            COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'New') AS task_technician,
            COALESCE(gut.id, '0') AS assigned_id,
            COALESCE(gs.name, 'Request') AS slt,
            gt.date AS date_entered,
            'Unplanned' AS task_id,
            'Unplanned' AS task_state,
            'Unplanned' AS task_date,
            'Unplanned' AS status,
            COALESCE(gt.time_to_resolve, 'TTR Missing') AS ttr,
            COALESCE(gt.time_to_resolve, 'TTR Missing') AS action_time
         FROM glpi_tickets gt
         LEFT JOIN glpi_tickets_users AS gtut ON gtut.tickets_id = gt.id AND gtut.type = 2
         LEFT JOIN glpi_tickets_users AS gtuu ON gtuu.tickets_id = gt.id AND gtuu.type = 1
         LEFT JOIN glpi_users AS gut ON gut.id = gtut.users_id
         LEFT JOIN glpi_users AS guu ON guu.id = gtuu.users_id
         LEFT JOIN glpi_slas AS gs ON gs.id = gt.slas_id_ttr
         LEFT JOIN glpi_tickettasks AS gtt ON gtt.tickets_id = gt.id
         LEFT JOIN glpi_users AS gtu ON gtu.id = gtt.users_id_tech
         WHERE
            gt.status != 6
            AND gt.is_deleted = 0
            AND gt.id NOT IN (
               SELECT gt.id
               FROM glpi_tickets gt
               LEFT JOIN glpi_tickettasks AS gtt ON gtt.tickets_id = gt.id
               WHERE gt.status != 6 AND (gtt.state = 1 OR gtt.state IS NULL)
            )
         GROUP BY gt.id";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getTodaysTicketTasks() {
      global $DB;

      $sql = "
         SELECT
            gt.id,
            IF (gt.name LIKE '%Leaver%', 'Equipment Request', gt.name) AS title,
            COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gtuu.alternative_email) AS customer_name,
            COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
            COALESCE(CONCAT(gtu.firstname, ' ', gtu.realname), 'New') AS task_technician,
            COALESCE(gut.id, '0') AS assigned_id,
            COALESCE(gs.name, 'Request') AS slt,
            gt.date AS date_entered,
            COALESCE(gtt.id, '0') AS task_id,
            COALESCE(gtt.state, '0') AS task_state,
            COALESCE(gtt.end, 'Action Required') AS task_date,
            gt.status,
            COALESCE(gt.time_to_resolve, 'TTR Missing') AS ttr,
            COALESCE(gtt.end, 'Unplanned') AS action_time
         FROM glpi_tickets gt
         LEFT JOIN glpi_tickets_users AS gtut ON gtut.tickets_id = gt.id AND gtut.type = 2
         LEFT JOIN glpi_tickets_users AS gtuu ON gtuu.tickets_id = gt.id AND gtuu.type = 1
         LEFT JOIN glpi_users AS gut ON gut.id = gtut.users_id
         LEFT JOIN glpi_users AS guu ON guu.id = gtuu.users_id
         LEFT JOIN glpi_slas AS gs ON gs.id = gt.slas_id_ttr
         LEFT JOIN glpi_tickettasks AS gtt ON gtt.tickets_id = gt.id
         LEFT JOIN glpi_users AS gtu ON gtu.id = gtt.users_id_tech
         WHERE
            gt.status NOT IN (6)
            AND gtt.state = 1
            AND gtt.end >= CURDATE()
            AND gtt.end < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND gtt.id != 0
            AND gt.is_deleted = 0";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getFutureTicketTasks() {
      global $DB;

      $sql = "SELECT
            gt.id,
            IF (gt.name LIKE '%Leaver%', 'Equipment Request', gt.name) AS title,
            COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gtuu.alternative_email) AS customer_name,
            COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
            COALESCE(CONCAT(gtu.firstname, ' ', gtu.realname), 'New') AS task_technician,
            COALESCE(gut.id, '0') AS assigned_id,
            COALESCE(gs.name, 'Request') AS slt,
            gt.date AS date_entered,
            COALESCE(gtt.id, '0') AS task_id,
            COALESCE(gtt.state, '0') AS task_state,
            COALESCE(gtt.end, 'Action Required') AS task_date,
            gt.status,
            COALESCE(gt.time_to_resolve, 'TTR Missing') AS ttr,
            COALESCE(gtt.end, 'Unplanned') AS action_time
         FROM glpi_tickets gt
         LEFT JOIN glpi_tickets_users AS gtut ON gtut.tickets_id = gt.id AND gtut.type = 2
         LEFT JOIN glpi_tickets_users AS gtuu ON gtuu.tickets_id = gt.id AND gtuu.type = 1
         LEFT JOIN glpi_users AS gut ON gut.id = gtut.users_id
         LEFT JOIN glpi_users AS guu ON guu.id = gtuu.users_id
         LEFT JOIN glpi_slas AS gs ON gs.id = gt.slas_id_ttr
         LEFT JOIN glpi_tickettasks AS gtt ON gtt.tickets_id = gt.id
         LEFT JOIN glpi_users AS gtu ON gtu.id = gtt.users_id_tech
         WHERE
            gt.status NOT IN (6)
            AND gtt.state = 1
            AND gtt.end >= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND gtt.id != 0
            AND gt.is_deleted = 0";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }
}

