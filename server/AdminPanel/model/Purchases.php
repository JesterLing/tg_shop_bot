<?php

namespace AdminPanel\Model;

use AdminPanel\Module\DB;
use \PDO;

class Purchases
{
    public static function getAll()
    {
        return DB::query(
            'SELECT 
                orders.*, 
                payments.id as payment_id, 
                payments.service, 
                payments.status, 
                user.first_name, 
                user.last_name, 
                user.username 
            FROM 
                orders 
            LEFT JOIN user ON user.id = orders.user_id 
            LEFT JOIN payments ON payments.order_id = orders.id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPositionsByID(int $order_id)
    {
        $sth = DB::prepare('SELECT * FROM `purchases` WHERE `order_id` = ?');
        $sth->execute([$order_id]);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getWithPositionsByUserID(int $user_id)
    {
        $sth = DB::prepare(
            'SELECT 
                orders.*, 
                purchases.* 
            FROM 
                orders 
                LEFT JOIN purchases ON purchases.order_id = orders.id 
            WHERE 
                orders.user_id = ? 
            ORDER BY 
                created_at DESC'
        );
        $sth->execute([$user_id]);
        $products = $sth->fetchAll(PDO::FETCH_ASSOC);
        $result = array();
        foreach ($products as $row) {
            if (!array_key_exists($row['order_id'], $result)) {
                $result[$row['order_id']] = [
                    'id' => $row['order_id'],
                    'created_at' => $row['created_at'],
                    'delivery' => $row['delivery'],
                    'address' => $row['address'],
                    'number' => $row['number']
                ];
            }

            $result[$row['order_id']]['positions'][] = [
                'id'   => $row['id'],
                'category' => $row['category'],
                'product' => $row['product'],
                'price' => $row['price'],
                'discount' => $row['discount'],
                'dis_percent' => $row['dis_percent'],
                'quantity' => $row['quantity']
            ];
        }
        //usort($result, fn(array $a, array $b): int => $b['date'] <=> $a['date']);
        return $result;
    }

    public static function getPaymentInfoByID(int $id)
    {
        $sth = DB::prepare('SELECT * FROM `payments` WHERE id = ?');
        $sth->execute([$id]);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public static function addNew(int $user_id, float $sum, $address = null, $number = null, $delivery = null): int
    {
        $order_id = mt_rand(100000, 999999);
        $sth = DB::prepare('INSERT INTO orders (id, user_id, address, number, delivery, sum, created_at) VALUES (:id, :usid, :address, :number, :delivery, :sum, :created)');
        $sth->bindParam(':id', $order_id, PDO::PARAM_INT);
        $sth->bindParam(':usid', $user_id, PDO::PARAM_INT);
        $sth->bindParam(':address', $address, PDO::PARAM_STR);
        $sth->bindParam(':number', $number, PDO::PARAM_STR);
        $sth->bindParam(':delivery', $delivery, PDO::PARAM_BOOL);
        $sth->bindParam(':sum', $sum, PDO::PARAM_INT);
        $sth->bindParam(':created', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $sth->execute();
        return $order_id;
    }

    public static function fillWithPositionsDigital(int $order_id, $product_id, $quanity)
    {

        $product = Products::getByID($product_id);

        $sth = DB::getPdo()->prepare('INSERT INTO purchases (order_id, category, product, quantity, discount, dis_percent, price, content_type, content) VALUES (:order_id, :category, :product, :quantity, :discount, :dis_percent, :price, :content_type, :content)');
        $sth->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $sth->bindParam(':category', $product['cname'], PDO::PARAM_STR);
        $sth->bindParam(':product', $product['pname'], PDO::PARAM_STR);
        $sth->bindParam(':quantity', $quanity, PDO::PARAM_INT);
        $sth->bindParam(':discount', $product['discount'], PDO::PARAM_INT);
        $sth->bindParam(':dis_percent', $product['dis_percent'], PDO::PARAM_INT);
        $sth->bindParam(':price', $product['price'], PDO::PARAM_INT);
        if ($product['content_type'] == 'FILE') {
            $files = array_column($product['content'], 'id');
            $content = implode(',', $files);
        } else {
            $content = self::getContent($product_id, $product['quantity'], $product['content_type'], $product['divider'], $product['content'], $quanity);
            $product['content_type'] = 'TEXT';
        }
        $sth->bindParam(':content_type', $product['content_type'], PDO::PARAM_STR);
        $sth->bindParam(':content', $content, PDO::PARAM_STR);
        $sth->execute();
    }

    public static function fillWithPositionsReal(int $user_id, int $order_id)
    {
        $products = Cart::getAllByUserID($user_id);
        try {
            DB::getPdo()->beginTransaction();
            $sth = DB::getPdo()->prepare('INSERT INTO purchases (order_id, category, product, quantity, discount, dis_percent, price) VALUES (:order_id, :category, :product, :quantity, :discount, :dis_percent, :price)');
            foreach ($products as $product) {
                $sth->bindParam(':order_id', $order_id, PDO::PARAM_INT);
                $sth->bindParam(':category', $product['cname'], PDO::PARAM_STR);
                $sth->bindParam(':product', $product['pname'], PDO::PARAM_STR);
                $sth->bindParam(':quantity', $product['quantity'], PDO::PARAM_INT);
                $sth->bindParam(':discount', $product['discount'], PDO::PARAM_INT);
                $sth->bindParam(':dis_percent', $product['dis_percent'], PDO::PARAM_INT);
                $sth->bindParam(':price', $product['price'], PDO::PARAM_INT);
                $sth->execute();
            }
            DB::getPdo()->commit();
        } catch (\Exception $ex) {
            DB::getPdo()->rollback();
            throw $ex;
        }
        Cart::clearAll($user_id);
    }

    private static function getContent(int $product_id, $p_quantity, $p_content_type, $p_divider, $p_content, int $quantity = 1)
    {
        $result = false;
        switch ($p_content_type) {
            case 'TEXT':
                $result = $p_content;
                break;
            case 'TEXT_SEPARATED':
                $content_array = explode($p_divider, $p_content);
                $result = '';
                for ($i = 0; $i < $quantity; $i++) {
                    $result .= $content_array[$i];
                    if ($quantity > 1) $result .= PHP_EOL;
                    unset($content_array[$i]);
                }
                $content_updated = implode($p_divider, $content_array);
                break;
            case 'TEXT_LINES':
                $content_array = preg_split('/\r\n|\r|\n/', $p_content);
                $cut = intval($p_divider) * $quantity;
                $content_cuted = array_slice($content_array, 0, $cut);
                $result = implode(PHP_EOL, $content_cuted);
                array_splice($content_array, 0, $cut);
                $content_updated = implode(PHP_EOL, $content_array);
                break;
        }
        if (!is_null($p_quantity)) $p_quantity -= $quantity;
        if ($p_content_type == 'TEXT_SEPARATED' || $p_content_type == 'TEXT_LINES') {
            $sth = DB::prepare('UPDATE products SET quantity = :quantity, content = :content WHERE products.id = :id');
            $sth->bindParam(':quantity', $p_quantity, PDO::PARAM_INT);
            $sth->bindParam(':content', $content_updated, PDO::PARAM_STR);
            $sth->bindParam(':id', $product_id, PDO::PARAM_INT);
            $sth->execute();
        } else {
            if (!is_null($p_quantity)) {
                $sth = DB::prepare('UPDATE products SET quantity = :quantity WHERE products.id = :id');
                $sth->bindParam(':quantity', $p_quantity, PDO::PARAM_INT);
                $sth->bindParam(':id', $product_id, PDO::PARAM_INT);
                $sth->execute();
            }
        }
        return $result;
    }

    public static function deleteByID(int $order_id)
    {
        DB::prepare('DELETE FROM purchases WHERE order_id = ?')->execute([$order_id]);
        DB::prepare('DELETE FROM orders WHERE id = ?')->execute([$order_id]);
    }

    public static function paymentToString($payment)
    {
        if (is_string($payment)) $payment = intval($payment);
        switch ($payment) {
            case 0:
                return 'Наличными';
            case 1:
                return 'Crystalpay';
            case 2:
                return 'QIWI';
            case 3:
                return 'Coinbase';
            default:
                return false;
        }
    }
}
