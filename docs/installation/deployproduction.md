---
order: 4
---

# Deploy for Production

> [!WARNING]
> This sites describes a potentially deprecated feature of MPM

The production mode will automatically install **Let\'s Encrypt SSL
certificates**. Therefore you need firstly register a domain.

When you have your domain, the first thing to do is editing `app.conf`
and `db.conf` files under
`NginxProxy/conf.p`.

Afer that, paste `chmod +x ./install-production.sh` to make the file
executable and run it via `./install-production.sh`. This will guide you
through the installation and finally, it will start the services.
