#!/bin/sh

cd /app
npm run build

http-server dist -p 8081 


# -----------------------------------------------------------------------------
# Dockerfile for MPM frontend production docker image
# -----------------------------------------------------------------------------
# This Dockerfile sets up the environment to build and run the frontend. 
# The main objective is to create a production-ready Docker image 
# that serves the frontend  using http-server.
#
# The approach taken here is to use an entrypoint script as a workaround to handle
# the loading of environment variables. This ensures that any dynamic environment 
# variables required at runtime are correctly 
# applied before starting the server.