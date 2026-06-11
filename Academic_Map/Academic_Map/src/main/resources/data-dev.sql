INSERT INTO users (name, account, password, email, gender, affiliation) VALUES
('Demo User', 'demo', 'demo123', 'demo@example.com', 'UNKNOWN', 'Academic Map Team'),
('Lin Wang', 'linwang', 'scholar123', 'lin.wang@example.com', 'FEMALE', 'Shanghai Institute of Technology');

INSERT INTO papers (id, title, abstract_text, authors, publication_date, journal, doi, url) VALUES
('p-001', 'Graph Neural Networks for Citation Recommendation', 'A retrieval framework for citation ranking using graph neural networks.', 'Ming Zhao; Lin Wang', '2025-03-14', 'Journal of Information Retrieval', '10.1000/jir.2025.001', 'https://example.org/papers/p-001'),
('p-002', 'Large Language Models in Scholarly Search', 'Evaluation of query understanding for academic search tasks.', 'Qian Liu; Yifan Chen', '2024-11-08', 'ACM Computing Surveys', '10.1000/cs.2024.118', 'https://example.org/papers/p-002'),
('p-003', 'Temporal Analysis of Research Communities', 'A longitudinal map of collaboration networks across disciplines.', 'Hao Sun; Rui Zhang', '2023-06-19', 'Scientometrics', '10.1000/sci.2023.219', 'https://example.org/papers/p-003'),
('p-004', 'Hybrid Retrieval for Academic Knowledge Bases', 'Combines sparse and dense retrieval for better recall and precision.', 'Yu Chen; Lin Wang', '2025-01-21', 'Information Processing & Management', '10.1000/ipm.2025.067', 'https://example.org/papers/p-004'),
('p-005', 'Benchmarking Multilingual Scholarly Datasets', 'Dataset curation and quality control for multilingual paper retrieval.', 'Amina Rahman; Bo Li', '2022-09-03', 'Data Intelligence', '10.1000/di.2022.305', 'https://example.org/papers/p-005'),
('p-006', 'Personalized Paper Recommendation via User Intent', 'Intent-aware retrieval improves relevance in academic search portals.', 'Rui Zhang; Xiang He', '2024-04-30', 'Knowledge-Based Systems', '10.1000/kbs.2024.212', 'https://example.org/papers/p-006');

INSERT INTO user_paper_shelf (user_id, paper_id, shelf_type) VALUES
(1, 'p-002', 'FAVORITE'),
(1, 'p-004', 'READING'),
(2, 'p-001', 'FAVORITE');