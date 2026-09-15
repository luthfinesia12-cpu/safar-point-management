<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class WorkflowStatusChanged implements ShouldDispatchAfterCommit
{
    public function __construct(public array $recipientIds, public string $title, public string $message, public array $context = []) {}
}
