#include <iostream>

int main() {
    int distance_km;
    std::cin >> distance_km;
    double time_minutes = (distance_km / 30.0) * 60;
    std::cout << time_minutes << " minutos" << std::endl;

    return 0;
}