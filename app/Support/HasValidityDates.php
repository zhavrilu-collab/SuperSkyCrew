<?php

namespace App\Support;

use Carbon\Carbon;

trait HasValidityDates
{
    public function isValidOn(Carbon $date): bool
    {
        $day = $date->toDateString();

        if ($this->valid_from && $this->valid_from->toDateString() > $day) {
            return false;
        }

        if ($this->valid_to && $this->valid_to->toDateString() < $day) {
            return false;
        }

        return true;
    }
}
