<?php
class User {
    var $id;
    var $account;
    var $password;
    var $role;

    function __construct($id, $account, $password, $role = null) {
        $this->id = $id;
        $this->setAccount($account);
        $this->setPassword($password);
        $this->role = $role;
    }

    function getId() {
        return $this->id;
    }

    function getAccount() {
        return $this->account;
    }

    function getPassword() {
        return $this->password;
    }

    function verifyPassword($password) {
        return password_verify($password, $this->password);
    }

    function setId($id) {
        $this->id = $id;
    }

    function setAccount($account) {
        if (strlen($account) < 6 || strlen($account) > 15) {
            throw new InvalidArgumentException("Account must be between 6 and 15 characters.");
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $account)) {
            throw new InvalidArgumentException("Account can only contain letters, numbers, and underscores.");
        }

        $this->account = $account;
    }

    function setPassword($password) {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException("Password must be at least 8 characters long.");
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException("Password must contain at least one uppercase letter.");
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException("Password must contain at least one lowercase letter.");
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException("Password must contain at least one number.");
        }

        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }
}

?>