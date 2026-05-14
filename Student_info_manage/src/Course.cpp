#include "Course.h"

#include <algorithm>
#include <iostream>

Course::Course() : credits(0), maxStudents(0) {}

Course::Course(const std::string& name, const std::string& code,
               const std::string& instr, int cred, int maxStud)
    : courseName(name),
      courseCode(code),
      instructor(instr),
      credits(cred < 0 ? 0 : cred),
      maxStudents(maxStud < 0 ? 0 : maxStud) {}

std::string Course::getCourseName() const { return courseName; }
std::string Course::getCourseCode() const { return courseCode; }
std::string Course::getInstructor() const { return instructor; }
int Course::getCredits() const { return credits; }
int Course::getMaxStudents() const { return maxStudents; }
int Course::getCurrentStudents() const { return enrolledStudents.size(); }

void Course::setCourseName(const std::string& name) { courseName = name; }
void Course::setCourseCode(const std::string& code) { courseCode = code; }
void Course::setInstructor(const std::string& instr) { instructor = instr; }
void Course::setCredits(int cred) {
  if (cred < 0) {
    std::cerr << "Error: Credits cannot be negative." << std::endl;
    return;
  }
  credits = cred;
}

void Course::setEnrolledStudents(const std::vector<std::string>& students) {
  enrolledStudents = students;
  if (static_cast<int>(enrolledStudents.size()) > maxStudents) {
    enrolledStudents.resize(maxStudents);
  }
}

bool Course::enrollStudent(const std::string& studentName) {
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

bool Course::dropStudent(const std::string& studentName) {
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

bool Course::isStudentEnrolled(const std::string& studentName) const {
  return std::find(enrolledStudents.begin(), enrolledStudents.end(),
                   studentName) != enrolledStudents.end();
}

const std::vector<std::string>& Course::getEnrolledStudents() const {
  return enrolledStudents;
}

void Course::displayCourseInfo() const {
  std::cout << "[" << courseCode << "] " << courseName
            << " | Instructor: " << instructor << " | Credits: " << credits
            << " | Enrollment: " << enrolledStudents.size() << "/"
            << maxStudents << std::endl;
}
