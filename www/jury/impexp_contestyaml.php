<?php
/**
 * Import/export configration settings to and from contest.yaml.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');

requireAdmin();

// Calculate the difference between two HH:MM:SS strings and output
// again in that format. Assumes that $time1 >= $time2.
function timestring_diff($time1, $time2)
{
	sscanf($time1, '%2d:%2d:%2d', $h1, $m1, $s1);
	sscanf($time2, '%2d:%2d:%2d', $h2, $m2, $s2);

	$s = 3600 * ($h1 - $h2) + 60 * ($m1 - $m2) + ($s1 - $s2);

	$h = floor($s/(60*60)); $s -= $h * 60*60;
	$m = floor($s/60);      $s -= $m * 60;

	return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

if ( isset($_POST['import']) ) {

	if ( isset($_FILES) && isset($_FILES['import_config']) &&
	     isset($_FILES['import_config']['name']) &&
	     isset($_FILES['import_config']['tmp_name']) ) {

		$file = $_FILES['import_config']['name'];

		$contest_yaml_data = Spyc::YAMLLoad($_FILES['import_config']['tmp_name']);

		if ( empty($contest_yaml_data) ) {
			echo "<p>Error parsing YAML file.</p>\n";
			require(LIBWWWDIR . '/footer.php');
			exit;
		}

		require(LIBWWWDIR . '/checkers.jury.php');

		$invalid_regex = '/[^' . substr(IDENTIFIER_CHARS,1).'/';

		$contest = array();
		$contest['name'] = $contest_yaml_data['name'];
		$contest['shortname'] = preg_replace($invalid_regex, '_',
		                                     $contest_yaml_data['short-name']);
		$contest['starttime_string'] =
			date_format('Y-m-d H-i-s e',
		                date_create_from_format('c', $contest_yaml_data['start-time']));
		$contest['activatetime_string'] = '-1:00';
		$contest['endtime_string'] = '+' . $contest_yaml_data['duration'];
		// First try new key then fallback to old 'scoreboard-freeze':
		if ( ! empty($contest_yaml_data['scoreboard-freeze-length']) ) {
			$contest['freezetime_string'] =
			    '+' . $contest_yaml_data['scoreboard-freeze-length'];
		}
		else if ( ! empty($contest_yaml_data['scoreboard-freeze']) ) {
			$contest['freezetime_string'] =
				'+' . timestring_diff($contest_yaml_data['duration'],
			                          $contest_yaml_data['scoreboard-freeze']);
		}
		// unfreezetime is not supported by the current standard
		$contest['unfreezetime_string'] = null;
		$contest['enabled'] = 1;

		$contest = check_contest($contest);

		$cid = $DB->q("RETURNID INSERT INTO contest SET %S", $contest);

		if ( ! empty($CHECKER_ERRORS) ) {
			echo "<p>Contest data not valid:</p>\n";
			echo "<ul>\n";
			foreach ($CHECKER_ERRORS as $error) {
				echo "<li>" . $error . "</li>\n";
			}
			echo "</ul>\n";
			require(LIBWWWDIR . '/footer.php');
			exit;

		}

		dbconfig_init();

		// TODO: event-feed-port
		if (isset($contest_yaml_data)) {
			$LIBDBCONFIG['penalty_time']['value'] = (int)$contest_yaml_data['penaltytime'];
		}

	/* clarification answers/categories currently not supported; ignore them.
		$LIBDBCONFIG['clar_answers']['value'] = $contest_yaml_data['default-clars'];
		$categories = array();
		foreach ( $contest_yaml_data['clar-categories'] as $category ) {
			$cat_key = substr(str_replace(array(' ', ',', '.'), '-',
			                  strtolower($category)), 0, 9);
			$categories[$cat_key] = $category;
		}
		$LIBDBCONFIG['clar_categories']['value'] = $categories;
	*/

	/* Disable importing language details, as there's very little to actually import:
		$DB->q("DELETE FROM language");
		foreach ($contest_yaml_data['languages'] as $language) {
			$lang = array();
			// TODO better lang-id?
			$lang['langid'] = str_replace(array('+', '#', ' '),
	                                      array('p', 'sharp', '-'),
										  strtolower($language['name']));
			$lang['name'] = $language['name'];
			$lang['allow_submit'] = 1;
			$lang['allow_judge'] = 1;
			$lang['time_factor'] = 1;

			$DB->q("INSERT INTO language SET %S", $lang);
		}
	*/

		foreach ($contest_yaml_data['problems'] as $problem) {
			// TODO better lang-id?

			$probid = $DB->q('RETURNID INSERT INTO problem
			                  SET name = %s, timelimit = %i',
			                 $problem['short-name'], 10);
			// TODO: ask Fredrik about configuration of timelimit

			$DB->q('INSERT INTO contestproblem (cid, probid, shortname, color)
			        VALUES (%i, %i, %s, %s)',
			       $cid, $probid, $problem['letter'], $problem['rgb']);
		}

		dbconfig_store();

		// Redirect to the original page to prevent accidental redo's
		header('Location: impexp_contestyaml.php?import-ok&file='.$file);
		exit;

	} else {

		echo "<p>Error uploading file.</p>\n";
		require(LIBWWWDIR . '/footer.php');
		exit;

	}

} elseif ( isset($_POST['export']) ) {

	// Fetch data from database and store in an associative array
	$cid = @$_POST['contest'];

	$contest_row = $DB->q("MAYBETUPLE SELECT * FROM contest WHERE cid = %i", $cid);

	if ( ! $contest_row ) {
		echo "<p>Contest not found.</p>\n";
		require(LIBWWWDIR . '/footer.php');
		exit;
	}

	$contest_data = array();
	$contest_data['name'] = $contest_row['name'];
	$contest_data['short-name'] = $contest_row['name'];
	$contest_data['start-time'] = date('c', $contest_row['starttime']);
	$contest_data['duration'] =
		printtimerel(calcContestTime($contest_row['endtime'], $contest_row['cid']));

	if ( ! is_null($contest_row['freezetime']) ) {
		$contest_data['scoreboard-freeze'] =
			printtimerel(calcContestTime($contest_row['freezetime'], $contest_row['cid']));
	}

	// TODO: event-feed-port
	$contest_data['penaltytime'] = dbconfig_get('penalty_time');
	/*
	$contest_data['default-clars'] = dbconfig_get('clar_answers');
	$contest_data['clar-categories'] = array_values(dbconfig_get('clar_categories'));
	*/
	$contest_data['languages'] = array();
	$q = $DB->q("SELECT * FROM language");
	while ( $lang = $q->next() ) {

		$language = array();
		$language['name'] = $lang['name'];
		// TODO: compiler, -flags, runner, -flags?
		$contest_data['languages'][] = $language;

	}
	$contest_data['problems'] = array();
	$contests = getCurContests(FALSE);
	if ( !empty($contests) ) {
		$q = $DB->q("SELECT * FROM problem INNER JOIN contestproblem USING (probid) WHERE cid IN (%Ai)",
		            $contests);
		while ( $prob = $q->next() ) {

			$problem = array();
			$problem['letter'] = $prob['probid'];
			$problem['short-name'] = $prob['name'];
			// Our color field can be both a HTML color name and an RGB value,
			// so we output it only in the human-readable field "color" and
			// leave the field "rgb" unset.
			$problem['color'] = $prob['color'];
			$contest_data['problems'][] = $problem;
		}
	}


	$yaml = Spyc::YAMLDump($contest_data);

	echo $yaml;
	header('Content-type: text/x-yaml');
	header('Content-Disposition: attachment; filename="contest.yaml"');
	exit;

}

$title = "Import / export configuration";
require(LIBWWWDIR . '/header.php');

echo "<h1>Import / export configuration</h1>\n\n";

if ( isset($_GET['import-ok']) ) {
	echo msgbox("Import successful!", "The file " . specialchars(@$_GET['file']) .
	            " is successfully imported.");
}

echo "<h2>Import from YAML</h2>\n\n";
echo addForm('impexp_contestyaml.php', 'post', null, 'multipart/form-data');
echo msgbox("Please note!", "Importing a contest.yaml may overwrite some settings " .
            "(e.g. penalty time, clarification categories, clarification answers, etc.)." .
            "This action can not be undone!");
echo addFileField('import_config');
echo addSubmit('Import', 'import') . addEndForm();
echo "<h2>Export to YAML</h2>\n\n";
echo addForm('impexp_contestyaml.php');
echo '<label for="contest">Select contest: </label>';
$contests = $DB->q("KEYVALUETABLE SELECT cid, name FROM contest");
echo addSelect('contest', $contests, null, true);
echo addSubmit('Export', 'export') . addEndForm();


require(LIBWWWDIR . '/footer.php');
