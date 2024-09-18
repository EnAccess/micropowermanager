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
docker build --platform linux/amd64 -t micropowermanager-ui-prod -f Docker/DockerfileUIProd .
```


