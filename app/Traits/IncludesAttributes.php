<?php

namespace App\Traits;

trait IncludesAttributes
{
    /**
     * Include an attribute only if it exists.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function whenExists($value)
    {
        return $this->when(isset($value), $value);
    }
}