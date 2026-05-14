#include "Grade.h"

Grade::Grade() = default;

Grade::Grade(const std::string& account, const std::string& courseCode,
             const std::string& score)
    : account(account), courseCode(courseCode), score(score) {}

std::string Grade::getAccount() const { return account; }

std::string Grade::getCourseCode() const { return courseCode; }

std::string Grade::getScore() const { return score; }

void Grade::setAccount(const std::string& accountValue) {
  account = accountValue;
}

void Grade::setCourseCode(const std::string& courseCodeValue) {
  courseCode = courseCodeValue;
}

void Grade::setScore(const std::string& scoreValue) { score = scoreValue; }