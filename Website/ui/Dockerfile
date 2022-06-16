FROM node:16.10.0 as builder
WORKDIR /usr/app

# Copy the package.json and install dependencies
COPY package*.json ./
RUN npm install

# Copy rest of the files
COPY . .

# Build the project
RUN npm run build

# Stage 1, based on Nginx, to have only the compiled app, ready for production with Nginx

FROM nginx as production-build
COPY ./.nginx/nginx.conf /etc/nginx/nginx.conf

## Remove default nginx index page
RUN rm -rf /usr/share/nginx/html/*

# Copy from the stahg 1
COPY --from=builder /usr/app/dist /usr/share/nginx/html

RUN mkdir /usr/ui-src

COPY --from=builder /usr/app/src /usr/ui-src

EXPOSE 80
ENTRYPOINT ["nginx", "-g", "daemon off;"]