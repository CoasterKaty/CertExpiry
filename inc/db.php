<?php
$dbConn = new mysqli('localhost', 'urlshorten', 'FhB_5Pkaym7Q3HJQ', 'urlshorten');
if ($dbConn->connect_error) {
	die('Error connecting to database');
}

function listUrls($page = 1, $pageSize = 20) {
	global $dbConn;
	$query = 'SELECT * FROM url_shorten LIMIT ' . $pageSize . ' OFFSET ' . (($page - 1) * $pageSize);
	$result = $dbConn->query($query);
	while ($row = $result->fetch_assoc()) {
		$resultArray[] = $row;
	}
	return $resultArray;
}

function getUrl($slug) {
	global $dbConn;
	$query = 'SELECT * FROM url_shorten WHERE short_code = \'' . strtolower($dbConn->real_escape_string($slug)) . '\'';
	$result = $dbConn->query($query);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		return $row['url'];
	}
	return;
}
function hitUrl($slug) {
	global $dbConn;
	$query = 'UPDATE url_shorten SET hits = hits + 1 WHERE short_code = \'' .  strtolower($dbConn->real_escape_string($slug)) . '\'';
	$result = $dbConn->query($query);
}

function createUrl($slug, $url) {
	if (getUrl($slug)) {
		die('Error, already exists');
	}
	global $dbConn;
	$dbConn->query('INSERT INTO url_shorten (url, short_code, hits) VALUES (\'' . $dbConn->real_escape_string($url) . '\', \'' .  strtolower($dbConn->real_escape_string($slug)) . '\', 0)');

}

function deleteUrl($id) {
	global $dbConn;
	$dbConn->query('DELETE FROM url_shorten WHERE id = \'' . $dbConn->real_escape_string($id) . '\'');
}
?>
