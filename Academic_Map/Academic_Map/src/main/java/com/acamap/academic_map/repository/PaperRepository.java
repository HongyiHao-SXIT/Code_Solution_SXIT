package com.acamap.academic_map.repository;

import com.acamap.academic_map.entity.Paper;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.JpaSpecificationExecutor;

public interface PaperRepository extends JpaRepository<Paper, String>, JpaSpecificationExecutor<Paper> {
}
