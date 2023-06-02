<?php

namespace AdminPanel\Endpoints;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

use AdminPanel\Model\Purchases;
use AdminPanel\Model\Files;

final class PurchasesController
{
    public function get(ServerRequest $request, Response $response): Response
    {
        $result = [];
        $purchases = Purchases::getAll();
        foreach ($purchases as $entry) {
            $result[] = [
                'id' => $entry['id'],
                'user' => ['user_id' =>  $entry['user_id'], 'username' => $entry['username'], 'first_name' => $entry['first_name'], 'last_name' => $entry['last_name']],
                'number' => $entry['number'],
                'delivery' => $entry['delivery'],
                'address' => $entry['address'],
                'sum' => $entry['sum'],
                'date' => strtotime($entry['created_at']),
                'payment' => ['id' =>  $entry['payment_id'], 'service' => \AdminPanel\Model\Purchases::paymentToString($entry['service']), 'status' => $entry['status']]
            ];
        }
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function getPurchase(ServerRequest $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $purchase = Purchases::getPositionsByID($params['id']);
        if (empty($purchase)) throw new \InvalidArgumentException('Покупка с ID ' . $params['id'] . ' не найдена');
        foreach ($purchase as &$entry) {
            if ($entry['content_type'] === 'FILE') {
                $ids = explode(",", $entry['content']);
                $files = Files::getFilesByIDs($ids);
                $entry['content'] = $files;
            }
        }
        $response->getBody()->write(json_encode($purchase));
        return $response;
    }

    /*
	* @return { id, user_id, order_id, service, status, last_check, additionally }
	*/
    public function getPayment(ServerRequest $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $payment = Purchases::getPaymentInfoByID($params['id']);
        if (empty($payment)) throw new \InvalidArgumentException('Платеж с ID ' . $params['id'] . ' не найден');
        $payment['additionally'] = json_decode($payment['additionally']);
        $response->getBody()->write(json_encode($payment));
        return $response;
    }
}
