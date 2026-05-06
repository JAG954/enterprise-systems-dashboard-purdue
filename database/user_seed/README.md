# User Seed Utilities

This folder contains cleaned user-generation artifacts for local development and portfolio review.

The original assignment files generated and updated `User` table records for project testing. The public version has been sanitized:

- Real project-member and grader accounts were replaced with generic demo users.
- Plaintext generated passwords were removed.
- The notebook has no saved outputs or execution counts.
- SQL files use a local SQL session variable instead of committing a password.

## Files

| File | Purpose |
| --- | --- |
| `user_gen.ipynb` | Notebook used to generate the demo SQL seed files. |
| `user_insert.sql` | Inserts generic demo users into the `User` table. |
| `user_update.sql` | Updates generic demo users by username. |
| `user_reset.sql` | Truncates the `User` table. Use only on a local disposable database. |

## Usage

Before running `user_insert.sql` or `user_update.sql`, set a local SQL session variable:

```sql
SET @demo_user_password := 'your-local-demo-password';
```

Then source the desired script in your MySQL client or upload it through phpMyAdmin.

The application currently authenticates against `MD5(?)` because that was part of the original assignment schema. Replace this approach with modern password hashing before production use.
