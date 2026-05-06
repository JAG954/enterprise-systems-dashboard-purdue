-- Demo-only user seed data for local development.
-- Before running this file, set a local SQL session variable:
-- SET @demo_user_password := 'your-local-demo-password';

INSERT INTO User (FullName, Username, Password, Role)
SELECT FullName, Username, Password, Role
FROM (
    SELECT 'Demo Senior Manager' AS FullName, 'demo_sem' AS Username, MD5(@demo_user_password) AS Password, 'SeniorManager' AS Role
    UNION ALL
    SELECT 'Demo Supply Chain Manager' AS FullName, 'demo_scm' AS Username, MD5(@demo_user_password) AS Password, 'SupplyChainManager' AS Role
    UNION ALL
    SELECT 'Recruiter Review Senior' AS FullName, 'review_sem' AS Username, MD5(@demo_user_password) AS Password, 'SeniorManager' AS Role
    UNION ALL
    SELECT 'Recruiter Review SCM' AS FullName, 'review_scm' AS Username, MD5(@demo_user_password) AS Password, 'SupplyChainManager' AS Role
    UNION ALL
    SELECT 'Analytics Reviewer' AS FullName, 'analytics_sem' AS Username, MD5(@demo_user_password) AS Password, 'SeniorManager' AS Role
    UNION ALL
    SELECT 'Operations Reviewer' AS FullName, 'operations_scm' AS Username, MD5(@demo_user_password) AS Password, 'SupplyChainManager' AS Role
) AS demo_users
WHERE @demo_user_password IS NOT NULL;
