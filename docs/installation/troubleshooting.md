---
order: 7
---

# Troubleshooting

## Frequently Asked Questions (FAQs)

Nothing here yet 🫣

## Queue Worker

### The queue worker is running but jobs are not being processed

**Background:** Horizon runs as three nested levels of processes inside the queue worker container.

```text
supervisord
└─ php artisan horizon               the Horizon master supervisor
   └─ php artisan horizon:supervisor  the child supervisor
      └─ php artisan horizon:work     one worker per queue
```

Supervisor only watches the top-level `php artisan horizon` process.
If the child supervisor or the workers stop, that top-level process stays alive, so `supervisorctl status` still reports `RUNNING` and no supervisor event is raised.
A `RUNNING` status therefore tells you the master process exists, not that jobs are being processed.

**Diagnosis:** run the health check, which reads Horizon's own heartbeats rather than the process list.

```bash
docker compose exec queue-worker-dev php artisan mpm-system-checks:horizon --local
```

A non-zero exit code names what is wrong: a missing master heartbeat, a missing child supervisor, or zero worker processes.
The same check runs every five minutes from the scheduler container and reports failures to Slack when `HORIZON_SLACK_WEBHOOK_URL` or `LOG_SLACK_WEBHOOK_URL` is configured.

**Solution:** restart Horizon through supervisor.

```bash
docker compose exec queue-worker-dev supervisorctl restart horizon
kubectl exec deploy/mpm-queue-worker -- supervisorctl restart horizon
```

Use `supervisorctl` rather than `php artisan horizon:terminate`.
`horizon:terminate` looks up the process to signal in Horizon's heartbeat records, so when the master has stopped heartbeating it reports `No processes to terminate.` and exits successfully without doing anything.
Supervisor holds the real process id.

## Admin Tasks

### Update a user's role

**Background:** Permissions are now granted through RBAC. When someone changes departments, you may need to adjust their role so that the UI and API enforce the right access.

**Solution:** Navigate to **Settings → User Management**, edit the user, and update their role (Owner, Admin, Financial Manager, User). Changes take effect immediately — no manual database queries are required.
