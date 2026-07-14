#ifndef USER_H
#define USER_H

#include <string>

class User {
private:
  std::string name;
  std::string email;
  std::string account;
  std::string password;
  std::string phone;
  std::string major;

public:
  User();
  User(const std::string &userName, const std::string &emailAddress,
       const std::string &accountId, const std::string &plainPassword,
       const std::string &phoneNumber, const std::string &majorName);

  std::string getName() const;
  std::string getEmail() const;
  std::string getAccount() const;
  std::string getPassword() const;
  std::string getPhone() const;
  std::string getMajor() const;

  void setName(const std::string &userName);
  void setEmail(const std::string &emailAddress);
  void setAccount(const std::string &accountId);
  void setPassword(const std::string &plainPassword);
  void setPhone(const std::string &phoneNumber);
  void setMajor(const std::string &majorName);

  void displayInfo() const;
};

#endif