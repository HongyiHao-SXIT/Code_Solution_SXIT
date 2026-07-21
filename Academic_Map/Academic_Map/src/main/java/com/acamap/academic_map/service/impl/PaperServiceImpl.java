package com.acamap.academic_map.service.impl;

import com.acamap.academic_map.dto.PaperSearchResponse;
import com.acamap.academic_map.entity.Paper;
import com.acamap.academic_map.repository.PaperRepository;
import com.acamap.academic_map.service.PaperService;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Pageable;
import org.springframework.data.domain.Sort;
import org.springframework.data.jpa.domain.Specification;
import org.springframework.stereotype.Service;

import java.util.Arrays;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Optional;
import java.util.Set;
import java.util.UUID;
import java.util.stream.Collectors;

@Service
public class PaperServiceImpl implements PaperService {

    private final PaperRepository paperRepository;

    public PaperServiceImpl(PaperRepository paperRepository) {
        this.paperRepository = paperRepository;
    }

    @Override
    public PaperSearchResponse search(String keyword, String author, String journal, Integer year, int page, int size) {
        int validPage = Math.max(page, 0);
        int validSize = Math.max(size, 1);

        Pageable pageable = PageRequest.of(validPage, validSize, Sort.by(Sort.Direction.DESC, "publicationDate"));
        Page<Paper> result = paperRepository.findAll(buildSpecification(keyword, author, journal, year), pageable);

        return new PaperSearchResponse(result.getContent(), result.getTotalElements(), validPage, validSize, result.getTotalPages());
    }

    @Override
    public Optional<Paper> findById(String id) {
        return paperRepository.findById(id);
    }

    @Override
    public List<Paper> recommend(String id, int size) {
        Optional<Paper> baseOptional = paperRepository.findById(id);
        if (baseOptional.isEmpty()) {
            return List.of();
        }

        Paper base = baseOptional.get();
        int validSize = Math.max(size, 1);

        Set<String> baseAuthors = splitTokens(base.getAuthors(), "[;,，、]");
        Set<String> baseKeywords = splitTokens((base.getTitle() == null ? "" : base.getTitle()) + " " + (base.getAbstractText() == null ? "" : base.getAbstractText()), "[^a-zA-Z0-9]+")
                .stream()
                .filter(token -> token.length() >= 4)
                .collect(Collectors.toSet());

        return paperRepository.findAll().stream()
                .filter(candidate -> !candidate.getId().equals(id))
                .map(candidate -> new PaperScore(candidate, computeScore(base, candidate, baseAuthors, baseKeywords)))
                .filter(scored -> scored.score > 0)
                .sorted((a, b) -> {
                    if (a.score != b.score) {
                        return Integer.compare(b.score, a.score);
                    }
                    String bDate = b.paper.getPublicationDate() == null ? "" : b.paper.getPublicationDate();
                    String aDate = a.paper.getPublicationDate() == null ? "" : a.paper.getPublicationDate();
                    return bDate.compareTo(aDate);
                })
                .limit(validSize)
                .map(scored -> scored.paper)
                .toList();
    }

    @Override
    public Paper add(Paper paper) {
        if (paper.getId() == null || paper.getId().isBlank()) {
            paper.setId(UUID.randomUUID().toString());
        }
        return paperRepository.save(paper);
    }

    private Specification<Paper> buildSpecification(String keyword, String author, String journal, Integer year) {
        return (root, query, builder) -> {
            var predicates = new java.util.ArrayList<jakarta.persistence.criteria.Predicate>();

            if (hasText(keyword)) {
                String pattern = "%" + keyword.trim().toLowerCase(Locale.ROOT) + "%";
                predicates.add(builder.or(
                        builder.like(builder.lower(root.get("title")), pattern),
                        builder.like(builder.lower(root.get("abstractText")), pattern),
                        builder.like(builder.lower(root.get("authors")), pattern),
                        builder.like(builder.lower(root.get("doi")), pattern)
                ));
            }

            if (hasText(author)) {
                predicates.add(builder.like(builder.lower(root.get("authors")), "%" + author.trim().toLowerCase(Locale.ROOT) + "%"));
            }

            if (hasText(journal)) {
                predicates.add(builder.like(builder.lower(root.get("journal")), "%" + journal.trim().toLowerCase(Locale.ROOT) + "%"));
            }

            if (year != null) {
                predicates.add(builder.like(root.get("publicationDate"), year + "%"));
            }

            return builder.and(predicates.toArray(new jakarta.persistence.criteria.Predicate[0]));
        };
    }

    private boolean hasText(String value) {
        return value != null && !value.isBlank();
    }

    private int computeScore(Paper base, Paper candidate, Set<String> baseAuthors, Set<String> baseKeywords) {
        int score = 0;

        if (hasText(base.getJournal()) && hasText(candidate.getJournal())
                && base.getJournal().trim().equalsIgnoreCase(candidate.getJournal().trim())) {
            score += 5;
        }

        Set<String> candidateAuthors = splitTokens(candidate.getAuthors(), "[;,，、]");
        long sharedAuthors = candidateAuthors.stream()
                .filter(baseAuthors::contains)
                .count();
        score += (int) sharedAuthors * 3;

        Set<String> candidateKeywords = splitTokens((candidate.getTitle() == null ? "" : candidate.getTitle()) + " " + (candidate.getAbstractText() == null ? "" : candidate.getAbstractText()), "[^a-zA-Z0-9]+")
                .stream()
                .filter(token -> token.length() >= 4)
                .collect(Collectors.toSet());

        long sharedKeywords = candidateKeywords.stream()
                .filter(baseKeywords::contains)
                .limit(6)
                .count();
        score += (int) sharedKeywords;

        return score;
    }

    private Set<String> splitTokens(String value, String delimiterPattern) {
        if (!hasText(value)) {
            return Set.of();
        }
        return Arrays.stream(value.toLowerCase(Locale.ROOT).split(delimiterPattern))
                .map(String::trim)
                .filter(token -> !token.isBlank())
                .collect(Collectors.toCollection(HashSet::new));
    }

    private record PaperScore(Paper paper, int score) {
    }
}