

## steps to re-deploy the Demo version after a new release

### Navigate into deployments folder 
```
cd k8s/deployments
```

### Get all deployments
```
kubectl get deployments
```
### Delete each deploymenr

```
kubectl delete deployment deployment_name
```

### Apply them again 

```
kubectl apply -f .
```

### Load new data

### Get all pvcs
```
kubectl get pvc 
```
### Delete the database pvc 

```
kubectl delete pvc maria_db

```