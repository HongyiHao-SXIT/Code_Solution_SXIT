#include <iostream>
#include <string>

int main() {
    std::string phylum, animal_class, diet;
    std::cin >> phylum >> animal_class >> diet;

    if (phylum == "vertebrado" && animal_class == "ave" && diet == "carnivoro")
        std::cout << "aguia";
    else if (phylum == "vertebrado" && animal_class == "ave" && diet == "onivoro")
        std::cout << "pomba";
    else if (phylum == "vertebrado" && animal_class == "mamifero" && diet == "onivoro")
        std::cout << "homem";
    else if (phylum == "vertebrado" && animal_class == "mamifero" && diet == "herbivoro")
        std::cout << "vaca";
    else if (phylum == "invertebrado" && animal_class == "inseto" && diet == "hematofago")
        std::cout << "pulga";
    else if (phylum == "invertebrado" && animal_class == "inseto" && diet == "herbivoro")
        std::cout << "lagarta";
    else if (phylum == "invertebrado" && animal_class == "anelideo" && diet == "hematofago")
        std::cout << "sanguessuga";
    else
        std::cout << "minhoca";

    return 0;
}