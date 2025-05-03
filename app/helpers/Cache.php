<?php

class Cache {
    private static $instance = null;
    private $cacheDir;
    private $prefix = 'cache_';
    private $defaultTTL = 3600; // 1 hour

    private function __construct() {
        $this->cacheDir = __DIR__ . '/../../storage/cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }

        $content = file_get_contents($filename);
        if ($content === false) {
            return null;
        }

        $data = unserialize($content);
        if (!$data || !isset($data['expiry']) || !isset($data['value'])) {
            $this->delete($key);
            return null;
        }

        if ($data['expiry'] < time()) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    public function set($key, $value, $ttl = null) {
        $filename = $this->getCacheFilename($key);
        $ttl = $ttl ?: $this->defaultTTL;

        $data = [
            'expiry' => time() + $ttl,
            'value' => $value
        ];

        return file_put_contents($filename, serialize($data)) !== false;
    }

    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return true;
    }

    public function clear() {
        $files = glob($this->cacheDir . $this->prefix . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    public function remember($key, $ttl, $callback) {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function tags($tags) {
        return new CacheTagSet($this, (array)$tags);
    }

    private function getCacheFilename($key) {
        return $this->cacheDir . $this->prefix . md5($key);
    }
}

class CacheTagSet {
    private $cache;
    private $tags;

    public function __construct($cache, $tags) {
        $this->cache = $cache;
        $this->tags = $tags;
    }

    public function get($key) {
        return $this->cache->get($this->taggedKey($key));
    }

    public function set($key, $value, $ttl = null) {
        $this->storeTags();
        return $this->cache->set($this->taggedKey($key), $value, $ttl);
    }

    public function remember($key, $ttl, $callback) {
        return $this->cache->remember($this->taggedKey($key), $ttl, $callback);
    }

    public function flush() {
        foreach ($this->tags as $tag) {
            $this->cache->delete('tag_' . $tag);
        }
    }

    private function taggedKey($key) {
        $tagVersions = [];
        foreach ($this->tags as $tag) {
            $version = $this->cache->get('tag_' . $tag) ?: 1;
            $tagVersions[] = $tag . ':' . $version;
        }
        sort($tagVersions);
        return implode('|', $tagVersions) . '|' . $key;
    }

    private function storeTags() {
        foreach ($this->tags as $tag) {
            $key = 'tag_' . $tag;
            $version = $this->cache->get($key) ?: 1;
            $this->cache->set($key, $version + 1);
        }
    }
}
