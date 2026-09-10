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

    protected function sendPostForm(string $path, $postData = [])
    {
        try {
            if ($this->botToken && $this->channelId) {
                $url = implode('/', [
                    self::API_BASE,
                    'bot'.$this->botToken,
                    $path,
                ]);

                return Http::asForm()
                    ->timeout(15)
                    ->retry(2, 100)
                    ->post($url, $postData);
            } else {
                $this->logError(new Exception(
                    '[BaleService] Bot token or channel not configured, skipping message.',
                ));
            }
        } catch (\Throwable $e) {
            $this->logError($e);
        }

        return null;
    }
}
