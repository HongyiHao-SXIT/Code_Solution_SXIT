#include <iostream>
#include <string>
using namespace std;

void encrypt(string &s) {
    for (int i = 0; i < s.length(); i++) {
        if (s[i] >= 'a' && s[i] <= 'z') {
            if (s[i] == 'y') s[i] = 'a';
            else if (s[i] == 'z') s[i] = 'b';
            else s[i] = s[i] + 2;
            s[i] = s[i] - 32; // 转大写
        }
    }
}

int main() {
    string text;
    cout << "请输入要加密的小写字符串: ";
    cin >> text;
    encrypt(text);
    cout << "加密后的密文: " << text << endl;
    return 0;
}