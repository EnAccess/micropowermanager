FROM node:18.20.4
WORKDIR /app

COPY package.json /app/

RUN npm install

COPY . /app

RUN mkdir -p dist

RUN npm run build

COPY entrypoint-dev.sh /tmp/entrypoint-dev.sh

RUN chmod +x /tmp/entrypoint-dev.sh

RUN cp -a /app/. /app_backup

ENTRYPOINT [ "/tmp/entrypoint-dev.sh"]

EXPOSE 8081

CMD [ "tail", "-f", "/dev/null" ]
