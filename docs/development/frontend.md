---
order: 3
---

# Frontend

You can find frontend files under `src/frontend`.
The frontend is built with Vue.js.
After first run with `docker-compose up` dependencies will be installed automatically.
If you want to install dependencies manually, you can run `npm install` under `src/frontend` folder.

## Folder Structure

When adding new files to the project, please adhere to the following folder structure:

- **Creating New Modules:**
  Modules are the components used in pages.
  For example, the Client module holds components related to clients.
  Every component associated with clients should be placed under the Client module.

```sh
src/frontend
├── src
│   ├── modules
│   │   ├── newModule
```

- **Creating New Pages:**
  Pages are the components used in routes.
  We follow the nuxt.js folder structure for pages.
  The `index.vue` file under a page folder represents the listing page of the page, while the `_id.vue` file represents the detail page.
  Since we are not using nuxt.js, routes need to be defined manually.
  You can find the routes in the `src/frontend/src/ExportedRoutes.js` file.

```sh
src/frontend
├── src
│   ├── pages
│   │   ├── newPage
|   |   |   ├── index.vue
|   |   |   ├── _id.vue
```

## MPM Brand guidelines

To ensure a consistent look and feel across our applications, we maintain a set of brand guidelines covering typography, color palettes, iconography, and spacing.

When designing any user-facing UI elements, please consult the [MPM Brand guidelines](https://drive.google.com/file/d/1ZBszNtIvf_iNjUou7S3A_9swcIHBr37-/view?usp=sharing) for styling direction.
If you encounter a scenario not covered by the guidelines, feel free reach out!

[![MPM Brand guidelines](/screenshots/MPM_Brand_Guidelines.png)](https://drive.google.com/file/d/1ZBszNtIvf_iNjUou7S3A_9swcIHBr37-/view?usp=sharing)
