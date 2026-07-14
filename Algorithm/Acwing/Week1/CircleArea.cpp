#include <cstdio>
#include <iostream>

int main() {
    const double kPi = 3.14159;
    double radius;
    std::cin >> radius;

    double area = kPi * radius * radius;
    printf("A=%1.4f", area);

    return 0;
}