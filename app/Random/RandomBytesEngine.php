<?php

namespace App\Random;

class RandomBytesEngine implements \Random\Engine
{
    public function generate(): string
    {
        return random_bytes(64);
    }
}
