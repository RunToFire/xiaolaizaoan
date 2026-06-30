FROM node:22-alpine AS web-build

WORKDIR /src
COPY web/package*.json ./web/
WORKDIR /src/web
RUN npm ci
COPY web/index.html ./index.html
COPY web/vite.config.js ./vite.config.js
COPY web/src ./src
RUN npm run build

FROM nginx:1.27-alpine
COPY --from=web-build /src/web/dist /usr/share/nginx/html
