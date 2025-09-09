-- Migration: switch duties.student_id FK to reference student_info(student_id)
-- BACKUP your database before running these statements.
-- 1) Inspect current constraints (run manually to confirm names):
--    SHOW CREATE TABLE duties;\n--    SHOW CREATE TABLE student_info;\n
-- 2) If the existing foreign key constraint name is `duties_ibfk_1` (as in your error), run the following.
--    If the name differs, replace it with the one from SHOW CREATE TABLE output.

SET @OLD_FK_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `duties` DROP FOREIGN KEY `duties_ibfk_1`;

ALTER TABLE `duties`
  ADD CONSTRAINT `fk_duties_student_info`
    FOREIGN KEY (`student_id`) REFERENCES `student_info`(`student_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = @OLD_FK_CHECKS;

-- After running, verify with:
-- SHOW CREATE TABLE duties;
