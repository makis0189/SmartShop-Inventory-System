# SmartShop User Management Test Plan

Run these tests on a backup or local test database before using real business data.

1. Sign in as an administrator and open `user_management.php`.
2. Confirm a manager or cashier who directly opens `user_management.php`, `save_user.php`, or `toggle_user_status.php` receives access denied or is redirected safely.
3. Create a cashier with a unique username and a password of at least 12 characters. Confirm the user appears in the list and the database stores a password hash, not the plain password.
4. Try duplicate usernames and duplicate emails; the page should show a useful error and create no duplicate account.
5. Try mismatched passwords and a password shorter than 12 characters; the account should not be created.
6. Sign out and sign in as the new cashier. Confirm the cashier can access Sales but cannot access Products, Stock Alerts, Reports, or Manage Users where the current role policy denies access.
7. Deactivate the cashier, sign out, and confirm the inactive account cannot sign in. Activate it again and confirm sign-in works.
8. Confirm the currently signed-in admin cannot deactivate their own account.
9. If only one active admin exists, confirm the system refuses to deactivate that last admin. Create a second admin in a test environment and confirm one admin can be deactivated while another remains active.
10. Test on mobile width and confirm the user table can scroll horizontally without breaking the layout.

The page uses POST for state changes and CSRF tokens. This test plan does not replace a full application security review or production deployment test.
