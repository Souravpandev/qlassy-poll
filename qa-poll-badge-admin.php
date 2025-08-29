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

class qa_poll_badge_admin
{
	function allow_template($template)
	{
		return ($template!='admin');
	}

	function option_default($option)
	{
		// Provide default values for poll badge options
		$poll_badges = array(
			'poll_creator', 'poll_enthusiast', 'poll_master', 'poll_expert', 'poll_legend',
			'first_voter', 'active_voter', 'dedicated_voter', 'voting_champion', 'voting_legend',
			'trendsetter', 'influencer', 'viral_creator'
		);

		foreach ($poll_badges as $badge) {
			if ($option == 'badge_'.$badge.'_name') {
				return $this->get_badge_name($badge);
			}
			if ($option == 'badge_'.$badge.'_desc') {
				return $this->get_badge_description($badge);
			}
		}

		return null;
	}

	private function get_badge_name($badge_slug)
	{
		$badge_names = array(
			'poll_creator' => 'Poll Creator',
			'poll_enthusiast' => 'Poll Enthusiast',
			'poll_master' => 'Poll Master',
			'poll_expert' => 'Poll Expert',
			'poll_legend' => 'Poll Legend',
			'first_voter' => 'First Voter',
			'active_voter' => 'Active Voter',
			'dedicated_voter' => 'Dedicated Voter',
			'voting_champion' => 'Voting Champion',
			'voting_legend' => 'Voting Legend',
			'trendsetter' => 'Trendsetter',
			'influencer' => 'Influencer',
			'viral_creator' => 'Viral Creator',
		);

		return isset($badge_names[$badge_slug]) ? $badge_names[$badge_slug] : $badge_slug;
	}

	private function get_badge_description($badge_slug)
	{
		$badge_descriptions = array(
			'poll_creator' => 'Created your first poll',
			'poll_enthusiast' => 'Created 5 polls',
			'poll_master' => 'Created 10 polls',
			'poll_expert' => 'Created 25 polls',
			'poll_legend' => 'Created 50 polls',
			'first_voter' => 'Cast your first vote',
			'active_voter' => 'Voted in 10 polls',
			'dedicated_voter' => 'Voted in 25 polls',
			'voting_champion' => 'Voted in 50 polls',
			'voting_legend' => 'Voted in 100 polls',
			'trendsetter' => 'Created a popular poll',
			'influencer' => 'Created 3 popular polls',
			'viral_creator' => 'Created 5 popular polls',
		);

		return isset($badge_descriptions[$badge_slug]) ? $badge_descriptions[$badge_slug] : '';
	}
}
