#include <iostream>

int main() {
    int termCount;
    std::cin >> termCount;

    long long previous = 0;
    long long current = 1;

    for (int index = 0; index < termCount; ++index) {
        if (index == 0) {
            std::cout << 0;
        } else if (index == 1) {
            std::cout << " 1";
        } else {
            long long nextTerm = previous + current;
            std::cout << ' ' << nextTerm;
            previous = current;
            current = nextTerm;
        }
    }

    return 0;
}