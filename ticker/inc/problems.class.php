<?php

class PluginTickerProblems {

   static function getProblemsPastSLA() {
      global $DB;

      $sql = "
         SELECT
			gp.id,
			gp.name AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gpuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gpu.firstname, ' ', gpu.realname), 'New') AS task_technician,
			COALESCE(gpu.id, 0)               AS problem_task_technician_id,
			COALESCE(`gut`.`id`, '0') AS problem_assigned_id,
			'Problem' AS slt,
			gp.date AS date_entered,
			COALESCE(`gpt`.`id`, '0') AS task_id,
			COALESCE(`gpt`.`state`, '0') AS task_state,
			COALESCE(`gpt`.`end`,'Action Required') AS task_date,
			gp.status,
			COALESCE(`gp`.`time_to_resolve`,'TTR Missing') AS ttr,
			COALESCE(`gp`.`time_to_resolve`,'Action Required') AS action_time
		FROM
			glpi_problems gp
			LEFT JOIN glpi_problems_users AS gput ON gput.problems_id = gp.id AND gput.type = 2
			LEFT JOIN glpi_problems_users AS gpuu ON gpuu.problems_id = gp.id AND gpuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gput.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gpuu.users_id
			LEFT JOIN glpi_problemtasks AS gpt ON gpt.problems_id = gp.id
			LEFT JOIN glpi_users AS gpu ON gpu.id = gpt.users_id_tech
		WHERE
			gp.status NOT IN (4,6) AND (gp.time_to_resolve IS NULL OR gp.time_to_resolve < NOW())
			AND gp.is_deleted = 0
		GROUP BY gp.id
		ORDER BY end";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));

   }

   static function getProblemTasksPastActionTime() {
      global $DB;

      $sql = "
         SELECT
			gp.id,
			IF (`gp`.`name` LIKE '%Leaver%', 'Equipment Request', `gp`.`name`) AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gpuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gpu.firstname, ' ', gpu.realname), 'New') AS task_technician,
			COALESCE(gpu.id, 0)               AS problem_task_technician_id,
			COALESCE(`gut`.`id`, '0') AS problem_assigned_id,
			'Problem' AS slt,
			gp.date AS date_entered,
			COALESCE(`gpt`.`id`, '0') AS task_id,
			COALESCE(`gpt`.`state`, '0') AS task_state,
			COALESCE(`gpt`.`end`,'Action Required') AS task_date,
			gp.status,
			COALESCE(`gp`.`time_to_resolve`,'TTR Missing') AS ttr,
			COALESCE(gpt.end,'Unplanned')  AS action_time
		FROM
			glpi_problems gp
			LEFT JOIN glpi_problems_users AS gput ON gput.problems_id = gp.id AND gput.type = 2
			LEFT JOIN glpi_problems_users AS gpuu ON gpuu.problems_id = gp.id AND gpuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gput.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gpuu.users_id
			LEFT JOIN glpi_problemtasks AS gpt ON gpt.problems_id = gp.id
			LEFT JOIN glpi_users AS gpu ON gpu.id = gpt.users_id_tech
		WHERE
			gp.status NOT IN (6) AND gpt.state = 1 AND gpt.end < NOW()
			AND gpt.id != 0
			AND gp.is_deleted = 0
		ORDER BY end, slt";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getNewProblems() {
      global $DB;

      $sql = "
         SELECT
			gp.id,
			gp.name AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gpuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gpu.firstname, ' ', gpu.realname), 'New') AS task_technician,
			COALESCE(gpu.id, 0)               AS problem_task_technician_id,
			COALESCE(`gut`.`id`, '0') AS problem_assigned_id,
			'Problem' AS slt,
			gp.date AS date_entered,
			COALESCE(`gpt`.`id`, '0') AS task_id,
			COALESCE(`gpt`.`state`, '0') AS task_state,
			COALESCE(`gpt`.`end`,'Action Required') AS task_date,
			gp.status,
			COALESCE(`gp`.`time_to_resolve`,'TTR Missing') AS ttr,
			COALESCE(gp.time_to_resolve,gp.date + INTERVAL 30 MINUTE)  AS action_time
		FROM
			glpi_problems gp
			LEFT JOIN glpi_problems_users AS gput ON gput.problems_id = gp.id AND gput.type = 2
			LEFT JOIN glpi_problems_users AS gpuu ON gpuu.problems_id = gp.id AND gpuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gput.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gpuu.users_id
			LEFT JOIN glpi_problemtasks AS gpt ON gpt.problems_id = gp.id
			LEFT JOIN glpi_users AS gpu ON gpu.id = gpt.users_id_tech
		WHERE
			gp.status != 6 AND (gpt.state =1 OR gpt.state IS NULL)
			AND gpu.firstname IS NULL
			AND gp.is_deleted = 0
		GROUP BY gp.id
		ORDER BY action_time";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getUnplannedProblems() {
      global $DB;

      $sql = "
         SELECT DISTINCT
			gp.id,
			IF (`gp`.`name` LIKE '%Leaver%', 'Equipment Request', `gp`.`name`) AS title,
			COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gpuu.alternative_email) AS customer_name,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
			COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'New') AS task_technician,
			COALESCE(gpu.id, 0)               AS problem_task_technician_id,
			COALESCE(`gut`.`id`, '0') AS problem_assigned_id,
			'Problem' AS `slt`,
			gp.date AS date_entered,
			'Unplanned' AS task_id,
			'Unplanned' AS task_state,
			'Unplanned' AS task_date,
			'Unplanned' status,
			COALESCE(`gp`.`time_to_resolve`,'TTR Missing') AS ttr,
			COALESCE(`gp`.`time_to_resolve`,'TTR Missing') AS action_time
		FROM
			glpi_problems gp
			LEFT JOIN glpi_problems_users AS gput ON gput.problems_id = gp.id AND gput.type = 2
			LEFT JOIN glpi_problems_users AS gpuu ON gpuu.problems_id = gp.id AND gpuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gput.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gpuu.users_id
			LEFT JOIN glpi_problemtasks AS gpt ON gpt.problems_id = gp.id
			LEFT JOIN glpi_users AS gpu ON gpu.id = gpt.users_id_tech
		WHERE
			gp.status != 6
			AND gp.is_deleted = 0
			AND gp.id not in (SELECT gp.id
			FROM
			glpi_problems gp
			LEFT JOIN glpi_problems_users AS gput ON gput.problems_id = gp.id AND gput.type = 2
			LEFT JOIN glpi_problems_users AS gpuu ON gpuu.problems_id = gp.id AND gpuu.type = 1
			LEFT JOIN glpi_users AS gut ON gut.id = gput.users_id
			LEFT JOIN glpi_users AS guu ON guu.id = gpuu.users_id
			LEFT JOIN glpi_problemtasks AS gpt ON gpt.problems_id = gp.id
			LEFT JOIN glpi_users AS gpu ON gpu.id = gpt.users_id_tech
		WHERE
			gp.status != 6 AND (gpt.state =1 OR gpt.state IS NULL))
		GROUP BY gp.id";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getProblemTasksToday() {
      global $DB;

      $sql = "
         SELECT
                gp.id,
                gp.name AS title,
                COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gpuu.alternative_email) AS customer_name,
                COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
                COALESCE(CONCAT(gpu.firstname, ' ', gpu.realname), 'New') AS task_technician,
				COALESCE(gpu.id, 0)               AS problem_task_technician_id,
                COALESCE(gut.id, '0') AS problem_assigned_id,
                'Problem' AS slt,
                gp.date AS date_entered,
                COALESCE(gpt.id, '0') AS task_id,
                COALESCE(gpt.state, '0') AS task_state,
                COALESCE(gpt.end, 'Action Required') AS task_date,
                gp.status,
                COALESCE(gp.time_to_resolve, 'TTR Missing') AS ttr,
                COALESCE(gpt.end, 'Unplanned') AS action_time
            FROM glpi_problems gp
            LEFT JOIN glpi_problems_users AS gput ON gput.problems_id = gp.id AND gput.type = 2
            LEFT JOIN glpi_problems_users AS gpuu ON gpuu.problems_id = gp.id AND gpuu.type = 1
            LEFT JOIN glpi_users AS gut ON gut.id = gput.users_id
            LEFT JOIN glpi_users AS guu ON guu.id = gpuu.users_id
            LEFT JOIN glpi_problemtasks AS gpt ON gpt.problems_id = gp.id
            LEFT JOIN glpi_users AS gpu ON gpu.id = gpt.users_id_tech
            WHERE
                gp.status NOT IN (6)
                AND gpt.state = 1
                AND gpt.end >= NOW()
                AND gpt.end < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND gpt.id != 0
                AND gp.is_deleted = 0";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }

   static function getFutureProblemTasks() {
      global $DB;

      $sql = "
         SELECT
                gp.id,
                gp.name AS title,
                COALESCE(CONCAT(guu.firstname, ' ', guu.realname), gpuu.alternative_email) AS customer_name,
                COALESCE(CONCAT(gut.firstname, ' ', gut.realname), 'Unallocated') AS technician,
                COALESCE(CONCAT(gpu.firstname, ' ', gpu.realname), 'New') AS task_technician,
				COALESCE(gpu.id, 0)               AS problem_task_technician_id,
                COALESCE(gut.id, '0') AS problem_assigned_id,
                'Problem' AS slt,
                gp.date AS date_entered,
                COALESCE(gpt.id, '0') AS task_id,
                COALESCE(gpt.state, '0') AS task_state,
                COALESCE(gpt.end, 'Action Required') AS task_date,
                gp.status,
                COALESCE(gp.time_to_resolve, 'TTR Missing') AS ttr,
                COALESCE(gpt.end, 'Unplanned') AS action_time
            FROM glpi_problems gp
            LEFT JOIN glpi_problems_users AS gput ON gput.problems_id = gp.id AND gput.type = 2
            LEFT JOIN glpi_problems_users AS gpuu ON gpuu.problems_id = gp.id AND gpuu.type = 1
            LEFT JOIN glpi_users AS gut ON gut.id = gput.users_id
            LEFT JOIN glpi_users AS guu ON guu.id = gpuu.users_id
            LEFT JOIN glpi_problemtasks AS gpt ON gpt.problems_id = gp.id
            LEFT JOIN glpi_users AS gpu ON gpu.id = gpt.users_id_tech
            WHERE
                gp.status NOT IN (6)
                AND gpt.state = 1
                AND gpt.end >= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND gpt.id != 0
                AND gp.is_deleted = 0";

      //return $DB->request($sql)->fetchAll();
      return iterator_to_array($DB->request($sql));
   }
}

