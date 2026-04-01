<?php

declare(strict_types=1);

namespace App\User\Domain\Port;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;
}
