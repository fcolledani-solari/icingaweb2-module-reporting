ALTER TYPE frequency rename to old_frequency;
CREATE TYPE frequency AS ENUM ('none', 'minutely', 'hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom', 'cron_expr');

ALTER TABLE schedule
    ALTER COLUMN frequency type frequency using frequency::text::frequency;
DROP TYPE old_frequency;
