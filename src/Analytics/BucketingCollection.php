<?php

declare(strict_types=1);

namespace App\Analytics;

use App\Analytics\Dto\AdminSuccessLogin;
use App\Analytics\Dto\SuccessLogin;
use App\Analytics\Dto\UsersCreatedByRole;

class BucketingCollection
{
    private SuccessLoginBucket $successLoginBucket;

    private AdminSuccessLoginBucket $adminSuccessLoginBucket;

    private UsersCreatedByRoleBucket $usersCreatedByRoleBucket;

    public function __construct(
        SuccessLoginBucket $successLoginBucket,
        AdminSuccessLoginBucket $adminSuccessLoginBucket,
        UsersCreatedByRoleBucket $usersCreatedByRoleBucket
    ) {
        $this->successLoginBucket = $successLoginBucket;
        $this->adminSuccessLoginBucket = $adminSuccessLoginBucket;
        $this->usersCreatedByRoleBucket = $usersCreatedByRoleBucket;
    }

    public function add($analyticsLine): void
    {
        $decodedData = \json_decode($analyticsLine);
        $email = $decodedData->context->username;
        $dateKey = (new \DateTime($decodedData->datetime))->format('d.m.Y');

        if ($decodedData->message === "Success login") {
            $this->successLoginBucket->add(new SuccessLogin($email));

            return;
        }

        if ($decodedData->message === "Admin success login") {
            $this->adminSuccessLoginBucket->add(new AdminSuccessLogin($dateKey));

            return;
        }

        if ($decodedData->message === "User created") {
            $role = $decodedData->context->roles[0];
            $this->usersCreatedByRoleBucket->add(new UsersCreatedByRole($role));
        }
    }
}
