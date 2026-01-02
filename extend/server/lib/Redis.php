<?php

namespace server\lib;

use think\facade\Cache;
use think\cache\driver\Redis as R;

class Redis
{
    private $store = null;
    private $redis = null;

    public function __construct($store = 'redis')
    {
        $this->setStore($store);
//        var_dump(config('cache.stores.'.$this->store));exit();
        $this->redis = new R(config('cache.stores.'.$this->store));
//        exit();
//        $this->redis = Cache::store($this->store);

    }

    public function set($key, $value, $ttl = null)
    {
        return Cache::store($this->store)->set($key, $value, $ttl);
    }

    public function get($key)
    {
        return Cache::store($this->store)->set($key, $this->redis->get($key));
    }

    public function delete($key)
    {
        return Cache::store($this->store)->delete($key);
    }

    public function reset($key, $value, $ttl = null) {
        return $this->redis->set($key, $value, $ttl);
    }

    public function multi() {
        return $this->redis->multi();
    }

    public function exec() {
        return $this->redis->exec();
    }

    public function discard() {
        return $this->redis->discard();
    }

    public function setStore($store)
    {
        $this->store = $store;
        return $this;
    }

}