USE telesom_learnhub;
-- Demo video lessons (public sample MP4). Replace the URL with your own YouTube/Vimeo/MP4 link in Manage Course > Lessons.
INSERT INTO lessons (course_id,title,type,content,url,position) VALUES
(1,'Watch: Course Introduction','video','Watch this short video, then continue to the next lesson.','https://www.w3schools.com/html/mov_bbb.mp4',5),
(2,'Watch: Service in Action','video','A short demo video lesson.','https://www.w3schools.com/html/movie.mp4',3),
(5,'Watch: Stay Safe Online','video','A short demo video lesson.','https://www.w3schools.com/html/mov_bbb.mp4',3);
