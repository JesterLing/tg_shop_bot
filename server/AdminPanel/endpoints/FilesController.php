<?php

namespace AdminPanel\Endpoints;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

use AdminPanel\Model\Files;

final class FilesController
{
    public function upload(ServerRequest $request, Response $response): Response
    {
        set_time_limit(0);
        $files = $request->getUploadedFiles();
        if (empty($files)) return $response->withBody(Utils::streamFor(json_encode(['type' => 'error', 'error' => 'Неверный запрос загрузки файла'])));
        if (empty($files['files'])) throw new \InvalidArgumentException('Отсутствует поле files');
        $uploaded = [];
        foreach ($files['files'] as $file) {
            if ($file->getError() === UPLOAD_ERR_OK) {
                $name = $file->getClientFilename();
                $size = $file->getSize();
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $basename = Files::generateFilename() . "." . $extension;
                if (!is_dir('Upload')) mkdir('Upload', 0777, true);
                $destination = 'Upload/' . $basename;
                $file->moveTo($destination);
                $id = Files::insertFile($name, $size, '/' . $destination);
                array_push($uploaded, ['id' => $id, 'name' => $name, 'size' => $size, 'path' => '/' . $destination]);
            }
        }
        $response->getBody()->write(json_encode(['type' => 'success', 'update' => $uploaded]));
        return $response;
    }
}
