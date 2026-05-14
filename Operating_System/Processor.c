#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// 定义进程控制块 PCB 结构
typedef struct pcb {
    char name[10];      // 进程名
    int priority;       // 优先权
    int burst_time;     // 需要运行的时间（服务时间）
    int remain_time;    // 剩余运行时间
    char state;         // 状态：'R'就绪, 'F'完成
    struct pcb *next;   // 指向下一个 PCB 的指针
} PCB;

PCB *ready_queue = NULL; // 就绪队列头指针

// 按优先权降序插入就绪队列
void insert_priority(PCB *p) {
    if (ready_queue == NULL || p->priority > ready_queue->priority) {
        p->next = ready_queue;
        ready_queue = p;
    } else {
        PCB *current = ready_queue;
        while (current->next != NULL && current->next->priority >= p->priority) {
            current = current->next;
        }
        p->next = current->next;
        current->next = p;
    }
}

// 输入并初始化进程
void input_processes() {
    int n;
    printf("请输入进程数 (不少于5个): ");
    scanf("%d", &n);
    for (int i = 0; i < n; i++) {
        PCB *p = (PCB *)malloc(sizeof(PCB));
        printf("请输入第 %d 个进程名, 优先权, 服务时间: ", i + 1);
        scanf("%s %d %d", p->name, &p->priority, &p->burst_time);
        p->remain_time = p->burst_time;
        p->state = 'R';
        p->next = NULL;
        insert_priority(p);
    }
}

// 显示当前队列状态
void display_queue(int time) {
    printf("\n当前时刻: %d\n", time);
    printf("进程名\t优先权\t剩余时间\t状态\n");
    PCB *p = ready_queue;
    while (p != NULL) {
        printf("%s\t%d\t%d\t\t%c\n", p->name, p->priority, p->remain_time, p->state);
        p = p->next;
    }
}

// 处理器调度模拟
void simulate_scheduling() {
    int current_time = 0;
    while (ready_queue != NULL) {
        PCB *running = ready_queue; // 取出队首（优先权最高）的进程
        ready_queue = ready_queue->next;

        display_queue(current_time);
        printf("--> 正在运行进程: %s\n", running->name);

        // 模拟运行：执行一个时间单位
        running->remain_time--;
        running->priority--; // 动态优先权：运行后优先级降低，防止饥饿
        current_time++;

        if (running->remain_time > 0) {
            // 未完成，重新插入就绪队列
            running->next = NULL;
            insert_priority(running);
        } else {
            // 已完成
            running->state = 'F';
            printf("--- 进程 %s 已完成 ---\n", running->name);
            free(running);
        }
    }
    printf("\n所有进程调度完毕，总耗时: %d\n", current_time);
}

int main() {
    printf("======= 处理器调度模拟程序 (优先权算法) =======\n");
    input_processes();
    simulate_scheduling();
    return 0;
}