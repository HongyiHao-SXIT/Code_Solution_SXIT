#include "User.h"

#include <iostream>

User::User() {}

User::User(const std::string &name, const std::string &email,
           const std::string &account, const std::string &password,
           const std::string &phone, const std::string &major) {
  this->name = name;
  this->email = email;
  this->account = account;
  this->password = password;
  this->phone = phone;
  this->major = major;
}

std::string User::getAccount() const { return account; }

std::string User::getEmail() const { return email; }

std::string User::getMajor() const { return major; }

std::string User::getName() const { return name; }

std::string User::getPassword() const { return password; }

std::string User::getPhone() const { return phone; }

void User::setAccount(const std::string &account) { this->account = account; }

void User::setEmail(const std::string &email) { this->email = email; }

void User::setMajor(const std::string &major) { this->major = major; }

void User::setName(const std::string &name) { this->name = name; }

void User::setPassword(const std::string &password) {
  this->password = password;
}

void User::setPhone(const std::string &phone) { this->phone = phone; }

void User::displayInfo() const {
  std::cout << "Name: " << name << std::endl;
  std::cout << "Email: " << email << std::endl;
  std::cout << "Account: " << account << std::endl;
  std::cout << "Phone: " << phone << std::endl;
  std::cout << "Major: " << major << std::endl;
}