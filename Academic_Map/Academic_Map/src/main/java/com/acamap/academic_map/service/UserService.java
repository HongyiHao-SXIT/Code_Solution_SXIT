package com.acamap.academic_map.service;

import com.acamap.academic_map.entity.User;

import java.util.List;
import java.util.Optional;

public interface UserService {

	User register(User user);

	Optional<User> login(String account, String password);

	Optional<User> findById(Integer id);

	List<User> findAll();
}
