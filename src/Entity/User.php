<?php

namespace App\Entity;

use App\Controller\Dto\UserDto;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator as MyAssert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups ("api:programme:all")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\Email
     */
    public string $email = '';

    /**
     * @var string The hashed password
     * @ORM\Column(type="string", length=255)
     * @MyAssert\Password
     */
    private string $password = '';

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @ORM\Column(type="string", length=13)
     * @MyAssert\Cnp
     */
    public string $cnp = '';

    /**
     * @ORM\Column(type="string", length=255, nullable="false")
     * @Assert\NotBlank
     * @Assert\Regex("/^[\p{Lu}].+/")
     * @Groups ("api:programme:all")
     */
    public string $firstName = '';

    /**
     * @ORM\Column(type="string", length=255, nullable="false")
     * @Assert\NotBlank
     * @Assert\Regex("/^[\p{Lu}].+/")
     * @Groups ("api:programme:all")
     */
    public string $lastName = '';

    /**
     * @ORM\ManyToMany(targetEntity="Programme", mappedBy="customers")
     */
    private Collection $programmes;

    /**
     * @ORM\Column(type="uuid", unique=true, nullable=true)
     */
    private Uuid $token;

    /**
     * @ORM\Column(type="datetime", name="deletedAt", nullable=true)
     */
    private ?\DateTimeInterface $deletedAt;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Assert\Regex("/^[0-9]+/")
     */
    private ?string $phone;

    public function getToken(): Uuid
    {
        return $this->token;
    }

    public function setToken(Uuid $token): void
    {
        $this->token = $token;
    }

    public function __construct()
    {
        $this->programmes = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): self
    {
        if (in_array($role, $this->roles)) {
            return $this;
        }
        $this->roles[] = $role;

        return $this;
    }

    public function removeRole(string $role): self
    {
        $key = array_search($role, $this->roles);
        if (false === $key) {
            return $this;
        }
        unset($this->roles[$key]);

        return $this;
    }

    public function getProgrammes(): Collection
    {
        return $this->programmes;
    }

    public function setProgrammes(Collection $programmes): self
    {
        $this->programmes = $programmes;

        return $this;
    }

    public function addProgramme(Programme $programme): self
    {
        if ($this->programmes->contains($programme)) {
            return $this;
        }

        $this->programmes->add($programme);
        $programme->addCustomer($this);

        return $this;
    }

    public function removeProgramme(Programme $programme): self
    {
        if (!$this->programmes->contains($programme)) {
            return $this;
        }

        $this->programmes->removeElement($programme);
        $programme->removeCustomer($this);

        return $this;
    }

    public static function createFromDto(UserDto $userDto): self
    {
        $user = new self();
        $user->addRole('ROLE_USER');
        $user->cnp = $userDto->cnp;
        $user->firstName = $userDto->firstName;
        $user->lastName = $userDto->lastName;
        $user->email = $userDto->email;
        $user->setPassword($userDto->password);

        return $user;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function __toString(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
