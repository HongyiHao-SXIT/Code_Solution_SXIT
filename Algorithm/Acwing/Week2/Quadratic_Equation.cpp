#include <cmath>
#include <cstdio>
#include <iostream>

int main() {
    double a, b, c;
    std::cin >> a >> b >> c;

    if (a == 0 || b * b - 4 * a * c < 0) {
        std::cout << "Impossivel calcular";
    } else {
        double discriminant = std::sqrt(b * b - 4 * a * c);
        double root1 = (-b + discriminant) / (2 * a);
        double root2 = (-b - discriminant) / (2 * a);
        printf("R1 = %1.5f\n", root1);
        printf("R2 = %1.5f\n", root2);
    }

    return 0;
}