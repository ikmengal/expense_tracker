<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetAlertNotification extends Notification
{
    use Queueable;

    protected $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function via($notifiable)
    {
        return ['database']; // System local database channel
    }

    public function toArray($notifiable)
    {
        return [
            'category_name' => $this->details['category_name'],
            'percentage' => $this->details['percentage'],
            'message' => "Alert! Aapka month budget for {$this->details['category_name']} {$this->details['percentage']}% use ho chuka hy.",
            'type' => 'budget_warning'
        ];
    }
}
