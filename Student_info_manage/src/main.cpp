#include <cctype>
#include <iostream>

#include "AuthControl.h"
#include "Menu.h"

int main() {
  char choice = 'E';
  bool isLoggedIn = false;

  std::cout << "=== Student Information Management System ===" << std::endl;

  while (true) {
    std::cout << "\nPlease choose an option:" << std::endl;
    std::cout << "A. Login" << std::endl;
    std::cout << "B. Register" << std::endl;
    std::cout << "C. Forget Service" << std::endl;
    std::cout << "D. Show Main Menu" << std::endl;
    std::cout << "E. Exit" << std::endl;
    std::cout << "> ";

    std::cin >> choice;

    switch (std::toupper(static_cast<unsigned char>(choice))) {
    case 'A':
      if (isLoggedIn) {
        std::cout << "You are already logged in." << std::endl;
      } else {
        isLoggedIn = login();
      }
      break;
    case 'B':
      registerUser();
      break;
    case 'C':
      forget();
      break;
    case 'D':
      if (!isLoggedIn) {
        std::cout << "Please login first." << std::endl;
      } else {
        isLoggedIn = Menu();
      }
      break;
    case 'E':
      std::cout << "Bye." << std::endl;
      return 0;
    default:
      std::cout << "Invalid option, please try again." << std::endl;
      break;
    }
  }
}
