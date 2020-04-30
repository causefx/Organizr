<?php

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
            $data['units'] = 'megabits/s';
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