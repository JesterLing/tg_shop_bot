<?php

namespace AdminPanel\Payments;

use GuzzleHttp\Client;

use AdminPanel\Module\Payment;
use \DateTime;
use \DateTimeZone;

class Crystalpay extends Payment
{
    private $service = 1;
    private $login;
    private $secret_key;
    private $api_url = 'https://api.crystalpay.ru/v1';

    public function __construct($login, $secret)
    {
        $this->login = $login;
        $this->secret_key = $secret;
    }

    public function createPayment($user_id, $amount, $comment = null, $order_id = null)
    {
        $client = new Client();
        $request = $client->request('GET', $this->api_url, [
            'query' => [
                'o' => 'invoice-create',
                'n' => $this->login,
                's' => $this->secret_key,
                'amount' => $amount,
                'lifetime' => 180 // 3 hours
            ],
            ['timeout' => 10]
        ]);
        $response = json_decode($request->getBody()->getContents(), true);
        if ($response['auth'] == 'error' || $response['error'] == true) {
            return false;
        }
        $datetime = new DateTime();
        $this->created_at = $response['created_at'] = $datetime->format('H:i:s d.m.y');
        $this->expires_at = $response['expires_at'] = $datetime->modify('+3 hour')->format('H:i:s d.m.y');
        $this->pay_url = $response['url'];
        $this->dbAddPayment($user_id, $this->service, $response, $order_id);
    }

    public function checkPayment($payment_id)
    {
        $current_info = $this->dbGetPayment($payment_id);
        if ((time() - strtotime($current_info['last_check'])) < 15) return false;
        $current_info['additionally']['state'] = $current_info['additionally']['state'] ?? Status::UNKNOWN;
        $new_status = $current_info['additionally']['state'];
        $client = new Client();
        $request = $client->request('GET', $this->api_url, [
            'query' => [
                'o' => 'invoice-check',
                'n' => $this->login,
                's' => $this->secret_key,
                'i' => $current_info['additionally']['id']
            ],
            ['timeout' => 10]
        ]);
        $response = json_decode($request->getBody()->getContents(), true);
        if ($response['auth'] == 'error' || $response['error'] == true) {
            return false;
        }
        if (strtotime("now") > DateTime::createFromFormat('H:i:s d.m.y', $current_info['additionally']['expires_at'])->getTimestamp()) {
            $new_status = Status::EXPIRED;
        } else {
            switch ($response['state']) {
                case 'notpayed':
                    $new_status = Status::PENDING;
                    break;
                case 'processing':
                    $new_status = Status::PROCESSING;
                    break;
                case 'payed':
                    $new_status = Status::PAID;
                    break;
                default:
                    $new_status = Status::UNKNOWN;
            }
        }
        if ($current_info['additionally']['state'] != $new_status) {
            $response['id'] = $current_info['additionally']['id'];
            $response['created_at'] = $current_info['additionally']['created_at'];
            $response['expires_at'] = $current_info['additionally']['expires_at'];
            $this->dbEditPayment($payment_id, $new_status, $response);
        } else {
            $this->dbLastCheckUpdate($payment_id);
        }
        return $new_status;
    }

    public function cancelPayment($payment_id)
    {
        return $this->dbEditPayment($payment_id, Status::CANCELED);
    }
}
