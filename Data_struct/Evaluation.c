#include <stdio.h>
#include <stdlib.h>

char Precede(char theta1, char theta2) {
    if ((theta1 == '+' || theta1 == '-') && (theta2 == '*' || theta2 == '/' || theta2 == '(')) return '<';
    if (theta1 == '(' || theta1 == '#') return (theta1 == '#' && theta2 == '#') ? '=' : '<';
    if (theta1 == ')' && theta2 != '(') return '>';
    if (theta2 == ')') return (theta1 == '(') ? '=' : '>';
    return '>';
}

int Operate(int a, char theta, int b) {
    switch(theta) {
        case '+': return a + b;
        case '-': return a - b;
        case '*': return a * b;
        case '/': return a / b;
    }
    return 0;
}

int main() {
    char optr[100] = {'#'}; int opnd[100];
    int top1 = 0, top2 = -1;
    char c = getchar();
    while (c != '#' || optr[top1] != '#') {
        if (c >= '0' && c <= '9') {
            opnd[++top2] = c - '0'; c = getchar();
        } else {
            switch(Precede(optr[top1], c)) {
                case '<': optr[++top1] = c; c = getchar(); break;
                case '=': top1--; c = getchar(); break;
                case '>': {
                    int b = opnd[top2--], a = opnd[top2--];
                    opnd[++top2] = Operate(a, optr[top1--], b);
                    break;
                }
            }
        }
    }
    printf("结果: %d\n", opnd[top2]);
    return 0;
}