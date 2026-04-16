# PhonksBrazililian.noAds

Organized repository structure for GitHub + Render deployment.

## Project structure

- `index.html` — main page
- `style.css` — styles
- `app.js` — frontend logic
- `music.php` — backend JSON endpoint
- `music-db.json` — music catalog
- `healthz.php` — Render health check endpoint
- `Dockerfile` — Docker build for PHP/Apache
- `start-render.sh` — Apache port bootstrap for Render
- `render.yaml` — Render Blueprint config

## Run locally with Docker

```bash
docker build -t phonks-brazilian .
docker run -p 10000:10000 -e PORT=10000 phonks-brazililian
```

Then open:

- `http://localhost:10000`

## Notes

The current frontend already requests `api/music.php`, and the PHP endpoint expects the JSON database under `data/music-db.json`. This repo layout keeps those paths aligned.
