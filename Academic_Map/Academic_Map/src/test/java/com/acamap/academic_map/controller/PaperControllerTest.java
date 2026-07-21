package com.acamap.academic_map.controller;

import com.acamap.academic_map.entity.ShelfType;
import com.acamap.academic_map.repository.PaperRepository;
import com.acamap.academic_map.repository.UserPaperShelfRepository;
import com.acamap.academic_map.repository.UserRepository;
import com.acamap.academic_map.service.PaperService;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.http.MediaType;
import org.springframework.test.context.ActiveProfiles;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.*;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@ActiveProfiles("test")
class PaperControllerTest {

    @Autowired
    private WebApplicationContext context;

    @Autowired
    private PaperService paperService;

    @Autowired
    private PaperRepository paperRepository;

    @Autowired
    private UserRepository userRepository;

    @Autowired
    private UserPaperShelfRepository userPaperShelfRepository;

    private MockMvc mockMvc;

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).build();
    }

    @Test
    void shouldSearchWithoutParams() throws Exception {
        mockMvc.perform(get("/api/papers"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.items").isArray())
                .andExpect(jsonPath("$.total").isNumber())
                .andExpect(jsonPath("$.page").isNumber())
                .andExpect(jsonPath("$.totalPages").isNumber());
    }

    @Test
    void shouldSearchByKeyword() throws Exception {
        mockMvc.perform(get("/api/papers").param("keyword", "graph"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.items").isArray());
    }

    @Test
    void shouldReturn404ForUnknownPaper() throws Exception {
        mockMvc.perform(get("/api/papers/nonexistent-id"))
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.message").value("paper not found"));
    }

    @Test
    void shouldGetStats() throws Exception {
        mockMvc.perform(get("/api/papers/stats"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.totalPapers").isNumber())
                .andExpect(jsonPath("$.totalUsers").isNumber())
                .andExpect(jsonPath("$.totalFavorites").isNumber())
                .andExpect(jsonPath("$.totalReading").isNumber());
    }

    @Test
    void shouldRejectPaperWithoutTitle() throws Exception {
        String invalidJson = """
                {"abstractText":"some abstract"}
                """;

        mockMvc.perform(post("/api/papers")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(invalidJson))
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.message").value("title is required"));
    }

    @Test
    void shouldCreateAndRetrievePaper() throws Exception {
        String paperJson = """
                {
                    "title": "Integration Test Paper",
                    "abstractText": "Test abstract for integration.",
                    "authors": "Alice; Bob",
                    "publicationDate": "2026-07-01",
                    "journal": "Test Journal",
                    "doi": "10.9999/itest.2026"
                }
                """;

        String response = mockMvc.perform(post("/api/papers")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(paperJson))
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.id").isNotEmpty())
                .andExpect(jsonPath("$.title").value("Integration Test Paper"))
                .andReturn()
                .getResponse()
                .getContentAsString();

        String createdId = extractId(response);

        mockMvc.perform(get("/api/papers/" + createdId))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.title").value("Integration Test Paper"));
    }

    private String extractId(String json) {
        int idStart = json.indexOf("\"id\":\"") + 6;
        if (idStart < 6) {
            return "unknown";
        }
        int idEnd = json.indexOf("\"", idStart);
        return json.substring(idStart, idEnd);
    }
}