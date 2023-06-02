<?php

namespace AdminPanel\Module;

use \PDO;

abstract class Payment
{
    protected $payment_id;
    protected $created_at;
    protected $expires_at;
    protected $pay_url;

    public abstract function createPayment($user_id, $amount, $comment = null, $order_id = null);
    public abstract function checkPayment($payment_id);
    public abstract function cancelPayment($payment_id);

    protected function dbAddPayment($user_id, $service, $additionally = null, $order_id = null)
    {
        $sth = DB::prepare('INSERT INTO `payments` (user_id, order_id, service, status, additionally) VALUES (:user_id, :order_id, :service, :status, :additionally)');
        $sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $sth->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $sth->bindParam(':service', $service, PDO::PARAM_INT);
        $sth->bindValue(':status', PaymentStatus::PENDING, PDO::PARAM_INT);
        if (!is_null($additionally)) $additionally = json_encode($additionally);
        $sth->bindValue(':additionally', $additionally, PDO::PARAM_STR);
        $sth->execute();
        $this->payment_id = DB::lastInsertId();
    }

    protected function dbEditPayment($id, $status, $additionally = null)
    {
        if (is_null($additionally)) {
            $sth = DB::prepare('UPDATE `payments` SET `status` = :status, `last_check` = NOW() WHERE `id` = :id');
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->bindParam(':status', $status, PDO::PARAM_INT);
            return $sth->execute();
        } else {
            $sth = DB::prepare('UPDATE `payments` SET `status` = :status, `last_check` = NOW(), `additionally` = :additionally WHERE `id` = :id');
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->bindParam(':status', $status, PDO::PARAM_INT);
            $sth->bindValue(':additionally', json_encode($additionally), PDO::PARAM_STR);
            return $sth->execute();
        }
    }

    protected function dbGetPayment($id = null)
    {
        $sth = DB::prepare('SELECT `status`, `last_check`, `additionally` FROM `payments` WHERE `id` = :id');
        if (!func_num_args()) {
            if (empty($this->payment_id)) return false;
            $sth->bindParam(':id', $this->payment_id, PDO::PARAM_INT);
        } else {
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
        }
        $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_ASSOC)[0];
        $result['additionally'] = json_decode($result['additionally'], true);
        return $result;
    }

    protected function dbLastCheckUpdate($id = null)
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $sth = DB::prepare('UPDATE `payments` SET `last_check` = :checknow WHERE `id` = :id');
        $sth->bindParam(':checknow', $now, PDO::PARAM_INT);
        if (!func_num_args()) {
            if (empty($this->payment_id)) return false;
            $sth->bindParam(':id', $this->payment_id, PDO::PARAM_INT);
        } else {
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
        }
        return $sth->execute();
    }

    public function getPaymentId()
    {
        return $this->payment_id;
    }
    public function getCreatedAt()
    {
        return $this->created_at;
    }
    public function getExpiresAt()
    {
        return $this->expires_at;
    }
    public function getUrl()
    {
        return $this->pay_url;
    }
}
