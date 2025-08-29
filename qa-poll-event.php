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

class qa_poll_event
{
	function process_event($event, $userid, $handle, $cookieid, $params)
	{
		// Handle question creation to save poll data
		if ($event == 'q_post' && isset($params['postid'])) {
			$this->save_poll_data($params['postid']);
		}
	}

	private function save_poll_data($postid)
	{
		// Check if poll data was submitted
		$poll_question = qa_post_text('poll_question');
		$poll_options = qa_post_array('poll_options');

		$show_results_after_close = qa_opt('poll_show_results_after_close') ? 1 : 0;
		$allow_changing_votes = qa_opt('poll_allow_changing_votes') ? 1 : 0;
		$close_date = qa_post_text('poll_close_date'); // Only close_date is per-poll
		// Convert empty string to NULL for proper database handling
		if (empty($close_date)) {
			$close_date = null;
		}
		$created_by = qa_get_logged_in_userid();

		// Only create poll if question and at least 2 options are provided
		if (!empty($poll_question) && is_array($poll_options)) {
			$valid_options = array();
			foreach ($poll_options as $option_text) {
				$option_text = trim($option_text);
				if (!empty($option_text)) {
					$valid_options[] = $option_text;
				}
			}

			if (count($valid_options) >= 2) {
				// Create poll record with new fields (handle missing columns gracefully)
				try {
					qa_db_query_sub(
						'INSERT INTO ^polls (postid, question, show_results_after_close, allow_changing_votes, close_date, created, created_by) VALUES (#, $, #, #, $, NOW(), #)',
						$postid, $poll_question, $show_results_after_close, $allow_changing_votes, $close_date, $created_by
					);
				} catch (Exception $e) {
					// Fallback to basic poll creation if new columns don't exist
					qa_db_query_sub(
						'INSERT INTO ^polls (postid, question, created) VALUES (#, $, NOW())',
						$postid, $poll_question
					);
				}

				$pollid = qa_db_last_insert_id();

				// Create poll options
				foreach ($valid_options as $option_text) {
					qa_db_query_sub(
						'INSERT INTO ^poll_options (pollid, option_text, votes) VALUES (#, $, 0)',
						$pollid, $option_text
					);
				}

				// Award points for creating poll if badge system is enabled
				if (qa_opt('poll_badge_enabled') && $created_by) {
					$this->award_poll_creation_points($created_by);
					$this->update_user_poll_stats($created_by, 'polls_created');
					
					// Trigger badge event
					qa_report_event('poll_created', $created_by, qa_get_logged_in_handle(), qa_cookie_get(), array('pollid' => $pollid));
				}
			}
		}
	}

	private function award_poll_creation_points($userid)
	{
		$points = qa_opt('poll_points_for_creating');
		if ($points > 0) {
			// Check if points column exists before updating
			try {
				$points_column_exists = qa_db_read_one_value(qa_db_query_sub(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "qa_users" AND COLUMN_NAME = "points"'
				));
				
				if ($points_column_exists) {
					qa_db_query_sub(
						'UPDATE ^users SET points = points + # WHERE userid = #',
						$points, $userid
					);
				}
			} catch (Exception $e) {
				// Silently ignore if points column doesn't exist
			}
		}
	}

	private function update_user_poll_stats($userid, $field)
	{
		// Check if user stats exist
		$exists = qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^poll_user_stats WHERE userid = #',
			$userid
		));

		if ($exists) {
			qa_db_query_sub(
				'UPDATE ^poll_user_stats SET ' . $field . ' = ' . $field . ' + 1, last_updated = NOW() WHERE userid = #',
				$userid
			);
		} else {
			qa_db_query_sub(
				'INSERT INTO ^poll_user_stats (userid, ' . $field . ', last_updated) VALUES (#, 1, NOW())',
				$userid
			);
		}
	}
}
