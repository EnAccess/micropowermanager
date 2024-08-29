---
order: 3
---

# Frontend

You can find frontend files under `Website/ui`.
The frontend is built with Vue.js. After first run with `docker-compose up` dependencies will be installed
automatically.
If you want to install dependencies manually, you can run `npm install` under `Website/ui` folder.

## Folder Structure

When adding new files to the project, please adhere to the following folder structure:

- **Creating New Modules:**
  Modules are the components used in pages. For example, the Client module holds components related to clients. Every
  component associated with clients should be placed under the Client module.

```sh
Website/ui
├── src
│   ├── modules
│   │   ├── newModule
```

- **Creating New Pages:**
  Pages are the components used in routes. We follow the nuxt.js folder structure for pages. The `index.vue` file under
  a page folder represents the listing page of the page, while the `_id.vue` file represents the detail page. Since we
  are not using nuxt.js, routes need to be defined manually. You can find the routes in
  the `Website/ui/src/ExportedRoutes.js` file.

```sh
Website/ui
├── src
│   ├── pages
│   │   ├── newPage
|   |   |   ├── index.vue
|   |   |   ├── _id.vue
```
