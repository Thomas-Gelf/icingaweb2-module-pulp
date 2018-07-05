<?php

namespace Icinga\Module\Pulp;

use InvalidArgumentException;
use RuntimeException;

class Api
{
    protected $curl;

    protected $baseUrl;

    protected $username;

    protected $password;

    protected $proxy;

    public function __construct($baseUrl, $username, $password, $proxy = null)
    {
        $this->baseUrl = $baseUrl;
        $this->username = $username;
        $this->password = $password;
        $this->proxy = $proxy;
    }

    public function getHostname()
    {
        return parse_url($this->baseUrl, PHP_URL_HOST);
    }

    public function getStatus()
    {
        return $this->get('status/');
    }

    public function getTasks()
    {
        return $this->get('tasks/');
    }

    protected function searchTasks()
    {
        $body = (object) [
            'criteria' => (object) [
                'filters'  => (object) [
                    'id'     => (object) ['$in' => ['fee', 'fie', 'foe', 'foo']],
                    'group'  => (object) ['$regex' => '.*-dev']
                ],
                'sort'   => [['id', 'ascending'], ['timestamp', 'descending']],
                'limit'  => 100,
                'skip'   => 0,
                'fields' => ['id', 'group', 'description', 'timestamp']
            ]
        ];

        return $body;
    }

    protected function url($url)
    {
        return sprintf('%s/v2/%s', $this->baseUrl, $url);
    }

    public function request($method, $url, $body = null, $raw = false)
    {
        if (function_exists('curl_version')) {
            return $this->curlRequest($method, $url, $body, $raw);
        } else {
            throw new RuntimeException(
                'No CURL extension detected, it must be installed and enabled'
            );
        }
    }

    protected function curlRequest($method, $url, $body = null, $raw = false)
    {
        $auth = sprintf('%s:%s', $this->username, $this->password);
        $headers = array(
            'Host: ' . $this->getHostname(),
            'Connection: close'
        );

        if (! $raw) {
            $headers[] = 'Accept: application/json';
        }

        if ($body !== null) {
            $body = json_encode($body);
            $headers[] = 'Content-Type: application/json';
        }

        $curl = $this->curl();
        $opts = array(
            CURLOPT_URL            => $this->url($url),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERPWD        => $auth,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,

            // TODO: Fix this!
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        );

        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }

        $this->eventuallySetProxyOptions($opts);
        curl_setopt_array($curl, $opts);
        $res = curl_exec($curl);
        if ($res === false) {
            throw new RuntimeException('CURL ERROR: ' . curl_error($curl));
        }

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($statusCode === 401) {
            throw new RuntimeException(
                'Unable to authenticate, please check your API credentials'
            );
        }

        if ($raw) {
            return $res;
        } else {
            return json_decode($res);
        }
    }

    protected function eventuallySetProxyOptions(& $opts)
    {
        if ($this->proxy === null) {
            return;
        }

        $parts = parse_url($this->proxy);
        switch ($parts['scheme']) {
            case 'http':
                $opts[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
                break;
            case 'socks':
                $opts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
                break;
            default:
                throw new InvalidArgumentException(sprintf(
                    "Got invalid proxy scheme: '%s'",
                    $parts['scheme']
                ));
        }
        // SOCKS
        if (isset($parts['port'])) {
            $opts[CURLOPT_PROXY] = sprintf('%s:%d', $parts['host'], $parts['port']);
        } else {
            $opts[CURLOPT_PROXY] = $parts['host'];
        }

        if (isset($parts['user'])) {
            $opts[CURLOPT_PROXYUSERPWD] = sprintf(
                '%s:%s',
                $parts['user'],
                $parts['pass']
            );
        }
    }

    public function get($url, $body = null)
    {
        return $this->request('get', $url, $body);
    }

    public function getRaw($url, $body = null)
    {
        return $this->request('get', $url, $body, true);
    }

    public function post($url, $body = null)
    {
        return $this->request('post', $url, $body);
    }

    public function put($url, $body = null)
    {
        return $this->request('put', $url, $body);
    }

    public function delete($url, $body = null)
    {
        return $this->request('delete', $url, $body);
    }

    protected function curl()
    {
        if ($this->curl === null) {
            $this->curl = curl_init();
            if (! $this->curl) {
                throw new RuntimeException('CURL INIT ERROR: ' . curl_error($this->curl));
            }
        }

        return $this->curl;
    }
}
