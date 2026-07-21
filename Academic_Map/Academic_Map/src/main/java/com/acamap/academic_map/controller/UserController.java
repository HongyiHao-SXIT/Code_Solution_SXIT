package com.acamap.academic_map.controller;

import com.acamap.academic_map.dto.UserShelfResponse;
import com.acamap.academic_map.entity.User;
import com.acamap.academic_map.service.UserShelfService;
import com.acamap.academic_map.service.UserService;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;
import java.util.Optional;

@RestController
@RequestMapping("/api/users")
@CrossOrigin(origins = "http://localhost:5173")
public class UserController {
    private final UserService userService;
    private final UserShelfService userShelfService;

    public UserController(UserService userService, UserShelfService userShelfService) {
        this.userService = userService;
        this.userShelfService = userShelfService;
    }

    @PostMapping("/register")
    public ResponseEntity<?> register(@RequestBody User user) {
        if (user == null || user.getAccount() == null || user.getPassword() == null) {
            return ResponseEntity.badRequest().body(Map.of("message", "account and password are required"));
        }
        try {
            User created = userService.register(user);
            return ResponseEntity.status(HttpStatus.CREATED).body(created);
        } catch (IllegalArgumentException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("message", e.getMessage()));
        }
    }

    @PostMapping("/login")
    public ResponseEntity<?> login(@RequestBody Map<String, String> payload) {
        String account = payload.get("account");
        String password = payload.get("password");
        Optional<User> user = userService.login(account, password);
        if (user.isEmpty()) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED)
                    .body(Map.of("message", "invalid account or password"));
        }
        return ResponseEntity.ok(user.get());
    }

    @GetMapping
    public List<User> list() {
        return userService.findAll();
    }

    @GetMapping("/{id}")
    public ResponseEntity<?> getById(@PathVariable Integer id) {
        return userService.findById(id)
                .<ResponseEntity<?>>map(ResponseEntity::ok)
                .orElseGet(() -> ResponseEntity.status(HttpStatus.NOT_FOUND)
                        .body(Map.of("message", "user not found")));
    }

    @GetMapping("/{id}/shelf")
    public ResponseEntity<?> getShelf(@PathVariable Integer id) {
        try {
            return ResponseEntity.ok(userShelfService.getShelf(id));
        } catch (IllegalArgumentException exception) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("message", exception.getMessage()));
        }
    }

    @PostMapping("/{id}/shelf/favorites/{paperId}")
    public ResponseEntity<?> addFavorite(@PathVariable Integer id, @PathVariable String paperId) {
        return updateShelf(() -> userShelfService.addFavorite(id, paperId));
    }

    @DeleteMapping("/{id}/shelf/favorites/{paperId}")
    public ResponseEntity<?> removeFavorite(@PathVariable Integer id, @PathVariable String paperId) {
        return updateShelf(() -> userShelfService.removeFavorite(id, paperId));
    }

    @PostMapping("/{id}/shelf/reading/{paperId}")
    public ResponseEntity<?> addReading(@PathVariable Integer id, @PathVariable String paperId) {
        return updateShelf(() -> userShelfService.addReading(id, paperId));
    }

    @DeleteMapping("/{id}/shelf/reading/{paperId}")
    public ResponseEntity<?> removeReading(@PathVariable Integer id, @PathVariable String paperId) {
        return updateShelf(() -> userShelfService.removeReading(id, paperId));
    }

    private ResponseEntity<?> updateShelf(ShelfOperation operation) {
        try {
            return ResponseEntity.ok(operation.execute());
        } catch (IllegalArgumentException exception) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("message", exception.getMessage()));
        }
    }

    @FunctionalInterface
    private interface ShelfOperation {
        UserShelfResponse execute();
    }


}
