<?php
function optionLimit()
{
	return array(
		array(
			'name' => '1 Item',
			'value' => '1'
		),
		array(
			'name' => '2 Items',
			'value' => '2'
		),
		array(
			'name' => '3 Items',
			'value' => '3'
		),
		array(
			'name' => '4 Items',
			'value' => '4'
		),
		array(
			'name' => '5 Items',
			'value' => '5'
		),
		array(
			'name' => '6 Items',
			'value' => '6'
		),
		array(
			'name' => '7 Items',
			'value' => '7'
		),
		array(
			'name' => '8 Items',
			'value' => '8'
		),
		array(
			'name' => 'Unlimited',
			'value' => '1000'
		),
	);
}

function optionNotificationTypes()
{
	return array(
		array(
			'name' => 'Toastr',
			'value' => 'toastr'
		),
		array(
			'name' => 'Izi',
			'value' => 'izi'
		),
		array(
			'name' => 'Alertify',
			'value' => 'alertify'
		),
		array(
			'name' => 'Noty',
			'value' => 'noty'
		),
	);
}

function optionNotificationPositions()
{
	return array(
		array(
			'name' => 'Bottom Right',
			'value' => 'br'
		),
		array(
			'name' => 'Bottom Left',
			'value' => 'bl'
		),
		array(
			'name' => 'Bottom Center',
			'value' => 'bc'
		),
		array(
			'name' => 'Top Right',
			'value' => 'tr'
		),
		array(
			'name' => 'Top Left',
			'value' => 'tl'
		),
		array(
			'name' => 'Top Center',
			'value' => 'tc'
		),
		array(
			'name' => 'Center',
			'value' => 'c'
		),
	);
}

function optionTime()
{
	return array(
		array(
			'name' => '2.5',
			'value' => '2500'
		),
		array(
			'name' => '5',
			'value' => '5000'
		),
		array(
			'name' => '10',
			'value' => '10000'
		),
		array(
			'name' => '15',
			'value' => '15000'
		),
		array(
			'name' => '30',
			'value' => '30000'
		),
		array(
			'name' => '60 [1 Minute]',
			'value' => '60000'
		),
		array(
			'name' => '300 [5 Minutes]',
			'value' => '300000'
		),
		array(
			'name' => '600 [10 Minutes]',
			'value' => '600000'
		),
		array(
			'name' => '900 [15 Minutes]',
			'value' => '900000'
		),
		array(
			'name' => '1800 [30 Minutes]',
			'value' => '1800000'
		),
		array(
			'name' => '3600 [1 Hour]',
			'value' => '3600000'
		),
	);
	
}

function netdataOptions()
{
	return [
		[
			'name' => 'Disk Read',
			'value' => 'disk-read',
		],
		[
			'name' => 'Disk Write',
			'value' => 'disk-write',
		],
		[
			'name' => 'CPU',
			'value' => 'cpu'
		],
		[
			'name' => 'Network Inbound',
			'value' => 'net-in',
		],
		[
			'name' => 'Network Outbound',
			'value' => 'net-out',
		],
		[
			'name' => 'Used RAM',
			'value' => 'ram-used',
		],
		[
			'name' => 'Used Swap',
			'value' => 'swap-used',
		],
		[
			'name' => 'Disk space used',
			'value' => 'disk-used',
		],
		[
			'name' => 'Disk space available',
			'value' => 'disk-avail',
		],
		[
			'name' => 'Custom',
			'value' => 'custom',
		]
	];
}

function netdataChartOptions()
{
	return [
		[
			'name' => 'Easy Pie Chart',
			'value' => 'easypiechart',
		],
		[
			'name' => 'Gauge',
			'value' => 'gauge'
		]
	];
}

function netdataColourOptions()
{
	return [
		[
			'name' => 'Red',
			'value' => 'fe3912',
		],
		[
			'name' => 'Green',
			'value' => '46e302',
		],
		[
			'name' => 'Purple',
			'value' => 'CC22AA'
		],
		[
			'name' => 'Blue',
			'value' => '5054e6',
		],
		[
			'name' => 'Yellow',
			'value' => 'dddd00',
		],
		[
			'name' => 'Orange',
			'value' => 'd66300',
		]
	];
}

function netdataSizeOptions()
{
	return [
		[
			'name' => 'Large',
			'value' => 'lg',
		],
		[
			'name' => 'Medium',
			'value' => 'md',
		],
		[
			'name' => 'Small',
			'value' => 'sm'
		]
	];
}