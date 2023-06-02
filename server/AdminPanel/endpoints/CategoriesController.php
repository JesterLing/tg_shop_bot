<?php

namespace AdminPanel\Endpoints;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

use AdminPanel\Model\Categories;

class CategoriesController
{
	public function get(ServerRequest $request, Response $response): Response
	{
		$result = [];
		$catgs = Categories::getAll();
		$this->transformToTree($result, $catgs);
		$this->sortByOrderKey($result);
		$response->getBody()->write(json_encode($result));
		return $response;
	}

	public function add(ServerRequest $request, Response $response): Response
	{
		$params = json_decode($request->getBody()->getContents(), true);
		Categories::addNew($params);
		$response->getBody()->write(json_encode(['type' => 'success']));
		return $response;
	}

	public function edit(ServerRequest $request, Response $response): Response
	{
		$params = json_decode($request->getBody()->getContents(), true);
		Categories::editByID($params);
		$response->getBody()->write(json_encode(['type' => 'success']));
		return $response;
	}

	public function order(ServerRequest $request, Response $response): Response
	{
		$params = json_decode($request->getBody()->getContents(), true);
		$transformed = [];
		self::transformToArray($transformed, $params);
		Categories::editOrder($transformed);
		$response->getBody()->write(json_encode(['type' => 'success']));
		return $response;
	}

	public function delete(ServerRequest $request, Response $response): Response
	{
		$params = json_decode($request->getBody()->getContents(), true);
		Categories::deleteByID($params['id']);
		$response->getBody()->write(json_encode(['type' => 'success']));
		return $response;
	}

	private function transformToArray(&$result, &$target, $parent_id = NULL)
	{
		foreach ($target as $key => &$subarray) {
			if (!empty($subarray['children'])) {
				self::transformToArray($result, $subarray['children'], $subarray['id']);
			} else {
				$subarray['order'] = $key;
				$subarray['parent_id'] = $parent_id;
				unset($subarray['children']);
				$result[] = &$subarray;
			}
		}
	}

	private function transformToTree(&$result, &$target)
	{
		$references = [];
		foreach ($target as &$entry) {
			$entry['name'] = $entry['cname'];
			unset($entry['cname']);
			$references[$entry['id']] = &$entry;
		}

		array_walk($references, function (&$entry) use ($references, &$result) {
			$par_id = $entry['parent_id'];
			unset($entry['parent_id']);
			if ($par_id != null) {
				$references[$par_id]['children'][] = &$entry;
			} else {
				$result[] = &$entry;
			}
		});
	}

	private function sortByOrderKey(&$array)
	{
		usort($array, function ($a, $b) {
			return $a['order'] <=> $b['order'];
		});
		foreach ($array as &$subarray) {
			if (isset($subarray['children'])) self::sortByOrderKey($subarray['children']);
			unset($subarray['order']);
		}
	}
}
