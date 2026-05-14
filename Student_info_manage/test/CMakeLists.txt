add_executable(student_info_tests
  test_main.cpp
  course_test.cpp
  grade_test.cpp
)

target_link_libraries(student_info_tests PRIVATE student_info_core)
target_compile_features(student_info_tests PRIVATE cxx_std_20)

if(MSVC)
  target_compile_options(student_info_tests PRIVATE /W4)
else()
  target_compile_options(student_info_tests PRIVATE -Wall -Wextra -Wpedantic)
endif()

add_test(NAME student_info_tests COMMAND student_info_tests)
