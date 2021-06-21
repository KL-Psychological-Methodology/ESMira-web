<?php


const NUMBER_OF_SHOWN_DAYS = 7, //should not be greater than NUMBER_OF_SAVED_DAYS_IN_SERVER_STATISTICS
	PLOT_MARGIN_X = 40,
	PLOT_MARGIN_Y = 30,
	IMAGE_DEFAULT_WIDTH = 1000,
	IMAGE_DEFAULT_HEIGHT = 800;

$colored = isset($_GET['colored']);
$color_android = $colored ?			'#ff0000' : '#eeeeee';
$color_ios = $colored ?				'#ff9900' : '#bbbbbb';
$color_web = $colored ?				'#ffff00' : '#000000';
$color_questionnaire = $colored ?	'#00ffff' : '#666666';
$color_joined = $colored ?			'#99ff99' : '#666666';

$image_width = isset($_GET['width']) ? (int) $_GET['width'] : IMAGE_DEFAULT_WIDTH;
$image_height = isset($_GET['height']) ? (int) $_GET['height'] : IMAGE_DEFAULT_HEIGHT;
$plot_height = $image_height / 4 - PLOT_MARGIN_Y*2;
$currentY = 0;

require_once 'php/configs.php';
require_once 'php/basic_fu.php';
require_once 'php/files.php';
require_once 'php/libs/phplot/phplot.php';

$lang_name = get_lang();
$LANG = json_decode(file_get_contents("parts/locales/$lang_name.json"));


$file_serverStatistics = FILE_SERVER_STATISTICS;
if(!file_exists($file_serverStatistics))
	exit();
$serverStatistics = json_decode(file_get_contents(FILE_SERVER_STATISTICS));



function createPlot(&$plot, $data, $legend, $title, $color = null, $half = 0) {
	global $image_width;
	global $plot_height;
	global $currentY;
	global $black;
	
	switch($half) {
		case 0:
			imagestring($plot->img, 10, PLOT_MARGIN_X, $currentY, $title, $black);
			$plot->SetPlotAreaPixels(PLOT_MARGIN_X, $currentY += PLOT_MARGIN_Y, $image_width - PLOT_MARGIN_X, $currentY += $plot_height);
			$currentY += PLOT_MARGIN_Y;
			break;
		case 1:
			imagestring($plot->img, 10, PLOT_MARGIN_X, $currentY, $title, $black);
			$plot->SetPlotAreaPixels(PLOT_MARGIN_X, $currentY + PLOT_MARGIN_Y, ($image_width - PLOT_MARGIN_X)/2, $currentY + PLOT_MARGIN_Y + $plot_height);
			break;
		case 2:
			$x = $image_width/2 + PLOT_MARGIN_X;
			imagestring($plot->img, 10, $x, $currentY, $title, $black);
			$plot->SetPlotAreaPixels($x, $currentY += PLOT_MARGIN_Y, $image_width - PLOT_MARGIN_X, $currentY += $plot_height);
			$currentY += PLOT_MARGIN_Y;
			break;
	}
	
	$plot->SetDataValues($data);
	$plot->SetPlotAreaWorld(NULL, 0);
	$plot->SetLegend($legend);
	if($color !== null)
		$plot->SetDataColors($color);
	$plot->SetXTickPos('none');
	
	
	$plot->DrawGraph();
	
	
}
function get_first_obj_key($obj) {
	foreach($obj as $key => $value) {
		return $key;
	}
	return null;
}
function calcTimestamp($timestamp, $timeInterval) {
	return floor($timestamp / $timeInterval) * $timeInterval;
}



$dailyData_questionnaire = [];
$dailyData_join = [];

$now = time();
$nowMidnight = calcTimestamp($now, ONE_DAY);
$cutoff_today = $now - ONE_DAY;
$cutoff_yesterday = $cutoff_today - ONE_DAY;
$cutoff_week = $now - ONE_DAY*7;

$daysArray = $serverStatistics->days;
for($timestamp = $nowMidnight - NUMBER_OF_SHOWN_DAYS * ONE_DAY; $timestamp <= $nowMidnight; $timestamp += ONE_DAY) {
	if($timestamp < $cutoff_week)
		$date = date("m.d.y", $timestamp);
	else if($timestamp < $cutoff_yesterday)
		$date = sprintf(utf8_decode($LANG->x_days_ago), ($now - $timestamp) / ONE_DAY);
	else if($timestamp < $cutoff_today)
		$date = utf8_decode($LANG->yesterday);
	else
		$date = utf8_decode($LANG->today);
	
	if(isset($daysArray->{$timestamp})) {
		$set = $daysArray->{$timestamp};
		$value_questionnaire = isset($set->questionnaire) ? $set->questionnaire : 0;
		$value_join = isset($set->joined) ? $set->joined : 0;
	}
	else {
		$value_questionnaire = 0;
		$value_join = 0;
	}
	
	$dailyData_questionnaire[] = [$date, $value_questionnaire];
	$dailyData_join[] = [$date, $value_join];
}


//*****
//Start plotting
//*****

$plot = new PHPlot($image_width, $image_height);
$plot->SetPrintImage(0);
$plot->SetPrecisionY(0);
$plot->SetDrawYGrid(false);


$plot->SetLegendStyle('left', 'left');
//$plot->SetLegendPosition(0.5, 0.5, 'plot', 0.5, 1);


//$plot->SetTitle(utf8_decode($LANG->total_count_per_weekday));
$black = imagecolorresolve($plot->img, 0, 0, 0);

//
//App type
//

$plot->SetDataType('text-data-single');
$plot->SetPlotType('pie');
createPlot(
	$plot,
	[
		['', $serverStatistics->total->android],
		['', $serverStatistics->total->ios],
		['', $serverStatistics->total->web]
	],
	[utf8_decode($LANG->Android), utf8_decode($LANG->iOS), utf8_decode($LANG->Web)],
	utf8_decode($LANG->app_type),
	[$color_android, $color_ios,$color_web],
	2
);

//
//Basic data
//

$text_spacing = $plot_height/3;
imagestring($plot->img, 10, PLOT_MARGIN_X, PLOT_MARGIN_Y, utf8_decode($LANG->colon_active_studies) .' ' .$serverStatistics->total->studies, $black);
imagestring($plot->img, 10, PLOT_MARGIN_X, PLOT_MARGIN_Y + $text_spacing, utf8_decode($LANG->colon_total_completed_questionnaires) .' ' .$serverStatistics->total->questionnaire, $black);
imagestring($plot->img, 10, PLOT_MARGIN_X, PLOT_MARGIN_Y + $text_spacing*2, utf8_decode($LANG->colon_total_participants) .' ' .$serverStatistics->total->joined, $black);

//$source = imagecreatefrompng('parts/imgs/web_header.png');
//list($icon_width, $icon_height) = getimagesize('parts/imgs/web_header.png');
//imagecopy($plot->img, $source, 10, 10, 0, 0, $icon_width, $icon_height);

//*****
//per day - questionnaires
//*****
$plot->SetLegend([]);
$plot->SetDataType('text-data');
$plot->SetPlotType('linepoints');

$plot->SetXTickLabelPos('none');
$plot->SetXTickPos('none');
$plot->SetYDataLabelPos('plotin');
$plot->SetYTickLabelPos('none');
$plot->SetYTickPos('none');
//$plot->SetDrawYGrid(0);

createPlot(
	$plot,
	$dailyData_questionnaire,
	null,
	sprintf(utf8_decode($LANG->x_per_day), utf8_decode($LANG->questionnaires)),
	$color_questionnaire
);

//*****
//per day - joined
//*****

createPlot(
	$plot,
	$dailyData_join,
	null,
	sprintf(utf8_decode($LANG->x_per_day), utf8_decode($LANG->joined_studies)),
	$color_joined
);


//*****
//Weekday - questionnaires
//*****

$plot->SetDataType('text-data');
$plot->SetPlotType('bars');

createPlot(
	$plot,
	[
		[utf8_decode($LANG->weekday_mon), $serverStatistics->week->questionnaire[1]],
		[utf8_decode($LANG->weekday_tue), $serverStatistics->week->questionnaire[2]],
		[utf8_decode($LANG->weekday_wed), $serverStatistics->week->questionnaire[3]],
		[utf8_decode($LANG->weekday_thu), $serverStatistics->week->questionnaire[4]],
		[utf8_decode($LANG->weekday_fri), $serverStatistics->week->questionnaire[5]],
		[utf8_decode($LANG->weekday_sat), $serverStatistics->week->questionnaire[6]],
		[utf8_decode($LANG->weekday_sun), $serverStatistics->week->questionnaire[0]]
	],
	null,
	sprintf(utf8_decode($LANG->x_per_weekday), utf8_decode($LANG->questionnaires)),
	$color_questionnaire,
	1
);



//*****
//Weekday - joined
//*****

createPlot(
	$plot,
	[
		[utf8_decode($LANG->weekday_mon), $serverStatistics->week->joined[1]],
		[utf8_decode($LANG->weekday_tue), $serverStatistics->week->joined[2]],
		[utf8_decode($LANG->weekday_wed), $serverStatistics->week->joined[3]],
		[utf8_decode($LANG->weekday_thu), $serverStatistics->week->joined[4]],
		[utf8_decode($LANG->weekday_fri), $serverStatistics->week->joined[5]],
		[utf8_decode($LANG->weekday_sat), $serverStatistics->week->joined[6]],
		[utf8_decode($LANG->weekday_sun), $serverStatistics->week->joined[0]]
	],
	null,
	sprintf(utf8_decode($LANG->x_per_weekday), utf8_decode($LANG->joined_studies)),
	$color_joined,
	2
);


$plot->PrintImage();

