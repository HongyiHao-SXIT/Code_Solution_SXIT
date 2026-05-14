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
  User(const std::string& name, const std::string& email,
       const std::string& account, const std::string& password,
       const std::string& phone, const std::string& major);

  std::string getName() const;
  std::string getEmail() const;
  std::string getAccount() const;
  std::string getPassword() const;
  std::string getPhone() const;
  std::string getMajor() const;

  void setName(const std::string& name);
  void setEmail(const std::string& email);
  void setAccount(const std::string& account);
  void setPassword(const std::string& password);
  void setPhone(const std::string& phone);
  void setMajor(const std::string& major);

  void displayInfo() const;
};

#endif