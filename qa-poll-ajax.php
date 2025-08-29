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

class qa_poll_ajax
{
	function match_request($request)
	{
		return $request == 'poll-vote';
	}

	function process_request($request)
	{
		// Set content type to JSON immediately
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-cache, must-revalidate');
		
		$response = array();

		// Get poll data
		$pollid = qa_post_text('pollid');
		$optionid = qa_post_text('optionid');

		if (!$pollid || !$optionid) {
			$response['error'] = 'Invalid poll data.';
			$response['success'] = false;
			http_response_code(400);
			// Clear any output buffers to ensure clean JSON response
			while (ob_get_level()) {
				ob_end_clean();
			}
			echo json_encode($response);
			exit;
		}

		// Get poll information
		$poll = qa_db_read_one_assoc(qa_db_query_sub(
			'SELECT * FROM ^polls WHERE pollid = #',
			$pollid
		));

		if (!$poll) {
			$response['error'] = 'Poll not found.';
			$response['success'] = false;
			http_response_code(404);
			// Clear any output buffers to ensure clean JSON response
			while (ob_get_level()) {
				ob_end_clean();
			}
			echo json_encode($response);
			exit;
		}



		// Check if poll has closed (handle missing column gracefully)
		if (isset($poll['close_date']) && $poll['close_date'] !== null && $poll['close_date'] !== '' && $poll['close_date'] !== '0000-00-00 00:00:00') {
			$close_timestamp = strtotime($poll['close_date']);
			if ($close_timestamp !== false && $close_timestamp < time()) {
				$response['error'] = 'This poll has closed.';
				$response['success'] = false;
				http_response_code(400);
				// Clear any output buffers to ensure clean JSON response
				while (ob_get_level()) {
					ob_end_clean();
				}
				echo json_encode($response);
				exit;
			}
		}

		// Get user information - only logged-in users can vote
		$userid = qa_get_logged_in_userid();

		// Check if user is logged in
		if (!$userid) {
			$response['error'] = 'Please login to vote in this poll.';
			$response['success'] = false;
			http_response_code(401);
			// Clear any output buffers to ensure clean JSON response
			while (ob_get_level()) {
				ob_end_clean();
			}
			echo json_encode($response);
			exit;
		}

		// Check if option exists
		$option = qa_db_read_one_assoc(qa_db_query_sub(
			'SELECT * FROM ^poll_options WHERE optionid = # AND pollid = #',
			$optionid, $pollid
		));

		if (!$option) {
			$response['error'] = 'Poll option not found.';
			$response['success'] = false;
			http_response_code(404);
			// Clear any output buffers to ensure clean JSON response
			while (ob_get_level()) {
				ob_end_clean();
			}
			echo json_encode($response);
			exit;
		}

		// Check if user has already voted for this specific option
		$result = qa_db_query_sub(
			'SELECT COUNT(*) FROM ^poll_votes WHERE pollid = # AND optionid = # AND userid = #',
			$pollid, $optionid, $userid
		);
		$existing_vote_for_option = qa_db_read_one_value($result);

		// Check if user has already voted for any option in this poll
		$result = qa_db_query_sub(
			'SELECT COUNT(*) FROM ^poll_votes WHERE pollid = # AND userid = #',
			$pollid, $userid
		);
		$existing_vote_in_poll = qa_db_read_one_value($result);

		if ($existing_vote_for_option) {
			// User is trying to vote for the same option they already voted for
			// Check if changing votes is allowed
			if (!qa_opt('poll_allow_changing_votes')) {
				$response['error'] = 'You have already voted for this option. Multiple votes for the same option are not allowed.';
				$response['success'] = false;
				http_response_code(400);
				// Clear any output buffers to ensure clean JSON response
				while (ob_get_level()) {
					ob_end_clean();
				}
				echo json_encode($response);
				exit;
			}
			
			// If changing votes is allowed, treat this as an unvote action
			// Remove existing vote
			qa_db_query_sub(
				'DELETE FROM ^poll_votes WHERE pollid = # AND optionid = # AND userid = #',
				$pollid, $optionid, $userid
			);

			// Vote count will be recalculated from actual votes

			$response['success'] = true;
			$response['action'] = 'unvoted';
		} else {
			// User is trying to vote for a new option
			
			// Check if user has already voted in this poll and changing votes is not allowed
			if ($existing_vote_in_poll && !qa_opt('poll_allow_changing_votes')) {
				$response['error'] = 'You have already voted in this poll. Changing votes is not allowed.';
				$response['success'] = false;
				http_response_code(400);
				// Clear any output buffers to ensure clean JSON response
				while (ob_get_level()) {
					ob_end_clean();
				}
				echo json_encode($response);
				exit;
			}

			// If user has already voted and changing votes is allowed, remove previous vote
			if ($existing_vote_in_poll && qa_opt('poll_allow_changing_votes')) {
				// Get the previous option that was voted
				$result = qa_db_query_sub(
					'SELECT optionid FROM ^poll_votes WHERE pollid = # AND userid = # LIMIT 1',
					$pollid, $userid
				);
				$previous_option = qa_db_read_one_value($result);

				// Remove previous vote
				qa_db_query_sub(
					'DELETE FROM ^poll_votes WHERE pollid = # AND userid = #',
					$pollid, $userid
				);

				// Vote count will be recalculated from actual votes
			}



			// Add vote for logged-in user
			qa_db_query_sub(
				'INSERT INTO ^poll_votes (pollid, optionid, userid, voted) VALUES (#, #, #, NOW())',
				$pollid, $optionid, $userid
			);

			// Vote count will be recalculated from actual votes

			// Award points for voting if badge system is enabled
			if (qa_opt('poll_badge_enabled') && $userid) {
				$this->award_voting_points($userid);
				$this->update_user_poll_stats($userid, 'total_votes_cast');
				
				// Trigger badge event
				qa_report_event('poll_voted', $userid, qa_get_logged_in_handle(), qa_cookie_get(), array('pollid' => $pollid, 'optionid' => $optionid));
			}

			$response['success'] = true;
			$response['action'] = 'voted';
		}



		// Check poll popularity for badge system
		if (qa_opt('poll_badge_enabled') && class_exists('qa_poll_badges')) {
			try {
				$badge_handler = new qa_poll_badges();
				$badge_handler->check_poll_popularity($pollid);
			} catch (Exception $e) {
				// Silently ignore badge system errors
			}
		}

		// Recalculate vote counts from actual votes in the database
		$options = qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT po.optionid, po.option_text, COUNT(pv.voteid) as votes 
			 FROM ^poll_options po 
			 LEFT JOIN ^poll_votes pv ON po.pollid = pv.pollid AND po.optionid = pv.optionid 
			 WHERE po.pollid = # 
			 GROUP BY po.optionid, po.option_text 
			 ORDER BY po.optionid',
			$pollid
		));

		$total_votes = 0;
		foreach ($options as &$option) {
			$total_votes += $option['votes'];
		}

		// Check if results should be hidden
		$hide_results = false;
		if (qa_opt('poll_show_results_after_close')) {
			// Get poll close date
			$poll_data = qa_db_read_one_assoc(qa_db_query_sub(
				'SELECT close_date FROM ^polls WHERE pollid = #',
				$pollid
			));
			
			// Only hide results if poll has a closing date
			if ($poll_data && isset($poll_data['close_date']) && $poll_data['close_date'] !== null && $poll_data['close_date'] !== '' && $poll_data['close_date'] !== '0000-00-00 00:00:00') {
				$close_timestamp = strtotime($poll_data['close_date']);
				if ($close_timestamp !== false && $close_timestamp > time()) {
					$hide_results = true;
				}
			}
			// If no closing date is set, don't hide results even if setting is enabled
		}

		// Get user's voted option for highlighting
		$user_voted_option = null;
		if ($userid) {
			$result = qa_db_query_sub(
				'SELECT optionid FROM ^poll_votes WHERE pollid = # AND userid = # LIMIT 1',
				$pollid, $userid
			);
			if (qa_db_num_rows($result) > 0) {
				$user_voted_option = qa_db_read_one_value($result);
			}
		}

		$response['poll_data'] = array(
			'options' => $options,
			'total_votes' => $total_votes,
			'hide_results' => $hide_results,
			'user_voted_option' => $user_voted_option
		);

		// Return JSON response
		// Clear any output buffers to ensure clean JSON response
		while (ob_get_level()) {
			ob_end_clean();
		}
		echo json_encode($response);
		exit;
	}

	private function award_voting_points($userid)
	{
		$points = qa_opt('poll_points_for_voting');
		if ($points > 0) {
					// Check if points column exists before updating
		try {
			$result = qa_db_query_sub(
				'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "qa_users" AND COLUMN_NAME = "points"'
			);
			$points_column_exists = qa_db_read_one_value($result);
				
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
		$result = qa_db_query_sub(
			'SELECT COUNT(*) FROM ^poll_user_stats WHERE userid = #',
			$userid
		);
		$exists = qa_db_read_one_value($result);

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
