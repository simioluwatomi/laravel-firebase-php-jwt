<?php

declare(strict_types=1);

namespace App\Support\DataTransferObjects;

final class LoginDataTransferObject
{
    private string $email;

    private string $password;

    /**
     * @throws \Throwable
     */
    public function __construct(string $email, #[\SensitiveParameter] string $password)
    {
        throw_if(
            empty($email),
            new \UnexpectedValueException('The email can not be an empty value.')
        );

        throw_if(
            empty($password),
            new \UnexpectedValueException('The password can not be an empty value.')
        );

        $this->email = $email;

        $this->password = $password;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
