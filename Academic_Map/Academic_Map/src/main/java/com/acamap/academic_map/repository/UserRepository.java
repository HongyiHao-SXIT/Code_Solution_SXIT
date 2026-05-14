package com.acamap.academic_map.repository;

import com.acamap.academic_map.entity.User;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface UserRepository extends JpaRepository<User, Integer> {

    Optional<User> findByAccountAndPassword(String account, String password);

    boolean existsByAccount(String account);
}
