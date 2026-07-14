#include <iostream>

int main() {
    int applesPerPerson = 0;
    int studentCount = 0;
    std::cin >> applesPerPerson >> studentCount;

    std::cout << applesPerPerson * studentCount << '\n';
    return 0;
}