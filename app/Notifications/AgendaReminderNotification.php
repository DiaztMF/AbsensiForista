<?php

namespace App\Notifications;

use App\Models\AgendaSession;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AgendaReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public AgendaSession $session,
        public string $title,
        public string $body
    ) {}

    /**
     * Notification ini hanya dikirim ke model User.
     *
     * @param  User  $notifiable
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * @param  User  $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'agenda_session_id' => $this->session->id,
            'agenda_title' => $this->session->agenda->title,
            'title' => $this->title,
            'body' => $this->body,
            'url' => route('member.dashboard'),
        ];
    }

    /**
     * @param  User  $notifiable
     * @return array<string, mixed>
     */
    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'icon' => '/icons/icon-192x192.png',
            'badge' => '/icons/badge-72x72.png',
            'data' => [
                'url' => route('member.dashboard'),
            ],
        ];
    }
}
