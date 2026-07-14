#include <iostream>

int main() {
    double firstScore, secondScore;
    std::cin >> firstScore >> secondScore;

    double average = (firstScore * 3.5 + secondScore * 7.5) / 11.0;
    std::cout << "MEDIA = " << average << std::endl;

    return 0;
}