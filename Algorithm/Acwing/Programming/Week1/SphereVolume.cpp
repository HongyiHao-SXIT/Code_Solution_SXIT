#include <cstdio>
#include <iostream>

int main() {
    const double pi = 3.14159;
    int radius;
    std::cin >> radius;
    double volume = pi * radius * radius * radius * (4.0 / 3.0);
    printf("VOLUME = %1.3f", volume);

    return 0;
}