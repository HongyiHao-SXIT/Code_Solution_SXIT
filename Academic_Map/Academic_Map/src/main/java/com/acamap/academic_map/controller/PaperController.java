package com.acamap.academic_map.controller;

import com.acamap.academic_map.dto.PaperSearchResponse;
import com.acamap.academic_map.entity.Paper;
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

    public PaperController(PaperService paperService) {
        this.paperService = paperService;
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

    @PostMapping
    public ResponseEntity<?> add(@RequestBody Paper paper) {
        if (paper == null || paper.getTitle() == null || paper.getTitle().isBlank()) {
            return ResponseEntity.badRequest().body(Map.of("message", "title is required"));
        }
        Paper created = paperService.add(paper);
        return ResponseEntity.status(HttpStatus.CREATED).body(created);
    }
}
