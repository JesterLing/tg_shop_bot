<?php

namespace AdminPanel\Endpoints;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

class HomePageController
{
    public function getHtmlTemplate(ServerRequest $request, Response $response): Response
    {
        $response = $response->withHeader('Content-type', 'text/html; charset=utf-8');
        $dir = './dist';
        $start = 'template';
        $ext = 'html';

        $files = glob($dir . '/[' . $start . ']*.' . $ext);
        if (empty($files)) {
            throw new \Exception('Файл HTML шаблона не найден');
        } else {
            $response->getBody()->write(file_get_contents($files[0]));
            return $response;
        }
    }
}
