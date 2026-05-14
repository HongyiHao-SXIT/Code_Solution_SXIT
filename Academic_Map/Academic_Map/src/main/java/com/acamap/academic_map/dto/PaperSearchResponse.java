package com.acamap.academic_map.dto;

import com.acamap.academic_map.entity.Paper;

import java.util.List;

public record PaperSearchResponse(
        List<Paper> items,
        long total,
        int page,
        int size,
        int totalPages
) {
}
