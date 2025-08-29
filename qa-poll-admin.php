<?php
/*
	Plugin Name: Qlassy Poll
	Plugin URI: https://github.com/Souravpandev/qlassy-poll
	Plugin Description: Adds polling functionality to questions, allowing users to create polls with multiple options and vote on them
	Plugin Version: 1.0.0
	Plugin Date: 2025-01-20
	Plugin Author: Sourav Pan
	Plugin Author URI: https://github.com/Souravpandev
	Plugin License: GPL v3
	Plugin Minimum Question2Answer Version: 1.6

	This program is free software. You can redistribute and modify it
	under the terms of the GNU General Public License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html
*/

class qa_poll_admin
{
	function init_queries($tableslc)
	{
		$result = array();

		// Create polls table with new fields
		if (!in_array('qa_polls', $tableslc)) {
			$result[] = 'CREATE TABLE IF NOT EXISTS ^polls (
				pollid int(11) NOT NULL AUTO_INCREMENT,
				postid int(11) NOT NULL,
				question text NOT NULL,
				require_login tinyint(1) NOT NULL DEFAULT 0,
				allow_guests tinyint(1) NOT NULL DEFAULT 1,
				show_results_after_close tinyint(1) NOT NULL DEFAULT 0,
				anonymous_voting tinyint(1) NOT NULL DEFAULT 0,
				allow_changing_votes tinyint(1) NOT NULL DEFAULT 1,
				close_date datetime DEFAULT NULL,
				created datetime NOT NULL,
				created_by int(11) NOT NULL,
				PRIMARY KEY (pollid),
				KEY postid (postid),
				KEY created_by (created_by)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8';
		} else {
			// Add new columns to existing table if they don't exist
			$columns = array(
				'require_login' => 'ALTER TABLE ^polls ADD COLUMN require_login tinyint(1) NOT NULL DEFAULT 0',
				'allow_guests' => 'ALTER TABLE ^polls ADD COLUMN allow_guests tinyint(1) NOT NULL DEFAULT 1',
				'show_results_after_close' => 'ALTER TABLE ^polls ADD COLUMN show_results_after_close tinyint(1) NOT NULL DEFAULT 0',
				'anonymous_voting' => 'ALTER TABLE ^polls ADD COLUMN anonymous_voting tinyint(1) NOT NULL DEFAULT 0',
				'allow_changing_votes' => 'ALTER TABLE ^polls ADD COLUMN allow_changing_votes tinyint(1) NOT NULL DEFAULT 1',
				'close_date' => 'ALTER TABLE ^polls ADD COLUMN close_date datetime DEFAULT NULL',
				'created_by' => 'ALTER TABLE ^polls ADD COLUMN created_by int(11) NOT NULL DEFAULT 0'
			);
			
			// Check which columns already exist and only add missing ones
			foreach ($columns as $column => $query) {
				$column_exists = qa_db_read_one_value(qa_db_query_sub(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "qa_polls" AND COLUMN_NAME = $',
					$column
				));
				
				if (!$column_exists) {
					$result[] = $query;
				}
			}
		}

		// Create poll options table
		if (!in_array('qa_poll_options', $tableslc)) {
			$result[] = 'CREATE TABLE IF NOT EXISTS ^poll_options (
				optionid int(11) NOT NULL AUTO_INCREMENT,
				pollid int(11) NOT NULL,
				option_text varchar(255) NOT NULL,
				votes int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (optionid),
				KEY pollid (pollid)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8';
		}

		// Create poll votes table with new fields
		if (!in_array('qa_poll_votes', $tableslc)) {
			$result[] = 'CREATE TABLE IF NOT EXISTS ^poll_votes (
				voteid int(11) NOT NULL AUTO_INCREMENT,
				pollid int(11) NOT NULL,
				optionid int(11) NOT NULL,
				userid int(11) DEFAULT NULL,
				cookieid bigint(20) DEFAULT NULL,
				ip_address varchar(45) DEFAULT NULL,
				voted datetime NOT NULL,
				PRIMARY KEY (voteid),
				UNIQUE KEY unique_vote (pollid, optionid, userid, cookieid),
				KEY pollid (pollid),
				KEY optionid (optionid),
				KEY userid (userid),
				KEY ip_address (ip_address)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8';
		} else {
			// Add new columns to existing table if they don't exist
			$ip_column_exists = qa_db_read_one_value(qa_db_query_sub(
				'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "qa_poll_votes" AND COLUMN_NAME = "ip_address"'
			));
			
			if (!$ip_column_exists) {
				$result[] = 'ALTER TABLE ^poll_votes ADD COLUMN ip_address varchar(45) DEFAULT NULL';
				$result[] = 'ALTER TABLE ^poll_votes ADD KEY ip_address (ip_address)';
			}
		}

		// Create poll user stats table for badges
		if (!in_array('qa_poll_user_stats', $tableslc)) {
			$result[] = 'CREATE TABLE IF NOT EXISTS ^poll_user_stats (
				userid int(11) NOT NULL,
				polls_created int(11) NOT NULL DEFAULT 0,
				polls_voted int(11) NOT NULL DEFAULT 0,
				total_votes_cast int(11) NOT NULL DEFAULT 0,
				popular_polls_created int(11) NOT NULL DEFAULT 0,
				last_updated datetime NOT NULL,
				PRIMARY KEY (userid)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8';
		}

		// Create poll badges table
		if (!in_array('qa_poll_badges', $tableslc)) {
			$result[] = 'CREATE TABLE IF NOT EXISTS ^poll_badges (
				badgeid int(11) NOT NULL AUTO_INCREMENT,
				userid int(11) NOT NULL,
				badge_type varchar(50) NOT NULL,
				badge_name varchar(100) NOT NULL,
				badge_description text,
				awarded_date datetime NOT NULL,
				PRIMARY KEY (badgeid),
				UNIQUE KEY unique_user_badge (userid, badge_type),
				KEY userid (userid),
				KEY badge_type (badge_type)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8';
		}

		return $result;
	}

	function option_default($option)
	{
		switch ($option) {
			case 'poll_enabled':
				return 1;
			case 'poll_show_results_after_close':
				return 0;
			case 'poll_allow_changing_votes':
				return 1;

			case 'poll_show_chart':
				return 1;
			case 'poll_max_options':
				return 10;
			case 'poll_min_options':
				return 2;
			case 'poll_badge_enabled':
				return 1;
			case 'poll_points_for_voting':
				return 1;
			case 'poll_points_for_creating':
				return 5;
			default:
				return null;
		}
	}

	function admin_form(&$qa_content)
	{
		// Ensure database is up to date
		$this->ensure_database_updated();
		
		// Process form submission
		$saved = false;
		if (qa_clicked('poll_save_button')) {
			qa_opt('poll_enabled', (bool)qa_post_text('poll_enabled_field'));
			qa_opt('poll_show_results_after_close', (bool)qa_post_text('poll_show_results_after_close_field'));
			qa_opt('poll_allow_changing_votes', (bool)qa_post_text('poll_allow_changing_votes_field'));

			qa_opt('poll_show_chart', (bool)qa_post_text('poll_show_chart_field'));
			qa_opt('poll_max_options', (int)qa_post_text('poll_max_options_field'));
			qa_opt('poll_min_options', (int)qa_post_text('poll_min_options_field'));
			qa_opt('poll_badge_enabled', (bool)qa_post_text('poll_badge_enabled_field'));
			qa_opt('poll_points_for_voting', (int)qa_post_text('poll_points_for_voting_field'));
			qa_opt('poll_points_for_creating', (int)qa_post_text('poll_points_for_creating_field'));
			
			$saved = true;
		}

		// Define form fields
		$fields = array();
		
		// General Settings
		$fields[] = array(
			'type' => 'static',
			'label' => '<h3>General Settings</h3>',
		);
		
		$fields[] = array(
			'label' => 'Enable Polls:',
			'type' => 'checkbox',
			'value' => qa_opt('poll_enabled'),
			'tags' => 'name="poll_enabled_field"',
			'note' => 'Enable or disable the poll functionality site-wide',
		);

		$fields[] = array(
			'label' => 'Show Chart Visualization:',
			'type' => 'checkbox',
			'value' => qa_opt('poll_show_chart'),
			'tags' => 'name="poll_show_chart_field"',
			'note' => 'Display bar charts showing poll results',
		);

		// User Restrictions
		$fields[] = array(
			'type' => 'static',
			'label' => '<h3>User Restrictions</h3>',
		);

		$fields[] = array(
			'label' => 'Voting Policy:',
			'type' => 'static',
			'value' => 'Only logged-in users can vote in polls. Guest users will see a login notification.',
		);

		// Poll Behavior
		$fields[] = array(
			'type' => 'static',
			'label' => '<h3>Poll Behavior</h3>',
		);

		$fields[] = array(
			'label' => 'Show Results Only After Poll Closes:',
			'type' => 'checkbox',
			'value' => qa_opt('poll_show_results_after_close'),
			'tags' => 'name="poll_show_results_after_close_field"',
			'note' => 'Hide poll results until the poll closing date. Note: This only works if a closing date is set when creating the poll.',
		);

		$fields[] = array(
			'label' => 'Allow Changing Votes:',
			'type' => 'checkbox',
			'value' => qa_opt('poll_allow_changing_votes'),
			'tags' => 'name="poll_allow_changing_votes_field"',
			'note' => 'Allow users to change their votes after voting',
		);





		// Poll Limits
		$fields[] = array(
			'type' => 'static',
			'label' => '<h3>Poll Limits</h3>',
		);

		$fields[] = array(
			'label' => 'Maximum Poll Options:',
			'type' => 'number',
			'value' => qa_opt('poll_max_options'),
			'tags' => 'name="poll_max_options_field"',
			'note' => 'Maximum number of options allowed per poll',
		);

		$fields[] = array(
			'label' => 'Minimum Poll Options:',
			'type' => 'number',
			'value' => qa_opt('poll_min_options'),
			'tags' => 'name="poll_min_options_field"',
			'note' => 'Minimum number of options required per poll',
		);

		// Badge System
		$fields[] = array(
			'type' => 'static',
			'label' => '<h3>Badge & Points System</h3>',
		);

		$fields[] = array(
			'label' => 'Enable Badge System:',
			'type' => 'checkbox',
			'value' => qa_opt('poll_badge_enabled'),
			'tags' => 'name="poll_badge_enabled_field"',
			'note' => 'Enable badges and points for poll activities',
		);

		$fields[] = array(
			'label' => 'Points for Voting:',
			'type' => 'number',
			'value' => qa_opt('poll_points_for_voting'),
			'tags' => 'name="poll_points_for_voting_field"',
			'note' => 'Points awarded to users for voting in polls',
		);

		$fields[] = array(
			'label' => 'Points for Creating Poll:',
			'type' => 'number',
			'value' => qa_opt('poll_points_for_creating'),
			'tags' => 'name="poll_points_for_creating_field"',
			'note' => 'Points awarded to users for creating polls',
		);

		return array(
			'ok' => $saved ? 'Qlassy Poll settings saved successfully!' : null,
			'style' => 'wide',
			'fields' => $fields,
			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="poll_save_button"',
				),
			),
		);
	}

	private function ensure_database_updated()
	{
		// Get current tables
		$tables = qa_db_list_tables();
		
		// Get required queries from init_queries
		$queries = $this->init_queries($tables);
		
		// Execute any pending queries
		if (!empty($queries)) {
			foreach ($queries as $query) {
				try {
					qa_db_query_sub($query);
				} catch (Exception $e) {
					// Silently ignore errors (columns might already exist)
				}
			}
		}
	}
}
