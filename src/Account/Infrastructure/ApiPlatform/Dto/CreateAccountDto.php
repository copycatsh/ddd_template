<?php

namespace App\Account\Infrastructure\ApiPlatform\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Account\Infrastructure\ApiPlatform\Resource\AccountResource;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\CreateAccountStateProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/accounts',
            output: AccountResource::class,
            processor: CreateAccountStateProcessor::class
        ),
    ]
)]
class CreateAccountDto
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $userId;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['UAH', 'USD'])]
    public string $currency;
}
