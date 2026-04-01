<?php

namespace App\User\Infrastructure\ApiPlatform\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;
use App\User\Infrastructure\ApiPlatform\StateProcessor\CreateUserStateProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/users',
            output: UserResource::class,
            processor: CreateUserStateProcessor::class
        ),
    ]
)]
class CreateUserDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password;

    #[Assert\Choice(choices: ['USER', 'ADMIN'])]
    public string $role = 'USER';
}
