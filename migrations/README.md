This migration changes the foreign key on `duties.student_id` so it references `student_info(student_id)` instead of `users(id)`.

Important:
- Backup your database before running any migration.
- Confirm the current constraint name using `SHOW CREATE TABLE duties;` and replace `duties_ibfk_1` in the SQL if it differs.

How to run (mysql CLI):
1. Backup: `mysqldump -u root -p duty_log_system > duty_log_system_backup.sql`
2. Run migration: `mysql -u root -p duty_log_system < alter_duties_fk.sql`

Or paste the SQL from `alter_duties_fk.sql` into phpMyAdmin SQL tab and execute.

If you prefer I can implement a safer approach that keeps duties linked to `users.id` and additionally store the `student_info` id in duties (e.g., `student_info_id`) — tell me if you want that instead.
