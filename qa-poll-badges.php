<?php
/*
	Plugin Name: Qlassy Poll
	Plugin URI: https://github.com/Souravpandev/q2a-qlassy-poll
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
	function process_event($event, $userid, $handle, $cookieid, $params)
	{
		if (!qa_opt('poll_badge_enabled') || !$userid) {
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
		
		// Poll Creator badges
		if ($polls_created >= 1) {
			$this->award_badge($userid, 'poll_creator', 'Poll Creator', 'Created your first poll');
		}
		if ($polls_created >= 5) {
			$this->award_badge($userid, 'poll_enthusiast', 'Poll Enthusiast', 'Created 5 polls');
		}
		if ($polls_created >= 10) {
			$this->award_badge($userid, 'poll_master', 'Poll Master', 'Created 10 polls');
		}
		if ($polls_created >= 25) {
			$this->award_badge($userid, 'poll_expert', 'Poll Expert', 'Created 25 polls');
		}
		if ($polls_created >= 50) {
			$this->award_badge($userid, 'poll_legend', 'Poll Legend', 'Created 50 polls');
		}
	}

	private function check_voting_badges($userid)
	{
		$total_votes = $this->get_user_poll_stat($userid, 'total_votes_cast');
		$polls_voted = $this->get_user_poll_stat($userid, 'polls_voted');
		
		// Voting badges
		if ($total_votes >= 1) {
			$this->award_badge($userid, 'first_voter', 'First Voter', 'Cast your first vote');
		}
		if ($total_votes >= 10) {
			$this->award_badge($userid, 'active_voter', 'Active Voter', 'Voted in 10 polls');
		}
		if ($total_votes >= 25) {
			$this->award_badge($userid, 'dedicated_voter', 'Dedicated Voter', 'Voted in 25 polls');
		}
		if ($total_votes >= 50) {
			$this->award_badge($userid, 'voting_champion', 'Voting Champion', 'Voted in 50 polls');
		}
		if ($total_votes >= 100) {
			$this->award_badge($userid, 'voting_legend', 'Voting Legend', 'Voted in 100 polls');
		}
	}

	private function check_popular_poll_badges($userid)
	{
		$popular_polls = $this->get_user_poll_stat($userid, 'popular_polls_created');
		
		// Popular poll badges
		if ($popular_polls >= 1) {
			$this->award_badge($userid, 'trendsetter', 'Trendsetter', 'Created a popular poll');
		}
		if ($popular_polls >= 3) {
			$this->award_badge($userid, 'influencer', 'Influencer', 'Created 3 popular polls');
		}
		if ($popular_polls >= 5) {
			$this->award_badge($userid, 'viral_creator', 'Viral Creator', 'Created 5 popular polls');
		}
	}

	private function get_user_poll_stat($userid, $field)
	{
		$result = qa_db_read_one_value(qa_db_query_sub(
			'SELECT ' . $field . ' FROM ^poll_user_stats WHERE userid = #',
			$userid
		));
		
		return $result ? $result : 0;
	}

	private function award_badge($userid, $badge_id, $badge_name, $badge_description)
	{
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

	// Check if a poll has become popular (more than 50 votes)
	public function check_poll_popularity($pollid)
	{
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

				// Trigger badge check
				$this->check_popular_poll_badges($poll_creator);
			}
		}
	}

	// Get user badges for display
	public function get_user_badges($userid)
	{
		$badges = qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT badge_type, badge_name, badge_description, awarded_date FROM ^poll_badges WHERE userid = # ORDER BY awarded_date DESC',
			$userid
		));
		
		return $badges;
	}

	// Get user badge count
	public function get_user_badge_count($userid)
	{
		$count = qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^poll_badges WHERE userid = #',
			$userid
		));
		
		return $count ? $count : 0;
	}
}
