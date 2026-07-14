#include <cstdio>
#include <iostream>

int main() {
    int time_hours;
    double speed_kmh;
    std::cin >> time_hours >> speed_kmh;
    double fuel_liters = time_hours * speed_kmh / 12.0;
    printf("%1.3f", fuel_liters);

    return 0;
}