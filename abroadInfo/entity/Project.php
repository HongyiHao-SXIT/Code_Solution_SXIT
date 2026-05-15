<?php

class Project {
    public int $id;
    public string $name;
    public ?string $description;
    public ?string $language_requirement;
    public ?float $GPA_requirement;
    public int $Belong_to;

    function __construct(
        int $id,
        string $name,
        ?string $description,
        ?string $language_requirement,
        ?float $GPA_requirement,
        int $Belong_to
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->language_requirement = $language_requirement;
        $this->GPA_requirement = $GPA_requirement;
        $this->Belong_to = $Belong_to;
    }

    function getId(): int {
        return $this->id;
    }

    function getName(): string {
        return $this->name;
    }

    function getDescription(): ?string {
        return $this->description;
    }

    function getLanguageRequirement(): ?string {
        return $this->language_requirement;
    }

    function getGPARequirement(): ?float {
        return $this->GPA_requirement;
    }

    function getBelongTo(): int {
        return $this->Belong_to;
    }

    function setId(int $id): void {
        $this->id = $id;
    }

    function setName(string $name): void {
        $this->name = $name;
    }

    function setDescription(?string $description): void {
        $this->description = $description;
    }

    function setLanguageRequirement(?string $language_requirement): void {
        $this->language_requirement = $language_requirement;
    }

    function setGPARequirement(?float $GPA_requirement): void {
        $this->GPA_requirement = $GPA_requirement;
    }

    function setBelongTo(int $Belong_to): void {
        $this->Belong_to = $Belong_to;
    }
}

?>