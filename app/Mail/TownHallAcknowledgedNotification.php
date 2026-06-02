<?php

namespace App\Mail;

use App\Models\TownHallAcknowledgement;
use App\Models\TownHallCommunication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TownHallAcknowledgedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TownHallCommunication $communication;
    public TownHallAcknowledgement $acknowledgement;
    public array $summary;

    public function __construct(
        TownHallCommunication $communication,
        TownHallAcknowledgement $acknowledgement,
        array $summary
    ) {
        $this->communication = $communication;
        $this->acknowledgement = $acknowledgement;
        $this->summary = $summary;
    }

    public function build()
    {
        return $this
            ->subject('Communication Acknowledged: ' . ($this->communication->ref_no ?: 'TownHall Communication'))
            ->view('emails.townhall-acknowledged');
    }
}
