package com.acamap.academic_map.service;

import com.acamap.academic_map.dto.UserShelfResponse;

public interface UserShelfService {

    UserShelfResponse getShelf(Integer userId);

    UserShelfResponse addFavorite(Integer userId, String paperId);

    UserShelfResponse removeFavorite(Integer userId, String paperId);

    UserShelfResponse addReading(Integer userId, String paperId);

    UserShelfResponse removeReading(Integer userId, String paperId);
}
