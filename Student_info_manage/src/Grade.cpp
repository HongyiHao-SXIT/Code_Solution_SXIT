#include "Grade.h"

Grade::Grade() = default;

Grade::Grade(const std::string &accountId,
       const std::string &courseCodeValue,
       const std::string &scoreValue)
  : account(accountId), courseCode(courseCodeValue), score(scoreValue) {}

std::string Grade::getAccount() const { return account; }

std::string Grade::getCourseCode() const { return courseCode; }

std::string Grade::getScore() const { return score; }

void Grade::setAccount(const std::string &accountId) { account = accountId; }

void Grade::setCourseCode(const std::string &courseCodeValue) {
  courseCode = courseCodeValue;
}

void Grade::setScore(const std::string &scoreValue) { score = scoreValue; }