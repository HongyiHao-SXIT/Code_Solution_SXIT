package com.acamap.academic_map.service.impl;

import com.acamap.academic_map.entity.Gender;
import com.acamap.academic_map.entity.User;
import com.acamap.academic_map.repository.UserRepository;
import com.acamap.academic_map.service.UserService;
import org.springframework.stereotype.Service;

import java.util.List;
import java.util.Optional;

@Service
public class UserServiceImpl implements UserService {

    private final UserRepository userRepository;

    public UserServiceImpl(UserRepository userRepository) {
        this.userRepository = userRepository;
    }

    @Override
    public User register(User user) {
        if (userRepository.existsByAccount(user.getAccount())) {
            throw new IllegalArgumentException("account already exists");
        }
        if (user.getGender() == null) {
            user.setGender(Gender.UNKNOWN);
        }
        return userRepository.save(user);
    }

    @Override
    public Optional<User> login(String account, String password) {
        if (account == null || password == null) {
            return Optional.empty();
        }
        return userRepository.findByAccountAndPassword(account, password);
    }

    @Override
    public Optional<User> findById(Integer id) {
        return userRepository.findById(id);
    }

    @Override
    public List<User> findAll() {
        return userRepository.findAll();
    }
}