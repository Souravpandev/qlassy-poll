<?php
/*
	Plugin Name: Qlassy Poll
	Plugin URI: https://github.com/Souravpandev/qlassy-poll
	Plugin Description: A comprehensive polling plugin for Question2Answer that allows users to create polls with multiple options, vote on them, and view real-time results. Features include AJAX-powered voting, customizable poll settings, admin controls, badge system integration, responsive design, and performance optimizations with minified assets.
	Plugin Version: 1.0.0
	Plugin Date: 2025-01-28
	Plugin Author: Sourav Pan
	Plugin Author URI: https://github.com/Souravpandev
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.8
	Plugin Update Check URI: https://raw.githubusercontent.com/Souravpandev/q2a-qlassy-poll/main/qa-plugin.php

	This program is free software: you can redistribute it and/or
	modify it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License along
	with this program. If not, see <http://www.gnu.org/licenses/>.

	Developer: Sourav Pan
	Website: https://wpoptimizelab.com/
	GitHub: https://github.com/Souravpandev
	Repository: https://github.com/Souravpandev/qlassy-poll

	FEATURES:
	- AJAX-powered voting with real-time results updates
	- Multiple poll options with customizable settings
	- Admin controls for poll behavior and permissions
	- Badge system integration for voting achievements
	- Responsive design with modern UI/UX
	- Performance optimizations with minified CSS/JS
	- Database query optimization with JOIN queries
	- Conditional asset loading for better performance
	- SVG icon integration for enhanced visual appeal
	- Professional styling and user experience
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

qa_register_plugin_layer('qa-poll-layer.php', 'Qlassy Poll Layer');
qa_register_plugin_module('module', 'qa-poll-admin.php', 'qa_poll_admin', 'Qlassy Poll Settings');
qa_register_plugin_module('event', 'qa-poll-event.php', 'qa_poll_event', 'Qlassy Poll Event Handler');
qa_register_plugin_module('event', 'qa-poll-badges.php', 'qa_poll_badges', 'Qlassy Poll Badge Handler');
qa_register_plugin_module('page', 'qa-poll-ajax.php', 'qa_poll_ajax', 'Qlassy Poll AJAX Handler');
