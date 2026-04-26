<?php
if ($f == 'explore_nearby_suggestions') {
	$data = array(
		'status' => 200,
		'items' => array()
	);
	$query = '';
	$limit = 4;

	if (!empty($_GET['query'])) {
		$query = trim($_GET['query']);
	}
	if (!empty($_GET['limit']) && is_numeric($_GET['limit'])) {
		$limit = (int) $_GET['limit'];
	}
	if ($limit < 1) {
		$limit = 1;
	}
	if ($limit > 10) {
		$limit = 10;
	}
	if ($query === '') {
		header("Content-type: application/json");
		echo json_encode($data);
		exit();
	}

	$keyword = Wo_Secure($query);
	$user_limit = $limit;
	$page_limit = $limit;
	$user_sql_where = " WHERE `active` = '1' AND ((`username` LIKE '%{$keyword}%') OR CONCAT(`first_name`, ' ', `last_name`) LIKE '%{$keyword}%')";
	$page_sql_where = " WHERE `active` = '1' AND ((`page_name` LIKE '%{$keyword}%') OR (`page_title` LIKE '%{$keyword}%') OR (`address` LIKE '%{$keyword}%'))";

	if ($wo['loggedin'] == true) {
		$logged_user_id = Wo_Secure($wo['user']['user_id']);
		$user_sql_where .= " AND `user_id` <> '{$logged_user_id}'";
		$user_sql_where .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}')";
		$user_sql_where .= " AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
	}

	$user_sql = "SELECT `user_id` FROM " . T_USERS . $user_sql_where . " ORDER BY (`username` LIKE '{$keyword}%') DESC, (CONCAT(`first_name`, ' ', `last_name`) LIKE '{$keyword}%') DESC, `user_id` DESC LIMIT {$user_limit}";
	$user_query = mysqli_query($sqlConnect, $user_sql);
	if ($user_query && mysqli_num_rows($user_query) > 0) {
		while ($fetched_user = mysqli_fetch_assoc($user_query)) {
			$user = Wo_UserData($fetched_user['user_id']);
			if (empty($user) || !is_array($user)) {
				continue;
			}

			$data['items'][] = array(
				'type' => 'user',
				'type_label' => (!empty($wo['lang']['user']) ? $wo['lang']['user'] : 'User'),
				'id' => (int) $user['user_id'],
				'title' => $user['name'],
				'subtitle' => (!empty($user['username']) ? '@' . $user['username'] : ''),
				'location' => (!empty($user['address']) ? $user['address'] : ''),
				'avatar' => (!empty($user['avatar']) ? $user['avatar'] : ''),
				'url' => (!empty($user['url']) ? $user['url'] : ''),
				'search_value' => (!empty($user['username']) ? $user['username'] : $user['name'])
			);
		}
	}

	$page_sql = "SELECT `page_id` FROM " . T_PAGES . $page_sql_where . " ORDER BY (`page_name` LIKE '{$keyword}%') DESC, (`page_title` LIKE '{$keyword}%') DESC, (`address` LIKE '{$keyword}%') DESC, `page_id` DESC LIMIT {$page_limit}";
	$page_query = mysqli_query($sqlConnect, $page_sql);
	if ($page_query && mysqli_num_rows($page_query) > 0) {
		while ($fetched_page = mysqli_fetch_assoc($page_query)) {
			$page = Wo_PageData($fetched_page['page_id']);
			if (empty($page) || !is_array($page)) {
				continue;
			}

			$data['items'][] = array(
				'type' => 'page',
				'type_label' => (!empty($wo['lang']['page']) ? $wo['lang']['page'] : 'Page'),
				'id' => (int) $page['page_id'],
				'title' => (!empty($page['page_title']) ? $page['page_title'] : $page['name']),
				'subtitle' => (!empty($page['page_name']) ? '@' . $page['page_name'] : ''),
				'location' => (!empty($page['address']) ? $page['address'] : ''),
				'avatar' => (!empty($page['avatar']) ? $page['avatar'] : ''),
				'url' => (!empty($page['url']) ? $page['url'] : ''),
				'search_value' => (!empty($page['page_title']) ? $page['page_title'] : $page['page_name'])
			);
		}
	}

	header("Content-type: application/json");
	echo json_encode($data);
	exit();
}
