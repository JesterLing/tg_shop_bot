<?php

namespace AdminPanel\Module;

use GuzzleHttp\Client;

class Exchange
{
    private $privat_api = 'https://api.privatbank.ua/p24api/pubinfo';
    private $tinkoff_api = 'https://api.tinkoff.ru/v1/currency_rates';

    private $from_currency = null;
    private $to_currency = null;
    private $cache_value = null;

    public function exchange($from, $to, $value)
    {
        if ($from == $this->from_currency && $to == $this->to_currency) {
            $caching = true;
        } else {
            $caching = false;
            $this->from_currency = $from;
            $this->to_currency = $to;
        }

        $result = 0;
        if ($from == 'RUB' || $to == 'RUB') {
            if (!$caching) {
                $response = $this->TinkoffRequest($from, $to);
                $this->cache_value = $response['payload']['rates'][2]['buy'];
            }
            $result = $this->cache_value * $value;
        }
        if ($from == 'USD' && $to == 'UAH') {
            if (!$caching) {
                $response = $this->PrivatRequest();
                $this->cache_value = $response[1]['buy'];
            }
            $result = $this->cache_value * $value;
        }
        if ($from == 'UAH' && $to == 'USD') {
            if (!$caching) {
                $response = $this->PrivatRequest();
                $this->cache_value = $response[1]['sale'];
            }
            $result = $value / $this->cache_value;
        }

        if ($to == 'USD') $result = round($result, 4);
        else $result = round($result, 1);

        return $result;
    }

    private function PrivatRequest()
    {
        $client = new Client();
        $response = $client->request('GET', $this->privat_api, [
            'query' => [
                'exchange' => '',
                'json' => '',
                'coursid' => 11,
            ],
            ['timeout' => 10]
        ]);
        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody()->getContents(), true);
        }
    }

    private function TinkoffRequest($from, $to)
    {
        $client = new Client();
        $response = $client->request('GET', $this->tinkoff_api, [
            'query' => [
                'from' => $from,
                'to' => $to,
            ],
            ['timeout' => 10]
        ]);
        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody()->getContents(), true);
        }
    }

    static function convertInternal($currency)
    {
        if (is_int($currency)) {
            switch ($currency) {
                case 980:
                    return 'грн';
                case 643:
                    return 'руб';
                case 840:
                    return '$';
                default:
                    return '';
            }
        }
        if (is_string($currency)) {
            switch ($currency) {
                case 'грн':
                    return 980;
                case 'руб':
                    return 643;
                case '$':
                    return 840;
                default:
                    return 0;
            }
        }
    }

    static function convertISO4217($currency)
    {
        if (is_int($currency)) {
            switch ($currency) {
                case 980:
                    return 'UAH';
                case 643:
                    return 'RUB';
                case 840:
                    return 'USD';
                default:
                    return '';
            }
        }
        if (is_string($currency)) {
            switch ($currency) {
                case 'UAH':
                    return 980;
                case 'RUB':
                    return 643;
                case 'USD':
                    return 840;
                default:
                    return 0;
            }
        }
    }
}
