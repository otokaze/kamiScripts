<?php

class DNSPod {
	public function RecordList($domain){
		$url = 'https://dnsapi.cn/Record.List';
		$field = ['domain' => $domain];
		$response = $this->HTTPPost($url, $field);
		return json_decode($response, true);
	}

	public function RecordCreate($domain, $value, $prefix = '@', $type = 'A', $line = '默认'){
		$url = 'https://dnsapi.cn/Record.Create';
		$field = ['domain' => $domain, 'record_type' => $type, 'sub_domain' => $prefix, 'value' => $value, 'record_line' => $line];
		$response = $this->HTTPPost($url, $field);
		return json_decode($response, true);
	}

	public function RecordModify($domain, $value, $record_id, $prefix = '@', $type = 'A', $line = '默认'){
		$url = 'https://dnsapi.cn/Record.Modify';
		$field = ['domain' => $domain, 'record_type' => $type, 'sub_domain' => $prefix, 'value' => $value, 'record_id' => $record_id, 'record_line' => $line];
		$response = $this->HTTPPost($url, $field);
		return json_decode($response, true);
	}

	public function HTTPPost($url, $field){
		$ch = curl_init($url);
		$header = [
			'content-type: application/x-www-form-urlencoded',
			'useragent: Otokaze Tools/1.0.0 (admin@otokaze.cn)'
		];
		$field = array_merge($field, [
			'format' => 'json',
			'login_token' => 'YOUR_TOKEN',
		]);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($field));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}
}

if(PHP_OS == 'Darwin'){
	$cmd = "ifconfig en0 | sed -n '/inet /p' | awk '{print $2}' | awk -F ':' '{print $1}'";
	$ip = trim(exec($cmd));
}
if(!isset($ip) || empty($ip)){
	printf('[ERROR] cmd(%s) error(%s)', $cmd, '没有找到ip!');
	exit(1);
}
$dnspod = new DNSPod();
if(!$records = $dnspod->RecordList('otokaze.cn')){
	printf('[ERROR] $dnspod->RecordList(%s) error(%s)', 'otokaze.cn', '没有找到记录!');
	exit(1);
}
$macRecord = [];
foreach ($records['records'] as $rec) {
	$rec['name'] == 'mac' && $macRecord = $rec;
}
if(!$macRecord){
	$result = $dnspod->RecordCreate('otokaze.cn', $ip, 'mac');
}else{
	$result = $dnspod->RecordModify('otokaze.cn', $ip, $macRecord['id'], 'mac');
}
if(!$result || $result['status']['code'] != '1'){
	printf('[ERROR] $dnspod->RecordModify|Create(%s) ip(%s) error(%s)', 'otokaze.cn', $ip, json_encode($result));
	exit(1);
}
exit(json_encode(['code' => 0, 'msg' => 'ok']));
