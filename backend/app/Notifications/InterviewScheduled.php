<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InterviewScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Interview $interview, public string $event = 'scheduled') {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->interview->application->jobOffer;
        $when = $this->interview->scheduled_at->format('M j, Y H:i');

        $message = match ($this->event) {
            'rescheduled' => "Your interview for {$job->title} has been rescheduled to {$when}.",
            'cancelled' => "Your interview for {$job->title} has been cancelled.",
            default => "An interview for {$job->title} is scheduled for {$when}.",
        };

        return [
            'interview_id' => $this->interview->id,
            'job_title' => $job->title,
            'scheduled_at' => $this->interview->scheduled_at?->toDateTimeString(),
            'status' => $this->interview->status->value,
            'message' => $message,
        ];
    }
}