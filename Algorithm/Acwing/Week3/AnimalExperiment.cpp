#include <iomanip>
#include <iostream>

int main() {
    int caseCount;
    int totalAnimals = 0;
    int coneyCount = 0;
    int ratCount = 0;
    int frogCount = 0;

    std::cin >> caseCount;
    for (int index = 0; index < caseCount; ++index) {
        int animalCount;
        char animalType;
        std::cin >> animalCount >> animalType;

        totalAnimals += animalCount;
        if (animalType == 'C') {
            coneyCount += animalCount;
        } else if (animalType == 'R') {
            ratCount += animalCount;
        } else if (animalType == 'F') {
            frogCount += animalCount;
        }
    }

    std::cout << "Total: " << totalAnimals << " animals\n";
    std::cout << "Total coneys: " << coneyCount << '\n';
    std::cout << "Total rats: " << ratCount << '\n';
    std::cout << "Total frogs: " << frogCount << '\n';
    std::cout << std::fixed << std::setprecision(2);
    std::cout << "Percentage of coneys: " << coneyCount * 100.0 / totalAnimals << " %\n";
    std::cout << "Percentage of rats: " << ratCount * 100.0 / totalAnimals << " %\n";
    std::cout << "Percentage of frogs: " << frogCount * 100.0 / totalAnimals << " %";

    return 0;
}