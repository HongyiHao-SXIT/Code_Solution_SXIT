#ifndef GRADE_H
#define GRADE_H

#include <string>

class Grade {
public:
  Grade();
  Grade(const std::string &accountId, const std::string &courseCodeValue,
        const std::string &scoreValue);

  std::string getAccount() const;
  std::string getCourseCode() const;
  std::string getScore() const;

  void setAccount(const std::string &accountId);
  void setCourseCode(const std::string &courseCodeValue);
  void setScore(const std::string &scoreValue);

private:
  std::string account;
  std::string courseCode;
  std::string score;
};

#endif