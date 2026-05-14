/*
 * @lc app=leetcode id=3 lang=cpp
 *
 * [3] Longest Substring Without Repeating Characters
 */

// @lc code=start
class Solution {
public:
    int lengthOfLongestSubstring(string s) {
        unordered_map<char, int> lastPos;
        int left = 0, right = 0, maxLen = 0;
        for (right = 0; right < s.size(); right++) {
            if (lastPos.count(s[right]) && lastPos[s[right]] >= left) {
                left = lastPos[s[right]] + 1;
            }
            lastPos[s[right]] = right;
            maxLen = max(maxLen, right - left + 1);
        }
        return maxLen;
    }
};
// @lc code=end

