#include <cstdio>
#include <iostream>

int main() {
    const double pi = 3.14159;
    double r;
    std::cin >> r;
    double area = pi * r * r;
    printf("A=%1.4f", area);

    return 0;
}