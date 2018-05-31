<?php

/**
 * User: Stefan Кaradjov
 * Date: 02/11/2017
 * Time: 6:08 PM
 */
class GoogleRecaptchaHelper
{
    static function isValid(array $post) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, Config()->GOOGLE_RECAPTCHA['url']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);  // Tell cURL you want to post something
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'secret' => Config()->GOOGLE_RECAPTCHA['private_key'],
            'response' => $post['g-recaptcha-response'],
            'remoteip'  => $_SERVER['REMOTE_ADDR']
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the output in string format
        $response = curl_exec ($ch); // Execute
        curl_close ($ch); // Close cURL handle

        $response = json_decode($response);

        if ($response) {
            return $response->success;
        }

        return false;
    }
}