<!--
Component: ChildRouteWrapper

This is a simple wrapper component that will be used to wrap all the child
routes in the application.
This is required when distinct components are to be rendered for each route.

It is required in vue-router@3 but not in vue-router@4 as parent components
can be omited, see
https://router.vuejs.org/guide/essentials/nested-routes.html#Omitting-parent-components-

Example Usage (in routes.js):

```js
{
    path: '/people',
    component: ChildRouteWrapper,
    children: [
        {
            path: '',
            component: require('./pages/Client/index.vue').default,
        },
        {
            path: ':id',
            component: require('./pages/Client/_id.vue').default,,
        },
    ]
}
```
-->

<template>
  <div>
    <router-view />
  </div>
</template>

<script>
export default {
  // Optional: you can add props, data, methods, etc. here
}
</script>
