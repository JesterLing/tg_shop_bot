<?php

namespace AdminPanel\Payments;

use Qiwi\Api\BillPayments;
use Qiwi\Api\BillPaymentsException;

use AdminPanel\Module\Payment;

use \DateTime;
use \DateTimeZone;

class Qiwi extends Payment
{
    private $service = 2;
    private $currency = 'RUB';
    private $secret_key;

    public function __construct($secret)
    {
        $this->secret_key = $secret;
    }

    public function createPayment($user_id, $amount, $comment = null, $order_id = null)
    {
        $qiwi = new BillPayments($this->secret_key);
        $fields = [
            'amount' => $amount,
            'currency' => $this->currency,
            'expirationDateTime' => (new DateTime())->setTimezone(new DateTimeZone('Europe/Moscow'))->modify('+1 hour')->format(DateTime::ATOM),
            'comment' => $comment,
        ];
        $response = $qiwi->createBill($qiwi->generateId(), $fields);
        $this->dbAddPayment($user_id, $this->service, $response, $order_id);
        $this->created_at = $response['creationDateTime'];
        $this->expires_at = $response['expirationDateTime'];
        $this->pay_url = $response['payUrl'];
    }

    public function checkPayment($payment_id)
    {
        $current_info = $this->dbGetPayment($payment_id);
        if ((time() - strtotime($current_info['last_check'])) < 15) return false;
        $qiwi = new BillPayments($this->secret_key);
        $new_info = $qiwi->getBillInfo($current_info['additionally']['billId']);
        switch ($new_info['status']['value']) {
            case 'WAITING':
                $new_status = Status::PENDING;
                break;
            case 'PAID':
                $new_status = Status::PAID;
                break;
            case 'REJECTED':
                $new_status = Status::REJECTED;
                break;
            case 'EXPIRED':
                $new_status = Status::EXPIRED;
                break;
            default:
                $new_status = Status::UNKNOWN;
        }
        if ($new_info['status']['value'] != $current_info['additionally']['status']['value']) {
            $this->dbEditPayment($payment_id, $new_status, $new_info);
        } else {
            $this->dbLastCheckUpdate($payment_id);
        }
        return $new_status;
    }

    public function cancelPayment($payment_id)
    {
        $qiwi = new BillPayments($this->secret_key);
        $current_info = $this->dbGetPayment($payment_id);
        try {
            $new_info = $qiwi->cancelBill($current_info['additionally']['billId']);
            $this->dbEditPayment($payment_id, Status::CANCELED, $new_info);
        } catch (BillPaymentsException $e) {
            $this->dbEditPayment($payment_id, Status::ERROR, $e);
        }
    }

    public function getCreatedAt()
    {
        $dt = DateTime::createFromFormat(DateTime::ATOM, $this->created_at, new DateTimeZone(date_default_timezone_get()));
        return $dt->format('H:i:s d.m.y');
    }

    public function getExpiresAt()
    {
        $dt = DateTime::createFromFormat(DateTime::ATOM, $this->expires_at, new DateTimeZone(date_default_timezone_get()));
        return $dt->format('H:i:s d.m.y');
    }
}
