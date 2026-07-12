#include <cstdio>
#include <iostream>

int main() {
    int total_seconds;
    std::cin >> total_seconds;

    int hours = total_seconds / 3600;
    total_seconds %= 3600;
    int minutes = total_seconds / 60;
    int seconds = total_seconds % 60;

    printf("%d:%d:%d", hours, minutes, seconds);

    return 0;
}