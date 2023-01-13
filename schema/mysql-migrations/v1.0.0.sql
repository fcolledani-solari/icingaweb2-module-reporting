ALTER TABLE schedule
    MODIFY column frequency enum ('none', 'minutely', 'hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom', 'cron_expr');
