import asyncio
import csv
import re
from typing import Dict, List, Set
from urllib.parse import urljoin, urlparse

import httpx
from bs4 import BeautifulSoup


class AdmissionRequirementSpider:
    """Crawl admission requirement pages from foreign university websites."""

    def __init__(self, seed_urls: List[str], max_links_per_site: int = 15):
        self.seed_urls = seed_urls
        self.max_links_per_site = max_links_per_site
        self.headers = {
            "User-Agent": (
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/124.0.0.0 Safari/537.36"
            )
        }
        self.link_keywords = re.compile(
            r"admission|apply|application|requirements|entry requirements|"
            r"international students|undergraduate admissions|graduate admissions",
            re.IGNORECASE,
        )
        self.content_keywords = re.compile(
            r"requirement|gpa|ielts|toefl|sat|act|deadline|"
            r"documents|transcript|tuition|scholarship",
            re.IGNORECASE,
        )

    async def fetch_html(self, client: httpx.AsyncClient, url: str) -> str:
        try:
            response = await client.get(url, timeout=15.0)
            response.raise_for_status()
            return response.text
        except Exception as exc:
            print(f"[WARN] Failed to fetch {url}: {exc}")
            return ""

    def _same_domain(self, seed_url: str, target_url: str) -> bool:
        return urlparse(seed_url).netloc == urlparse(target_url).netloc

    def find_admission_links(self, seed_url: str, html: str) -> List[Dict[str, str]]:
        soup = BeautifulSoup(html, "html.parser")
        links: List[Dict[str, str]] = []
        seen: Set[str] = set()

        for a_tag in soup.find_all("a", href=True):
            text = a_tag.get_text(" ", strip=True)
            href = a_tag["href"].strip()
            full_url = urljoin(seed_url, href)
            if not full_url.startswith("http"):
                continue
            if not self._same_domain(seed_url, full_url):
                continue

            merged_text = f"{text} {full_url}"
            if not self.link_keywords.search(merged_text):
                continue

            if full_url in seen:
                continue
            seen.add(full_url)
            links.append({"title": text or "Untitled", "url": full_url})

            if len(links) >= self.max_links_per_site:
                break

        return links

    def extract_main_content(self, html: str) -> str:
        if not html:
            return ""

        soup = BeautifulSoup(html, "html.parser")
        candidates = soup.select("main, article, .content, .main-content, #content")

        if candidates:
            text = "\n".join(
                node.get_text(" ", strip=True)
                for node in candidates
                if node.get_text(" ", strip=True)
            )
        else:
            text = soup.get_text(" ", strip=True)

        text = re.sub(r"\s+", " ", text).strip()
        if len(text) > 3000:
            text = text[:3000]
        return text

    def get_requirement_snippet(self, content: str) -> str:
        if not content:
            return ""

        sentences = re.split(r"(?<=[.!?])\s+", content)
        matched = [s for s in sentences if self.content_keywords.search(s)]
        snippet = " ".join(matched[:8]).strip()
        return snippet if snippet else content[:800]

    async def crawl(self) -> List[Dict[str, str]]:
        results: List[Dict[str, str]] = []

        async with httpx.AsyncClient(headers=self.headers, follow_redirects=True) as client:
            for seed_url in self.seed_urls:
                print(f"\n[INFO] Scanning index: {seed_url}")
                index_html = await self.fetch_html(client, seed_url)
                if not index_html:
                    continue

                admission_links = self.find_admission_links(seed_url, index_html)
                print(f"[INFO] Found {len(admission_links)} candidate links")

                for link in admission_links:
                    detail_html = await self.fetch_html(client, link["url"])
                    content = self.extract_main_content(detail_html)
                    snippet = self.get_requirement_snippet(content)
                    if not snippet:
                        continue

                    results.append(
                        {
                            "university_home": seed_url,
                            "page_title": link["title"],
                            "page_url": link["url"],
                            "requirement_snippet": snippet,
                        }
                    )

        return results


def save_to_csv(rows: List[Dict[str, str]], output_path: str = "admission_requirements.csv") -> None:
    if not rows:
        print("[INFO] No data to save.")
        return

    fieldnames = ["university_home", "page_title", "page_url", "requirement_snippet"]
    with open(output_path, "w", newline="", encoding="utf-8-sig") as csv_file:
        writer = csv.DictWriter(csv_file, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)

    print(f"[INFO] Saved {len(rows)} records to {output_path}")


async def main() -> None:
    seed_urls = [
        "https://www.ox.ac.uk/admissions",
        "https://www.imperial.ac.uk/study/",
        "https://www.mit.edu/admissions-aid/",
        "https://www.ucl.ac.uk/prospective-students/",
    ]

    spider = AdmissionRequirementSpider(seed_urls=seed_urls, max_links_per_site=12)
    data = await spider.crawl()
    save_to_csv(data)


if __name__ == "__main__":
    asyncio.run(main())