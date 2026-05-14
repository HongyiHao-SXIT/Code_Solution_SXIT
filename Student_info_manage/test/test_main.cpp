#include <exception>
#include <iostream>

bool RunCourseTests();
bool RunGradeTests();

int main() {
  try {
    const bool coursePassed = RunCourseTests();
    const bool gradePassed = RunGradeTests();

    if (!coursePassed || !gradePassed) {
      std::cerr << "One or more tests failed." << std::endl;
      return 1;
    }
  } catch (const std::exception& ex) {
    std::cerr << "Unhandled test exception: " << ex.what() << std::endl;
    return 1;
  }

  std::cout << "All tests passed." << std::endl;
  return 0;
}
