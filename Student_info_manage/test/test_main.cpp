#include <exception>
#include <iostream>

bool RunCourseTests();
bool RunGradeTests();

int main() {
  try {
    const bool courseTestsPassed = RunCourseTests();
    const bool gradeTestsPassed = RunGradeTests();

    if (!courseTestsPassed || !gradeTestsPassed) {
      std::cerr << "One or more tests failed." << std::endl;
      return 1;
    }
  } catch (const std::exception &exception) {
    std::cerr << "Unhandled test exception: " << exception.what()
              << std::endl;
    return 1;
  }

  std::cout << "All tests passed." << std::endl;
  return 0;
}
