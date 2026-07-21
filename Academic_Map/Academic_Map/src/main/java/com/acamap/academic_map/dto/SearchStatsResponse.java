package com.acamap.academic_map.dto;

public record SearchStatsResponse(
        long totalPapers,
        long totalUsers,
        long totalFavorites,
        long totalReading
) {
}