<?php

class Project {
    var $id;
    var $name;
    var $description;
    var $language_requirement;
    var $GPA_requirement;
    var $Belong_to;

    function __construct($id, $name, $description, $language_requirement, $GPA_requirement, $Belong_to) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->language_requirement = $language_requirement;
        $this->GPA_requirement = $GPA_requirement;
        $this->Belong_to = $Belong_to;
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getDescription() {
        return $this->description;
    }

    function getLanguageRequirement() {
        return $this->language_requirement;
    }

    function getGPARequirement() {
        return $this->GPA_requirement;
    }

    function getBelongTo() {
        return $this->Belong_to;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setName($name) {
        $this->name = $name;
    }

    function setDescription($description) {
        $this->description = $description;
    }

    function setLanguageRequirement($language_requirement) {
        $this->language_requirement = $language_requirement;
    }

    function setGPARequirement($GPA_requirement) {
        $this->GPA_requirement = $GPA_requirement;
    }

    function setBelongTo($Belong_to) {
        $this->Belong_to = $Belong_to;
    }
}

?>