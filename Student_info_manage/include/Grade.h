#ifndef GRADE_H
#define GRADE_H

#include <string>

class Grade {
public:
  Grade();
  Grade(const std::string &account, const std::string &courseCode,
        const std::string &score);

  std::string getAccount() const;
  std::string getCourseCode() const;
  std::string getScore() const;

  void setAccount(const std::string &account);
  void setCourseCode(const std::string &courseCode);
  void setScore(const std::string &score);

private:
  std::string account;
  std::string courseCode;
  std::string score;
};

#endif