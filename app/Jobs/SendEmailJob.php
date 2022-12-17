<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\Send;
use Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $_recipient;
    public $_subject;
    public $_view;
    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($recipient, $subject, $view, $data)
    {
        $this->_recipient = $recipient;
        $this->_subject = $subject;
        $this->_view = $view;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->_recipient)->send(
            new Send($this->_subject, $this->_view, $this->data)
        );
    }
}
