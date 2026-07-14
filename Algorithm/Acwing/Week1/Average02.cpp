#include <cstdio>
#include <iostream>

int main() {
    double firstScore, secondScore, thirdScore;
    std::cin >> firstScore >> secondScore >> thirdScore;

    double average = (firstScore * 2 + secondScore * 3 + thirdScore * 5) / 10.0;
    printf("MEDIA = %1.1f", average);

    return 0;
}