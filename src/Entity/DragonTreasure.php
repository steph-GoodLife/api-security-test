<?php

namespace App\Entity;

use Carbon\Carbon;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use function Symfony\Component\String\u;
use App\Repository\DragonTreasureRepository;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: DragonTreasureRepository::class)]
#[ApiResource(
    shortName: 'Treasure',
    description: 'A rare and valuable treasure.',
    operations: [
        new Get(
            normalizationContext: [
                'groups' => ['treasure:read', 'treasure:item:get'],
            ],
        ),
        new GetCollection(),
        new Post(security: 'is_granted("ROLE_TREASURE_CREATE")'),
        //new Put(security: 'is_granted("ROLE_TREASURE_EDIT")'),
        new Patch(
            security: 'is_granted("ROLE_TREASURE_EDIT") and object.getOwner() == user',
            securityPostDenormalize: 'object.getOwner() == user',
        ),
        new Delete(security: 'is_granted("ROLE_AMIN")')
    ],
    formats: [
        'jsonld',
        'json',
        'html',
        'jsonhal',
        'csv' => 'text/csv',
    ],
    normalizationContext: [
        'groups' => ['treasure:read'],
    ],
    denormalizationContext: [
        'groups' => ['treasure:write'],
    ],
    paginationItemsPerPage: 10,
    extraProperties: [
        'standard_put' => true,
    ]
)]
#[ApiResource(
    uriTemplate: '/users/{user_id}/treasures.{_format}',
    shortName: 'Treasure',
    operations: [new GetCollection()],
    uriVariables: [
        'user_id' => new Link(
            fromProperty: 'dragonTreasures',
            fromClass: User::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['treasure:read'],
    ],
    extraProperties: [
        'standard_put' => true,
    ],
)]
#[ApiFilter(PropertyFilter::class)]
#[ApiFilter(SearchFilter::class, properties: [
    'owner.username' => 'partial',
])]
class DragonTreasure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['treasure:read', 'treasure:write', 'user:read', 'user:write'])]
    #[ApiFilter(SearchFilter::class, strategy: 'partial')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50, maxMessage: 'Describe your loot in 50 chars or less')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['treasure:read'])]
    #[ApiFilter(SearchFilter::class, strategy: 'partial')]
    #[Assert\NotBlank]
    private ?string $description = null;

    /**
     * The estimated value of this treasure, in gold coins.
     */
    #[ORM\Column]
    #[Groups(['treasure:read', 'treasure:write', 'user:read', 'user:write'])]
    #[ApiFilter(RangeFilter::class)]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $value = 0;

    #[ORM\Column]
    #[Groups(['treasure:read', 'treasure:write'])]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\LessThanOrEqual(10)]
    private ?int $coolFactor = 0;

    #[ORM\Column]
    private \DateTimeImmutable $plunderedAt;

    #[ORM\Column]
    #[ApiFilter(BooleanFilter::class)]
    private bool $isPublished = false;

    #[ORM\ManyToOne(inversedBy: 'dragonTreasures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['treasure:read', 'treasure:write'])]
    #[Assert\Valid]
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    private ?User $owner = null;

    public function __construct(string $name = null)
    {
        $this->name = $name;
        $this->plunderedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    #[Groups(['treasure:read'])]
    public function getShortDescription(): string
    {
        return u($this->getDescription())->truncate(40, '...');
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    #[SerializedName('description')]
    #[Groups(['treasure:write', 'user:write'])]
    public function setTextDescription(string $description): self
    {
        $this->description = nl2br($description);

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getCoolFactor(): ?int
    {
        return $this->coolFactor;
    }

    public function setCoolFactor(int $coolFactor): self
    {
        $this->coolFactor = $coolFactor;

        return $this;
    }

    public function getPlunderedAt(): ?\DateTimeImmutable
    {
        return $this->plunderedAt;
    }

    public function setPlunderedAt(\DateTimeImmutable $plunderedAt): self
    {
        $this->plunderedAt = $plunderedAt;

        return $this;
    }

    /**
     * A human-readable representation of when this treasure was plundered.
     */
    #[Groups(['treasure:read'])]
    public function getPlunderedAtAgo(): string
    {
        return Carbon::instance($this->plunderedAt)->diffForHumans();
    }

    public function getIsPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
