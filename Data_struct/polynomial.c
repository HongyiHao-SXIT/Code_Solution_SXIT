#include <stdio.h>
#include <stdlib.h>

typedef struct PolyNode {
    float coef;
    int expn;
    struct PolyNode *next;
} PolyNode, *PolyList;

void CreatePoly(PolyList *L, int n) {
    *L = (PolyList)malloc(sizeof(PolyNode));
    (*L)->next = NULL;
    for (int i = 0; i < n; i++) {
        PolyNode *p = (PolyNode*)malloc(sizeof(PolyNode));
        scanf("%f %d", &p->coef, &p->expn);
        p->next = (*L)->next;
        (*L)->next = p;
    }
}

void AddPoly(PolyList Pa, PolyList Pb) {
    PolyNode *p1 = Pa->next, *p2 = Pb->next, *pre = Pa, *temp;
    while (p1 && p2) {
        if (p1->expn < p2->expn) {
            pre = p1; p1 = p1->next;
        } else if (p1->expn > p2->expn) {
            temp = p2->next;
            p2->next = p1; pre->next = p2;
            pre = p2; p2 = temp;
        } else {
            p1->coef += p2->coef;
            if (p1->coef == 0) {
                pre->next = p1->next; free(p1);
            } else pre = p1;
            p1 = pre->next;
            temp = p2; p2 = p2->next; free(temp);
        }
    }
    if (p2) pre->next = p2;
    free(Pb);
}

void PrintPoly(PolyList L) {
    PolyNode *p = L->next;
    while (p) {
        printf("%.1fx^%d ", p->coef, p->expn);
        p = p->next;
    }
    printf("\n");
}

int main() {
    PolyList Pa, Pb;
    int n1, n2;
    printf("输入多项式a的项数及系数指数: ");
    scanf("%d", &n1); CreatePoly(&Pa, n1);
    printf("输入多项式b的项数及系数指数: ");
    scanf("%d", &n2); CreatePoly(&Pb, n2);
    AddPoly(Pa, Pb);
    printf("相加结果: "); PrintPoly(Pa);
    return 0;
}