#include "AuthControl.h"

#include <cctype>
#include <fstream>
#include <functional>
#include <iostream>
#include <sstream>
#include <string>
#include <vector>

namespace {

constexpr const char *kUserDbPath = "users.txt";
constexpr const char *kHashedPrefix = "H:";
std::string gCurrentAccount;

struct UserRecord {
  std::string account;
  std::string password;
  std::string name;
  std::string email;
  std::string phone;
  std::string major;
  bool passwordHashed = false;
};

std::string trimLeft(const std::string &input) {
  size_t pos = 0;
  while (pos < input.size() &&
         std::isspace(static_cast<unsigned char>(input[pos]))) {
    ++pos;
  }
  return input.substr(pos);
}

std::string hashPassword(const std::string &password) {
  return std::to_string(std::hash<std::string>{}(password));
}

bool validatePassword(const std::string &password) {
  if (password.length() < 8) {
    std::cerr << "Error: Password must be at least 8 characters long."
              << std::endl;
    return false;
  }
  if (password.find_first_of("0123456789") == std::string::npos) {
    std::cerr << "Error: Password must contain at least one number."
              << std::endl;
    return false;
  }
  if (password.find_first_of("!@#$%^&*()_+-=") == std::string::npos) {
    std::cerr << "Error: Password must contain at least one special character."
              << std::endl;
    return false;
  }
  if (password.find_first_of("ABCDEFGHIJKLMNOPQRSTUVWXYZ") ==
      std::string::npos) {
    std::cerr << "Error: Password must contain at least one uppercase letter."
              << std::endl;
    return false;
  }
  return true;
}

bool isValidEmail(const std::string &email) {
  return !email.empty() && email.find('@') != std::string::npos;
}

bool isValidPhone(const std::string &phone) {
  return phone.length() == 11 &&
         phone.find_first_not_of("0123456789") == std::string::npos;
}

bool parseModernLine(const std::string &line, UserRecord &record) {
  std::vector<std::string> fields;
  std::string field;
  std::istringstream iss(line);
  while (std::getline(iss, field, '\t')) {
    fields.push_back(field);
  }

  if (fields.size() != 6) {
    return false;
  }

  record.account = fields[0];
  record.password = fields[1];
  record.name = fields[2];
  record.email = fields[3];
  record.phone = fields[4];
  record.major = fields[5];

  if (record.password.rfind(kHashedPrefix, 0) == 0) {
    record.password = record.password.substr(2);
    record.passwordHashed = true;
  }

  return true;
}

bool parseLegacyLine(const std::string &line, UserRecord &record) {
  std::istringstream iss(line);
  if (!(iss >> record.account >> record.password >> record.name >>
        record.email >> record.phone)) {
    return false;
  }

  std::string majorRest;
  std::getline(iss, majorRest);
  record.major = trimLeft(majorRest);
  record.passwordHashed = false;
  return true;
}

std::vector<UserRecord> loadUsers(bool &fileAvailable) {
  std::ifstream readFile(kUserDbPath);
  if (!readFile.is_open()) {
    fileAvailable = false;
    return {};
  }

  fileAvailable = true;
  std::vector<UserRecord> users;
  std::string line;
  while (std::getline(readFile, line)) {
    if (line.empty()) {
      continue;
    }

    UserRecord record;
    if (parseModernLine(line, record) || parseLegacyLine(line, record)) {
      users.push_back(record);
    }
  }
  return users;
}

bool saveUsers(const std::vector<UserRecord> &users) {
  std::ofstream writeFile(kUserDbPath, std::ios::trunc);
  if (!writeFile.is_open()) {
    return false;
  }

  for (const auto &user : users) {
    const std::string finalHash =
        user.passwordHashed ? user.password : hashPassword(user.password);
    writeFile << user.account << '\t' << kHashedPrefix << finalHash << '\t'
              << user.name << '\t' << user.email << '\t' << user.phone << '\t'
              << user.major << '\n';
  }

  return true;
}

bool isPasswordMatched(const UserRecord &user,
                       const std::string &inputPassword) {
  if (user.passwordHashed) {
    return user.password == hashPassword(inputPassword);
  }
  return user.password == inputPassword;
}

} // namespace

bool login() {
  std::string inputAccount, inputPassword;

  std::cout << "Enter Account: ";
  std::cin >> inputAccount;
  std::cout << "Enter Password: ";
  std::cin >> inputPassword;

  bool fileAvailable = false;
  std::vector<UserRecord> users = loadUsers(fileAvailable);
  if (!fileAvailable) {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }

  bool isAuthenticated = false;
  bool needRewrite = false;

  for (auto &user : users) {
    if (user.account == inputAccount &&
        isPasswordMatched(user, inputPassword)) {
      isAuthenticated = true;
      if (!user.passwordHashed) {
        user.password = hashPassword(inputPassword);
        user.passwordHashed = true;
        needRewrite = true;
      }
      break;
    }
  }

  if (needRewrite && !saveUsers(users)) {
    std::cerr << "Warning: Login succeeded, but failed to update password "
                 "storage format."
              << std::endl;
  }

  if (isAuthenticated) {
    gCurrentAccount = inputAccount;
    std::cout << "Login Successful!" << std::endl;
  } else {
    gCurrentAccount.clear();
    std::cout << "Login Failed! Invalid account or password." << std::endl;
  }

  return isAuthenticated;
}

std::string getCurrentAccount() { return gCurrentAccount; }

void logoutUser() { gCurrentAccount.clear(); }

bool registerUser() {
  std::string name, email, account, password, phone, major;

  std::cout << "--- User Registration ---" << std::endl;
  std::cout << "Enter Name: ";
  std::getline(std::cin >> std::ws, name);
  if (name.empty()) {
    std::cerr << "Error: Name cannot be empty." << std::endl;
    return false;
  }

  std::cout << "Enter Email: ";
  std::cin >> email;
  if (!isValidEmail(email)) {
    std::cerr << "Error: Invalid email format." << std::endl;
    return false;
  }

  std::cout << "Enter Account (the length of account should longer than 5 "
               "characters and shorter than 15 characters): ";
  std::cin >> account;
  if (account.length() < 5 || account.length() > 15) {
    std::cerr << "Error: Account must be at least 5 characters long and less "
                 "than 15 characters long."
              << std::endl;
    return false;
  }

  std::cout << "Enter Password: ";
  std::cin >> password;
  if (!validatePassword(password)) {
    return false;
  }

  std::cout << "Enter Phone: ";
  std::cin >> phone;
  if (!isValidPhone(phone)) {
    std::cerr << "Error: Phone number must be exactly 11 digits and contain "
                 "only numbers."
              << std::endl;
    return false;
  }

  std::cout << "Enter Major: ";
  std::getline(std::cin >> std::ws, major);
  if (major.empty()) {
    std::cerr << "Error: Major cannot be empty." << std::endl;
    return false;
  }

  bool fileAvailable = false;
  std::vector<UserRecord> users = loadUsers(fileAvailable);

  for (const auto &user : users) {
    if (user.account == account) {
      std::cerr << "Error: Account already exists." << std::endl;
      return false;
    }
  }

  UserRecord newUser;
  newUser.account = account;
  newUser.password = hashPassword(password);
  newUser.name = name;
  newUser.email = email;
  newUser.phone = phone;
  newUser.major = major;
  newUser.passwordHashed = true;
  users.push_back(newUser);

  if (saveUsers(users)) {
    std::cout << "Registration Successful!" << std::endl;
    return true;
  } else {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }
}

bool forgetAccount() {
  std::string inputEmail;
  std::cout << "Please enter your email: ";
  std::cin >> inputEmail;

  if (!isValidEmail(inputEmail)) {
    std::cerr << "Error: Invalid email format." << std::endl;
    return false;
  }
  if (inputEmail.empty()) {
    std::cerr << "Error: Email cannot be empty." << std::endl;
    return false;
  }

  bool fileAvailable = false;
  std::vector<UserRecord> users = loadUsers(fileAvailable);
  if (!fileAvailable) {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }

  bool isFound = false;

  for (const auto &user : users) {
    if (user.email == inputEmail) {
      isFound = true;
      std::cout << "Account found: " << user.account << std::endl;
      break;
    }
  }

  if (!isFound) {
    std::cerr << "Error: Email not found in database." << std::endl;
    return false;
  }

  return true;
}

bool forgetPassword() {
  std::string inputAccount, inputEmail;
  std::cout << "Please enter your account: ";
  std::cin >> inputAccount;
  std::cout << "Please enter your email: ";
  std::cin >> inputEmail;

  if (!isValidEmail(inputEmail)) {
    std::cerr << "Error: Invalid email format." << std::endl;
    return false;
  }

  bool fileAvailable = false;
  std::vector<UserRecord> users = loadUsers(fileAvailable);
  if (!fileAvailable) {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }

  bool isFound = false;

  for (auto &user : users) {
    if (user.account == inputAccount && user.email == inputEmail) {
      isFound = true;

      std::string newPassword;
      std::cout << "Account verified. Enter a new password: ";
      std::cin >> newPassword;

      if (!validatePassword(newPassword)) {
        return false;
      }

      user.password = hashPassword(newPassword);
      user.passwordHashed = true;

      if (!saveUsers(users)) {
        std::cerr << "Error: Could not update database file." << std::endl;
        return false;
      }

      std::cout << "Password reset successful." << std::endl;
      break;
    }
  }

  if (!isFound) {
    std::cerr << "Error: Account and email combination not found." << std::endl;
    return false;
  }

  return true;
}

bool getCurrentUser(User &currentUser) {
  if (gCurrentAccount.empty()) {
    std::cerr << "Error: No user is currently logged in." << std::endl;
    return false;
  }

  bool fileAvailable = false;
  const std::vector<UserRecord> users = loadUsers(fileAvailable);
  if (!fileAvailable) {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }

  for (const auto &user : users) {
    if (user.account == gCurrentAccount) {
      currentUser =
          User(user.name, user.email, user.account, "", user.phone, user.major);
      return true;
    }
  }

  std::cerr << "Error: Current user not found." << std::endl;
  return false;
}

bool updateCurrentUser(const User &updatedUser) {
  if (gCurrentAccount.empty()) {
    std::cerr << "Error: No user is currently logged in." << std::endl;
    return false;
  }

  if (updatedUser.getName().empty()) {
    std::cerr << "Error: Name cannot be empty." << std::endl;
    return false;
  }
  if (!isValidEmail(updatedUser.getEmail())) {
    std::cerr << "Error: Invalid email format." << std::endl;
    return false;
  }
  if (!isValidPhone(updatedUser.getPhone())) {
    std::cerr << "Error: Phone number must be exactly 11 digits and contain "
                 "only numbers."
              << std::endl;
    return false;
  }
  if (updatedUser.getMajor().empty()) {
    std::cerr << "Error: Major cannot be empty." << std::endl;
    return false;
  }

  bool fileAvailable = false;
  std::vector<UserRecord> users = loadUsers(fileAvailable);
  if (!fileAvailable) {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }

  for (auto &user : users) {
    if (user.account == gCurrentAccount) {
      user.name = updatedUser.getName();
      user.email = updatedUser.getEmail();
      user.phone = updatedUser.getPhone();
      user.major = updatedUser.getMajor();
      return saveUsers(users);
    }
  }

  std::cerr << "Error: Current user not found." << std::endl;
  return false;
}

bool forget() {
  char choice;

  std::cout << "--- Forget Services ---" << std::endl;
  std::cout << "Please select an option:" << std::endl;
  std::cout << "A. Forget Account?" << std::endl;
  std::cout << "B. Forgot Password?" << std::endl;
  std::cout << "C. Exit" << std::endl;

  std::cin >> choice;

  switch (std::toupper(static_cast<unsigned char>(choice))) {
  case 'A':
    return forgetAccount();
  case 'B':
    return forgetPassword();
  case 'C':
    std::cout << "Exiting forget services..." << std::endl;
    return false;
  default:
    std::cerr << "Error: Invalid option." << std::endl;
    return false;
  }
}