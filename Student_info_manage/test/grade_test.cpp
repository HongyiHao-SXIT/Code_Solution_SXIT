#include "Grade.h"

#include <iostream>
#include <string>

namespace {

bool Expect(bool condition, const std::string &message) {
  if (!condition) {
    std::cerr << message << std::endl;
    return false;
  }
  return true;
}

} // namespace

bool RunGradeTests() {
  bool allChecksPassed = true;

  Grade grade("student01", "CS101", "95");
  allChecksPassed &= Expect(grade.getAccount() == "student01",
                            "Grade account should match constructor input.");
  allChecksPassed &=
      Expect(grade.getCourseCode() == "CS101",
             "Grade course code should match constructor input.");
  allChecksPassed &= Expect(grade.getScore() == "95",
                            "Grade score should match constructor input.");

  grade.setAccount("student02");
  grade.setCourseCode("CS201");
  grade.setScore("88");

  allChecksPassed &=
      Expect(grade.getAccount() == "student02",
             "Grade account setter should update the value.");
  allChecksPassed &=
      Expect(grade.getCourseCode() == "CS201",
             "Grade course code setter should update the value.");
  allChecksPassed &=
      Expect(grade.getScore() == "88",
             "Grade score setter should update the value.");

  return allChecksPassed;
}
