#include "Course.h"

#include <algorithm>
#include <iostream>

Course::Course() : credits(0), maxStudents(0) {}

Course::Course(const std::string &courseNameValue,
               const std::string &courseCodeValue,
               const std::string &instructorName, int creditCount,
               int maxStudentCount)
    : courseName(courseNameValue), courseCode(courseCodeValue),
      instructor(instructorName),
      credits(creditCount < 0 ? 0 : creditCount),
      maxStudents(maxStudentCount < 0 ? 0 : maxStudentCount) {}

std::string Course::getCourseName() const { return courseName; }
std::string Course::getCourseCode() const { return courseCode; }
std::string Course::getInstructor() const { return instructor; }
int Course::getCredits() const { return credits; }
int Course::getMaxStudents() const { return maxStudents; }
int Course::getCurrentStudents() const { return enrolledStudents.size(); }

void Course::setCourseName(const std::string &courseNameValue) {
  courseName = courseNameValue;
}

void Course::setCourseCode(const std::string &courseCodeValue) {
  courseCode = courseCodeValue;
}

void Course::setInstructor(const std::string &instructorName) {
  instructor = instructorName;
}

void Course::setCredits(int creditCount) {
  if (creditCount < 0) {
    std::cerr << "Error: Credits cannot be negative." << std::endl;
    return;
  }

  credits = creditCount;
}

void Course::setEnrolledStudents(
    const std::vector<std::string> &studentAccounts) {
  enrolledStudents = studentAccounts;
  if (static_cast<int>(enrolledStudents.size()) > maxStudents) {
    enrolledStudents.resize(maxStudents);
  }
}

bool Course::enrollStudent(const std::string &studentName) {
  if (isStudentEnrolled(studentName)) {
    std::cerr << "Error: Student is already enrolled in " << courseCode << "!"
              << std::endl;
    return false;
  }

  if (enrolledStudents.size() < static_cast<size_t>(maxStudents)) {
    enrolledStudents.push_back(studentName);
    return true;
  }
  std::cerr << "Error: Course " << courseCode << " is full!" << std::endl;
  return false;
}

bool Course::dropStudent(const std::string &studentName) {
  const auto it =
      std::find(enrolledStudents.begin(), enrolledStudents.end(), studentName);
  if (it == enrolledStudents.end()) {
    std::cerr << "Error: Student is not enrolled in " << courseCode << "!"
              << std::endl;
    return false;
  }

  enrolledStudents.erase(it);
  return true;
}

bool Course::isStudentEnrolled(const std::string &studentName) const {
  return std::find(enrolledStudents.begin(), enrolledStudents.end(),
                   studentName) != enrolledStudents.end();
}

const std::vector<std::string> &Course::getEnrolledStudents() const {
  return enrolledStudents;
}

void Course::displayCourseInfo() const {
  std::cout << "[" << courseCode << "] " << courseName
            << " | Instructor: " << instructor << " | Credits: " << credits
            << " | Enrollment: " << enrolledStudents.size() << "/"
            << maxStudents << std::endl;
}
