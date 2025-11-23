<?php

namespace lib;

class Str
{

    public function createToken($str) {
        $tokenSalt = md5(uniqid(md5(microtime(true)), true));
        return sha1($tokenSalt . $str);
    }

    public function salt($bit) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $bit; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

}