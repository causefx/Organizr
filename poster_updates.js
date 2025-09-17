            // Most Watched Movies with Posters
            if (' . $showMostWatchedMovies . ' && stats.most_watched_movies && stats.most_watched_movies.length > 0) {
                console.log("Rendering most watched movies:", stats.most_watched_movies);
                html += "<div class=\"col-lg-12\" style=\"margin-top: 30px;\">";
                html += "<h5><i class=\"fa fa-film text-primary\"></i> Most Watched Movies</h5>";
                html += "<div style=\"margin-top: 15px; overflow-x: auto; white-space: nowrap; padding: 10px 0;\">";
                
                stats.most_watched_movies.forEach(function(movie) {
                    console.log("Processing movie:", movie);
                    var posterUrl = getPosterUrl(movie.poster_path, movie.id, movie.server_id);
                    var playCount = movie.play_count || 0;
                    var year = movie.year || "N/A";
                    var title = movie.title || "Unknown Movie";
                    
                    html += "<div style=\"display: inline-block; margin: 10px; width: 150px; vertical-align: top;\">";
                    html += "<div class=\"poster-card\" style=\"position: relative; background: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 150px; height: 290px;\">";
                    
                    // Poster image container
                    html += "<div class=\"poster-image\" style=\"position: relative; width: 150px; height: 225px; background: #e9ecef;\">";
                    if (posterUrl) {
                        html += "<img src=\"" + posterUrl + "\" alt=\"" + title + "\" style=\"width: 150px; height: 225px; object-fit: cover;\" onerror=\"this.style.display='none'; this.nextElementSibling.style.display='flex';\">";
                    }
                    html += "<div class=\"poster-placeholder\" style=\"position: absolute; top: 0; left: 0; width: 150px; height: 225px; display: " + (posterUrl ? "none" : "flex") + "; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;\">";
                    html += "<i class=\"fa fa-film fa-3x\"></i>";
                    html += "</div>";
                    
                    // Play count badge
                    html += "<div class=\"play-count-badge\" style=\"position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px;\">";
                    html += "<i class=\"fa fa-play\"></i> " + playCount;
                    html += "</div>";
                    html += "</div>";
                    
                    // Movie info with improved height and text clipping
                    html += "<div class=\"poster-info\" style=\"padding: 8px; text-align: center; height: 65px; display: flex; flex-direction: column; justify-content: space-between;\">";
                    html += "<div style=\"font-size: 12px; font-weight: bold; line-height: 1.2; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;\" title=\"" + title + "\">" + title + "</div>";
                    html += "<small class=\"text-muted\">" + year + "</small>";
                    html += "</div>";
                    
                    html += "</div></div>";
                });
                
                html += "</div></div>";
            } else {
                console.log("Movies not showing because:");
                console.log("- Setting enabled:", ' . $showMostWatchedMovies . ');
                console.log("- Has data:", stats.most_watched_movies && stats.most_watched_movies.length > 0);
                console.log("- Data:", stats.most_watched_movies);
            }
            
            // Most Watched TV Shows with Posters (updated to match movies)
            if (' . $showMostWatchedShows . ' && stats.most_watched_shows && stats.most_watched_shows.length > 0) {
                html += "<div class=\"col-lg-12\" style=\"margin-top: 30px;\">";
                html += "<h5><i class=\"fa fa-television text-info\"></i> Most Watched TV Shows</h5>";
                html += "<div style=\"margin-top: 15px; overflow-x: auto; white-space: nowrap; padding: 10px 0;\">";
                
                stats.most_watched_shows.forEach(function(show) {
                    var posterUrl = getPosterUrl(show.poster_path, show.id, show.server_id);
                    var playCount = show.play_count || 0;
                    var year = show.year || "N/A";
                    var title = show.title || "Unknown Show";
                    
                    html += "<div style=\"display: inline-block; margin: 10px; width: 150px; vertical-align: top;\">";
                    html += "<div class=\"poster-card\" style=\"position: relative; background: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 150px; height: 290px;\">";
                    
                    // Poster image with fixed dimensions like movies
                    html += "<div class=\"poster-image\" style=\"position: relative; width: 150px; height: 225px; background: #e9ecef;\">";
                    if (posterUrl) {
                        html += "<img src=\"" + posterUrl + "\" alt=\"" + title + "\" style=\"width: 150px; height: 225px; object-fit: cover;\" onerror=\"this.style.display='none'; this.nextElementSibling.style.display='flex';\">";
                    }
                    html += "<div class=\"poster-placeholder\" style=\"position: absolute; top: 0; left: 0; width: 150px; height: 225px; display: " + (posterUrl ? "none" : "flex") + "; align-items: center; justify-content: center; background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); color: white;\">";
                    html += "<i class=\"fa fa-television fa-3x\"></i>";
                    html += "</div>";
                    
                    // Play count badge
                    html += "<div class=\"play-count-badge\" style=\"position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px;\">";
                    html += "<i class=\"fa fa-play\"></i> " + playCount;
                    html += "</div>";
                    html += "</div>";
                    
                    // Show info matching movies format
                    html += "<div class=\"poster-info\" style=\"padding: 8px; text-align: center; height: 65px; display: flex; flex-direction: column; justify-content: space-between;\">";
                    html += "<div style=\"font-size: 12px; font-weight: bold; line-height: 1.2; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;\" title=\"" + title + "\">" + title + "</div>";
                    html += "<small class=\"text-muted\">" + year + "</small>";
                    html += "</div>";
                    
                    html += "</div></div>";
                });
                
                html += "</div></div>";
            }
            
            // Most Listened Music with Cover Art (updated to match movies)
            if (' . $showMostListenedMusic . ' && stats.most_listened_music && stats.most_listened_music.length > 0) {
                html += "<div class=\"col-lg-12\" style=\"margin-top: 30px;\">";
                html += "<h5><i class=\"fa fa-music text-success\"></i> Most Listened Music</h5>";
                html += "<div style=\"margin-top: 15px; overflow-x: auto; white-space: nowrap; padding: 10px 0;\">";
                
                stats.most_listened_music.forEach(function(music) {
                    var posterUrl = getPosterUrl(music.poster_path || music.cover_art, music.id, music.server_id);
                    var playCount = music.play_count || 0;
                    var artist = music.artist || "Unknown Artist";
                    var title = music.title || music.album || "Unknown";
                    
                    html += "<div style=\"display: inline-block; margin: 10px; width: 150px; vertical-align: top;\">";
                    html += "<div class=\"poster-card\" style=\"position: relative; background: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 150px; height: 290px;\">";
                    
                    // Cover art with fixed dimensions like movies (square for music)
                    html += "<div class=\"poster-image\" style=\"position: relative; width: 150px; height: 150px; background: #e9ecef;\">";
                    if (posterUrl) {
                        html += "<img src=\"" + posterUrl + "\" alt=\"" + title + "\" style=\"width: 150px; height: 150px; object-fit: cover;\" onerror=\"this.style.display='none'; this.nextElementSibling.style.display='flex';\">";
                    }
                    html += "<div class=\"poster-placeholder\" style=\"position: absolute; top: 0; left: 0; width: 150px; height: 150px; display: " + (posterUrl ? "none" : "flex") + "; align-items: center; justify-content: center; background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%); color: white;\">";
                    html += "<i class=\"fa fa-music fa-3x\"></i>";
                    html += "</div>";
                    
                    // Play count badge
                    html += "<div class=\"play-count-badge\" style=\"position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px;\">";
                    html += "<i class=\"fa fa-play\"></i> " + playCount;
                    html += "</div>";
                    html += "</div>";
                    
                    // Music info with proper space for title and artist
                    html += "<div class=\"poster-info\" style=\"padding: 8px; text-align: center; height: 140px; display: flex; flex-direction: column; justify-content: space-between;\">";
                    html += "<div style=\"font-size: 12px; font-weight: bold; line-height: 1.2; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;\" title=\"" + title + "\">" + title + "</div>";
                    html += "<small class=\"text-muted\">" + artist + "</small>";
                    html += "</div>";
                    
                    html += "</div></div>";
                });
                
                html += "</div></div>";
            }
