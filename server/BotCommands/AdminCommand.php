<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

use Longman\TelegramBot\Entities\InlineKeyboard;

use AdminPanel\Model\Admins;

class AdminCommand extends SystemCommand
{

    protected $name = 'admin';
    protected $description = 'Админка';
    protected $usage = '/admin';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $user_id = $this->getMessage()->getFrom()->getId();
        $admin = Admins::getByID($user_id);
        if (!empty($admin)) return $this->replyToChat('У вас нет прав администратора');
        $secret = $this->generateAuthCode($user_id);
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
        Admins::editByID($user_id, $secret, $exp);
        return $secret;
    }

    private function generateAuthLink($secret)
    {
        if (substr($_ENV['APP_URL'], -1) == '/') $url = $_ENV['APP_URL'];
        else $url = $_ENV['APP_URL'] . '/';
        $url = $url . 'auth/' . $secret;
        return $url;
    }
}
