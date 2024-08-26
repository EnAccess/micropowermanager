FROM node:18.20.4
WORKDIR /app

COPY package.json /app/

RUN npm install

COPY . /app

RUN mkdir -p dist

RUN npm run build

COPY entrypoint.sh /tmp/entrypoint.sh

RUN chmod +x /tmp/entrypoint.sh

RUN cp -a /app/. /app_backup

ENTRYPOINT [ "/tmp/entrypoint.sh"]

EXPOSE 8081

CMD [ "tail", "-f", "/dev/null" ]
