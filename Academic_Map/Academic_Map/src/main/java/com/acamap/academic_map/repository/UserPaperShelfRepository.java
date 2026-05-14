package com.acamap.academic_map.repository;

import com.acamap.academic_map.entity.ShelfType;
import com.acamap.academic_map.entity.UserPaperShelf;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.List;

public interface UserPaperShelfRepository extends JpaRepository<UserPaperShelf, Long> {

    @Query("select ups from UserPaperShelf ups join fetch ups.paper where ups.user.id = :userId and ups.shelfType = :shelfType order by ups.createdAt desc")
    List<UserPaperShelf> findWithPaperByUserIdAndShelfType(@Param("userId") Integer userId, @Param("shelfType") ShelfType shelfType);

    boolean existsByUserIdAndPaperIdAndShelfType(Integer userId, String paperId, ShelfType shelfType);

    void deleteByUserIdAndPaperIdAndShelfType(Integer userId, String paperId, ShelfType shelfType);
}
