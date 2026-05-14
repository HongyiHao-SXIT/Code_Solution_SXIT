package com.acamap.academic_map.entity;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import jakarta.persistence.Table;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@AllArgsConstructor
@NoArgsConstructor
@Entity
@Table(name = "papers")
public class Paper {

    @Id
    @Column(length = 64)
    private String id;

    @Column(nullable = false, length = 500)
    private String title;

    @Column(name = "abstract_text", nullable = false, columnDefinition = "TEXT")
    private String abstractText;

    @Column(nullable = false, length = 500)
    private String authors;

    @Column(name = "publication_date", length = 10)
    private String publicationDate;

    @Column(length = 255)
    private String journal;

    @Column(unique = true, length = 100)
    private String doi;

    @Column(length = 500)
    private String url;
}
