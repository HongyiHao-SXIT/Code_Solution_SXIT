/*
 * @lc app=leetcode id=1 lang=cpp
 *
 * [1] Two Sum
 */

// @lc code=start
class Solution {
public:
    vector<int> twoSum(vector<int>& nums, int target) {
        int complement;
        unordered_map<int, int> map;
        for (int i = 0; i < nums.size(); i++) {
            complement = target - nums[i];
            if (map.find(complement) != map.end()) {
                return {map[complement], i};
            }
            else {
                map[nums[i]] = i;
            }
        }
        return {};
    }
};
// @lc code=end

