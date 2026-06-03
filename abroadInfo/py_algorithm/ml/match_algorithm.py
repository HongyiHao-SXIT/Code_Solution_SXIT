"""
University matching algorithm for UniData platform.

Matches students to suitable universities and programs based on:
- GPA score
- Language test scores (IELTS / TOEFL)
- Preferred country
- Budget constraints (optional)
- Degree level (undergraduate / graduate)

Usage:
    from match_algorithm import UniversityMatcher
    matcher = UniversityMatcher()
    results = matcher.match(gpa=85, ielts=6.5, country='美国')
"""

from __future__ import annotations

import json
import math
from dataclasses import dataclass, field
from typing import Dict, List, Optional, Tuple

import numpy as np
from sklearn.metrics.pairwise import cosine_similarity


# ============================================================
# Data Models
# ============================================================

@dataclass
class StudentProfile:
    """Student input profile for matching."""
    gpa: float                          # GPA score (0-100 or 0-4.0 scale, normalized internally)
    ielts: Optional[float] = None       # IELTS band score (0-9)
    toefl: Optional[float] = None       # TOEFL iBT score (0-120)
    preferred_country: Optional[str] = None  # e.g., '英国', '美国'
    degree_level: Optional[str] = None  # 'undergraduate' or 'graduate'
    budget_per_year: Optional[float] = None  # USD per year
    gpa_scale: str = '100'             # '100' or '4.0'


@dataclass
class UniversityProgram:
    """A simplified university program record for matching."""
    id: int
    university_name: str
    country: str
    program_name: str
    degree_level: Optional[str] = None
    min_gpa: Optional[float] = None        # On 100-point scale
    min_ielts: Optional[float] = None
    min_toefl: Optional[float] = None
    qs_rank: Optional[int] = None
    usnews_rank: Optional[int] = None
    tuition_per_year: Optional[float] = None
    match_score: float = 0.0


# ============================================================
# Matcher
# ============================================================

class UniversityMatcher:
    """
    Score-based university matching engine.

    Scoring dimensions (each 0-100):
    1. GPA compatibility (30%)
    2. Language requirement (25%)
    3. Country preference (15%)
    4. Ranking bonus (10%)
    5. Budget compatibility (10%)
    6. Degree level match (10%)
    """

    DIMENSIONS = {
        'gpa': 0.30,
        'language': 0.25,
        'country': 0.15,
        'ranking': 0.10,
        'budget': 0.10,
        'degree': 0.10,
    }

    def normalize_gpa(self, gpa: float, scale: str = '100') -> float:
        """Convert GPA to 0-100 scale."""
        if scale == '4.0':
            return (gpa / 4.0) * 100.0
        return gpa

    def _score_gpa(self, student: StudentProfile, program: UniversityProgram) -> float:
        """Score GPA compatibility. Higher = better match."""
        if program.min_gpa is None:
            return 80.0  # No requirement = moderate fit

        normalized = self.normalize_gpa(student.gpa, student.gpa_scale)

        if normalized >= program.min_gpa:
            # Above threshold: score decreases as GPA exceeds (overqualified still good)
            ratio = min(normalized / program.min_gpa, 2.0)
            return min(100.0, 70.0 + 30.0 * (ratio - 1.0) / 0.5)
        else:
            # Below threshold: steep penalty
            ratio = normalized / program.min_gpa
            return max(0.0, ratio * 50.0)

    def _score_language(self, student: StudentProfile, program: UniversityProgram) -> float:
        """Score language test compatibility."""
        has_ielts = student.ielts is not None and student.ielts > 0
        has_toefl = student.toefl is not None and student.toefl > 0

        if not has_ielts and not has_toefl:
            return 50.0  # No language score available = neutral

        scores = []

        if program.min_ielts is not None and has_ielts:
            ratio = student.ielts / program.min_ielts
            scores.append(min(100.0, ratio * 85.0))
        elif program.min_ielts is not None:
            scores.append(0.0)  # Program requires IELTS but student has none

        if program.min_toefl is not None and has_toefl:
            ratio = student.toefl / program.min_toefl
            scores.append(min(100.0, ratio * 85.0))
        elif program.min_toefl is not None:
            scores.append(0.0)

        if not scores:
            return 80.0  # No language requirement from program

        return float(np.mean(scores)) if scores else 50.0

    def _score_country(self, student: StudentProfile, program: UniversityProgram) -> float:
        """Score country preference."""
        if student.preferred_country is None:
            return 70.0  # No preference

        if student.preferred_country == program.country:
            return 100.0

        return 20.0  # Different country

    def _score_ranking(self, program: UniversityProgram) -> float:
        """Score ranking prestige. Higher rank = higher score."""
        rank = program.qs_rank or program.usnews_rank
        if rank is None:
            return 50.0

        # Top 10 -> 100, Top 50 -> 85, Top 100 -> 70, Top 500 -> 40
        if rank <= 10:
            return 100.0
        if rank <= 50:
            return 85.0 + (50 - rank) / 40 * 15
        if rank <= 100:
            return 70.0 + (100 - rank) / 50 * 15
        if rank <= 500:
            return 40.0 + (500 - rank) / 400 * 30
        return max(10.0, 40.0 - (rank - 500) / 100)

    def _score_budget(self, student: StudentProfile, program: UniversityProgram) -> float:
        """Score budget compatibility."""
        if student.budget_per_year is None or program.tuition_per_year is None:
            return 70.0  # Neutral if unknown

        if program.tuition_per_year <= student.budget_per_year:
            # Under budget: better score if much under
            ratio = program.tuition_per_year / student.budget_per_year
            return 70.0 + 30.0 * (1.0 - ratio)
        else:
            # Over budget: penalty
            ratio = student.budget_per_year / program.tuition_per_year
            return max(0.0, ratio * 60.0)

    def _score_degree(self, student: StudentProfile, program: UniversityProgram) -> float:
        """Score degree level match."""
        if student.degree_level is None or program.degree_level is None:
            return 70.0

        # Normalize
        student_level = student.degree_level.lower().strip()
        program_level = program.degree_level.lower().strip()

        # Map common aliases
        undergrad_aliases = {'undergraduate', 'bachelor', 'bsc', 'ba', 'beng', 'ug'}
        grad_aliases = {'graduate', 'master', 'msc', 'ma', 'meng', 'phd', 'doctoral', 'pg'}

        def classify(level: str) -> str:
            if any(a in level for a in undergrad_aliases):
                return 'undergraduate'
            if any(a in level for a in grad_aliases):
                return 'graduate'
            return level

        s_cls = classify(student_level)
        p_cls = classify(program_level)

        if s_cls == p_cls:
            return 100.0
        return 30.0

    def score_program(self, student: StudentProfile, program: UniversityProgram) -> UniversityProgram:
        """Compute composite match score for a program."""
        scores = {
            'gpa': self._score_gpa(student, program),
            'language': self._score_language(student, program),
            'country': self._score_country(student, program),
            'ranking': self._score_ranking(program),
            'budget': self._score_budget(student, program),
            'degree': self._score_degree(student, program),
        }

        composite = sum(scores[k] * self.DIMENSIONS[k] for k in self.DIMENSIONS)
        program.match_score = round(composite, 2)
        return program

    def match(
        self,
        student: StudentProfile,
        programs: List[UniversityProgram],
        top_n: int = 20,
    ) -> List[UniversityProgram]:
        """
        Match a student against a list of programs.

        Returns programs sorted by match_score descending.
        """
        if not programs:
            return []

        scored = [self.score_program(student, p) for p in programs]
        scored.sort(key=lambda p: p.match_score, reverse=True)

        return scored[:top_n]

    def match_with_categories(
        self,
        student: StudentProfile,
        programs: List[UniversityProgram],
        top_n: int = 20,
    ) -> Dict[str, List[UniversityProgram]]:
        """
        Match and categorize results into tiers.

        Returns:
            {
                'reach': [...],      # match_score 80-100
                'match': [...],      # match_score 55-79
                'safety': [...],     # match_score 30-54
            }
        """
        results = self.match(student, programs, top_n=len(programs))

        tiers = {
            'reach': [],
            'match': [],
            'safety': [],
        }

        for p in results:
            if p.match_score >= 80:
                tiers['reach'].append(p)
            elif p.match_score >= 55:
                tiers['match'].append(p)
            else:
                tiers['safety'].append(p)

        # Limit each tier
        for key in tiers:
            tiers[key] = sorted(tiers[key], key=lambda p: p.match_score, reverse=True)[:top_n // 3]

        return tiers

    def explain_score(self, student: StudentProfile, program: UniversityProgram) -> Dict[str, float]:
        """Return detailed score breakdown."""
        return {
            'gpa_score': round(self._score_gpa(student, program), 2),
            'language_score': round(self._score_language(student, program), 2),
            'country_score': round(self._score_country(student, program), 2),
            'ranking_score': round(self._score_ranking(program), 2),
            'budget_score': round(self._score_budget(student, program), 2),
            'degree_score': round(self._score_degree(student, program), 2),
            'composite': round(self.score_program(student, program).match_score, 2),
        }


# ============================================================
# Convenience Functions
# ============================================================

def load_programs_from_list(data: List[dict]) -> List[UniversityProgram]:
    """Convert list of dicts to UniversityProgram list."""
    programs = []
    for item in data:
        programs.append(UniversityProgram(
            id=item.get('id', 0),
            university_name=item.get('university_name', ''),
            country=item.get('country', ''),
            program_name=item.get('program_name', ''),
            degree_level=item.get('degree_level'),
            min_gpa=item.get('min_gpa'),
            min_ielts=item.get('min_ielts'),
            min_toefl=item.get('min_toefl'),
            qs_rank=item.get('qs_rank'),
            usnews_rank=item.get('usnews_rank'),
            tuition_per_year=item.get('tuition_per_year'),
        ))
    return programs


def match_student(
    gpa: float,
    ielts: Optional[float] = None,
    toefl: Optional[float] = None,
    preferred_country: Optional[str] = None,
    degree_level: Optional[str] = None,
    budget_per_year: Optional[float] = None,
    programs: Optional[List[dict]] = None,
    top_n: int = 20,
) -> List[dict]:
    """
    One-shot matching convenience function.
    Returns list of matched program dicts with match_score.
    """
    student = StudentProfile(
        gpa=gpa,
        ielts=ielts,
        toefl=toefl,
        preferred_country=preferred_country,
        degree_level=degree_level,
        budget_per_year=budget_per_year,
    )

    if programs is None:
        programs = []

    program_objs = load_programs_from_list(programs)
    matcher = UniversityMatcher()
    results = matcher.match(student, program_objs, top_n=top_n)

    return [
        {
            'id': p.id,
            'university_name': p.university_name,
            'country': p.country,
            'program_name': p.program_name,
            'degree_level': p.degree_level,
            'min_gpa': p.min_gpa,
            'min_ielts': p.min_ielts,
            'min_toefl': p.min_toefl,
            'qs_rank': p.qs_rank,
            'usnews_rank': p.usnews_rank,
            'tuition_per_year': p.tuition_per_year,
            'match_score': p.match_score,
        }
        for p in results
    ]


# ============================================================
# Demo / Test
# ============================================================

if __name__ == '__main__':
    # Sample programs data
    sample_programs = [
        {
            'id': 1, 'university_name': 'MIT', 'country': '美国',
            'program_name': 'Computer Science (BS)',
            'degree_level': 'undergraduate', 'min_gpa': 92.0,
            'min_ielts': 7.0, 'min_toefl': 100, 'qs_rank': 1, 'tuition_per_year': 58000,
        },
        {
            'id': 2, 'university_name': 'Stanford', 'country': '美国',
            'program_name': 'Computer Science (MS)',
            'degree_level': 'graduate', 'min_gpa': 90.0,
            'min_ielts': 7.0, 'min_toefl': 100, 'qs_rank': 5, 'tuition_per_year': 56000,
        },
        {
            'id': 3, 'university_name': 'Oxford', 'country': '英国',
            'program_name': 'Computer Science (BA)',
            'degree_level': 'undergraduate', 'min_gpa': 90.0,
            'min_ielts': 7.0, 'min_toefl': 100, 'qs_rank': 3, 'tuition_per_year': 45000,
        },
        {
            'id': 4, 'university_name': 'University of Toronto', 'country': '加拿大',
            'program_name': 'Computer Science (BSc)',
            'degree_level': 'undergraduate', 'min_gpa': 82.0,
            'min_ielts': 6.5, 'min_toefl': 89, 'qs_rank': 21, 'tuition_per_year': 42000,
        },
        {
            'id': 5, 'university_name': 'University of Sydney', 'country': '澳大利亚',
            'program_name': 'Computer Science (BSc)',
            'degree_level': 'undergraduate', 'min_gpa': 78.0,
            'min_ielts': 6.5, 'min_toefl': 85, 'qs_rank': 19, 'tuition_per_year': 38000,
        },
        {
            'id': 6, 'university_name': 'TUM', 'country': '德国',
            'program_name': 'Informatics (BSc)',
            'degree_level': 'undergraduate', 'min_gpa': 75.0,
            'min_ielts': 6.0, 'qs_rank': 28, 'tuition_per_year': 3000,
        },
        {
            'id': 7, 'university_name': 'NUS', 'country': '新加坡',
            'program_name': 'Computer Science (BComp)',
            'degree_level': 'undergraduate', 'min_gpa': 88.0,
            'min_ielts': 6.5, 'min_toefl': 92, 'qs_rank': 8, 'tuition_per_year': 28000,
        },
    ]

    # Demo student
    student = StudentProfile(
        gpa=85.0,
        ielts=6.5,
        preferred_country='美国',
        degree_level='undergraduate',
        budget_per_year=50000,
    )

    matcher = UniversityMatcher()
    results = matcher.match(student, load_programs_from_list(sample_programs))

    print("=" * 60)
    print(f"Student: GPA={student.gpa}, IELTS={student.ielts}, "
          f"Country={student.preferred_country}, Budget=${student.budget_per_year}")
    print("=" * 60)
    print(f"{'Rank':<5} {'Score':<8} {'University':<25} {'Country':<10}")
    print("-" * 60)

    for i, p in enumerate(results, 1):
        print(f"{i:<5} {p.match_score:<8.2f} {p.university_name:<25} {p.country:<10}")

    print()
    print("Detailed breakdown (top match):")
    if results:
        breakdown = matcher.explain_score(student, results[0])
        for k, v in breakdown.items():
            print(f"  {k}: {v}")