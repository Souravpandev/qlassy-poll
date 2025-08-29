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

class qa_poll_badges
{
	// Check if q2a-badges-master plugin is active
	private function is_badge_plugin_active()
	{
		return function_exists('qa_badge_award_check');
	}

	// Custom badges method for q2a-badges-master integration
	public function custom_badges()
	{
		$badges = array();
		
		// Poll Creation Badges
		$badges['poll_creator'] = array('var'=>1, 'type'=>0);
		$badges['poll_enthusiast'] = array('var'=>5, 'type'=>0);
		$badges['poll_master'] = array('var'=>10, 'type'=>1);
		$badges['poll_expert'] = array('var'=>25, 'type'=>1);
		$badges['poll_legend'] = array('var'=>50, 'type'=>2);
		
		// Voting Badges
		$badges['first_voter'] = array('var'=>1, 'type'=>0);
		$badges['active_voter'] = array('var'=>10, 'type'=>0);
		$badges['dedicated_voter'] = array('var'=>25, 'type'=>1);
		$badges['voting_champion'] = array('var'=>50, 'type'=>1);
		$badges['voting_legend'] = array('var'=>100, 'type'=>2);
		
		// Popular Poll Badges
		$badges['trendsetter'] = array('var'=>1, 'type'=>0);
		$badges['influencer'] = array('var'=>3, 'type'=>1);
		$badges['viral_creator'] = array('var'=>5, 'type'=>2);
		
		return $badges;
	}

	function process_event($event, $userid, $handle, $cookieid, $params)
	{
		// Only process if badge system is enabled and q2a-badges-master is active
		if (!qa_opt('poll_badge_enabled') || !$userid || !$this->is_badge_plugin_active()) {
			return;
		}

		switch ($event) {
			case 'poll_created':
				$this->check_poll_creation_badges($userid);
				break;
			case 'poll_voted':
				$this->check_voting_badges($userid);
				break;
			case 'poll_popular':
				$this->check_popular_poll_badges($userid);
				break;
		}
	}

	private function check_poll_creation_badges($userid)
	{
		$polls_created = $this->get_user_poll_stat($userid, 'polls_created');
		
		// Use q2a-badges-master system to award badges
		$badges = array('poll_creator', 'poll_enthusiast', 'poll_master', 'poll_expert', 'poll_legend');
		qa_badge_award_check($badges, $polls_created, $userid);
	}

	private function check_voting_badges($userid)
	{
		$total_votes = $this->get_user_poll_stat($userid, 'total_votes_cast');
		
		// Use q2a-badges-master system to award badges
		$badges = array('first_voter', 'active_voter', 'dedicated_voter', 'voting_champion', 'voting_legend');
		qa_badge_award_check($badges, $total_votes, $userid);
	}

	private function check_popular_poll_badges($userid)
	{
		$popular_polls = $this->get_user_poll_stat($userid, 'popular_polls_created');
		
		// Use q2a-badges-master system to award badges
		$badges = array('trendsetter', 'influencer', 'viral_creator');
		qa_badge_award_check($badges, $popular_polls, $userid);
	}

	private function get_user_poll_stat($userid, $field)
	{
		$result = qa_db_read_one_value(qa_db_query_sub(
			'SELECT ' . $field . ' FROM ^poll_user_stats WHERE userid = #',
			$userid
		));
		
		return $result ? $result : 0;
	}

	// Check if a poll has become popular (more than 50 votes)
	public function check_poll_popularity($pollid)
	{
		// Only check if q2a-badges-master is active
		if (!$this->is_badge_plugin_active()) {
			return;
		}

		$total_votes = qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^poll_votes WHERE pollid = #',
			$pollid
		));

		if ($total_votes >= 50) {
			$poll_creator = qa_db_read_one_value(qa_db_query_sub(
				'SELECT created_by FROM ^polls WHERE pollid = #',
				$pollid
			));

			if ($poll_creator) {
				// Update popular polls count
				qa_db_query_sub(
					'UPDATE ^poll_user_stats SET popular_polls_created = popular_polls_created + 1 WHERE userid = #',
					$poll_creator
				);

				// Trigger badge check using q2a-badges-master
				$this->check_popular_poll_badges($poll_creator);
			}
		}
	}

	// Legacy methods for backward compatibility (if q2a-badges-master is not active)
	private function award_badge($userid, $badge_id, $badge_name, $badge_description)
	{
		// Only use legacy system if q2a-badges-master is not active
		if ($this->is_badge_plugin_active()) {
			return; // Let q2a-badges-master handle it
		}

		// Check if user already has this badge
		$existing = qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^poll_badges WHERE userid = # AND badge_type = $',
			$userid, $badge_id
		));

		if (!$existing) {
			// Award the badge
			qa_db_query_sub(
				'INSERT INTO ^poll_badges (userid, badge_type, badge_name, badge_description, awarded_date) VALUES (#, $, $, $, NOW())',
				$userid, $badge_id, $badge_name, $badge_description
			);

			// Add points for badge (check if points column exists)
			try {
				$points_column_exists = qa_db_read_one_value(qa_db_query_sub(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "qa_users" AND COLUMN_NAME = "points"'
				));
				
				if ($points_column_exists) {
					qa_db_query_sub(
						'UPDATE ^users SET points = points + 10 WHERE userid = #',
						$userid
					);
				}
			} catch (Exception $e) {
				// Silently ignore if points column doesn't exist
			}
		}
	}

	// Get user badges for display (legacy method)
	public function get_user_badges($userid)
	{
		// If q2a-badges-master is active, use its system
		if ($this->is_badge_plugin_active() && function_exists('qa_badge_plugin_user_form')) {
			return array(); // Let q2a-badges-master handle display
		}

		// Legacy display method
		$badges = qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT badge_type, badge_name, badge_description, awarded_date FROM ^poll_badges WHERE userid = # ORDER BY awarded_date DESC',
			$userid
		));
		
		return $badges;
	}

	// Get user badge count (legacy method)
	public function get_user_badge_count($userid)
	{
		// If q2a-badges-master is active, use its system
		if ($this->is_badge_plugin_active() && function_exists('qa_badge_plugin_user_widget')) {
			return 0; // Let q2a-badges-master handle display
		}

		// Legacy count method
		$count = qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^poll_badges WHERE userid = #',
			$userid
		));
		
		return $count ? $count : 0;
	}
}
