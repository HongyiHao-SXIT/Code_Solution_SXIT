# Student Information Management System

This is a C++ console project for student account management, personal information, course selection, and grade viewing.

## Implemented Features

- User registration, login, and password recovery
- Login state management and logout
- Personal information viewing and updating
- Course list initialization, enrollment, and dropping
- Grade viewing for enrolled courses
- Teacher/admin grade entry (add/update score records)

## Data Files

The program creates these files automatically in the project root when needed:

- `users.txt`: registered user data
- `courses.txt`: course catalog and enrollment data
- `grades.txt`: grade records

## Build

### VS Code with CMake Tools

1. Open the project folder in VS Code.
2. Run `CMake: Select a Kit` and choose your compiler.
3. Run `CMake: Configure`.
4. Run `CMake: Build`.
5. If you want to run tests, run `CMake: Run Tests`.

The workspace is configured for normal VS Code CMake Tools workflow and does not depend on CMake Presets.

### Command Line

Configure:

```powershell
cmake -S . -B build
```

Build:

```powershell
cmake --build build
```

Run tests:

```powershell
ctest --test-dir build --output-on-failure
```

## Windows Toolchain Troubleshooting

### Error: `Running 'nmake' '-?' failed with: no such file or directory`

Reason:

- CMake picked the `NMake Makefiles` generator.
- Your current terminal does not provide MSVC tools (`nmake`/`cl`).

Fix (recommended for this project):

```powershell
cmake -S . -B build -G "MinGW Makefiles" -DCMAKE_CXX_COMPILER=g++
cmake --build build
```

### Error: `generator ... does not match the generator used previously`

Reason:

- `build/` cache was created with another generator (for example `NMake Makefiles`).

Fix:

```powershell
Remove-Item build -Recurse -Force
cmake -S . -B build -G "MinGW Makefiles" -DCMAKE_CXX_COMPILER=g++
cmake --build build
```

### Quick environment checks

```powershell
cmake --version
g++ --version
mingw32-make --version
```

If these commands work, this project should configure and build with MinGW on Windows.

## Grade Records Format

Each line in `grades.txt` uses this format:

```text
account<TAB>course_code<TAB>score
```

Example:

```text
student01	CS101	89
student01	MATH101	92
```

If a student is enrolled in a course but no grade record exists yet, the program shows `Not graded yet`.

## Admin Grade Entry

In the `My Grades` menu:

- `1`: View all my course grades
- `2`: View grade for one course
- `3`: Teacher/Admin grade entry

Admin grade entry supports:

- Viewing all grade records
- Adding or updating one student's score for one course
- Validation that account exists and the student is enrolled in the course

Current demo admin key:

```text
admin123
```