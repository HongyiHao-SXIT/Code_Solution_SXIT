#include <cmath>
#include <iostream>

int main() {
    int a, b, c;
    std::cin >> a >> b >> c;
    int max_ab = (a + b + std::abs(a - b)) / 2;
    int max = (max_ab + c + std::abs(max_ab - c)) / 2;
    std::cout << max << " eh o maior" << std::endl;

    return 0;
}