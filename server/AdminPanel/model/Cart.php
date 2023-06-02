<?php

namespace AdminPanel\Model;

use AdminPanel\Module\DB;
use \PDO;

class Cart
{
    public static function getAllByUserID(int $user_id)
    {
        $sth = DB::getPdo()->prepare(
            'SELECT 
                products.id, 
                products.pname, 
                products.price, 
                products.discount, 
                products.dis_percent, 
                cart.quantity, 
                categories.cname 
            FROM 
                cart 
            LEFT JOIN `products` ON cart.product_id = products.id 
            LEFT JOIN `categories` ON products.cat_id = categories.id 
            WHERE 
                cart.user_id = ?'
        );
        $sth->execute([$user_id]);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getCartSum(int $user_id): float
    {
        $sth = DB::prepare('SELECT products.id, products.price, products.discount, products.dis_percent, cart.quantity FROM products, cart WHERE cart.product_id = products.id AND cart.user_id = ?');
        $sth->execute([$user_id]);
        $amount = 0;
        while ($item = $sth->fetch()) {
            $discount = 0;
            if (!is_null($item['dis_percent']) && !is_null($item['discount'])) {
                if ($item['quantity'] >= $item['discount']) {
                    $discount = round(($item['price'] / 100) * $item['dis_percent']);
                }
            }
            $amount += ($item['price'] - $discount) * $item['quantity'];
        }
        return $amount;
    }

    public static function getPositionQuantity(int $user_id, int $product_id)
    {
        $sth = DB::prepare('SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?');
        $sth->bindValue(1, $user_id, PDO::PARAM_INT);
        $sth->bindValue(2, $product_id, PDO::PARAM_INT);
        $sth->execute();
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        if (empty($result)) {
            return null;
        } else {
            return $result['quantity'];
        }
    }

    public static function addPosition(int $user_id, int $product_id, int $quantity = 1)
    {
        $current_quantity = self::getPositionQuantity($user_id, $product_id);
        if (empty($current_quantity)) {
            $sth = DB::getPdo()->prepare('INSERT INTO cart (user_id, product_id, quantity) VALUES (:usid, :prdid, :qa)');
            $sth->bindParam(':usid', $user_id, PDO::PARAM_INT);
            $sth->bindParam(':prdid', $product_id, PDO::PARAM_INT);
            $sth->bindParam(':qa', $quantity, PDO::PARAM_INT);
            return $sth->execute();
        } else {
            $sth = DB::getPdo()->prepare('UPDATE cart SET quantity = :curq + :addq WHERE user_id = :usid AND product_id = :prdid');
            $sth->bindParam(':curq', $current_quantity, PDO::PARAM_INT);
            $sth->bindParam(':addq', $quantity, PDO::PARAM_INT);
            $sth->bindParam(':usid', $user_id, PDO::PARAM_INT);
            $sth->bindParam(':prdid', $product_id, PDO::PARAM_INT);
            return $sth->execute();
        }
    }

    public static function delPosition(int $user_id, int $product_id, int $quantity = 1)
    {
        try {
            $sth = DB::prepare('UPDATE `cart` SET quantity = quantity - ? WHERE product_id = ? AND user_id = ?');
            $sth->bindValue(1, $quantity, PDO::PARAM_INT);
            $sth->bindValue(2, $product_id, PDO::PARAM_INT);
            $sth->bindValue(3, $user_id, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $ex) {
            DB::prepare('DELETE FROM `cart` WHERE product_id = ? AND user_id = ?');
            $sth->bindValue(1, $quantity, PDO::PARAM_INT);
            $sth->bindValue(2, $product_id, PDO::PARAM_INT);
            $sth->bindValue(3, $user_id, PDO::PARAM_INT);
            $sth->execute();
        }
    }

    public static function clearAll(int $user_id)
    {
        $sth = DB::prepare('DELETE FROM cart WHERE user_id = ?');
        $sth->bindValue(1, $user_id, PDO::PARAM_INT);
        return $sth->execute();
    }
}
