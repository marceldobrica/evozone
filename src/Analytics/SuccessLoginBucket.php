<?php

declare(strict_types=1);

namespace App\Analytics;

use App\Analytics\Dto\SuccessLogin;

class SuccessLoginBucket
{
    private array $apiLogins = [];

    public function add(SuccessLogin $successLogin): void
    {
        if (!isset($this->apiLogins[$successLogin->getEmail()])) {
            $this->apiLogins[$successLogin->getEmail()] = 0;
        }
        $this->apiLogins[$successLogin->getEmail()] ++;
    }

    public function get(): \Generator
    {
        \arsort($this->apiLogins, SORT_NUMERIC);
        foreach ($this->apiLogins as $key => $item) {
            $apiLogin = new SuccessLogin($key);
            $apiLogin->setLoginCounts($item);

            yield $apiLogin;
        }
    }
}
