cmake_minimum_required(VERSION 3.16)

project(Student_info_manage LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 20)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_CXX_EXTENSIONS OFF)
set(CMAKE_EXPORT_COMPILE_COMMANDS ON)

include(CTest)

add_subdirectory(src)

if(BUILD_TESTING)
  add_subdirectory(test)
endif()
