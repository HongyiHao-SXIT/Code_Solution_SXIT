#include <iostream>
#include <vector>

int main() {
    int size;
    std::cin >> size;

    std::vector<int> numbers(size);
    for (int i = 0; i < size; i++) {
        std::cin >> numbers[i];
    }

    int minValue = numbers[0];
    int position = 0;
    for (int i = 1; i < size; i++) {
        if (numbers[i] < minValue) {
            minValue = numbers[i];
            position = i;
        }
    }

    std::cout << "Minimum value: " << minValue << std::endl;
    std::cout << "Position: " << position << std::endl;

    return 0;
}