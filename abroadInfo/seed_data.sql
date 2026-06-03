-- ============================================================
-- UniData Seed Data: 预设大学和项目数据
-- 运行: mysql -u root -p < seed_data.sql
-- 或者在 PHPMyAdmin 中导入
-- ============================================================

USE abroad_info;

-- ============================================================
-- 大学数据
-- ============================================================

INSERT INTO universities (name, country, city, qs_rank, usnews_rank, website) VALUES
-- 英国
('University of Oxford', '英国', 'Oxford', 3, 4, 'https://www.ox.ac.uk'),
('University of Cambridge', '英国', 'Cambridge', 5, 6, 'https://www.cam.ac.uk'),
('Imperial College London', '英国', 'London', 2, 12, 'https://www.imperial.ac.uk'),
('UCL (University College London)', '英国', 'London', 9, 7, 'https://www.ucl.ac.uk'),
('University of Edinburgh', '英国', 'Edinburgh', 27, 38, 'https://www.ed.ac.uk'),
('University of Manchester', '英国', 'Manchester', 34, 67, 'https://www.manchester.ac.uk'),
('King''s College London', '英国', 'London', 36, 36, 'https://www.kcl.ac.uk'),
('London School of Economics', '英国', 'London', 50, 239, 'https://www.lse.ac.uk'),

-- 美国
('Massachusetts Institute of Technology', '美国', 'Cambridge, MA', 1, 2, 'https://www.mit.edu'),
('Stanford University', '美国', 'Stanford, CA', 6, 3, 'https://www.stanford.edu'),
('Harvard University', '美国', 'Cambridge, MA', 4, 1, 'https://www.harvard.edu'),
('California Institute of Technology', '美国', 'Pasadena, CA', 10, 23, 'https://www.caltech.edu'),
('University of California, Berkeley', '美国', 'Berkeley, CA', 12, 5, 'https://www.berkeley.edu'),
('University of Chicago', '美国', 'Chicago, IL', 21, 25, 'https://www.uchicago.edu'),
('Princeton University', '美国', 'Princeton, NJ', 22, 18, 'https://www.princeton.edu'),
('Yale University', '美国', 'New Haven, CT', 23, 10, 'https://www.yale.edu'),

-- 澳大利亚
('University of Melbourne', '澳大利亚', 'Melbourne', 13, 27, 'https://www.unimelb.edu.au'),
('University of Sydney', '澳大利亚', 'Sydney', 18, 29, 'https://www.sydney.edu.au'),
('University of Queensland', '澳大利亚', 'Brisbane', 40, 41, 'https://www.uq.edu.au'),
('Australian National University', '澳大利亚', 'Canberra', 30, 85, 'https://www.anu.edu.au'),

-- 加拿大
('University of Toronto', '加拿大', 'Toronto', 25, 17, 'https://www.utoronto.ca'),
('University of British Columbia', '加拿大', 'Vancouver', 38, 39, 'https://www.ubc.ca'),
('McGill University', '加拿大', 'Montreal', 29, 56, 'https://www.mcgill.ca'),

-- 德国
('Technical University of Munich', '德国', 'Munich', 28, 82, 'https://www.tum.de'),
('Heidelberg University', '德国', 'Heidelberg', 84, 55, 'https://www.uni-heidelberg.de'),
('Humboldt University of Berlin', '德国', 'Berlin', 126, 48, 'https://www.hu-berlin.de'),

-- 法国
('Sorbonne University', '法国', 'Paris', 63, 46, 'https://www.sorbonne-universite.fr'),
('PSL University', '法国', 'Paris', 24, 112, 'https://www.psl.eu'),

-- 日本
('University of Tokyo', '日本', 'Tokyo', 32, 84, 'https://www.u-tokyo.ac.jp'),
('Kyoto University', '日本', 'Kyoto', 50, 168, 'https://www.kyoto-u.ac.jp'),

-- 新加坡
('National University of Singapore', '新加坡', 'Singapore', 8, 22, 'https://www.nus.edu.sg'),
('Nanyang Technological University', '新加坡', 'Singapore', 15, 27, 'https://www.ntu.edu.sg'),

-- 中国香港
('University of Hong Kong', '中国', 'Hong Kong', 17, 44, 'https://www.hku.hk'),
('Chinese University of Hong Kong', '中国', 'Hong Kong', 36, 42, 'https://www.cuhk.edu.hk'),

-- 瑞士
('ETH Zurich', '其他', 'Zurich', 7, 33, 'https://www.ethz.ch'),
('EPFL', '其他', 'Lausanne', 26, 88, 'https://www.epfl.ch')
ON DUPLICATE KEY UPDATE name=VALUES(name);


-- ============================================================
-- 项目数据 (projects)
-- ============================================================

INSERT INTO projects (university_id, name, description, language_requirement, gpa_requirement, degree_level, deadline_date, page_url) VALUES

-- 英国项目
(1, 'Computer Science (BA)', '牛津大学计算机科学本科项目，涵盖算法、人工智能、系统等方向', 'IELTS 7.0 (单项不低于6.5)', 90.00, 'undergraduate', '2025-10-15', 'https://www.ox.ac.uk/admissions/undergraduate'),
(1, 'Mathematics (MMath)', '牛津大学数学本科本硕连读项目', 'IELTS 7.0', 92.00, 'undergraduate', '2025-10-15', 'https://www.ox.ac.uk/admissions/undergraduate'),
(1, 'Advanced Computer Science (MSc)', '牛津大学计算机科学硕士', 'IELTS 7.5 (单项不低于7.0)', 88.00, 'graduate', '2025-12-01', 'https://www.ox.ac.uk/admissions/graduate'),
(2, 'Computer Science (BA)', '剑桥大学计算机科学本科', 'IELTS 7.5 (单项不低于7.0)', 92.00, 'undergraduate', '2025-10-15', 'https://www.cam.ac.uk/admissions'),
(2, 'Engineering (MEng)', '剑桥大学工程本硕连读', 'IELTS 7.5', 90.00, 'undergraduate', '2025-10-15', 'https://www.cam.ac.uk/admissions'),
(3, 'Computing (BEng)', '帝国理工计算机科学本科', 'IELTS 6.5 (单项不低于6.0)', 85.00, 'undergraduate', '2025-01-29', 'https://www.imperial.ac.uk/study'),
(3, 'Artificial Intelligence (MSc)', '帝国理工人工智能硕士', 'IELTS 7.0 (单项不低于6.5)', 82.00, 'graduate', '2025-06-30', 'https://www.imperial.ac.uk/study'),
(4, 'Computer Science (BSc)', 'UCL计算机科学本科', 'IELTS 6.5 (单项不低于6.0)', 85.00, 'undergraduate', '2025-01-29', 'https://www.ucl.ac.uk/prospective-students'),
(4, 'Data Science (MSc)', 'UCL数据科学硕士', 'IELTS 7.0 (单项不低于6.5)', 80.00, 'graduate', '2025-07-01', 'https://www.ucl.ac.uk/prospective-students'),
(5, 'Computer Science (BSc)', '爱丁堡大学计算机科学本科', 'IELTS 6.5 (单项不低于5.5)', 80.00, 'undergraduate', '2025-01-29', 'https://www.ed.ac.uk/studying'),
(5, 'Artificial Intelligence (MSc)', '爱丁堡大学人工智能硕士', 'IELTS 7.0', 78.00, 'graduate', '2025-05-31', 'https://www.ed.ac.uk/studying'),

-- 美国项目
(9, 'Computer Science and Engineering (BS)', 'MIT电子工程与计算机科学本科', 'TOEFL 100', 95.00, 'undergraduate', '2025-01-01', 'https://mitadmissions.org'),
(9, 'Electrical Engineering and Computer Science (MEng)', 'MIT EECS硕士项目', 'TOEFL 100', 90.00, 'graduate', '2024-12-15', 'https://mitadmissions.org'),
(10, 'Computer Science (BS)', '斯坦福大学计算机科学本科', 'TOEFL 100', 93.00, 'undergraduate', '2025-01-05', 'https://www.stanford.edu/admission'),
(10, 'Computer Science (MS)', '斯坦福大学计算机科学硕士', 'TOEFL 100', 88.00, 'graduate', '2024-12-03', 'https://www.stanford.edu/admission'),
(11, 'Computer Science (AB)', '哈佛大学计算机科学本科', 'TOEFL 100', 93.00, 'undergraduate', '2025-01-01', 'https://college.harvard.edu/admissions'),
(13, 'Electrical Engineering and Computer Sciences (BS)', 'UC Berkeley EECS本科', 'TOEFL 80', 88.00, 'undergraduate', '2024-11-30', 'https://www.berkeley.edu/admissions'),
(13, 'Computer Science (MS)', 'UC Berkeley计算机科学硕士', 'TOEFL 90', 85.00, 'graduate', '2024-12-09', 'https://www.berkeley.edu/admissions'),

-- 澳大利亚
(17, 'Computer Science (BSci)', '墨尔本大学计算机科学本科', 'IELTS 6.5 (单项不低于6.0)', 78.00, 'undergraduate', '2025-02-28', 'https://www.unimelb.edu.au/study'),
(17, 'Information Technology (MIT)', '墨尔本大学信息技术硕士', 'IELTS 6.5', 72.00, 'graduate', '2025-05-31', 'https://www.unimelb.edu.au/study'),
(18, 'Computer Science (BSc)', '悉尼大学计算机科学本科', 'IELTS 6.5', 75.00, 'undergraduate', '2025-01-31', 'https://www.sydney.edu.au/study'),
(18, 'Data Science (MDS)', '悉尼大学数据科学硕士', 'IELTS 7.0', 70.00, 'graduate', '2025-06-30', 'https://www.sydney.edu.au/study'),

-- 加拿大
(21, 'Computer Science (BSc)', '多伦多大学计算机科学本科', 'IELTS 6.5 (单项不低于6.0)', 82.00, 'undergraduate', '2025-01-15', 'https://www.utoronto.ca/future-students'),
(21, 'Computer Science (MSc)', '多伦多大学计算机科学硕士', 'IELTS 7.0', 80.00, 'graduate', '2024-12-01', 'https://www.utoronto.ca/future-students'),
(22, 'Computer Science (BSc)', 'UBC计算机科学本科', 'IELTS 6.5', 80.00, 'undergraduate', '2025-01-15', 'https://www.ubc.ca/admissions'),
(23, 'Computer Science (BSc)', '麦吉尔大学计算机科学本科', 'IELTS 6.5', 83.00, 'undergraduate', '2025-01-15', 'https://www.mcgill.ca/undergraduate-admissions'),

-- 德国
(24, 'Informatics (BSc)', '慕尼黑工业大学信息学本科', 'IELTS 6.0', 75.00, 'undergraduate', '2025-07-15', 'https://www.tum.de/en/studies'),
(24, 'Data Engineering (MSc)', '慕尼黑工业大学数据工程硕士', 'IELTS 6.5', 72.00, 'graduate', '2025-05-31', 'https://www.tum.de/en/studies'),
(25, 'Computer Science (BSc)', '海德堡大学计算机科学本科', 'IELTS 6.0', 75.00, 'undergraduate', '2025-07-15', 'https://www.uni-heidelberg.de'),

-- 新加坡
(31, 'Computer Science (BComp)', '新加坡国立大学计算机本科', 'IELTS 6.5', 85.00, 'undergraduate', '2025-02-28', 'https://www.nus.edu.sg/admissions'),
(31, 'Computer Science (MComp)', '新加坡国立大学计算机硕士', 'IELTS 6.0', 78.00, 'graduate', '2025-03-31', 'https://www.nus.edu.sg/admissions'),
(32, 'Computer Science (BEng)', '南洋理工大学计算机本科', 'IELTS 6.0', 82.00, 'undergraduate', '2025-03-19', 'https://www.ntu.edu.sg/admissions'),

-- 中国香港
(33, 'Computer Science (BEng)', '香港大学计算机工程本科', 'IELTS 6.5', 82.00, 'undergraduate', '2025-06-30', 'https://www.hku.hk/admission'),
(34, 'Computer Science (BSc)', '香港中文大学计算机科学本科', 'IELTS 6.0', 80.00, 'undergraduate', '2025-05-31', 'https://www.cuhk.edu.hk/adm'),

-- 瑞士
(35, 'Computer Science (BSc)', 'ETH Zurich计算机科学本科', 'IELTS 7.0', 88.00, 'undergraduate', '2025-04-30', 'https://www.ethz.ch'),
(36, 'Computer Science (BSc)', 'EPFL计算机科学本科', 'IELTS 6.5', 85.00, 'undergraduate', '2025-04-30', 'https://www.epfl.ch/education')
ON DUPLICATE KEY UPDATE name=VALUES(name);