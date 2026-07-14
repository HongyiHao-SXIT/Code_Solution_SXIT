#include "User.h"

#include <iostream>

User::User() = default;

User::User(const std::string &userName, const std::string &emailAddress,
           const std::string &accountId, const std::string &plainPassword,
           const std::string &phoneNumber, const std::string &majorName)
    : name(userName), email(emailAddress), account(accountId),
      password(plainPassword), phone(phoneNumber), major(majorName) {}

std::string User::getAccount() const { return account; }

std::string User::getEmail() const { return email; }

std::string User::getMajor() const { return major; }

std::string User::getName() const { return name; }

std::string User::getPassword() const { return password; }

std::string User::getPhone() const { return phone; }

void User::setAccount(const std::string &accountId) { account = accountId; }

void User::setEmail(const std::string &emailAddress) { email = emailAddress; }

void User::setMajor(const std::string &majorName) { major = majorName; }

void User::setName(const std::string &userName) { name = userName; }

void User::setPassword(const std::string &plainPassword) {
  password = plainPassword;
}

void User::setPhone(const std::string &phoneNumber) { phone = phoneNumber; }

void User::displayInfo() const {
  std::cout << "Name: " << name << std::endl;
  std::cout << "Email: " << email << std::endl;
  std::cout << "Account: " << account << std::endl;
  std::cout << "Phone: " << phone << std::endl;
  std::cout << "Major: " << major << std::endl;
}