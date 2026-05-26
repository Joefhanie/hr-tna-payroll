<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;

class SystemNotification
{
    use Queueable;

    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $message,
        private readonly string $url,
        private readonly string $icon = 'ti ti-bell',
        private readonly array $meta = []
    ) {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function meta(): array
    {
        return $this->meta;
    }
}
