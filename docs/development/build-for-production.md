---
order: 6
---


# Building docker image for production

### Backend Prod

From the root directory run

```
docker build --platform linux/amd64 -t micropowermanager-laravel-prod -f Docker/DockerfileLaravelProd .
```


### Frontend Prod
```
cd Website/ui
```

```
docker build --platform linux/amd64 -t micropowermanager-frontend-prod -f Dockerfile 
```


