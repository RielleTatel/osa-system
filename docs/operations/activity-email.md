# Operating activity email notifications

## Delivery rules

An initial submission emails all `osa_admin` and `osa_director` accounts plus `OSA_OFFICE_EMAIL`. Trimmed, lowercase addresses are deduplicated per event. The `DocsComplete` handoff emails only directors. Endorsement, OSA Form 3 saves, organization decisions, and tracking updates do not send these emails. Existing in-app notifications continue independently.

The **Email deliveries** page is available to OSA admins and directors. It shows each recipient, activity, event, attempts, last attempt, and delivery state, with a failed-only filter. Missing/invalid office configuration is shown on this page; account recipients still receive notices. Configure the mailbox before activation: adding it later does not backfill older submissions. No university addresses or credentials have been supplied or installed by this change.

## Configure university SMTP

Obtain the university's SMTP host, port, authentication/relay requirements, TLS scheme, permitted sender address, and shared OSA mailbox from IT. Set these in the deployment environment, not committed source files. For a university server using STARTTLS on port 587, the configuration has this shape (replace all example values):

```dotenv
APP_URL=https://osa.example.edu
MAIL_SCHEME=smtp
MAIL_HOST=smtp.example.edu
MAIL_PORT=587
MAIL_USERNAME=approved-smtp-account
MAIL_PASSWORD=deployment-secret
MAIL_FROM_ADDRESS=osa-system@example.edu
OSA_OFFICE_EMAIL=osa@example.edu
```

For implicit TLS, use the university's `smtps` scheme and port (commonly 465). Use IT's specified values; authenticated SMTP and IP-authorized relay have different requirements. Clear any obsolete `MAIL_URL` override. Do not disable certificate verification to address connection failures. The app host must be permitted to reach the university server. Configure `MAIL_EHLO_DOMAIN` if IT requires it.

Activity emails explicitly use the `smtp` mailer even if `MAIL_MAILER=log` is used for other application mail. The sender name is **OSA Activity System**, and Reply-To is the configured office mailbox. Development can point the SMTP host/port to a local mail capture server; do not start the activity email worker against a live university server unless delivery is intended.

SMTP timeout is 30 seconds. Emails include only the organization, activity title, proposed dates, request reference, action, and protected tracker link. The configured `APP_URL` must be reachable by recipients. The shared mailbox does not bypass login; staff use their individual OSA accounts to open the link.

## Migrate and run the worker

After deploying the code, run:

```sh
php artisan migrate --force
php artisan config:cache
```

Use a process supervisor to keep this worker running from the project directory as the application user:

```sh
php artisan queue:work activity-email --queue=activity-email --sleep=3 --timeout=60 --tries=5 --max-time=3600
```

Configure automatic restart and stdout/stderr logging in the hosting platform's supervisor. Restart workers after deployments or mail configuration changes (`php artisan queue:restart`, with a shared persistent cache store). If the host cannot supervise workers, arrange an equivalent scheduled `queue:work activity-email --queue=activity-email --stop-when-empty --timeout=60 --tries=5` process before activation.

The dedicated `activity-email` connection always uses the default application database, the existing jobs table, and a 120-second reservation. Keep this connection on the same database as activity requests. Its jobs are inserted inside the workflow transaction: a rollback removes the submission and delivery work together, and another database connection cannot see uncommitted jobs. This is deliberate; do not change this connection to Redis, sync, or an after-commit callback without replacing the atomic persistence guarantee.

The normal `composer dev` process starts the default queue listener, which does not consume this dedicated connection/queue. Start the command above in a separate terminal for local email processing. `QUEUE_CONNECTION=sync` may remain for existing behavior; it never makes activity SMTP delivery run during a submission request.

## Retry and recovery

Each recipient has an independent job. Transient exceptions retry after 60 seconds, 5 minutes, 15 minutes, and 1 hour, for five attempts total. Worker timeouts also use Laravel queue retry handling. A stopped worker leaves jobs pending; a supervisor must restart it. Attempts on the page count executions that reached email handling, and can differ from queue reservation attempts after abrupt process termination.

After retries are exhausted, the page shows **Failed** and the delivery reference; Laravel retains the failed job. SMTP errors remain in restricted operational logs/failed-job storage, never in the staff page. Operators can inspect:

```sh
php artisan queue:failed
```

Match the failed job's `SendActivityEmail` payload `deliveryId` to the delivery reference shown to staff. Fix the mail configuration or connectivity, refresh cached configuration and restart workers, then retry the specific failed job UUID:

```sh
php artisan queue:retry <failed-job-uuid>
```

The worker will update the delivery state on its next attempt. Its cumulative attempt count remains available. Do not use `queue:retry all` in production without reviewing other failed jobs. A delivery already recorded as sent is skipped if its job is replayed.

**Sent** means the SMTP server accepted the message, not proof of inbox delivery. A crash after SMTP acceptance but before the database update can result in a duplicate on retry; ordinary SMTP cannot guarantee exactly-once delivery. The unique activity/event/recipient key prevents duplicate scheduling from normal repeated workflow calls.

For prolonged **Pending** records, check worker health, queue backlog, application logs, and SMTP connectivity. If the database itself is unavailable, submission cannot be saved; the guarantee here concerns mail-server outages, not database outages.

## Deployment acceptance

The automated suite uses fake/captured SMTP and never sends live messages. Before production activation, the operator should confirm migrations are applied, the office mailbox is configured, the university accepts the sender, the worker is supervised, the login link resolves, and a controlled submission reaches the intended test recipients. Live delivery has not been verified by this implementation.
