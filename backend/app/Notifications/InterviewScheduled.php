<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InterviewScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Interview $interview) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->interview->application->jobOffer;

        return [
            'interview_id' => $this->interview->id,
            'job_title' => $job->title,
            'scheduled_at' => $this->interview->scheduled_at->toDateTimeString(),
            'message' => "An interview for {$job->title} is scheduled for {$this->interview->scheduled_at->format('M j, Y H:i')}.",
        ];
    }
}