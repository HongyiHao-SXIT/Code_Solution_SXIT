#include <iostream>
#include <map>
#include <string>

int main() {
    std::map<int, std::string> ddd_map;
    ddd_map[61] = "Brasilia";
    ddd_map[71] = "Salvador";
    ddd_map[11] = "Sao Paulo";
    ddd_map[21] = "Rio de Janeiro";
    ddd_map[32] = "Juiz de Fora";
    ddd_map[19] = "Campinas";
    ddd_map[27] = "Vitoria";
    ddd_map[31] = "Belo Horizonte";

    int code;
    std::cin >> code;

    auto it = ddd_map.find(code);
    if (it != ddd_map.end()) {
        std::cout << it->second << std::endl;
    } else {
        std::cout << "DDD nao cadastrado" << std::endl;
    }

    return 0;
}