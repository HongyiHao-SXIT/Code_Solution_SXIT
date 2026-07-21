package com.acamap.academic_map.controller;

import com.acamap.academic_map.dto.PaperSearchResponse;
import com.acamap.academic_map.dto.SearchStatsResponse;
import com.acamap.academic_map.entity.Paper;
import com.acamap.academic_map.entity.ShelfType;
import com.acamap.academic_map.repository.PaperRepository;
import com.acamap.academic_map.repository.UserPaperShelfRepository;
import com.acamap.academic_map.repository.UserRepository;
import com.acamap.academic_map.service.PaperService;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.Map;
import java.util.List;

@RestController
@RequestMapping("/api/papers")
@CrossOrigin(origins = "http://localhost:5173")
public class PaperController {

    private final PaperService paperService;
    private final PaperRepository paperRepository;
    private final UserRepository userRepository;
    private final UserPaperShelfRepository userPaperShelfRepository;

    public PaperController(PaperService paperService,
                           PaperRepository paperRepository,
                           UserRepository userRepository,
                           UserPaperShelfRepository userPaperShelfRepository) {
        this.paperService = paperService;
        this.paperRepository = paperRepository;
        this.userRepository = userRepository;
        this.userPaperShelfRepository = userPaperShelfRepository;
    }

    @GetMapping
    public PaperSearchResponse search(
            @RequestParam(required = false) String keyword,
            @RequestParam(required = false) String author,
            @RequestParam(required = false) String journal,
            @RequestParam(required = false) Integer year,
            @RequestParam(defaultValue = "0") int page,
            @RequestParam(defaultValue = "10") int size
    ) {
        return paperService.search(keyword, author, journal, year, page, size);
    }

    @GetMapping("/{id}")
    public ResponseEntity<?> getById(@PathVariable String id) {
        return paperService.findById(id)
                .<ResponseEntity<?>>map(ResponseEntity::ok)
                .orElseGet(() -> ResponseEntity.status(HttpStatus.NOT_FOUND)
                        .body(Map.of("message", "paper not found")));
    }

    @GetMapping("/{id}/recommendations")
    public List<Paper> recommend(@PathVariable String id, @RequestParam(defaultValue = "6") int size) {
        return paperService.recommend(id, size);
    }

    @GetMapping("/stats")
    public SearchStatsResponse stats() {
        long totalPapers = paperRepository.count();
        long totalUsers = userRepository.count();
        long totalFavorites = userPaperShelfRepository.countByShelfType(ShelfType.FAVORITE);
        long totalReading = userPaperShelfRepository.countByShelfType(ShelfType.READING);
        return new SearchStatsResponse(totalPapers, totalUsers, totalFavorites, totalReading);
    }

    @PostMapping
    public ResponseEntity<?> add(@RequestBody Paper paper) {
        if (paper == null || paper.getTitle() == null || paper.getTitle().isBlank()) {
            return ResponseEntity.badRequest().body(Map.of("message", "title is required"));
        }
        Paper created = paperService.add(paper);
        return ResponseEntity.status(HttpStatus.CREATED).body(created);
    }
}
