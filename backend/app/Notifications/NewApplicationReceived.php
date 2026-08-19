<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewApplicationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Application $application) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $candidate = $this->application->candidate;

        return [
            'application_id' => $this->application->id,
            'job_title' => $this->application->jobOffer->title,
            'candidate_name' => $candidate->first_name.' '.$candidate->last_name,
            'message' => "New application from {$candidate->first_name} {$candidate->last_name} for {$this->application->jobOffer->title}.",
        ];
    }
}