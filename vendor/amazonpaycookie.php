<?php
if (!function_exists('getAmazonPayCookie')) {
    function getAmazonPayCookie()
    {
        if (isset($_COOKIE['amazon_Login_accessToken'])) {
            return $_COOKIE['amazon_Login_accessToken'];
        }
        return false;
    }
}
if (!function_exists('unsetAmazonPayCookie')) {
    function unsetAmazonPayCookie()
    {
        if (isset($_COOKIE['amazon_Login_accessToken'])) {
            unset($_COOKIE['amazon_Login_accessToken']);
        }
        setcookie('amazon_Login_accessToken', '', time() - 3600, '/');
        return true;
    }
}