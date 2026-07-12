#include <iostream>
#include <vector>

int main() {
    int n;
    std::cin >> n;

    std::vector<int> x(n);
    for (int i = 0; i < n; i++) {
        std::cin >> x[i];
    }

    int min = x[0];
    int position = 0;
    for (int i = 1; i < n; i++) {
        if (x[i] < min) {
            min = x[i];
            position = i;
        }
    }

    std::cout << "Minimum value: " << min << std::endl;
    std::cout << "Position: " << position << std::endl;

    return 0;
}