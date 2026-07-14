#include <iostream>
#include <vector>

int main() {
    int number;
    std::cin >> number;

    std::vector<int> smallDivisors;
    std::vector<int> largeDivisors;

    for (int divisor = 1; divisor * divisor <= number; ++divisor) {
        if (number % divisor == 0) {
            smallDivisors.push_back(divisor);
            if (divisor != number / divisor) {
                largeDivisors.push_back(number / divisor);
            }
        }
    }

    for (int index = 0; index < static_cast<int>(smallDivisors.size()); ++index) {
        std::cout << smallDivisors[index] << '\n';
    }

    for (int index = static_cast<int>(largeDivisors.size()) - 1; index >= 0; --index) {
        std::cout << largeDivisors[index] << '\n';
    }

    return 0;
}
