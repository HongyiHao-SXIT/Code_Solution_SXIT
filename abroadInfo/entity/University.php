<?php

class University {
    var $id;
    var $QSrank;
    var $USnewsrank;
    var $country;
    var $city;
    var $name;

    function __construct($id, $QSrank, $USnewsrank, $country, $city, $name) {
        $this->id = $id;
        $this->QSrank = $QSrank;
        $this->USnewsrank = $USnewsrank;
        $this->country = $country;
        $this->city = $city;
        $this->name = $name;
    }

    function getId() {
        return $this->id;
    }

    function getQSrank() {
        return $this->QSrank;
    }

    function getUSnewsrank() {
        return $this->USnewsrank;
    }

    function getCountry() {
        return $this->country;
    }

    function getCity() {
        return $this->city;
    }

    function getName() {
        return $this->name;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setQSrank($QSrank) {
        $this->QSrank = $QSrank;
    }

    function setUSnewsrank($USnewsrank) {
        $this->USnewsrank = $USnewsrank;
    }

    function setCountry($country) {
        $this->country = $country;
    }

    function setCity($city) {
        $this->city = $city;
    }

    function setName($name) {
        $this->name = $name;
    }
    
}

?>