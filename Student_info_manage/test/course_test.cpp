#include "Course.h"

#include <iostream>
#include <string>

namespace {

bool Expect(bool condition, const std::string& message) {
  if (!condition) {
    std::cerr << message << std::endl;
    return false;
  }
  return true;
}

}  // namespace

bool RunCourseTests() {
  bool success = true;

  Course course("Data Structures", "CS201", "Prof. Zhang", 4, 2);
  success &= Expect(course.getCourseName() == "Data Structures",
                    "Course name should match constructor input.");
  success &= Expect(course.getCourseCode() == "CS201",
                    "Course code should match constructor input.");
  success &= Expect(course.getCredits() == 4,
                    "Course credits should match constructor input.");

  success &= Expect(course.enrollStudent("student01"),
                    "First enrollment should succeed.");
  success &= Expect(!course.enrollStudent("student01"),
                    "Duplicate enrollment should fail.");
  success &= Expect(course.enrollStudent("student02"),
                    "Second enrollment should succeed.");
  success &= Expect(!course.enrollStudent("student03"),
                    "Enrollment should fail when the course is full.");
  success &= Expect(course.getCurrentStudents() == 2,
                    "Course should contain exactly two students.");

  success &= Expect(course.dropStudent("student01"),
                    "Dropping an enrolled student should succeed.");
  success &= Expect(!course.dropStudent("student01"),
                    "Dropping the same student twice should fail.");
  success &= Expect(!course.isStudentEnrolled("student01"),
                    "Dropped student should no longer be enrolled.");
  success &= Expect(course.isStudentEnrolled("student02"),
                    "Remaining student should stay enrolled.");

  Course normalized("Networks", "CS301", "Prof. Liu", -1, -5);
  success &= Expect(normalized.getCredits() == 0,
                    "Negative credits should be normalized to zero.");
  success &= Expect(normalized.getMaxStudents() == 0,
                    "Negative max students should be normalized to zero.");

  return success;
}
