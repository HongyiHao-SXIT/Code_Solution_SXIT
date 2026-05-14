add_library(student_info_core STATIC)

set(STUDENT_INFO_CORE_SOURCES
  AuthControl.cpp
  Course.cpp
  Grade.cpp
  Menu.cpp
  User.cpp
)

target_sources(student_info_core
  PRIVATE
    ${STUDENT_INFO_CORE_SOURCES}
)

target_include_directories(student_info_core
  PUBLIC
    ${PROJECT_SOURCE_DIR}/include
)

target_compile_features(student_info_core PUBLIC cxx_std_20)

add_executable(${PROJECT_NAME} main.cpp)
target_link_libraries(${PROJECT_NAME} PRIVATE student_info_core)
target_compile_features(${PROJECT_NAME} PRIVATE cxx_std_20)

if(MSVC)
  target_compile_options(student_info_core PRIVATE /W4)
  target_compile_options(${PROJECT_NAME} PRIVATE /W4)
else()
  target_compile_options(student_info_core PRIVATE -Wall -Wextra -Wpedantic)
  target_compile_options(${PROJECT_NAME} PRIVATE -Wall -Wextra -Wpedantic)
endif()
