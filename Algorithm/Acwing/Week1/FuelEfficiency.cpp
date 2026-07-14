#include <cstdio>
#include <iostream>

int main() {
    int distance_km;
    double fuel_liters;
    std::cin >> distance_km >> fuel_liters;
    double efficiency = distance_km / fuel_liters;
    printf("%1.3f km/l", efficiency);

    return 0;
}