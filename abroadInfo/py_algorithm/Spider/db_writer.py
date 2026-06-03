"""
Write spider results directly to MySQL database.
Requires: pip install pymysql
Usage:
    from db_writer import save_to_db
    save_to_db(rows, host='127.0.0.1', port=3306, user='root', password='', database='abroad_info')
"""

import re
from typing import Dict, List, Optional
from urllib.parse import urlparse

try:
    import pymysql

    HAS_PYMYSQL = True
except ImportError:
    HAS_PYMYSQL = False
    pymysql = None


def detect_country(url: str) -> Optional[str]:
    """Detect country from URL domain suffix."""
    host = urlparse(url).netloc.lower()
    if not host:
        return None

    country_map = {
        ".uk": "英国",
        ".ac.uk": "英国",
        ".edu": "美国",
        ".edu.au": "澳大利亚",
        ".ca": "加拿大",
        ".de": "德国",
        ".fr": "法国",
        ".jp": "日本",
        ".sg": "新加坡",
        ".cn": "中国",
    }

    # Sort by suffix length descending to match .ac.uk before .uk
    for suffix in sorted(country_map.keys(), key=len, reverse=True):
        if host.endswith(suffix):
            # Special case: .edu could be US or Australia
            if suffix == ".edu" and host.endswith(".edu.au"):
                continue
            return country_map[suffix]

    return None


def extract_deadline(text: str) -> Optional[str]:
    """Extract deadline date from text. Returns YYYY-MM-DD or None."""
    match = re.search(r"(20\d{2})[/\-.](\d{1,2})[/\-.](\d{1,2})", text)
    if match:
        return f"{int(match.group(1)):04d}-{int(match.group(2)):02d}-{int(match.group(3)):02d}"
    return None


def save_to_db(
    rows: List[Dict[str, str]],
    host: str = "127.0.0.1",
    port: int = 3306,
    user: str = "root",
    password: str = "",
    database: str = "abroad_info",
) -> int:
    """
    Save spider rows directly to admission_pages table.

    Returns number of inserted rows.
    """
    if not HAS_PYMYSQL:
        print("[ERROR] pymysql is not installed. Run: pip install pymysql")
        return 0

    if not rows:
        print("[INFO] No data to save.")
        return 0

    connection = pymysql.connect(
        host=host,
        port=port,
        user=user,
        password=password,
        database=database,
        charset="utf8mb4",
    )

    inserted = 0
    try:
        with connection.cursor() as cursor:
            insert_sql = """
                INSERT IGNORE INTO admission_pages
                    (university_home, page_title, page_url, requirement_snippet, source)
                VALUES
                    (%s, %s, %s, %s, 'spider')
            """
            update_sql = """
                UPDATE admission_pages
                SET country = %s, deadline_date = %s
                WHERE page_url = %s
            """

            for row in rows:
                university_home = row.get("university_home", "") or None
                page_title = row.get("page_title", "") or "Admissions"
                page_url = row.get("page_url", "")
                snippet = row.get("requirement_snippet", "")

                if not page_url or not snippet:
                    continue

                cursor.execute(
                    insert_sql,
                    (university_home, page_title, page_url, snippet),
                )
                if cursor.rowcount > 0:
                    inserted += 1

                # Update country and deadline
                country = detect_country(page_url)
                deadline = extract_deadline(snippet)
                if country or deadline:
                    cursor.execute(
                        update_sql,
                        (country, deadline, page_url),
                    )

            connection.commit()

    finally:
        connection.close()

    print(f"[INFO] Inserted {inserted} records into admission_pages table")
    return inserted