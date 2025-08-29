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

class qa_html_theme_layer extends qa_html_theme_base
{
	function head_css()
	{
		qa_html_theme_base::head_css();
		
		// Add CSS for poll functionality on ask and question pages
		if ($this->template == 'ask' || $this->template == 'question') {
			$this->output('<link rel="stylesheet" type="text/css" href="' . qa_html(qa_path_to_root() . 'qa-plugin/qlassy-poll/css/poll.min.css?v=' . QA_VERSION) . '">');
		}
	}

	function head_script()
	{
		qa_html_theme_base::head_script();
		
		// Add JavaScript for poll functionality on ask and question pages
		if ($this->template == 'ask' || $this->template == 'question') {
			// Add AJAX URL as JavaScript variable
			$this->output('<script type="text/javascript">var pollAjaxURL = "' . qa_path_html('poll-vote') . '";</script>');
			
			// Load Chart.js from CDN if chart display is enabled
			if (qa_opt('poll_show_chart')) {
				$this->output('<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>');
			}
			
			// Load the main JavaScript file
			$this->output('<script type="text/javascript" src="' . qa_html(qa_path_to_root() . 'qa-plugin/qlassy-poll/js/poll.min.js?v=' . QA_VERSION) . '"></script>');
		}
	}

	function form($form)
	{
		// Add poll field to ask form before the content field
		if ($this->template == 'ask' && isset($form['fields']['content'])) {
			$poll_field = array(
				'type' => 'custom',
				'html' => $this->get_poll_field_html(),
			);

			// Insert poll field before content field
			$new_fields = array();
			foreach ($form['fields'] as $key => $field) {
				if ($key == 'content') {
					$new_fields['poll'] = $poll_field;
				}
				$new_fields[$key] = $field;
			}
			$form['fields'] = $new_fields;
		}

		qa_html_theme_base::form($form);
	}

	function q_view_content($q_view)
	{
		// Only display poll on the main question view page, not on edit or other pages
		$current_url = qa_request();
		$current_state = qa_get('state');
		$qa_action = qa_get('qa_action');
		
		// Check if we're on an edit page or other action page
		// Also check if the page has the edit form present (which indicates edit mode)
		if ($this->template !== 'question' || 
			strpos($current_url, '/edit') !== false || 
			strpos($current_url, '/answer') !== false || 
			strpos($current_url, '/comment') !== false || 
			strpos($current_url, '/flag') !== false ||
			(strpos($current_state, 'edit') !== false) ||
			$qa_action === 'edit' ||
			isset($this->content['form_q_edit'])) {
			qa_html_theme_base::q_view_content($q_view);
			return;
		}
		
		// Display poll if it exists for this question
		if (isset($q_view['raw']['postid'])) {
			try {
				$poll = $this->get_poll_data($q_view['raw']['postid']);
				if ($poll && isset($poll['options']) && is_array($poll['options'])) {
					// Check if results should be hidden
					$hide_results = false;
					if (qa_opt('poll_show_results_after_close')) {
						// Only hide results if poll has a closing date
						if (isset($poll['close_date']) && $poll['close_date'] !== null && $poll['close_date'] !== '' && $poll['close_date'] !== '0000-00-00 00:00:00') {
							$close_timestamp = strtotime($poll['close_date']);
							if ($close_timestamp !== false && $close_timestamp > time()) {
								$hide_results = true;
							}
						}
						// If no closing date is set, don't hide results even if setting is enabled
					}
					
					$this->output('<div class="qa-poll-container">');
					$this->output('<div class="qa-poll-question">' . qa_html($poll['question']) . '</div>');
					
					$this->output('<div class="qa-poll-options">');
					
					$total_votes = 0;
					foreach ($poll['options'] as $option) {
						$total_votes += $option['votes'];
					}

					// Check if current user has voted and for which option
					$user_voted_option = null;
					$userid = qa_get_logged_in_userid();
					if ($userid) {
						$result = qa_db_query_sub(
							'SELECT optionid FROM ^poll_votes WHERE pollid = # AND userid = # LIMIT 1',
							$poll['pollid'], $userid
						);
						if (qa_db_num_rows($result) > 0) {
							$user_voted_option = qa_db_read_one_value($result);
						}
					}

					foreach ($poll['options'] as $option) {
						$percentage = $total_votes > 0 ? round(($option['votes'] / $total_votes) * 100) : 0;
						
						// Check if user voted for this option
						$vote_class = '';
						if ($user_voted_option && $user_voted_option == $option['optionid']) {
							$vote_class = 'qa-poll-option-voted';
						}
						
						$this->output('<div class="qa-poll-option ' . $vote_class . '" data-pollid="' . $poll['pollid'] . '" data-optionid="' . $option['optionid'] . '">');
						
						// Progress bar background
						$this->output('<div class="qa-poll-option-progress">');
						if (!$hide_results) {
							$this->output('<div class="qa-poll-option-fill" style="width: ' . $percentage . '%"></div>');
						}
						$this->output('</div>');
						
						// Option content wrapper - contains radio and text
						$this->output('<div class="qa-poll-option-content">');
						$this->output('<div class="qa-poll-option-radio"></div>');
						$this->output('<div class="qa-poll-option-text">' . qa_html($option['option_text']) . '</div>');
						$this->output('</div>');
						
						// Votes display outside the content wrapper
						if (!$hide_results) {
							$this->output('<div class="qa-poll-option-votes">');
							$this->output('<span class="vote-percentage">' . $percentage . '%</span>');
							$this->output('<span class="vote-count">(' . $option['votes'] . ')</span>');
							$this->output('<span class="polling_icon">');
							$this->output('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024"><path fill="currentColor" d="M340.864 149.312a30.592 30.592 0 0 0 0 42.752L652.736 512 340.864 831.872a30.592 30.592 0 0 0 0 42.752 29.12 29.12 0 0 0 41.728 0L714.24 534.336a32 32 0 0 0 0-44.672L382.592 149.376a29.12 29.12 0 0 0-41.728 0z"></path></svg>');
							$this->output('</span>');
							$this->output('</div>');
						}
						
						$this->output('</div>');
					}
					
					$this->output('</div>');
					
					if (!$hide_results) {
						$this->output('<div class="qa-poll-total">');
						$this->output('<span class="qa-poll-total-votes">' . $total_votes . ' votes</span>');
						
						// Display voter avatars in stack format
						$this->output('<div class="qa-poll-voter-avatars">');
						
						// Collect all unique voters from all options
						$all_voters = array();
						foreach ($poll['options'] as $option) {
							if (isset($option['voters']) && is_array($option['voters'])) {
								foreach ($option['voters'] as $voter) {
									if ($voter['userid'] && !isset($all_voters[$voter['userid']])) {
										$all_voters[$voter['userid']] = $voter;
									}
								}
							}
						}
						
						// Display up to 8 avatars in stack
						$avatar_count = 0;
						foreach ($all_voters as $voter) {
							if ($avatar_count >= 8) break;
							
							$avatar_html = qa_get_user_avatar_html(
								$voter['flags'], 
								$voter['email'], 
								$voter['handle'], 
								$voter['avatarblobid'], 
								$voter['avatarwidth'], 
								$voter['avatarheight'], 
								24, // 24px size for stack
								false // no padding
							);
							
							if ($avatar_html) {
								$this->output('<div class="qa-poll-voter-avatar" title="' . qa_html($voter['handle']) . '">' . $avatar_html . '</div>');
							}
							
							$avatar_count++;
						}
						
						// Show count of additional voters if any
						$total_voters = count($all_voters);
						if ($total_voters > 8) {
							$this->output('<div class="qa-poll-voter-count">+' . ($total_voters - 8) . '</div>');
						}
						
						$this->output('</div>');
						$this->output('</div>');
					}
					
					// Add chart display if enabled
					if (qa_opt('poll_show_chart') && !$hide_results && $total_votes > 0) {
						$this->output('<div class="qa-poll-chart-container">');
						$this->output('<h4>Poll Results Chart</h4>');
						$this->output('<div class="qa-poll-chart-wrapper">');
						$this->output('<canvas id="poll-chart-' . $poll['pollid'] . '"></canvas>');
						$this->output('</div>');
						$this->output('</div>');
						
						// Add chart initialization script
						$this->output('<script type="text/javascript">');
						$this->output('document.addEventListener("DOMContentLoaded", function() {');
						$this->output('    var ctx = document.getElementById("poll-chart-' . $poll['pollid'] . '").getContext("2d");');
						$this->output('    var pollChart = new Chart(ctx, {');
						$this->output('        type: "bar",');
						$this->output('        data: {');
						$this->output('            labels: [');
						
						// Add option labels (truncate long labels for mobile)
						$labels = array();
						$data = array();
						$colors = array();
						foreach ($poll['options'] as $option) {
							// Truncate long option text for better mobile display
							$option_text = $option['option_text'];
							if (strlen($option_text) > 30) {
								$option_text = substr($option_text, 0, 27) . '...';
							}
							$labels[] = "'" . addslashes($option_text) . "'";
							$data[] = $option['votes'];
							$colors[] = "'rgba(0, 0, 0, 0.8)'";
						}
						
						$this->output('                ' . implode(', ', $labels));
						$this->output('            ],');
						$this->output('            datasets: [{');
						$this->output('                label: "Votes",');
						$this->output('                data: [' . implode(', ', $data) . '],');
						$this->output('                backgroundColor: [' . implode(', ', $colors) . '],');
						$this->output('                borderColor: "rgba(0, 0, 0, 1)",');
						$this->output('                borderWidth: 1');
						$this->output('            }]');
						$this->output('        },');
						$this->output('        options: {');
						$this->output('            responsive: true,');
						$this->output('            maintainAspectRatio: true,');
						$this->output('            aspectRatio: 2,');
						$this->output('            scales: {');
						$this->output('                x: {');
						$this->output('                    ticks: {');
						$this->output('                        maxRotation: 45,');
						$this->output('                        minRotation: 0,');
						$this->output('                        autoSkip: true,');
						$this->output('                        maxTicksLimit: 10');
						$this->output('                    }');
						$this->output('                },');
						$this->output('                y: {');
						$this->output('                    beginAtZero: true,');
						$this->output('                    ticks: {');
						$this->output('                        stepSize: 1');
						$this->output('                    }');
						$this->output('                }');
						$this->output('            },');
						$this->output('            plugins: {');
						$this->output('                legend: {');
						$this->output('                    display: false');
						$this->output('                }');
						$this->output('            }');
						$this->output('        }');
						$this->output('    });');
						$this->output('});');
						$this->output('</script>');
					}
					
					$this->output('</div>');
				}
			} catch (Exception $e) {
				// Silently ignore poll display errors
			}
		}

		qa_html_theme_base::q_view_content($q_view);
	}

	function q_view($q_view)
	{
		// Check if we're in edit mode - if so, don't display the q_view section at all
		if (isset($this->content['form_q_edit'])) {
			// In edit mode, don't display the q_view section
			return;
		}
		
		// Otherwise, display normally
		qa_html_theme_base::q_view($q_view);
	}

	function q_view_main($q_view)
	{
		// Check if we're in edit mode - if so, don't display the q_view_main section at all
		if (isset($this->content['form_q_edit'])) {
			// In edit mode, don't display the q_view_main section
			return;
		}
		
		// Otherwise, display normally
		qa_html_theme_base::q_view_main($q_view);
	}

	private function get_poll_field_html()
	{
		$html = '<div class="qa-poll-field">';
		$html .= '<div class="qa-poll-field-header">';
		$html .= '<label>Add a Poll</label>';
		$html .= '<button type="button" class="qa-poll-toggle-btn" onclick="togglePollForm()">';
		$html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">';
		$html .= '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>';
		$html .= '</svg>';
		$html .= ' Add Poll</button>';
		$html .= '</div>';
		
		$html .= '<div class="qa-poll-form" style="display: none;">';
		$html .= '<div class="qa-poll-question-field">';
		$html .= '<label for="poll_question">Poll Question:</label>';
		$html .= '<input type="text" name="poll_question" id="poll_question" placeholder="Enter your poll question..." class="qa-form-tall-text">';
		$html .= '</div>';
		
		$html .= '<div class="qa-poll-options-field">';
		$html .= '<label>Poll Options:</label>';
		$html .= '<div class="qa-poll-options-list">';
		$html .= '<div class="qa-poll-option-input">';
		$html .= '<input type="text" name="poll_options[]" placeholder="Option 1" class="qa-form-tall-text">';
		$html .= '</div>';
		$html .= '<div class="qa-poll-option-input">';
		$html .= '<input type="text" name="poll_options[]" placeholder="Option 2" class="qa-form-tall-text">';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<button type="button" class="qa-poll-add-option" onclick="addPollOption()">+ Add Option</button>';
		$html .= '</div>';
		
		$html .= '<div class="qa-poll-settings">';
		$html .= '<h4>Poll Settings</h4>';
		$html .= '<p class="qa-poll-settings-note">Poll behavior is controlled by admin settings. You can only set an optional closing date below.</p>';
		
		$html .= '<div class="qa-poll-setting-group">';
		$html .= '<label for="poll_close_date">Close Date (Optional):</label>';
		$html .= '<input type="datetime-local" name="poll_close_date" id="poll_close_date" class="qa-form-tall-text">';
		$html .= '<small>Leave empty if you don\'t want the poll to close automatically</small>';
		$html .= '</div>';
		
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	private function get_poll_data($postid)
	{
		if (!$postid) {
			return null;
		}

		// First check if poll exists
		$result = qa_db_query_sub(
			'SELECT COUNT(*) FROM ^polls WHERE postid = #',
			$postid
		);
		$poll_exists = qa_db_read_one_value($result);

		if (!$poll_exists) {
			return null;
		}

		// Now get the poll data (handle missing columns gracefully)
		try {
			$poll = qa_db_read_one_assoc(qa_db_query_sub(
				'SELECT pollid, question, require_login, allow_guests, show_results_after_close, anonymous_voting, allow_changing_votes, close_date, created_by FROM ^polls WHERE postid = #',
				$postid
			));
		} catch (Exception $e) {
			// Fallback to basic poll data if new columns don't exist
			$poll = qa_db_read_one_assoc(qa_db_query_sub(
				'SELECT pollid, question FROM ^polls WHERE postid = #',
				$postid
			));
		}

		if (!$poll) {
			return null;
		}

		// Get poll options with vote counts in a single query
		$options = qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT po.optionid, po.option_text, COUNT(pv.voteid) as votes 
			 FROM ^poll_options po 
			 LEFT JOIN ^poll_votes pv ON po.pollid = pv.pollid AND po.optionid = pv.optionid 
			 WHERE po.pollid = # 
			 GROUP BY po.optionid, po.option_text 
			 ORDER BY po.optionid',
			$poll['pollid']
		));

		// Get user information for voters of each option
		foreach ($options as &$option) {
			$voters = qa_db_read_all_assoc(qa_db_query_sub(
				'SELECT pv.userid, u.handle, u.flags, u.email, u.avatarblobid, u.avatarwidth, u.avatarheight 
				 FROM ^poll_votes pv 
				 LEFT JOIN ^users u ON pv.userid = u.userid 
				 WHERE pv.pollid = # AND pv.optionid = # 
				 ORDER BY pv.voted DESC 
				 LIMIT 10',
				$poll['pollid'], $option['optionid']
			));
			
			$option['voters'] = $voters;
		}

		$poll['options'] = $options;
		
		return $poll;
	}

	private function has_user_voted($pollid, $optionid)
	{
		// For now, return false to avoid database errors
		// This can be enhanced later when we fix the database query issues
		return false;
	}
}
