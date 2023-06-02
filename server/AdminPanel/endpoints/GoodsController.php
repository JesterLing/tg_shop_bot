<?php

namespace AdminPanel\Endpoints;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

use AdminPanel\Model\Products;

class GoodsController
{
    public function get(ServerRequest $request, Response $response): Response
    {
        $goods = Products::getAll();
        $response->getBody()->write(json_encode($goods));
        return $response;
    }

    public function getProduct(ServerRequest $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        if (empty($params['id'])) throw new \InvalidArgumentException('Не указан параметр ID');
        $product = Products::getByID($params['id']);
        if (empty($product)) throw new \InvalidArgumentException('Продукт с ID ' . $params['id'] . ' не найден');
        $response->getBody()->write(json_encode($product));
        return $response;
    }

    public function add(ServerRequest $request, Response $response): Response
    {
        $params = json_decode($request->getBody()->getContents(), true);
        Products::addNew($params);
        $response->getBody()->write(json_encode(['type' => 'success']));
        return $response;
    }

    public function edit(ServerRequest $request, Response $response): Response
    {
        $params = json_decode($request->getBody()->getContents(), true);
        Products::editByID($params);
        $response->getBody()->write(json_encode(['type' => 'success']));
        return $response;
    }

    public function delete(ServerRequest $request, Response $response): Response
    {
        $params = json_decode($request->getBody()->getContents(), true);
        Products::deleteByIDs($params);
        $response->getBody()->write(json_encode(['type' => 'success']));
        return $response;
    }
}
