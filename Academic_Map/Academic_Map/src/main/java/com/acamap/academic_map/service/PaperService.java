package com.acamap.academic_map.service;

import com.acamap.academic_map.dto.PaperSearchResponse;
import com.acamap.academic_map.entity.Paper;

import java.util.List;
import java.util.Optional;

public interface PaperService {

    PaperSearchResponse search(String keyword, String author, String journal, Integer year, int page, int size);

    Optional<Paper> findById(String id);

    List<Paper> recommend(String id, int size);

    Paper add(Paper paper);
}
