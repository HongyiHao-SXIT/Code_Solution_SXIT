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
std::string gCurrentAccountId;

struct UserRecord {
  std::string account;
  std::string password;
  std::string name;
  std::string email;
  std::string phone;
  std::string major;
  bool passwordHashed = false;
};

std::string trimLeadingWhitespace(const std::string &input) {
  size_t firstNonWhitespace = 0;
  while (firstNonWhitespace < input.size() &&
         std::isspace(static_cast<unsigned char>(input[firstNonWhitespace]))) {
    ++firstNonWhitespace;
  }

  return input.substr(firstNonWhitespace);
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
  record.major = trimLeadingWhitespace(majorRest);
  record.passwordHashed = false;
  return true;
}

std::vector<UserRecord> loadUsers(bool &isFileAvailable) {
  std::ifstream readFile(kUserDbPath);
  if (!readFile.is_open()) {
    isFileAvailable = false;
    return {};
  }

  isFileAvailable = true;
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
    const std::string passwordHash =
        user.passwordHashed ? user.password : hashPassword(user.password);
    writeFile << user.account << '\t' << kHashedPrefix << passwordHash << '\t'
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
  std::string enteredAccountId;
  std::string enteredPassword;

  std::cout << "Enter Account: ";
  std::cin >> enteredAccountId;
  std::cout << "Enter Password: ";
  std::cin >> enteredPassword;

  bool isUserFileAvailable = false;
  std::vector<UserRecord> users = loadUsers(isUserFileAvailable);
  if (!isUserFileAvailable) {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }

  bool isLoginSuccessful = false;
  bool shouldRewriteUsers = false;

  for (auto &userRecord : users) {
    if (userRecord.account == enteredAccountId &&
        isPasswordMatched(userRecord, enteredPassword)) {
      isLoginSuccessful = true;
      if (!userRecord.passwordHashed) {
        userRecord.password = hashPassword(enteredPassword);
        userRecord.passwordHashed = true;
        shouldRewriteUsers = true;
      }
      break;
    }
  }

  if (shouldRewriteUsers && !saveUsers(users)) {
    std::cerr << "Warning: Login succeeded, but failed to update password "
                 "storage format."
              << std::endl;
  }

  if (isLoginSuccessful) {
    gCurrentAccountId = enteredAccountId;
    std::cout << "Login Successful!" << std::endl;
  } else {
    gCurrentAccountId.clear();
    std::cout << "Login Failed! Invalid account or password." << std::endl;
  }

  return isLoginSuccessful;
}

std::string getCurrentAccount() { return gCurrentAccountId; }

void logoutUser() { gCurrentAccountId.clear(); }

bool registerUser() {
  std::string userName;
  std::string emailAddress;
  std::string accountId;
  std::string plainPassword;
  std::string phoneNumber;
  std::string majorName;

  std::cout << "--- User Registration ---" << std::endl;
  std::cout << "Enter Name: ";
  std::getline(std::cin >> std::ws, userName);
  if (userName.empty()) {
    std::cerr << "Error: Name cannot be empty." << std::endl;
    return false;
  }

  std::cout << "Enter Email: ";
  std::cin >> emailAddress;
  if (!isValidEmail(emailAddress)) {
    std::cerr << "Error: Invalid email format." << std::endl;
    return false;
  }

  std::cout << "Enter Account (the length of account should longer than 5 "
               "characters and shorter than 15 characters): ";
  std::cin >> accountId;
  if (accountId.length() < 5 || accountId.length() > 15) {
    std::cerr << "Error: Account must be at least 5 characters long and less "
                 "than 15 characters long."
              << std::endl;
    return false;
  }

  std::cout << "Enter Password: ";
  std::cin >> plainPassword;
  if (!validatePassword(plainPassword)) {
    return false;
  }

  std::cout << "Enter Phone: ";
  std::cin >> phoneNumber;
  if (!isValidPhone(phoneNumber)) {
    std::cerr << "Error: Phone number must be exactly 11 digits and contain "
                 "only numbers."
              << std::endl;
    return false;
  }

  std::cout << "Enter Major: ";
  std::getline(std::cin >> std::ws, majorName);
  if (majorName.empty()) {
    std::cerr << "Error: Major cannot be empty." << std::endl;
    return false;
  }

  bool isUserFileAvailable = false;
  std::vector<UserRecord> users = loadUsers(isUserFileAvailable);

  for (const auto &userRecord : users) {
    if (userRecord.account == accountId) {
      std::cerr << "Error: Account already exists." << std::endl;
      return false;
    }
  }

  UserRecord newUser;
  newUser.account = accountId;
  newUser.password = hashPassword(plainPassword);
  newUser.name = userName;
  newUser.email = emailAddress;
  newUser.phone = phoneNumber;
  newUser.major = majorName;
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
  std::string emailAddress;
  std::cout << "Please enter your email: ";
  std::cin >> emailAddress;

  if (!isValidEmail(emailAddress)) {
    std::cerr << "Error: Invalid email format." << std::endl;
    return false;
  }
  if (emailAddress.empty()) {
    std::cerr << "Error: Email cannot be empty." << std::endl;
    return false;
  }

  bool isUserFileAvailable = false;
  std::vector<UserRecord> users = loadUsers(isUserFileAvailable);
  if (!isUserFileAvailable) {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }

  bool isAccountFound = false;

  for (const auto &userRecord : users) {
    if (userRecord.email == emailAddress) {
      isAccountFound = true;
      std::cout << "Account found: " << userRecord.account << std::endl;
      break;
    }
  }

  if (!isAccountFound) {
    std::cerr << "Error: Email not found in database." << std::endl;
    return false;
  }

  return true;
}

bool forgetPassword() {
  std::string accountId;
  std::string emailAddress;
  std::cout << "Please enter your account: ";
  std::cin >> accountId;
  std::cout << "Please enter your email: ";
  std::cin >> emailAddress;

  if (!isValidEmail(emailAddress)) {
    std::cerr << "Error: Invalid email format." << std::endl;
    return false;
  }

  bool isUserFileAvailable = false;
  std::vector<UserRecord> users = loadUsers(isUserFileAvailable);
  if (!isUserFileAvailable) {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }

  bool isUserFound = false;

  for (auto &userRecord : users) {
    if (userRecord.account == accountId && userRecord.email == emailAddress) {
      isUserFound = true;

      std::string newPassword;
      std::cout << "Account verified. Enter a new password: ";
      std::cin >> newPassword;

      if (!validatePassword(newPassword)) {
        return false;
      }

      userRecord.password = hashPassword(newPassword);
      userRecord.passwordHashed = true;

      if (!saveUsers(users)) {
        std::cerr << "Error: Could not update database file." << std::endl;
        return false;
      }

      std::cout << "Password reset successful." << std::endl;
      break;
    }
  }

  if (!isUserFound) {
    std::cerr << "Error: Account and email combination not found." << std::endl;
    return false;
  }

  return true;
}

bool getCurrentUser(User &currentUser) {
  if (gCurrentAccountId.empty()) {
    std::cerr << "Error: No user is currently logged in." << std::endl;
    return false;
  }

  bool isUserFileAvailable = false;
  const std::vector<UserRecord> users = loadUsers(isUserFileAvailable);
  if (!isUserFileAvailable) {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }

  for (const auto &userRecord : users) {
    if (userRecord.account == gCurrentAccountId) {
      currentUser =
          User(userRecord.name, userRecord.email, userRecord.account, "",
               userRecord.phone, userRecord.major);
      return true;
    }
  }

  std::cerr << "Error: Current user not found." << std::endl;
  return false;
}

bool updateCurrentUser(const User &updatedUser) {
  if (gCurrentAccountId.empty()) {
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

  bool isUserFileAvailable = false;
  std::vector<UserRecord> users = loadUsers(isUserFileAvailable);
  if (!isUserFileAvailable) {
    std::cerr << "Error: Could not open database file." << std::endl;
    return false;
  }

  for (auto &userRecord : users) {
    if (userRecord.account == gCurrentAccountId) {
      userRecord.name = updatedUser.getName();
      userRecord.email = updatedUser.getEmail();
      userRecord.phone = updatedUser.getPhone();
      userRecord.major = updatedUser.getMajor();
      return saveUsers(users);
    }
  }

  std::cerr << "Error: Current user not found." << std::endl;
  return false;
}

bool forget() {
  char selectedOption;

  std::cout << "--- Forget Services ---" << std::endl;
  std::cout << "Please select an option:" << std::endl;
  std::cout << "A. Forget Account?" << std::endl;
  std::cout << "B. Forgot Password?" << std::endl;
  std::cout << "C. Exit" << std::endl;

  std::cin >> selectedOption;

  switch (std::toupper(static_cast<unsigned char>(selectedOption))) {
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