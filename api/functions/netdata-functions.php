<?php

function netdataSettngsArray()
{
    $array = array(
        'name' => 'Netdata',
        'enabled' => true,
        'image' => 'plugins/images/tabs/netdata.png',
        'category' => 'Monitor',
        'settings' => array(
            'Enable' => array(
                array(
                    'type' => 'switch',
                    'name' => 'homepageNetdataEnabled',
                    'label' => 'Enable',
                    'value' => $GLOBALS['homepageNetdataEnabled']
                ),
                array(
                    'type' => 'select',
                    'name' => 'homepageNetdataAuth',
                    'label' => 'Minimum Authentication',
                    'value' => $GLOBALS['homepageNetdataAuth'],
                    'options' => groupSelect()
                )
            ),
            'Connection' => array(
                array(
                    'type' => 'html',
                    'override' => 12,
                    'label' => 'Info',
                    'html' => 'The URL needs to be on the same domain as your Organizr, and be proxied by subdomain. E.g. If Organizr is accessed at: https://domain.com, then your URL for netdata should be: https://netdata.domain.com'
                ),
                array(
                    'type' => 'input',
                    'name' => 'netdataURL',
                    'label' => 'URL',
                    'value' => $GLOBALS['netdataURL'],
                    'help' => 'Please enter the local IP:PORT of your netdata instance'
                ),
                array(
                    'type' => 'blank',
                    'label' => ''
                ),
            ),
        )
    );

    for($i = 1; $i <= 7; $i++) {
        $array['settings']['Chart '.$i] = array(
            array(
                'type' => 'switch',
                'name' => 'netdata'.$i.'Enabled',
                'label' => 'Enable',
                'value' => $GLOBALS['netdata'.$i.'Enabled']
            ),
            array(
                'type' => 'blank',
                'label' => ''
            ),
            array(
                'type' => 'input',
                'name' => 'netdata'.$i.'Title',
                'label' => 'Title',
                'value' => $GLOBALS['netdata'.$i.'Title'],
                'help' => 'Title for the netdata graph'
            ),
            array(
                'type' => 'select',
                'name' => 'netdata'.$i.'Data',
                'label' => 'Data',
                'value' => $GLOBALS['netdata'.$i.'Data'],
                'options' => netdataOptions(),
            ),
            array(
                'type' => 'select',
                'name' => 'netdata'.$i.'Chart',
                'label' => 'Chart',
                'value' => $GLOBALS['netdata'.$i.'Chart'],
                'options' => netdataChartOptions(),
            ),
            array(
                'type' => 'select',
                'name' => 'netdata'.$i.'Colour',
                'label' => 'Colour',
                'value' => $GLOBALS['netdata'.$i.'Colour'],
                'options' => netdataColourOptions(),
            ),
            array(
                'type' => 'select',
                'name' => 'netdata'.$i.'Size',
                'label' => 'Size',
                'value' => $GLOBALS['netdata'.$i.'Size'],
                'options' => netdataSizeOptions(),
            ),
            array(
                'type' => 'blank',
                'label' => ''
            ),
            array(
                'type' => 'switch',
                'name' => 'netdata'.$i.'lg',
                'label' => 'Show on large screens',
                'value' => $GLOBALS['netdata'.$i.'lg']
            ),
            array(
                'type' => 'switch',
                'name' => 'netdata'.$i.'md',
                'label' => 'Show on medium screens',
                'value' => $GLOBALS['netdata'.$i.'md']
            ),
            array(
                'type' => 'switch',
                'name' => 'netdata'.$i.'sm',
                'label' => 'Show on small screens',
                'value' => $GLOBALS['netdata'.$i.'sm']
            ),
        );
    }

    $array['settings']['Options'] =  array(
        array(
            'type' => 'select',
            'name' => 'homepageNetdataRefresh',
            'label' => 'Refresh Seconds',
            'value' => $GLOBALS['homepageNetdataRefresh'],
            'options' => optionTime()
        ),
    );

    return $array;
}

function disk($dimension, $url)
{
    $data = [];
    // Get Data
    $dataUrl = $url . '/api/v1/data?chart=system.io&dimensions='.$dimension.'&format=array&points=540&group=average&gtime=0&options=absolute|jsonwrap|nonzero&after=-540';
    try {
        $response = Requests::get($dataUrl);
        if ($response->success) {
            $json = json_decode($response->body, true);
            $data['value'] = $json['latest_values'][0] / 1000;
            $data['percent'] = ($json['latest_values'][0] / $json['max']) * 100;
            $data['units'] = 'MiB/s';
            $data['max'] = $json['max'];
        }
    } catch (Requests_Exception $e) {
        writeLog('error', 'Netdata Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
    };

    return $data;
}

function diskSpace($dimension, $url)
{
    $data = [];
    // Get Data
    $dataUrl = $url . '/api/v1/data?chart=disk_space._&format=json&points=509&group=average&gtime=0&options=ms|jsonwrap|nonzero&after=-540&dimension='.$dimension;
    try {
        $response = Requests::get($dataUrl);
        if ($response->success) {
            $json = json_decode($response->body, true);
            $data['value'] = $json['result']['data'][0][1];
            $data['percent'] = $data['value'];
            $data['units'] = '%';
            $data['max'] = 100;
        }
    } catch (Requests_Exception $e) {
        writeLog('error', 'Netdata Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
    };

    return $data;
}

function net($dimension, $url)
{
    $data = [];

    // Get Data
    $dataUrl = $url . '/api/v1/data?chart=system.net&dimensions='.$dimension.'&format=array&points=540&group=average&gtime=0&options=absolute|jsonwrap|nonzero&after=-540';
    try {
        $response = Requests::get($dataUrl);
        if ($response->success) {
            $json = json_decode($response->body, true);
            $data['value'] = $json['latest_values'][0] / 1000;
            $data['percent'] = ($json['latest_values'][0] / $json['max']) * 100;
            $data['units'] = 'Mbit/s';
            $data['max'] = $json['max'];
        }
    } catch (Requests_Exception $e) {
        writeLog('error', 'Netdata Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
    };

    return $data;
}

function cpu($url)
{
    $data = [];
    $dataUrl = $url . '/api/v1/data?chart=system.cpu&format=array';
    try {
        $response = Requests::get($dataUrl);
        if ($response->success) {
            $json = json_decode($response->body, true);
            $data['value'] = $json[0];
            $data['percent'] = $data['value'];
            $data['max'] = 100;
            $data['units'] = '%';
        }
    } catch (Requests_Exception $e) {
        writeLog('error', 'Netdata Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
    };

    return $data;
}

function ram($url)
{
    $data = [];
    $dataUrl = $url . '/api/v1/data?chart=system.ram&format=array&points=540&group=average&gtime=0&options=absolute|percentage|jsonwrap|nonzero&after=-540&dimensions=used|buffers|active|wired';
    try {
        $response = Requests::get($dataUrl);
        if ($response->success) {
            $json = json_decode($response->body, true);
            $data['value'] = $json['result'][0];
            $data['percent'] = $data['value'];
            $data['max'] = 100;
            $data['units'] = '%';
        }
    } catch (Requests_Exception $e) {
        writeLog('error', 'Netdata Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
    };

    return $data;
}

function swap($url)
{
    $data = [];
    $dataUrl = $url . '/api/v1/data?chart=system.swap&format=array&points=540&group=average&gtime=0&options=absolute|percentage|jsonwrap|nonzero&after=-540&dimensions=used';
    try {
        $response = Requests::get($dataUrl);
        if ($response->success) {
            $json = json_decode($response->body, true);
            $data['value'] = $json['result'][0];
            $data['percent'] = $data['value'];
            $data['max'] = 100;
            $data['units'] = '%';
        }
    } catch (Requests_Exception $e) {
        writeLog('error', 'Netdata Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
    };

    return $data;
}

function ipmiTemp($url, $unit)
{
    $data = [];
    $dataUrl = $url . '/api/v1/data?chart=ipmi.temperatures_c&format=array&points=540&group=average&gtime=0&options=absolute|jsonwrap|nonzero&after=-540';
    try {
        $response = Requests::get($dataUrl);
        if ($response->success) {
            $json = json_decode($response->body, true);
            $data['value'] = $json['result'][0];
            if($unit == 'c') {
                $data['percent'] = ($data['value'] / 50) * 100;
                $data['max'] = 50;
            } else if($unit == 'f') {
                $data['value'] = ($data['value'] * 9/5) + 32;
                $data['percent'] = ($data['value'] / 122) * 100;
                $data['max'] = 122;
            }
            $data['units'] = '°'.strtoupper($unit);
        }
    } catch (Requests_Exception $e) {
        writeLog('error', 'Netdata Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
    };

    return $data;
}