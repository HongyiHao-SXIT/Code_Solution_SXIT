#include <iostream>

int main() {
    int a, b, c;
    std::cin >> a >> b >> c;

    int original_a = a, original_b = b, original_c = c;

    // Bubble sort the three values
    if (a > b) std::swap(a, b);
    if (b > c) std::swap(b, c);
    if (a > b) std::swap(a, b);

    std::cout << a << std::endl
              << b << std::endl
              << c << std::endl
              << std::endl
              << original_a << std::endl
              << original_b << std::endl
              << original_c << std::endl;

    return 0;
}