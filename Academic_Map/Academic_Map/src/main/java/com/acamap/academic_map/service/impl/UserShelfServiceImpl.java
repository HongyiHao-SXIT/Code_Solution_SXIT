package com.acamap.academic_map.service.impl;

import com.acamap.academic_map.dto.UserShelfResponse;
import com.acamap.academic_map.entity.Paper;
import com.acamap.academic_map.entity.ShelfType;
import com.acamap.academic_map.entity.User;
import com.acamap.academic_map.entity.UserPaperShelf;
import com.acamap.academic_map.repository.PaperRepository;
import com.acamap.academic_map.repository.UserPaperShelfRepository;
import com.acamap.academic_map.repository.UserRepository;
import com.acamap.academic_map.service.UserShelfService;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
public class UserShelfServiceImpl implements UserShelfService {

    private final UserPaperShelfRepository userPaperShelfRepository;
    private final UserRepository userRepository;
    private final PaperRepository paperRepository;

    public UserShelfServiceImpl(UserPaperShelfRepository userPaperShelfRepository,
                                UserRepository userRepository,
                                PaperRepository paperRepository) {
        this.userPaperShelfRepository = userPaperShelfRepository;
        this.userRepository = userRepository;
        this.paperRepository = paperRepository;
    }

    @Override
    @Transactional(readOnly = true)
    public UserShelfResponse getShelf(Integer userId) {
        assertUserExists(userId);
        return buildShelf(userId);
    }

    @Override
    @Transactional
    public UserShelfResponse addFavorite(Integer userId, String paperId) {
        addToShelf(userId, paperId, ShelfType.FAVORITE);
        return buildShelf(userId);
    }

    @Override
    @Transactional
    public UserShelfResponse removeFavorite(Integer userId, String paperId) {
        userPaperShelfRepository.deleteByUserIdAndPaperIdAndShelfType(userId, paperId, ShelfType.FAVORITE);
        return buildShelf(userId);
    }

    @Override
    @Transactional
    public UserShelfResponse addReading(Integer userId, String paperId) {
        addToShelf(userId, paperId, ShelfType.READING);
        return buildShelf(userId);
    }

    @Override
    @Transactional
    public UserShelfResponse removeReading(Integer userId, String paperId) {
        userPaperShelfRepository.deleteByUserIdAndPaperIdAndShelfType(userId, paperId, ShelfType.READING);
        return buildShelf(userId);
    }

    private void addToShelf(Integer userId, String paperId, ShelfType shelfType) {
        User user = userRepository.findById(userId)
                .orElseThrow(() -> new IllegalArgumentException("user not found"));
        Paper paper = paperRepository.findById(paperId)
                .orElseThrow(() -> new IllegalArgumentException("paper not found"));

        if (userPaperShelfRepository.existsByUserIdAndPaperIdAndShelfType(userId, paperId, shelfType)) {
            return;
        }

        UserPaperShelf row = new UserPaperShelf();
        row.setUser(user);
        row.setPaper(paper);
        row.setShelfType(shelfType);
        userPaperShelfRepository.save(row);
    }

    private void assertUserExists(Integer userId) {
        if (!userRepository.existsById(userId)) {
            throw new IllegalArgumentException("user not found");
        }
    }

    private UserShelfResponse buildShelf(Integer userId) {
        List<Paper> favorites = userPaperShelfRepository.findWithPaperByUserIdAndShelfType(userId, ShelfType.FAVORITE)
                .stream()
                .map(UserPaperShelf::getPaper)
                .toList();

        List<Paper> readingList = userPaperShelfRepository.findWithPaperByUserIdAndShelfType(userId, ShelfType.READING)
                .stream()
                .map(UserPaperShelf::getPaper)
                .toList();

        return new UserShelfResponse(favorites, readingList);
    }
}
