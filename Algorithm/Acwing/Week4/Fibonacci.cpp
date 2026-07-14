#include <iostream>

long long GetFibonacci(int n) {
    if (n <= 1) {
        return n;
    }

    long long first = 0;
    long long second = 1;

    for (int i = 2; i <= n; i++) {
        long long current = first + second;
        first = second;
        second = current;
    }

    return second;
}

int main() {
    int testCaseCount;

    std::cin >> testCaseCount;

    while (testCaseCount--) {
        int n;
        std::cin >> n;
        std::cout << "Fib(" << n << ") = " << GetFibonacci(n) << std::endl;
    }

    return 0;
}