#include <stdio.h>
#include <stdbool.h>

#define P_NUM 5    // 进程数量
#define R_NUM 3    // 资源种类数量

// 全局变量定义
int Available[R_NUM] = {3, 3, 2};             // 可利用资源向量
int Max[P_NUM][R_NUM] = {{7, 5, 3}, {3, 2, 2}, {9, 0, 2}, {2, 2, 2}, {4, 3, 3}}; // 最大需求矩阵
int Allocation[P_NUM][R_NUM] = {{0, 1, 0}, {2, 0, 0}, {3, 0, 2}, {2, 1, 1}, {0, 0, 2}}; // 已分配矩阵
int Need[P_NUM][R_NUM];                       // 需求矩阵

// 初始化Need矩阵
void init() {
    for (int i = 0; i < P_NUM; i++)
        for (int j = 0; j < R_NUM; j++)
            Need[i][j] = Max[i][j] - Allocation[i][j];
}

// 安全性检查算法
bool isSafe() {
    int Work[R_NUM];
    bool Finish[P_NUM] = {false};
    int SafeSequence[P_NUM];
    int count = 0;

    for (int i = 0; i < R_NUM; i++) Work[i] = Available[i];

    while (count < P_NUM) {
        bool found = false;
        for (int i = 0; i < P_NUM; i++) {
            if (!Finish[i]) {
                int j;
                for (j = 0; j < R_NUM; j++) {
                    if (Need[i][j] > Work[j]) break;
                }
                if (j == R_NUM) { // 如果该进程所有资源需求都能满足
                    for (int k = 0; k < R_NUM; k++) Work[k] += Allocation[i][k];
                    Finish[i] = true;
                    SafeSequence[count++] = i;
                    found = true;
                }
            }
        }
        if (!found) break; // 找不到可以加入安全序列的进程
    }

    if (count == P_NUM) {
        printf("系统当前处于安全状态。安全序列为: ");
        for (int i = 0; i < P_NUM; i++) printf("P%d ", SafeSequence[i]);
        printf("\n");
        return true;
    } else {
        printf("系统处于不安全状态！\n");
        return false;
    }
}

// 银行家请求分配算法
void requestResources(int p_id, int request[]) {
    // 1. 检查请求是否超过Need
    for (int i = 0; i < R_NUM; i++) {
        if (request[i] > Need[p_id][i]) {
            printf("错误：请求资源超过最大需求量！\n");
            return;
        }
    }
    // 2. 检查系统当前是否有足够资源
    for (int i = 0; i < R_NUM; i++) {
        if (request[i] > Available[i]) {
            printf("进程P%d必须等待，系统资源不足。\n", p_id);
            return;
        }
    }

    // 3. 试探性分配（修改数据结构）
    for (int i = 0; i < R_NUM; i++) {
        Available[i] -= request[i];
        Allocation[p_id][i] += request[i];
        Need[p_id][i] -= request[i];
    }

    // 4. 执行安全性检查
    if (isSafe()) {
        printf("同意为进程P%d分配资源。\n", p_id);
    } else {
        // 5. 若不安全，则恢复原状
        printf("分配后系统不安全，拒绝为进程P%d分配资源，恢复状态。\n", p_id);
        for (int i = 0; i < R_NUM; i++) {
            Available[i] += request[i];
            Allocation[p_id][i] -= request[i];
            Need[p_id][i] += request[i];
        }
    }
}

int main() {
    init();
    printf("--- 初始状态安全性检查 ---\n");
    isSafe();

    int p_id, req[R_NUM];
    printf("\n请输入请求资源的进程号(0-%d): ", P_NUM-1);
    scanf("%d", &p_id);
    printf("请输入请求各资源的数量(3种): ");
    for (int i = 0; i < R_NUM; i++) scanf("%d", &req[i]);

    requestResources(p_id, req);

    return 0;
}