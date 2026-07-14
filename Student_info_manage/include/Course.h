#ifndef COURSE_H
#define COURSE_H

#include <string>
#include <vector>

class Course {
public:
  Course();
  Course(const std::string &courseName, const std::string &courseCode,
         const std::string &instructorName, int creditCount,
         int maxStudentCount);

  std::string getCourseName() const;
  std::string getCourseCode() const;
  std::string getInstructor() const;
  int getCredits() const;
  int getMaxStudents() const;
  int getCurrentStudents() const;

  void setCourseName(const std::string &courseNameValue);
  void setCourseCode(const std::string &courseCodeValue);
  void setInstructor(const std::string &instructorName);
  void setCredits(int creditCount);
  void setEnrolledStudents(const std::vector<std::string> &studentAccounts);

  bool enrollStudent(const std::string &studentName);
  bool dropStudent(const std::string &studentName);
  bool isStudentEnrolled(const std::string &studentName) const;
  const std::vector<std::string> &getEnrolledStudents() const;
  void displayCourseInfo() const;

private:
  std::string courseName;
  std::string courseCode;
  std::string instructor;
  int credits;
  int maxStudents;

  std::vector<std::string> enrolledStudents;
};

#endif