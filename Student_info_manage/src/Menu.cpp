#include "Menu.h"

#include <algorithm>
#include <cctype>
#include <fstream>
#include <iostream>
#include <limits>
#include <sstream>
#include <string>
#include <vector>

#include "AuthControl.h"
#include "Course.h"
#include "Grade.h"
#include "User.h"

namespace {

constexpr const char *kCourseDbPath = "courses.txt";
constexpr const char *kGradeDbPath = "grades.txt";
constexpr const char *kUserDbPath = "users.txt";
constexpr const char *kAdminGradeKey = "admin123";

std::vector<Grade> loadGrades();
std::vector<Course> loadCourses();
const Course *findCourseByCode(const std::vector<Course> &courses,
                               const std::string &courseCode);

char readChoice() {
  char choice = '\0';
  std::cin >> choice;
  return static_cast<char>(std::toupper(static_cast<unsigned char>(choice)));
}

std::string readLineInput(const std::string &prompt) {
  std::string input;
  std::cout << prompt;
  std::getline(std::cin >> std::ws, input);
  return input;
}

std::string readOptionalLineInput(const std::string &prompt) {
  std::string input;
  std::cout << prompt;
  std::getline(std::cin, input);
  return input;
}

bool parseInt(const std::string &text, int &value) {
  try {
    size_t parsedChars = 0;
    const long long parsedValue = std::stoll(text, &parsedChars);
    if (parsedChars != text.size()) {
      return false;
    }
    if (parsedValue < std::numeric_limits<int>::min() ||
        parsedValue > std::numeric_limits<int>::max()) {
      return false;
    }
    value = static_cast<int>(parsedValue);
    return true;
  } catch (...) {
    return false;
  }
}

std::string normalizeCode(std::string courseCode) {
  std::transform(
      courseCode.begin(), courseCode.end(), courseCode.begin(),
      [](unsigned char ch) { return static_cast<char>(std::toupper(ch)); });
  return courseCode;
}

std::vector<Course> createDefaultCourses() {
  return {
      Course("Calculus", "MATH101", "Dr. Smith", 4, 40),
      Course("Programming Fundamentals", "CS101", "Prof. Johnson", 3, 35),
      Course("Data Structures", "CS201", "Prof. Zhang", 4, 30),
      Course("College English", "ENG101", "Ms. Davis", 2, 50),
  };
}

void ensureGradeFileExists() {
  std::ifstream readFile(kGradeDbPath);
  if (readFile.is_open()) {
    return;
  }

  std::ofstream createFile(kGradeDbPath, std::ios::app);
}

bool saveGrades(const std::vector<Grade> &grades) {
  std::ofstream writeFile(kGradeDbPath, std::ios::trunc);
  if (!writeFile.is_open()) {
    std::cerr << "Error: Could not open grade database file." << std::endl;
    return false;
  }

  for (const auto &grade : grades) {
    writeFile << grade.getAccount() << '\t' << grade.getCourseCode() << '\t'
              << grade.getScore() << '\n';
  }

  return true;
}

bool accountExists(const std::string &account) {
  std::ifstream readFile(kUserDbPath);
  if (!readFile.is_open()) {
    return false;
  }

  std::string line;
  while (std::getline(readFile, line)) {
    if (line.empty()) {
      continue;
    }

    std::istringstream iss(line);
    std::string accountField;
    if (!std::getline(iss, accountField, '\t')) {
      std::istringstream legacy(line);
      legacy >> accountField;
    }

    if (accountField == account) {
      return true;
    }
  }

  return false;
}

bool isValidScore(const std::string &scoreText) {
  if (scoreText.empty()) {
    return false;
  }

  if (scoreText.find_first_not_of("0123456789") != std::string::npos) {
    return false;
  }

  int score = 0;
  if (!parseInt(scoreText, score)) {
    return false;
  }
  return score >= 0 && score <= 100;
}

void showAllGradeRecords() {
  const std::vector<Grade> grades = loadGrades();
  std::cout << "\n-- All Grade Records --" << std::endl;
  if (grades.empty()) {
    std::cout << "No grade records found." << std::endl;
    return;
  }

  for (const auto &grade : grades) {
    std::cout << "Account: " << grade.getAccount()
              << " | Course: " << grade.getCourseCode()
              << " | Score: " << grade.getScore() << std::endl;
  }
}

bool upsertStudentGrade(const std::string &account,
                        const std::string &courseCode,
                        const std::string &scoreText) {
  if (!accountExists(account)) {
    std::cout << "Account not found." << std::endl;
    return false;
  }

  if (!isValidScore(scoreText)) {
    std::cout << "Invalid score. Please enter an integer between 0 and 100."
              << std::endl;
    return false;
  }

  const std::vector<Course> courses = loadCourses();
  const std::string normalizedCode = normalizeCode(courseCode);
  const Course *course = findCourseByCode(courses, normalizedCode);
  if (course == nullptr) {
    std::cout << "Course not found." << std::endl;
    return false;
  }

  if (!course->isStudentEnrolled(account)) {
    std::cout << "The student is not enrolled in this course." << std::endl;
    return false;
  }

  std::vector<Grade> grades = loadGrades();
  for (auto &grade : grades) {
    if (grade.getAccount() == account &&
        grade.getCourseCode() == normalizedCode) {
      grade.setScore(scoreText);
      return saveGrades(grades);
    }
  }

  grades.emplace_back(account, normalizedCode, scoreText);
  return saveGrades(grades);
}

void adminGradeEntryMenu() {
  const std::string adminKey = readLineInput("Enter admin key: ");
  if (adminKey != kAdminGradeKey) {
    std::cout << "Authentication failed." << std::endl;
    return;
  }

  while (true) {
    std::cout << "\n-- Admin Grade Management --" << std::endl;
    std::cout << "1. View all grade records" << std::endl;
    std::cout << "2. Add or update a grade" << std::endl;
    std::cout << "3. Back" << std::endl;
    std::cout << "> ";

    switch (readChoice()) {
    case '1':
      showAllGradeRecords();
      break;
    case '2': {
      const std::string account = readLineInput("Student account: ");
      const std::string courseCode = readLineInput("Course code: ");
      const std::string score = readLineInput("Score (0-100): ");
      if (upsertStudentGrade(account, courseCode, score)) {
        std::cout << "Grade saved successfully." << std::endl;
      } else {
        std::cout << "Failed to save grade." << std::endl;
      }
      break;
    }
    case '3':
      return;
    default:
      std::cout << "Invalid option, please try again." << std::endl;
      break;
    }
  }
}

std::string joinStudents(const std::vector<std::string> &students) {
  std::ostringstream oss;
  for (size_t index = 0; index < students.size(); ++index) {
    if (index != 0) {
      oss << ',';
    }
    oss << students[index];
  }
  return oss.str();
}

std::vector<std::string> splitStudents(const std::string &serializedStudents) {
  std::vector<std::string> students;
  std::istringstream iss(serializedStudents);
  std::string student;
  while (std::getline(iss, student, ',')) {
    if (!student.empty()) {
      students.push_back(student);
    }
  }
  return students;
}

bool saveCourses(const std::vector<Course> &courses) {
  std::ofstream writeFile(kCourseDbPath, std::ios::trunc);
  if (!writeFile.is_open()) {
    std::cerr << "Error: Could not open course database file." << std::endl;
    return false;
  }

  for (const auto &course : courses) {
    writeFile << course.getCourseCode() << '\t' << course.getCourseName()
              << '\t' << course.getInstructor() << '\t' << course.getCredits()
              << '\t' << course.getMaxStudents() << '\t'
              << joinStudents(course.getEnrolledStudents()) << '\n';
  }
  return true;
}

std::vector<Grade> loadGrades() {
  ensureGradeFileExists();

  std::ifstream readFile(kGradeDbPath);
  std::vector<Grade> grades;
  if (!readFile.is_open()) {
    std::cerr << "Error: Could not open grade database file." << std::endl;
    return grades;
  }

  std::string line;
  while (std::getline(readFile, line)) {
    if (line.empty()) {
      continue;
    }

    std::istringstream iss(line);
    std::vector<std::string> fields;
    std::string field;
    while (std::getline(iss, field, '\t')) {
      fields.push_back(field);
    }

    if (fields.size() != 3) {
      continue;
    }

    grades.emplace_back(fields[0], normalizeCode(fields[1]), fields[2]);
  }

  return grades;
}

std::vector<Course> loadCourses() {
  std::ifstream readFile(kCourseDbPath);
  if (!readFile.is_open()) {
    std::vector<Course> defaultCourses = createDefaultCourses();
    saveCourses(defaultCourses);
    return defaultCourses;
  }

  std::vector<Course> courses;
  std::string line;
  while (std::getline(readFile, line)) {
    if (line.empty()) {
      continue;
    }

    std::istringstream iss(line);
    std::vector<std::string> fields;
    std::string field;
    while (std::getline(iss, field, '\t')) {
      fields.push_back(field);
    }

    if (fields.size() < 5) {
      continue;
    }

    int credits = 0;
    int maxStudents = 0;
    if (!parseInt(fields[3], credits) || !parseInt(fields[4], maxStudents)) {
      continue;
    }

    Course course(fields[1], normalizeCode(fields[0]), fields[2], credits,
                  maxStudents);
    if (fields.size() >= 6) {
      course.setEnrolledStudents(splitStudents(fields[5]));
    }
    courses.push_back(course);
  }

  if (courses.empty()) {
    courses = createDefaultCourses();
    saveCourses(courses);
  }

  return courses;
}

Course *findCourseByCode(std::vector<Course> &courses,
                         const std::string &courseCode) {
  const std::string normalizedCode = normalizeCode(courseCode);
  for (auto &course : courses) {
    if (course.getCourseCode() == normalizedCode) {
      return &course;
    }
  }
  return nullptr;
}

const Course *findCourseByCode(const std::vector<Course> &courses,
                               const std::string &courseCode) {
  const std::string normalizedCode = normalizeCode(courseCode);
  for (const auto &course : courses) {
    if (course.getCourseCode() == normalizedCode) {
      return &course;
    }
  }
  return nullptr;
}

const Grade *findGradeByCourseCode(const std::vector<Grade> &grades,
                                   const std::string &account,
                                   const std::string &courseCode) {
  const std::string normalizedCode = normalizeCode(courseCode);
  for (const auto &grade : grades) {
    if (grade.getAccount() == account &&
        grade.getCourseCode() == normalizedCode) {
      return &grade;
    }
  }
  return nullptr;
}

void printCourseList(const std::vector<Course> &courses,
                     const std::string &currentAccount,
                     bool onlyEnrolledCourses) {
  bool hasOutput = false;
  for (const auto &course : courses) {
    const bool isEnrolled = course.isStudentEnrolled(currentAccount);
    if (onlyEnrolledCourses && !isEnrolled) {
      continue;
    }

    course.displayCourseInfo();
    if (isEnrolled) {
      std::cout << "  Status: enrolled" << std::endl;
    }
    hasOutput = true;
  }

  if (!hasOutput) {
    if (onlyEnrolledCourses) {
      std::cout << "You have not enrolled in any courses yet." << std::endl;
    } else {
      std::cout << "No courses available." << std::endl;
    }
  }
}

void showMyCourses() {
  const std::string currentAccount = getCurrentAccount();
  if (currentAccount.empty()) {
    std::cout << "Please login first." << std::endl;
    return;
  }

  const std::vector<Course> courses = loadCourses();
  std::cout << "\n-- My Courses --" << std::endl;
  printCourseList(courses, currentAccount, true);
}

void showAvailableCourses() {
  const std::string currentAccount = getCurrentAccount();
  const std::vector<Course> courses = loadCourses();
  std::cout << "\n-- Available Courses --" << std::endl;
  printCourseList(courses, currentAccount, false);
}

void enrollCurrentStudent() {
  const std::string currentAccount = getCurrentAccount();
  if (currentAccount.empty()) {
    std::cout << "Please login first." << std::endl;
    return;
  }

  std::vector<Course> courses = loadCourses();
  showAvailableCourses();

  std::string courseCode;
  std::cout << "Enter course code to enroll: ";
  std::cin >> courseCode;

  Course *course = findCourseByCode(courses, courseCode);
  if (course == nullptr) {
    std::cout << "Course not found." << std::endl;
    return;
  }

  if (course->enrollStudent(currentAccount) && saveCourses(courses)) {
    std::cout << "Enrollment successful." << std::endl;
  }
}

void dropCurrentStudent() {
  const std::string currentAccount = getCurrentAccount();
  if (currentAccount.empty()) {
    std::cout << "Please login first." << std::endl;
    return;
  }

  std::vector<Course> courses = loadCourses();
  std::cout << "\n-- Drop Course --" << std::endl;
  printCourseList(courses, currentAccount, true);

  std::string courseCode;
  std::cout << "Enter course code to drop: ";
  std::cin >> courseCode;

  Course *course = findCourseByCode(courses, courseCode);
  if (course == nullptr) {
    std::cout << "Course not found." << std::endl;
    return;
  }

  if (course->dropStudent(currentAccount) && saveCourses(courses)) {
    std::cout << "Course dropped successfully." << std::endl;
  }
}

void showAllGrades() {
  const std::string currentAccount = getCurrentAccount();
  if (currentAccount.empty()) {
    std::cout << "Please login first." << std::endl;
    return;
  }

  const std::vector<Course> courses = loadCourses();
  const std::vector<Grade> grades = loadGrades();

  bool hasEnrolledCourses = false;
  std::cout << "\n-- Grade Overview --" << std::endl;
  for (const auto &course : courses) {
    if (!course.isStudentEnrolled(currentAccount)) {
      continue;
    }

    hasEnrolledCourses = true;
    const Grade *grade =
        findGradeByCourseCode(grades, currentAccount, course.getCourseCode());
    std::cout << '[' << course.getCourseCode() << "] " << course.getCourseName()
              << " | Grade: "
              << (grade == nullptr ? "Not graded yet" : grade->getScore())
              << std::endl;
  }

  if (!hasEnrolledCourses) {
    std::cout << "You have not enrolled in any courses yet." << std::endl;
  }
}

void showSpecificCourseGrade() {
  const std::string currentAccount = getCurrentAccount();
  if (currentAccount.empty()) {
    std::cout << "Please login first." << std::endl;
    return;
  }

  const std::vector<Course> courses = loadCourses();
  const std::vector<Grade> grades = loadGrades();

  std::string courseCode;
  std::cout << "Enter course code: ";
  std::cin >> courseCode;
  courseCode = normalizeCode(courseCode);

  const Course *course = findCourseByCode(courses, courseCode);
  if (course == nullptr) {
    std::cout << "Course not found." << std::endl;
    return;
  }

  if (!course->isStudentEnrolled(currentAccount)) {
    std::cout << "You are not enrolled in this course." << std::endl;
    return;
  }

  const Grade *grade =
      findGradeByCourseCode(grades, currentAccount, courseCode);
  std::cout << '[' << course->getCourseCode() << "] " << course->getCourseName()
            << " | Grade: "
            << (grade == nullptr ? "Not graded yet" : grade->getScore())
            << std::endl;
}

void showCurrentUserInfo() {
  User currentUser;
  if (!getCurrentUser(currentUser)) {
    return;
  }

  std::cout << "\n-- Personal Information --" << std::endl;
  currentUser.displayInfo();
}

void updateCurrentUserInfo() {
  User currentUser;
  if (!getCurrentUser(currentUser)) {
    return;
  }

  std::cout << "Leave a field empty to keep the current value." << std::endl;

  // Clear the menu choice newline so empty input can be captured correctly.
  if (std::cin.good()) {
    std::cin.ignore(std::numeric_limits<std::streamsize>::max(), '\n');
  }

  const std::string name =
      readOptionalLineInput("Name [" + currentUser.getName() + "]: ");
  const std::string email =
      readOptionalLineInput("Email [" + currentUser.getEmail() + "]: ");
  const std::string phone =
      readOptionalLineInput("Phone [" + currentUser.getPhone() + "]: ");
  const std::string major =
      readOptionalLineInput("Major [" + currentUser.getMajor() + "]: ");

  if (!name.empty()) {
    currentUser.setName(name);
  }
  if (!email.empty()) {
    currentUser.setEmail(email);
  }
  if (!phone.empty()) {
    currentUser.setPhone(phone);
  }
  if (!major.empty()) {
    currentUser.setMajor(major);
  }

  if (updateCurrentUser(currentUser)) {
    std::cout << "Personal information updated successfully." << std::endl;
  } else {
    std::cout << "Failed to update personal information." << std::endl;
  }
}

} // namespace

bool Menu() {
  while (true) {
    std::cout << "\n-- Welcome to the Student Information Management System --"
              << std::endl;
    std::cout << "A. My information" << std::endl;
    std::cout << "B. My courses" << std::endl;
    std::cout << "C. My grades" << std::endl;
    std::cout << "D. Course selection" << std::endl;
    std::cout << "E. Logout" << std::endl;
    std::cout << "F. Back" << std::endl;
    std::cout << "> ";

    switch (readChoice()) {
    case 'A':
      InfoMenu();
      break;
    case 'B':
      showMyCourses();
      break;
    case 'C':
      GradeMenu();
      break;
    case 'D':
      CourseMenu();
      break;
    case 'E':
      logoutUser();
      std::cout << "Logged out." << std::endl;
      return false;
    case 'F':
      return true;
    default:
      std::cout << "Invalid option, please try again." << std::endl;
      break;
    }
  }
}

void CourseMenu() {
  while (true) {
    std::cout << "\n-- Course Selection --" << std::endl;
    std::cout << "1. View available courses" << std::endl;
    std::cout << "2. Enroll in a course" << std::endl;
    std::cout << "3. Drop a course" << std::endl;
    std::cout << "4. Back to main menu" << std::endl;
    std::cout << "> ";

    switch (readChoice()) {
    case '1':
      showAvailableCourses();
      break;
    case '2':
      enrollCurrentStudent();
      break;
    case '3':
      dropCurrentStudent();
      break;
    case '4':
      return;
    default:
      std::cout << "Invalid option, please try again." << std::endl;
      break;
    }
  }
}

void GradeMenu() {
  while (true) {
    std::cout << "\n-- My Grades --" << std::endl;
    std::cout << "1. View grades for all courses" << std::endl;
    std::cout << "2. View grade for a specific course" << std::endl;
    std::cout << "3. Teacher/Admin grade entry" << std::endl;
    std::cout << "4. Back to main menu" << std::endl;
    std::cout << "> ";

    switch (readChoice()) {
    case '1':
      showAllGrades();
      break;
    case '2':
      showSpecificCourseGrade();
      break;
    case '3':
      adminGradeEntryMenu();
      break;
    case '4':
      return;
    default:
      std::cout << "Invalid option, please try again." << std::endl;
      break;
    }
  }
}

void InfoMenu() {
  while (true) {
    std::cout << "\n-- My Information --" << std::endl;
    std::cout << "1. View personal information" << std::endl;
    std::cout << "2. Update personal information" << std::endl;
    std::cout << "3. Back to main menu" << std::endl;
    std::cout << "> ";

    switch (readChoice()) {
    case '1':
      showCurrentUserInfo();
      break;
    case '2':
      updateCurrentUserInfo();
      break;
    case '3':
      return;
    default:
      std::cout << "Invalid option, please try again." << std::endl;
      break;
    }
  }
}