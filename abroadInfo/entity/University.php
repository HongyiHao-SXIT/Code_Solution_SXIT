<?php

class University {
    public int $id;
    public ?int $QSrank;
    public ?int $USnewsrank;
    public ?string $country;
    public ?string $city;
    public string $name;

    function __construct(int $id, ?int $QSrank, ?int $USnewsrank, ?string $country, ?string $city, string $name) {
        $this->id = $id;
        $this->QSrank = $QSrank;
        $this->USnewsrank = $USnewsrank;
        $this->country = $country;
        $this->city = $city;
        $this->name = $name;
    }

    function getId(): int {
        return $this->id;
    }

    function getQSrank(): ?int {
        return $this->QSrank;
    }

    function getUSnewsrank(): ?int {
        return $this->USnewsrank;
    }

    function getCountry(): ?string {
        return $this->country;
    }

    function getCity(): ?string {
        return $this->city;
    }

    function getName(): string {
        return $this->name;
    }

    function setId(int $id): void {
        $this->id = $id;
    }

    function setQSrank(?int $QSrank): void {
        $this->QSrank = $QSrank;
    }

    function setUSnewsrank(?int $USnewsrank): void {
        $this->USnewsrank = $USnewsrank;
    }

    function setCountry(?string $country): void {
        $this->country = $country;
    }

    function setCity(?string $city): void {
        $this->city = $city;
    }

    function setName(string $name): void {
        $this->name = $name;
    }
    
}

?>