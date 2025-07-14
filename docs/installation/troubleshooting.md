---
order: 7
---

# Troubleshooting

## Frequently Asked Questions (FAQs)

Nothing here yet 🫣

## Admin Tasks

### Reset a users Protected Pages Password

**Background:** If a User has forgotten the [Password Protected Pages](/usage-guide/user-management-access-control#protected-pages-password) there is currently no easy way to reset (via email for example).
Changing the Protected Pages Password via the self-service flow requires knowledge of the Protected Pages Password itself.

**Solution:** An admin with access to MicroPowerManager database can reset a tenant's Protected Pages Password.

Run the following query on the affected tenant's database:

```sql
UPDATE main_settings
SET protected_page_password = 'eyJpdiI6ImpJUmZRSEJaQ2JBU084WVVhVi8yRlE9PSIsInZhbHVlIjoiQWFEbld4MFJGWTh4Y1plUDY2UmRLUT09IiwibWFjIjoiOWI4Y2UxNTgwZjVlZmM2MDZmYmI0OWNjMWNjZmUxYTcxMDgxYTYxNWNkMzE2NDI1ZDNmY2FhZTVkZDcwNTgzZCIsInRhZyI6IiJ9';
```

This will set the password to `123123` and can then be consequently changed by the user via the [Password Protected Pages](/usage-guide/user-management-access-control#protected-pages-password) self-service flow.
