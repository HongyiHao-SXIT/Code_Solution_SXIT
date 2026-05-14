package com.acamap.academic_map.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "user_paper_shelf", uniqueConstraints = {
        @UniqueConstraint(name = "uk_user_paper_type", columnNames = {"user_id", "paper_id", "shelf_type"})
})
@Getter
@Setter
@NoArgsConstructor
public class UserPaperShelf {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "user_id", nullable = false)
    private User user;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "paper_id", nullable = false)
    private Paper paper;

    @Enumerated(EnumType.STRING)
    @Column(name = "shelf_type", nullable = false, length = 16)
    private ShelfType shelfType;

    @Column(name = "created_at", nullable = false)
    private LocalDateTime createdAt;

    @PrePersist
    public void onCreate() {
        if (createdAt == null) {
            createdAt = LocalDateTime.now();
        }
    }
}
