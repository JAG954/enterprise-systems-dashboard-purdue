-- Demo-only user update data for local development.
-- Before running this file, set a local SQL session variable:
-- SET @demo_user_password := 'your-local-demo-password';

UPDATE User
SET FullName = 'Demo Senior Manager',
    Password = MD5(@demo_user_password),
    Role = 'SeniorManager'
WHERE Username = 'demo_sem'
  AND @demo_user_password IS NOT NULL;

UPDATE User
SET FullName = 'Demo Supply Chain Manager',
    Password = MD5(@demo_user_password),
    Role = 'SupplyChainManager'
WHERE Username = 'demo_scm'
  AND @demo_user_password IS NOT NULL;

UPDATE User
SET FullName = 'Recruiter Review Senior',
    Password = MD5(@demo_user_password),
    Role = 'SeniorManager'
WHERE Username = 'review_sem'
  AND @demo_user_password IS NOT NULL;

UPDATE User
SET FullName = 'Recruiter Review SCM',
    Password = MD5(@demo_user_password),
    Role = 'SupplyChainManager'
WHERE Username = 'review_scm'
  AND @demo_user_password IS NOT NULL;

UPDATE User
SET FullName = 'Analytics Reviewer',
    Password = MD5(@demo_user_password),
    Role = 'SeniorManager'
WHERE Username = 'analytics_sem'
  AND @demo_user_password IS NOT NULL;

UPDATE User
SET FullName = 'Operations Reviewer',
    Password = MD5(@demo_user_password),
    Role = 'SupplyChainManager'
WHERE Username = 'operations_scm'
  AND @demo_user_password IS NOT NULL;
