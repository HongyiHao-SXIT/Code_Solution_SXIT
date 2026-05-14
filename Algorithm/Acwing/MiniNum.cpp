#include <iostream>

int main() {
    int N, position;
    std::cin >> N;
    int X[N];
    for (int i = 0; i < N; i++) {
        std::cin >> X[i];
    }
    int min = X[0];
    position = 0;
    for (int i = 1; i < N; i++) {
        if (X[i] < min) {
            min = X[i];
            position = i;
        }
    }
    std::cout <<"Minimum value: " << min << std::endl;
    std::cout << "Position: " << position << std::endl;
}