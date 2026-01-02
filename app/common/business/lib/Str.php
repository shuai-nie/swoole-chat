<?php

namespace app\common\business\lib;

class Str
{

    public function createToken($str)
    {
        $tokenSalt = md5(uniqid(md5(microtime( true)), true));
        return sha1($tokenSalt . $str);
    }

    public function salt($bit)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $salt = '';
        for ($i = 0; $i < $bit; $i++) {
            $salt .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $salt;
    }
}