#ifndef COURSE_H
#define COURSE_H

#include <string>
#include <vector>

class Course {
 public:
  Course();
  Course(const std::string& name, const std::string& code,
         const std::string& instr, int cred, int maxStud);

  std::string getCourseName() const;
  std::string getCourseCode() const;
  std::string getInstructor() const;
  int getCredits() const;
  int getMaxStudents() const;
  int getCurrentStudents() const;

  void setCourseName(const std::string& name);
  void setCourseCode(const std::string& code);
  void setInstructor(const std::string& instr);
  void setCredits(int cred);
  void setEnrolledStudents(const std::vector<std::string>& students);

  bool enrollStudent(const std::string& studentName);
  bool dropStudent(const std::string& studentName);
  bool isStudentEnrolled(const std::string& studentName) const;
  const std::vector<std::string>& getEnrolledStudents() const;
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