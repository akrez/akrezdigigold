<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class BaleService extends Service
{
    protected const API_BASE = 'https://tapi.bale.ai';

    public function __construct(
        protected ?string $botToken = null,
        protected ?string $channelId = null,
    ) {
        $this->botToken = $botToken ?: config('services.bale.bot_token');
        $this->channelId = $channelId ?: config('services.bale.channel');
    }

    public function getChannelId()
    {
        return $this->channelId;
    }

    public function sendMessage(string $text, $optionalParameters = [])
    {
        $requiredParameters = [
            'chat_id' => $this->channelId,
            'text' => $text,
        ];

        return $this->sendPostForm('sendMessage', array_replace_recursive(
            $optionalParameters,
            $requiredParameters
        ));
    }

    public function sendPhoto(mixed $photo, ?string $caption = null, $optionalParameters = [])
    {
        $requiredParameters = [
            'chat_id' => $this->channelId,
            'photo' => $photo,
        ];
        if ($caption) {
            $requiredParameters['caption'] = $caption;
        }

        return $this->sendPostForm('sendPhoto', array_replace_recursive(
            $optionalParameters,
            $requiredParameters
        ));
    }

    public function deleteMessage(int $chatId, int $messageId, $optionalParameters = [])
    {
        $requiredParameters = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];

        return $this->sendGetForm('deleteMessage', array_replace_recursive(
            $optionalParameters,
            $requiredParameters
        ));
    }

    public function getUpdates(int $limit, $optionalParameters = [])
    {
        return $this->sendGetForm('getUpdates', $optionalParameters + [
            'limit' => $limit,
            'timeout' => 0,
        ]);
    }

    protected function sendGetForm(string $path, $query = [])
    {
        if (! $this->isConfiged()) {
            return null;
        }

        try {
            $url = implode('/', [
                self::API_BASE,
                'bot'.$this->botToken,
                $path,
            ]);

            return Http::timeout(15)
                ->retry(2, 100)
                ->get($url, $query);
        } catch (\Throwable $e) {
            $this->logError($e);
        }

        return null;
    }

    protected function sendPostForm(string $path, $postData = [])
    {
        if (! $this->isConfiged()) {
            return null;
        }

        try {
            $url = implode('/', [
                self::API_BASE,
                'bot'.$this->botToken,
                $path,
            ]);

            return Http::asForm()
                ->timeout(15)
                ->retry(2, 100)
                ->post($url, $postData);
        } catch (\Throwable $e) {
            $this->logError($e);
        }

        return null;
    }

    protected function isConfiged(): bool
    {
        if ($this->botToken && $this->channelId) {
            return true;
        }

        $this->logError(new Exception(
            '[BaleService] Bot token or channel not configured, skipping message.',
        ));

        return false;
    }
}
