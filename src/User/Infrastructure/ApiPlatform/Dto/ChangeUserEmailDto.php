<?php

namespace App\User\Infrastructure\ApiPlatform\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;
use App\User\Infrastructure\ApiPlatform\StateProcessor\ChangeUserEmailStateProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Put(
            uriTemplate: '/users/{id}/email',
            read: false,
            output: UserResource::class,
            processor: ChangeUserEmailStateProcessor::class
        ),
    ]
)]
class ChangeUserEmailDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;
}
