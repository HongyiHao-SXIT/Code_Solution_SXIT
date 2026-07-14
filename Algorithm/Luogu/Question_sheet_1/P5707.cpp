#include <iomanip>
#include <iostream>

int main() {
    int distance = 0;
    int speed = 0;
    std::cin >> distance >> speed;

    int requiredMinutes = (distance + speed - 1) / speed + 10;
    int departureMinutes = 8 * 60 - requiredMinutes;
    if (departureMinutes < 0) {
        departureMinutes += 24 * 60;
    }

    int hour = departureMinutes / 60;
    int minute = departureMinutes % 60;
    std::cout << std::setw(2) << std::setfill('0') << hour << ":"
              << std::setw(2) << std::setfill('0') << minute;
    return 0;
}