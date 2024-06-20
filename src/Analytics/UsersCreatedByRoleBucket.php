<?php

declare(strict_types=1);

namespace App\Analytics;

use App\Analytics\Dto\UsersCreatedByRole;

class UsersCreatedByRoleBucket
{
    private array $accountsCreatedByRole = [];

    public function add(UsersCreatedByRole $usersCreatedByRole): void
    {
        if (!isset($this->accountsCreatedByRole[$usersCreatedByRole->getRole()])) {
            $this->accountsCreatedByRole[$usersCreatedByRole->getRole()] = 0;
        }
        $this->accountsCreatedByRole[$usersCreatedByRole->getRole()] ++;
    }

    public function get(): \Generator
    {
        $total = array_sum($this->accountsCreatedByRole);
        foreach ($this->accountsCreatedByRole as $key => $item) {
            $usersCreatedByRole = new UsersCreatedByRole($key);
            $usersCreatedByRole->setCount($item);
            $usersCreatedByRole->setPercent(round($item / $total * 100, 2));

            yield $usersCreatedByRole;
        }
    }
}
