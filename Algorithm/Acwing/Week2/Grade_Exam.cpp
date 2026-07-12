#include <cstdio>
#include <iostream>

int main() {
    double grade1, grade2, grade3, grade4, exam_grade, final_media;
    std::cin >> grade1 >> grade2 >> grade3 >> grade4;

    double media = grade1 * 0.2 + grade2 * 0.3 + grade3 * 0.4 + grade4 * 0.1;
    printf("Media: %1.1f\n", media);

    if (media >= 7.0) {
        std::cout << "Aluno aprovado." << std::endl;
    } else if (media >= 5.0 && media < 7.0) {
        std::cout << "Aluno em exame." << std::endl;
        std::cin >> exam_grade;
        printf("Nota do exame: %1.1f\n", exam_grade);
        final_media = (media + exam_grade) / 2.0;
        if (final_media >= 5.0)
            std::cout << "Aluno aprovado." << std::endl;
        else
            std::cout << "Aluno reprovado." << std::endl;
        printf("Media final: %1.1f", final_media);
    } else {
        std::cout << "Aluno reprovado." << std::endl;
    }

    return 0;
}