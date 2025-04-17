<?php

class PluginTickerChanges {

   static function getChangesPastSLA() {
      global $DB;

      $sql = "
         SELECT
			gc.id,
			gc.name AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gcuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gcu.firstname, ' ', gcu.realname), 'New') AS task_technician,
			COALESCE(`gut`.`id`, '0') AS assigned_id,
			'Change Control' AS slt,
			gc.date AS date_entered,
			COALESCE(`gct`.`id`, '0') AS task_id,
			COALESCE(`gct`.`state`, '0') AS task_state,
			COALESCE(`gct`.`end`,'Action Required') AS task_date,
			gc.status,
			COALESCE(`gc`.`time_to_resolve`,'TTR Missing') AS ttr,
			COALESCE(`gc`.`time_to_resolve`,'Action Required') AS action_time
		FROM
			glpi_changes gc
			LEFT JOIN glpi_changes_users AS gcut ON gcut.changes_id = gc.id AND gcut.type = 2
			LEFT JOIN glpi_changes_users AS gcuu ON gcuu.changes_id = gc.id AND gcuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gcut.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gcuu.users_id
			LEFT JOIN glpi_changetasks AS gct ON gct.changes_id = gc.id
			LEFT JOIN glpi_users AS gcu ON gcu.id = gct.users_id_tech
		WHERE
			gc.status NOT IN (4,6,14) AND (gc.time_to_resolve IS NULL OR gc.time_to_resolve < NOW())
			AND gc.is_deleted = 0
			GROUP BY gc.id
			ORDER BY end";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));

   }

   static function getChangeTasksPastActionTime() {
      global $DB;

      $sql = "
         SELECT
			gc.id,
			IF (`gc`.`name` LIKE '%Leaver%', 'Equipment Request', `gc`.`name`) AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gcuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gcu.firstname, ' ', gcu.realname), 'New') AS task_technician,
			COALESCE(`gut`.`id`, '0') AS assigned_id,
			'Change Control' AS slt,
			gc.date AS date_entered,
			COALESCE(`gct`.`id`, '0') AS task_id,
			COALESCE(`gct`.`state`, '0') AS task_state,
			COALESCE(`gct`.`end`,'Action Required') AS task_date,
			gc.status,
			COALESCE(`gc`.`time_to_resolve`,'TTR Missing') AS ttr,
			COALESCE(gct.end,'Unplanned')  AS action_time
		FROM
			glpi_changes gc
			LEFT JOIN glpi_changes_users AS gcut ON gcut.changes_id = gc.id AND gcut.type = 2
			LEFT JOIN glpi_changes_users AS gcuu ON gcuu.changes_id = gc.id AND gcuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gcut.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gcuu.users_id
			LEFT JOIN glpi_changetasks AS gct ON gct.changes_id = gc.id
			LEFT JOIN glpi_users AS gcu ON gcu.id = gct.users_id_tech
		WHERE
			gc.status NOT IN (4,6,14) AND gct.state = 1 AND gct.end < NOW()
			AND gct.id != 0
			AND gc.is_deleted = 0
			ORDER BY end, slt";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getNewChanges() {
      global $DB;

      $sql = "
         SELECT
			gc.id,
			gc.name AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gcuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gcu.firstname, ' ', gcu.realname), 'New') AS task_technician,
			COALESCE(`gut`.`id`, '0') AS assigned_id,
			'Change Control' AS slt,
			gc.date AS date_entered,
			COALESCE(`gct`.`id`, '0') AS task_id,
			COALESCE(`gct`.`state`, '0') AS task_state,
			COALESCE(`gct`.`end`,'Action Required') AS task_date,
			gc.status,
			COALESCE(`gc`.`time_to_resolve`,'TTR Missing') AS ttr,
			COALESCE(gc.time_to_resolve,gc.date + INTERVAL 30 MINUTE)  AS action_time
		FROM
			glpi_changes gc
			LEFT JOIN glpi_changes_users AS gcut ON gcut.changes_id = gc.id AND gcut.type = 2
			LEFT JOIN glpi_changes_users AS gcuu ON gcuu.changes_id = gc.id AND gcuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gcut.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gcuu.users_id
			LEFT JOIN glpi_changetasks AS gct ON gct.changes_id = gc.id
			LEFT JOIN glpi_users AS gcu ON gcu.id = gct.users_id_tech
		WHERE
			gc.status != 6 AND (gct.state =1 OR gct.state IS NULL)
			AND gcu.firstname IS NULL
			AND gc.is_deleted = 0
			GROUP BY gc.id
			ORDER BY action_time";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getUnplannedChanges() {
      global $DB;

      $sql = "
         SELECT DISTINCT
			gc.id,
			IF (`gc`.`name` LIKE '%Leaver%', 'Equipment Request', `gc`.`name`) AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gcuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'New') AS task_technician,
			COALESCE(`gut`.`id`, '0') AS assigned_id,
			'Change Control' AS `slt`,
			gc.date AS date_entered,
			'Unplanned' AS task_id,
			'Unplanned' AS task_state,
			'Unplanned' AS task_date,
			'Unplanned' status,
			COALESCE(`gc`.`time_to_resolve`,'TTR Missing') AS ttr,
			COALESCE(`gc`.`time_to_resolve`,'TTR Missing') AS action_time
		FROM
			glpi_changes gc
			LEFT JOIN glpi_changes_users AS gcut ON gcut.changes_id = gc.id AND gcut.type = 2
			LEFT JOIN glpi_changes_users AS gcuu ON gcuu.changes_id = gc.id AND gcuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gcut.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gcuu.users_id
			LEFT JOIN glpi_changetasks AS gct ON gct.changes_id = gc.id
			LEFT JOIN glpi_users AS gcu ON gcu.id = gct.users_id_tech
		WHERE
			gc.status != 6
			AND gc.is_deleted = 0
			AND gc.id not in (SELECT gc.id
		FROM
			glpi_changes gc
			LEFT JOIN glpi_changes_users AS gcut ON gcut.changes_id = gc.id AND gcut.type = 2
			LEFT JOIN glpi_changes_users AS gcuu ON gcuu.changes_id = gc.id AND gcuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gcut.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gcuu.users_id
			LEFT JOIN glpi_changetasks AS gct ON gct.changes_id = gc.id
			LEFT JOIN glpi_users AS gcu ON gcu.id = gct.users_id_tech
		WHERE
			gc.status != 6 AND (gct.state =1 OR gct.state IS NULL))
		GROUP BY gc.id";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getTodaysChangeTasks() {
      global $DB;

      $sql = "
         SELECT
			gc.id,
			IF (`gc`.`name` LIKE '%Leaver%', 'Equipment Request', `gc`.`name`) AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gcuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gcu.firstname, ' ', gcu.realname), 'New') AS task_technician,
			COALESCE(`gut`.`id`, '0') AS assigned_id,
			'Change Control' AS `slt`,
			gc.date AS date_entered,
			COALESCE(`gct`.`id`, '0') AS task_id,
			COALESCE(`gct`.`state`, '0') AS task_state,
			COALESCE(`gct`.`end`,'Action Required') AS task_date,
			gc.status,
			COALESCE(`gc`.`time_to_resolve`,'TTR Missing') AS ttr,
			COALESCE(gct.end,'Unplanned')  AS action_time
		FROM
			glpi_changes gc
			LEFT JOIN glpi_changes_users AS gcut ON gcut.changes_id = gc.id AND gcut.type = 2
			LEFT JOIN glpi_changes_users AS gcuu ON gcuu.changes_id = gc.id AND gcuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gcut.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gcuu.users_id
			LEFT JOIN glpi_changetasks AS gct ON gct.changes_id = gc.id
			LEFT JOIN glpi_users AS gcu ON gcu.id = gct.users_id_tech
		WHERE
                gc.status NOT IN (6)
                AND gct.state = 1
                AND gct.end >= CURDATE()
                AND gct.end < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND gct.id != 0
                AND gc.is_deleted = 0";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getFutureChangeTasks() {
      global $DB;

      $sql = "
         SELECT
			gc.id,
			IF (`gc`.`name` LIKE '%Leaver%', 'Equipment Request', `gc`.`name`) AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gcuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gcu.firstname, ' ', gcu.realname), 'New') AS task_technician,
			COALESCE(`gut`.`id`, '0') AS assigned_id,
			'Change Control' AS `slt`,
			gc.date AS date_entered,
			COALESCE(`gct`.`id`, '0') AS task_id,
			COALESCE(`gct`.`state`, '0') AS task_state,
			COALESCE(`gct`.`end`,'Action Required') AS task_date,
			gc.status,
			COALESCE(`gc`.`time_to_resolve`,'TTR Missing') AS ttr,
			COALESCE(gct.end,'Unplanned')  AS action_time
		FROM
			glpi_changes gc
			LEFT JOIN glpi_changes_users AS gcut ON gcut.changes_id = gc.id AND gcut.type = 2
			LEFT JOIN glpi_changes_users AS gcuu ON gcuu.changes_id = gc.id AND gcuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gcut.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gcuu.users_id
			LEFT JOIN glpi_changetasks AS gct ON gct.changes_id = gc.id
			LEFT JOIN glpi_users AS gcu ON gcu.id = gct.users_id_tech
		WHERE
                gc.status NOT IN (6)
                AND gct.state = 1
                AND gct.end >= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND gct.id != 0
                AND gc.is_deleted = 0";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }
}

