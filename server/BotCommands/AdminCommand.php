<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

use Longman\TelegramBot\Entities\InlineKeyboard;

use Longman\TelegramBot\DB;
use PDO;

class AdminCommand extends SystemCommand
{

    protected $name = 'admin';
    protected $description = 'Админка';
    protected $usage = '/admin';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $admin_id = $this->getMessage()->getFrom()->getId(); // check for admin
        $secret = $this->generateAuthCode($admin_id);
        $url = $this->generateAuthLink($secret);

        return $this->replyToChat('Ссылка для входа в админку (действительна 5 минут)', [
            'parse_mode' => 'html',
            'reply_markup' => new InlineKeyboard([
                ['text' => "\xF0\x9F\x94\x91 Перейти", 'url' => $url]
            ]),
        ]);
    }

    private function generateAuthCode($user_id)
    {
        $secret = bin2hex(random_bytes(5));
        $exp = (new \DateTimeImmutable())->modify('+5 minutes')->format('Y-m-d H:i:s');
        DB::getPdo()->prepare('UPDATE `admins` SET `secret` = ?, `secret_expired` = ?, refresh_token = null WHERE `user_id` = ?')->execute([$secret, $exp, $user_id]);
        return $secret;
    }

    private function generateAuthLink($secret)
    {
        $url = '';
        if (isset($_SERVER["HTTPS"])) $url .= 'https://';
        else $url .= 'http://';
        $url .= $_SERVER['SERVER_NAME'] . '/auth/' . $secret;
        //return $url;
        return 'http://bot-admin.local/auth/' . $secret;
    }
}
