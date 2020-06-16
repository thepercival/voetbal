<?php

namespace Voetbal\Priority;

interface Prioritizable
{
    public function getPriority(): int;

    public function setPriority(int $priority);
}