<?php
declare(strict_types=1);

namespace Vaedly\HdgGeneralPackage\Providers\General;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Http
{
    /**
     * post请求
     * @param string $url
     * @param array $data
     * @param array $header
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post(string $url, array $data = [], array $header = []): array
    {
        try {
            $params = [];
            if ($header) {
                $params['headers'] = $header;
            }
            if ($data) {
                $params['json'] = $data;
            }
            $client = new Client();
            $res = $client->request("POST", $url, $params)->getBody()->getContents();
        } catch (ClientException $exception) {
            $res = $exception->getResponse()->getBody()->getContents();
        }
        return (array)json_decode($res, true);
    }

    /**
     * get请求
     * @param string $url
     * @param array $query
     * @param array $header
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(string $url, array $query = [], array $header = []): array
    {
        try {
            $params = [];
            if ($header) {
                $params['headers'] = $header;
            }
            if ($query) {
                $params['query'] = $query;
            }
            $client = new Client();
            $res = $client->request('GET', $url, $params)->getBody()->getContents();
        } catch (ClientException $exception) {
            $res = $exception->getResponse()->getBody()->getContents();
        }
        return (array)json_decode($res, true);
    }
}
