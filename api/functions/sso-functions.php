<?php

function ssoCheck($username, $password, $token=null){
	$test = '';
	if($GLOBALS['ssoPlex'] && $token){
		coookie('set','mpt',$token,7);
	}
	if($GLOBALS['ssoOmbi']){
		$ombiToken = getOmbiToken($username, $password);
		if($ombiToken){
			coookie('set','Auth',$ombiToken,7, false);
		}
	}
	if($GLOBALS['ssoTautulli']){
		$tautulliToken = getTautulliToken($username, $password);
		if($tautulliToken){
			coookie('set','tautulli_token_'.$tautulliToken['uuid'],$tautulliToken['token'],7, false);
		}
	}
	return true;
}
function getOmbiToken($username, $password){
	$url = $GLOBALS['ombiURL'].'/api/v1/Token';
	$token = null;
	$headers = array(
		"Accept" => "application/json",
		"Content-Type" => "application/json"
	);
	$data = array(
		"username" => $username,
		"password" => $password,
		"rememberMe" => "true",
	);
	$options = (localURL($url)) ? array('verify' => false ) : array();
	$response = Requests::post($url, $headers, json_encode($data), $options);
	if($response->success){
		$token = json_decode($response->body, true)['access_token'];
	}
	return ($token) ? $token : false;
}
function getTautulliToken($username, $password){
	$url = $GLOBALS['tautulliURL'].'/auth/signin';
	$token = null;
	$headers = array(
		"Accept" => "application/json",
		"Content-Type" => "application/x-www-form-urlencoded"
	);
	$data = array(
		"username" => $username,
		"password" => $password,
		"remember_me" => 1,
	);
	$options = (localURL($url)) ? array('verify' => false ) : array();
	$response = Requests::post($url, $headers, $data, $options);
	if($response->success){
		$token['token'] = json_decode($response->body, true)['token'];
		$token['uuid'] = json_decode($response->body, true)['uuid'];
	}
	return ($token) ? $token : false;
}
