<?php

function downloadFile($url, $filePath, $mode = 'w', $redirect = 0, $try = 0)
{

    $fp = fopen($filePath, $mode);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    $curlInfo = curl_getinfo($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    if ($curlInfo['http_code'] == 302 && $redirect < 3) {
        downloadFile($curlInfo['redirect_url'], $filePath, $mode, ++$redirect);
        return true;
    } elseif ($curlInfo['http_code'] == 302 && $redirect < 3) {
        echo '跳转次数过多';
        return false;
    }
    if ($curlInfo['http_code'] !== 200 && $try < 3) {
       sleep(1);
        downloadFile($url, $filePath, $mode, $redirect, ++$try);
        return true;
    } elseif ($curlInfo['http_code'] != 200 && $try >= 3) {
        echo '下载重试次数过多';
        return false;
    }
    if ($curlInfo['http_code'] !== 200 ){
        echo 'url:' . $url . ',error info:' . $curlError . "\n";
        return false;
    }
    return true;
}
