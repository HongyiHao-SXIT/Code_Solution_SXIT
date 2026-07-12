#include <cstdio>
#include <iostream>

int main() {
    double a, b, c;
    std::cin >> a >> b >> c;
    double media = (a * 2 + b * 3 + c * 5) / 10.0;
    printf("MEDIA = %1.1f", media);

    return 0;
}