package com.acamap.academic_map.dto;

import com.acamap.academic_map.entity.Paper;

import java.util.List;

public record UserShelfResponse(
        List<Paper> favorites,
        List<Paper> readingList
) {
}
