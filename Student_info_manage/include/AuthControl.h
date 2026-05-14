#ifndef AUTHCONTROL_H
#define AUTHCONTROL_H

#include <string>

#include "User.h"

bool login();
bool registerUser();
bool forget();
bool forgetAccount();
bool forgetPassword();
std::string getCurrentAccount();
void logoutUser();
bool getCurrentUser(User& currentUser);
bool updateCurrentUser(const User& updatedUser);

#endif
