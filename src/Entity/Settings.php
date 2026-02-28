<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingsRepository::class)]
class Settings
{
    public const HABITAT_NAME_MAX_LENGTH = 40;
    private const ENCRYPTION_METHOD = 'AES-256-CBC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getEncryptedValue(): ?string
    {
        $decryptedValue = openssl_decrypt(
            $this->value,
            self::ENCRYPTION_METHOD,
            $_ENV['ENCRYPTION_KEY'],
            0,
            substr(hash('sha256', $_ENV['ENCRYPTION_KEY']), 0, 16),
        );

        return $decryptedValue;
    }

    /**
     * Encrypting values using this method is only as an extra layer of security in the worst case scenario of somebody
     * getting hold of the database. The encrypted value is not expected to be displayed on the front-end. If it was,
     * we'd have a unique value for the IV. In any case, this may be replaced with the DoctrineEncryptBundle solution
     * once it becomes available for ORM 3.
     *
     * @see https://github.com/DoctrineEncryptBundle/DoctrineEncryptBundle/issues/20
     */
    public function setEncryptedValue(?string $value): static
    {
        $encryptedValue = openssl_encrypt(
            $value,
            self::ENCRYPTION_METHOD,
            $_ENV['ENCRYPTION_KEY'],
            0,
            substr(hash('sha256', $_ENV['ENCRYPTION_KEY']), 0, 16),
        );

        $this->value = $encryptedValue;

        return $this;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }
}
