<?php

declare(strict_types=1);

namespace App\Exception;

class NotValidProgrammeEntryException extends \Exception
{
    protected $message = 'Not able to assign a room to programme';
}
