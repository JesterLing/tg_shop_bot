<?php

namespace AdminPanel\Model;

use AdminPanel\Module\DB;
use \PDO;

class Products
{
    public static function getAll()
    {
        return DB::query(
            'SELECT categories.cname,
                    products.id,
                    products.pname,
                    products.description,
                    products.price,
                    products.discount,
                    products.dis_percent,
                    products.hide,
                    products.quantity,
                    files.path AS image
            FROM products
            JOIN categories ON products.cat_id = categories.id
            LEFT JOIN files ON products.image = files.id
            GROUP BY products.id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getByID(int $id)
    {
        $sth = DB::prepare(
            'SELECT products.*,
                    categories.cname,
                    files.name AS `image_name`,
                    files.size AS `image_size`,
                    files.path AS `image_path`
            FROM `products`
            LEFT JOIN `files` ON products.image = files.id
            LEFT JOIN `categories` ON products.cat_id = categories.id
            WHERE products.id = ?
            GROUP BY products.id'
        );
        $sth->execute([$id]);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        self::prepareToOutput($result);
        return $result;
    }

    public static function getByCtgID(int $category_id)
    {
        $sth = DB::prepare(
            'SELECT 
                products.id, 
                products.cat_id, 
                products.pname, 
                products.price, 
                products.quantity, 
                products.discount, 
                products.dis_percent 
            FROM 
                `products` 
            WHERE 
                `cat_id` = ? 
                AND products.hide = 0'
        );
        $sth->execute([$category_id]);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function addNew(array $product)
    {
        self::prepareToInsert($product);
        $sth = DB::getPdo()->prepare(
            'INSERT INTO products (
            `cat_id`, `pname`, `description`, 
            `price`, `quantity`, `image`, `discount`, 
            `dis_percent`, `hide`, `content_type`, 
            `divider`, `content`
          ) 
          VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
          '
        );
        $sth->bindValue(1, $product['category'], PDO::PARAM_INT);
        $sth->bindValue(2, $product['name'], PDO::PARAM_STR);
        $sth->bindValue(3, $product['description'], PDO::PARAM_STR);
        $sth->bindValue(4, $product['price'], PDO::PARAM_INT);
        $sth->bindValue(5, $product['quantity'], PDO::PARAM_INT);
        $sth->bindValue(6, $product['image'], PDO::PARAM_INT);
        $sth->bindValue(7, $product['discount'], PDO::PARAM_INT);
        $sth->bindValue(8, $product['dis_percent'], PDO::PARAM_INT);
        $sth->bindValue(9, $product['hide'], PDO::PARAM_BOOL);
        $sth->bindValue(10, $product['content_type'], PDO::PARAM_STR);
        $sth->bindValue(11, $product['divider'], PDO::PARAM_STR);
        $sth->bindValue(12, $product['content'], PDO::PARAM_STR);
        return $sth->execute();
    }

    public static function editByID(array $product)
    {
        self::prepareToInsert($product);
        $sth = DB::prepare(
            'UPDATE 
                products 
            SET 
                `cat_id` = ?, 
                `pname` = ?, 
                `description` = ?, 
                `price` = ?, 
                `quantity` = ?, 
                `image` = ?, 
                `discount` = ?, 
                `dis_percent` = ?, 
                `hide` = ?, 
                `content_type` = ?, 
                `divider` = ?, 
                `content` = ? 
            WHERE 
                `id` = ?'
        );
        $sth->bindValue(1, $product['category'], PDO::PARAM_INT);
        $sth->bindValue(2, $product['name'], PDO::PARAM_STR);
        $sth->bindValue(3, $product['description'], PDO::PARAM_STR);
        $sth->bindValue(4, $product['price'], PDO::PARAM_INT);
        $sth->bindValue(5, $product['quantity'], PDO::PARAM_INT);
        $sth->bindValue(6, $product['image'], PDO::PARAM_INT);
        $sth->bindValue(7, $product['discount'], PDO::PARAM_INT);
        $sth->bindValue(8, $product['dis_percent'], PDO::PARAM_INT);
        $sth->bindValue(9, $product['hide'], PDO::PARAM_BOOL);
        $sth->bindValue(10, $product['content_type'], PDO::PARAM_STR);
        $sth->bindValue(11, $product['divider'], PDO::PARAM_STR);
        $sth->bindValue(12, $product['content'], PDO::PARAM_STR);
        $sth->bindValue(13, $product['id'], PDO::PARAM_INT);
        return $sth->execute();
    }

    public static function deleteByIDs(array $ids)
    {
        $in = str_repeat('?,', count($ids) - 1) . '?';
        $sth = DB::prepare('DELETE FROM `products` WHERE `id` IN (' . $in . ')');
        return $sth->execute($ids);
    }

    private static function prepareToInsert(&$product)
    {
        if ($product['content_type'] == 'FILE') {
            $ids = '';
            foreach ($product['content'] as $value) $ids .= $value['id'] . ',';
            $product['content'] = $ids;
        }
        if (!is_null($product['image'])) {
            $product['image'] = $product['image']['id'];
        }
    }

    private static function prepareToOutput(&$product)
    {
        $product['category'] = $product['cat_id'];
        $product['name'] = $product['pname'];
        unset($product['cat_id'], $product['pname']);
        if (!is_null($product['image'])) {
            $product['image'] = ['id' => $product['image'], 'name' => $product['image_name'], 'size' => $product['image_size'], 'path' => $product['image_path']];
        }
        unset($product['image_name'], $product['image_path'], $product['image_size']);
        if ($product['content_type'] == 'FILE') {
            $ids = explode(',', $product['content']);
            $product['content'] = Files::getFilesByIDs($ids);
        }
    }
}
