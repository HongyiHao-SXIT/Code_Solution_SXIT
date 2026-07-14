#include "Course.h"

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

bool RunCourseTests() {
  bool allChecksPassed = true;

  Course course("Data Structures", "CS201", "Prof. Zhang", 4, 2);
  allChecksPassed &=
      Expect(course.getCourseName() == "Data Structures",
             "Course name should match constructor input.");
  allChecksPassed &= Expect(course.getCourseCode() == "CS201",
                            "Course code should match constructor input.");
  allChecksPassed &= Expect(course.getCredits() == 4,
                            "Course credits should match constructor input.");

  allChecksPassed &= Expect(course.enrollStudent("student01"),
                            "First enrollment should succeed.");
  allChecksPassed &= Expect(!course.enrollStudent("student01"),
                            "Duplicate enrollment should fail.");
  allChecksPassed &= Expect(course.enrollStudent("student02"),
                            "Second enrollment should succeed.");
  allChecksPassed &= Expect(!course.enrollStudent("student03"),
                            "Enrollment should fail when the course is full.");
  allChecksPassed &= Expect(course.getCurrentStudents() == 2,
                            "Course should contain exactly two students.");

  allChecksPassed &= Expect(course.dropStudent("student01"),
                            "Dropping an enrolled student should succeed.");
  allChecksPassed &= Expect(!course.dropStudent("student01"),
                            "Dropping the same student twice should fail.");
  allChecksPassed &= Expect(!course.isStudentEnrolled("student01"),
                            "Dropped student should no longer be enrolled.");
  allChecksPassed &= Expect(course.isStudentEnrolled("student02"),
                            "Remaining student should stay enrolled.");

  Course normalized("Networks", "CS301", "Prof. Liu", -1, -5);
  allChecksPassed &= Expect(normalized.getCredits() == 0,
                            "Negative credits should be normalized to zero.");
  allChecksPassed &=
      Expect(normalized.getMaxStudents() == 0,
             "Negative max students should be normalized to zero.");

  return allChecksPassed;
}
